<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error("Method not allowed", 405);
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error("Invalid JSON input", 400);
    }
    
    // Validate required fields
    $requiredFields = ['date', 'description', 'investor_name', 'buyout_amount', 'company_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            Response::error("Field '{$field}' is required", 400);
        }
    }
    
    // Sanitize input
    $date = trim($input['date']);
    $description = trim($input['description']);
    $investorName = trim($input['investor_name']);
    $buyoutAmount = (float)($input['buyout_amount'] ?? 0);
    $company_id = (int)($input['company_id']);
    
    // Validate amount
    if ($buyoutAmount <= 0) {
        Response::error("Buyout amount must be greater than 0", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get transaction type ID for investor exit
    $transactionType = $db->fetchOne(
        "SELECT transaction_type_id FROM transaction_types WHERE type_name = 'investor_exit'"
    );
    
    if (!$transactionType) {
        Response::error("Transaction type not found", 500);
    }
    
    // Get investor's equity account
    $equityAccount = $db->fetchOne(
        "SELECT account_id, current_balance, ownership_percentage FROM accounts 
         WHERE company_id = ? AND account_type = 'EQUITY' AND investor_name = ?",
        [$company_id, $investorName]
    );
    
    if (!$equityAccount) {
        Response::error("Investor equity account not found", 404);
    }
    
    // Get or create current accounting period
    $period = $db->fetchOne(
        "SELECT period_id FROM accounting_periods WHERE company_id = ? AND start_date <= ? AND end_date >= ?",
        [$company_id, $date, $date]
    );
    
    if (!$period) {
        // Create a simple period for this transaction
        $periodName = date('F Y', strtotime($date));
        $periodSql = "INSERT INTO accounting_periods (company_id, period_name, start_date, end_date, is_closed, created_at) VALUES (?, ?, ?, ?, 0, NOW())";
        $db->query($periodSql, [$company_id, $periodName, $date, $date]);
        $period_id = $db->lastInsertId();
    } else {
        $period_id = $period['period_id'];
    }
    
    // Generate transaction number
    $transactionNumber = 'EXIT' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert transaction record
        $sql = "
            INSERT INTO transactions (company_id, period_id, transaction_type_id, transaction_number, transaction_date, description, total_amount, external_source, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'posted', NOW())
            ";
        
        $db->query($sql, [
            $company_id,
            $period_id,
            $transactionType['transaction_type_id'],
            $transactionNumber,
            $date,
            $description,
            $buyoutAmount,
            $investorName
        ]);
        
        $transaction_id = $db->lastInsertId();
        
        // Create transaction lines
        $lineSql = "
            INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount)
                VALUES (?, ?, ?, ?), (?, ?, ?, ?)
            ";
        
        // Debit equity account (reduce investor's stake to zero)
        $db->query($lineSql, [
            $transaction_id,
            $equityAccount['account_id'],
            $description . " (" . $investorName . " stake buyout)",
            $equityAccount['current_balance'], // Debit current balance to zero
            0,
            $transaction_id,
            0, // Need to find company asset account to credit
            $description . " (cash paid to " . $investorName . ")",
            0,
            $buyoutAmount
        ]);
        
        // Get company asset account to credit (reduce company cash)
        $assetAccount = $db->fetchOne(
            "SELECT account_id FROM accounts WHERE company_id = ? AND account_type = 'ASSET' AND is_active = 1 LIMIT 1",
            [$company_id]
        );
        
        if ($assetAccount) {
            $db->query($lineSql, [
                $transaction_id,
                $assetAccount['account_id'],
                $description . " (cash paid to " . $investorName . ")",
                0,
                $buyoutAmount
            ]);
            
            // Update asset account balance (reduce cash)
            $db->query(
                "UPDATE accounts SET current_balance = current_balance - ? WHERE account_id = ?",
                [$buyoutAmount, $assetAccount['account_id']]
            );
        }
        
        // Update equity account balance (set to zero - investor fully exited)
        $db->query(
            "UPDATE accounts SET current_balance = 0, ownership_percentage = NULL, is_active = 0 WHERE account_id = ?",
            [$equityAccount['account_id']]
        );
        
        // Commit transaction
        $pdo->commit();
        
        // Format response
        $transactionData = [
            'id' => (int)$transaction_id,
            'transaction_number' => $transactionNumber,
            'date' => $date,
            'description' => $description,
            'buyout_amount' => $buyoutAmount,
            'transaction_type' => 'investor_exit',
            'investor_name' => $investorName,
            'status' => 'posted',
            'equity_account' => [
                'id' => (int)$equityAccount['account_id'],
                'name' => $equityAccount['account_name'],
                'previous_balance' => (float)$equityAccount['current_balance'],
                'ownership_percentage' => (float)$equityAccount['ownership_percentage'],
                'final_balance' => 0
            ],
            'asset_account' => $assetAccount ? [
                'id' => (int)$assetAccount['account_id'],
                'name' => $assetAccount['account_name'],
                'cash_reduced' => $buyoutAmount
            ] : null
        ];
        
        Response::success($transactionData, "Investor exit transaction completed successfully");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Investor exit transaction error: " . $e->getMessage());
        Response::serverError("Failed to process investor exit");
    }
    
} catch (Exception $e) {
    error_log("Investor exit API error: " . $e->getMessage());
    Response::serverError("Failed to process investor exit");
}
?>