<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

/**
 * Micro-Transaction API
 * Handles small asset-liability operations like paying small expenses, buying supplies, etc.
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
    $requiredFields = ['date', 'description', 'from_account_id', 'to_account_id', 'amount', 'company_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            Response::error("Field '{$field}' is required", 400);
        }
    }
    
    // Sanitize input
    $date = trim($input['date']);
    $description = trim($input['description']);
    $fromAccountId = (int)($input['from_account_id']);
    $toAccountId = (int)($input['to_account_id']);
    $amount = (float)($input['amount'] ?? 0);
    $company_id = (int)($input['company_id']);
    
    // Validate amount
    if ($amount <= 0) {
        Response::error("Amount must be greater than 0", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Verify accounts exist and belong to company
    $fromAccount = $db->fetchOne(
        "SELECT * FROM accounts WHERE account_id = ? AND company_id = ? AND is_active = 1",
        [$fromAccountId, $company_id]
    );
    
    $toAccount = $db->fetchOne(
        "SELECT * FROM accounts WHERE account_id = ? AND company_id = ? AND is_active = 1",
        [$toAccountId, $company_id]
    );
    
    if (!$fromAccount || !$toAccount) {
        Response::error("One or both accounts not found", 404);
    }
    
    // Validate micro-transaction rules
    $fromType = strtoupper($fromAccount['account_type']);
    $toType = strtoupper($toAccount['account_type']);
    
    // Define valid micro-transaction pairs
    $validPairs = [
        'ASSET->EXPENSE' => 'Pay small expense',
        'ASSET->LIABILITY' => 'Pay down small debt',
        'EXPENSE->ASSET' => 'Refund small expense',
        'LIABILITY->ASSET' => 'Receive small payment',
        'ASSET->LIABILITY' => 'Increase small credit line'
    ];
    
    $pairKey = "{$fromType}->{$toType}";
    
    if (!isset($validPairs[$pairKey])) {
        Response::error("Invalid account pair for micro-transaction. Valid pairs: " . implode(', ', array_keys($validPairs)), 400);
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
    $transactionNumber = 'MICRO' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert transaction record
        $sql = "
            INSERT INTO transactions (company_id, period_id, transaction_number, transaction_date, description, total_amount, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'posted', NOW())
            ";
        
        $db->query($sql, [
            $company_id,
            $period_id,
            $transactionNumber,
            $date,
            $description,
            $amount
        ]);
        
        $transaction_id = $db->lastInsertId();
        
        // Create transaction lines with proper double-entry
        $lineSql = "
            INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount)
                VALUES (?, ?, ?, ?, ?), (?, ?, ?, ?, ?)
            ";
        
        // Determine debit/credit based on account types
        if ($fromType === 'ASSET') {
            // Asset is giving money
            $fromDebit = 0;
            $fromCredit = $amount;
            $toDebit = $amount;
            $toCredit = 0;
        } else {
            // Non-asset is giving money (reducing their balance)
            $fromDebit = $amount;
            $fromCredit = 0;
            $toDebit = 0;
            $toCredit = $amount;
        }
        
        $db->query($lineSql, [
            $transaction_id,
            $fromAccountId,
            $description . " (payment from " . $fromAccount['account_name'] . ")",
            $fromDebit,
            $fromCredit,
            $transaction_id,
            $toAccountId,
            $description . " (payment to " . $toAccount['account_name'] . ")",
            $toDebit,
            $toCredit
        ]);
        
        // Update account balances
        updateAccountBalance($db, $fromAccount, $fromType === 'ASSET' ? 'credit' : 'debit', $amount);
        updateAccountBalance($db, $toAccount, $toType === 'ASSET' ? 'debit' : 'credit', $amount);
        
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
            'transaction_type' => $validPairs[$pairKey],
            'from_account' => [
                'id' => (int)$fromAccount['account_id'],
                'name' => $fromAccount['account_name'],
                'type' => strtolower($fromAccount['account_type'])
            ],
            'to_account' => [
                'id' => (int)$toAccount['account_id'],
                'name' => $toAccount['account_name'],
                'type' => strtolower($toAccount['account_type'])
            ],
            'status' => 'posted'
        ];
        
        Response::success($transactionData, "Micro-transaction created successfully");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Micro-transaction error: " . $e->getMessage());
        Response::serverError("Failed to create micro-transaction");
    }
    
} catch (Exception $e) {
    error_log("Micro-transaction API error: " . $e->getMessage());
    Response::serverError("Failed to process micro-transaction");
}

function updateAccountBalance($db, $account, $transactionType, $amount) {
    // Get fresh account data to ensure current balance is correct
    $freshAccount = $db->fetchOne("SELECT * FROM accounts WHERE account_id = ?", [$account['account_id']]);
    $accountType = strtoupper($freshAccount['account_type']);
    $balanceChange = 0;
    
    if ($transactionType === 'debit') {
        // Debit: Assets increase, Liabilities/Equity/Revenue decrease
        if ($accountType === 'EXPENSE') {
            // INTUITIVE: For expense accounts, debit moves balance TOWARD zero
            $balanceChange = $amount;
        } elseif ($accountType === 'LIABILITY') {
            // FIXED: Debit liability reduces debt (moves toward zero)
            $balanceChange = $amount;
        } else {
            $balanceChange = ($accountType === 'ASSET') ? $amount : -$amount;
        }
    } else {
        // Credit: Assets decrease, Liabilities/Equity/Revenue increase
        if ($accountType === 'EXPENSE') {
            // INTUITIVE: For expense accounts, credit moves balance AWAY from zero
            $balanceChange = -$amount;
        } elseif ($accountType === 'LIABILITY') {
            // FIXED: Credit liability increases debt (moves away from zero)
            $balanceChange = -$amount;
        } else {
            $balanceChange = ($accountType === 'ASSET') ? -$amount : $amount;
        }
    }
    
    $db->query(
        "UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ?",
        [$balanceChange, $freshAccount['account_id']]
    );
}
?>