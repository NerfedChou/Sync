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
    $requiredFields = ['date', 'description', 'total_profit', 'company_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            Response::error("Field '{$field}' is required", 400);
        }
    }
    
    // Sanitize input
    $date = trim($input['date']);
    $description = trim($input['description']);
    $totalProfit = (float)($input['total_profit'] ?? 0);
    $company_id = (int)($input['company_id']);
    
    // Validate profit amount
    if ($totalProfit <= 0) {
        Response::error("Total profit must be greater than 0", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get transaction type ID for profit distribution
    $transactionType = $db->fetchOne(
        "SELECT transaction_type_id FROM transaction_types WHERE type_name = 'profit_distribution'"
    );
    
    if (!$transactionType) {
        Response::error("Transaction type not found", 500);
    }
    
    // Get all equity accounts with ownership percentages
    $equityAccounts = $db->fetchAll(
        "SELECT account_id, account_name, investor_name, ownership_percentage, current_balance 
         FROM accounts 
         WHERE company_id = ? AND account_type = 'EQUITY' AND ownership_percentage IS NOT NULL AND ownership_percentage > 0"
    );
    
    if (empty($equityAccounts)) {
        Response::error("No equity accounts with ownership percentages found", 400);
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
    $transactionNumber = 'PROF' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert transaction record
        $sql = "
            INSERT INTO transactions (company_id, period_id, transaction_type_id, transaction_number, transaction_date, description, total_amount, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'posted', NOW())
        ";
        
        $db->query($sql, [
            $company_id,
            $period_id,
            $transactionType['transaction_type_id'],
            $transactionNumber,
            $date,
            $description,
            $totalProfit
        ]);
        
        $transaction_id = $db->lastInsertId();
        
        // Create transaction lines for each equity account
        $lineSql = "
            INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount)
            VALUES (?, ?, ?, ?, ?)
        ";
        
        $distributions = [];
        foreach ($equityAccounts as $equityAccount) {
            $distributionAmount = $totalProfit * ($equityAccount['ownership_percentage'] / 100);
            
            $db->query($lineSql, [
                $transaction_id,
                $equityAccount['account_id'],
                $description . " (" . $equityAccount['investor_name'] . " - " . round($equityAccount['ownership_percentage'], 2) . "%)",
                $distributionAmount,
                0
            ]);
            
            // Update equity account balance
            $db->query(
                "UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ?",
                [$distributionAmount, $equityAccount['account_id']]
            );
            
            $distributions[] = [
                'investor_name' => $equityAccount['investor_name'],
                'account_name' => $equityAccount['account_name'],
                'ownership_percentage' => (float)$equityAccount['ownership_percentage'],
                'distribution_amount' => $distributionAmount,
                'new_balance' => (float)($equityAccount['current_balance'] + $distributionAmount)
            ];
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Format response
        $transactionData = [
            'id' => (int)$transaction_id,
            'transaction_number' => $transactionNumber,
            'date' => $date,
            'description' => $description,
            'total_profit' => $totalProfit,
            'transaction_type' => 'profit_distribution',
            'status' => 'posted',
            'distributions' => $distributions,
            'total_distributed' => array_sum(array_column($distributions, 'distribution_amount'))
        ];
        
        Response::success($transactionData, "Profit distribution completed successfully");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Profit distribution error: " . $e->getMessage());
        Response::serverError("Failed to distribute profits");
    }
    
} catch (Exception $e) {
    error_log("Profit distribution API error: " . $e->getMessage());
    Response::serverError("Failed to process profit distribution");
}
?>