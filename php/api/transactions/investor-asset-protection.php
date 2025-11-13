<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

/**
 * Investor Asset Protection API
 * Separates investor assets from business operations to protect them
 */

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
    $requiredFields = ['date', 'description', 'amount', 'company_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            Response::error("Field '{$field}' is required", 400);
        }
    }
    
    // Sanitize input
    $date = trim($input['date']);
    $description = trim($input['description']);
    $amount = (float)($input['amount'] ?? 0);
    $company_id = (int)($input['company_id']);
    
    // Validate amount
    if ($amount <= 0) {
        Response::error("Amount must be greater than 0", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get transaction type ID for investor asset protection
    $transactionType = $db->fetchOne(
        "SELECT transaction_type_id FROM transaction_types WHERE type_name = 'investor_asset_protection'"
    );
    
    if (!$transactionType) {
        Response::error("Transaction type not found", 500);
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
    $transactionNumber = 'PROT' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
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
            $amount,
            'posted'
        ]);
        
        $transaction_id = $db->lastInsertId();
        
        // Create transaction lines
        $lineSql = "
            INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount)
                VALUES (?, ?, ?, ?), (?, ?, ?, ?)
            ";
        
        // Get company asset account to credit (reduce cash for protection)
        $assetAccount = $db->fetchOne(
            "SELECT account_id FROM accounts WHERE company_id = ? AND account_type = 'ASSET' AND is_active = 1 LIMIT 1",
            [$company_id]
        );
        
        if ($assetAccount) {
            // Credit company asset account (reducing cash for protection)
            $db->query($lineSql, [
                $transaction_id,
                $assetAccount['account_id'],
                $description . " (investor asset protection - cash reserve)",
                0, // No debit
                $amount // Credit amount
            ]);
            
            // Update asset account balance
            $db->query(
                "UPDATE accounts SET current_balance = current_balance - ? WHERE account_id = ?",
                [$amount, $assetAccount['account_id']]
            );
        }
        
        // Get investor equity account to debit (reduce their stake)
        $investorEquityAccounts = $db->fetchAll(
            "SELECT account_id, account_name, current_balance, ownership_percentage 
                 FROM accounts 
                 WHERE company_id = ? AND account_type = 'EQUITY' AND investor_name IS NOT NULL AND ownership_percentage > 0",
            [$company_id]
        );
        
        if (!empty($investorEquityAccounts)) {
            // Distribute protection amount proportionally among all investor equity accounts
            $totalInvestorEquity = array_sum(array_column($investorEquityAccounts, 'current_balance'));
            
            foreach ($investorEquityAccounts as $equityAccount) {
                $protectionAmount = $amount * ($equityAccount['current_balance'] / $totalInvestorEquity);
                
                // Debit investor equity account (reduce their stake)
                $db->query($lineSql, [
                    $transaction_id,
                    $equityAccount['account_id'],
                    $description . " (asset protection - " . $equityAccount['account_name'] . " stake reduction)",
                    $protectionAmount, // Debit amount
                    0 // No credit
                ]);
                
                // Update investor equity account balance
                $db->query(
                    "UPDATE accounts SET current_balance = current_balance - ? WHERE account_id = ?",
                    [$protectionAmount, $equityAccount['account_id']]
                );
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Get created transaction details
        $createdTransaction = $db->fetchOne("SELECT * FROM transactions WHERE transaction_id = ?", [$transaction_id]);
        
        // Format response
        $transactionData = [
            'id' => (int)$createdTransaction['transaction_id'],
            'transaction_number' => $createdTransaction['transaction_number'],
            'date' => $createdTransaction['transaction_date'],
            'description' => $createdTransaction['description'],
            'amount' => (float)$createdTransaction['total_amount'],
            'transaction_type' => 'investor_asset_protection',
            'status' => $createdTransaction['status'],
            'protection_details' => [
                'total_protection_amount' => $amount,
                'investor_accounts_protected' => count($investorEquityAccounts),
                'protection_per_account' => $investorEquityAccounts ? array_map(function($account) {
                    return [
                        'investor_name' => $account['account_name'],
                        'ownership_percentage' => $account['ownership_percentage'],
                        'protection_amount' => $amount * ($account['current_balance'] / array_sum(array_column($investorEquityAccounts, 'current_balance')))
                    ];
                }, $investorEquityAccounts) : []
            ]
        ];
        
        Response::success($transactionData, "Investor asset protection transaction created successfully");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Investor asset protection error: " . $e->getMessage());
        Response::serverError("Failed to create investor asset protection");
    }
    
} catch (Exception $e) {
    error_log("Investor asset protection API error: " . $e->getMessage());
    Response::serverError("Failed to process investor asset protection");
}
?>