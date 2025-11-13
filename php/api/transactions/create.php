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
    $requiredFields = ['date', 'description', 'account_id', 'amount', 'company_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            Response::error("Field '{$field}' is required", 400);
        }
    }
    
    // Sanitize input
    $date = trim($input['date']);
    $description = trim($input['description']);
    $account_id = (int)$input['account_id'];
    $amount = (float)($input['amount'] ?? 0);
    $type = trim($input['type'] ?? 'debit');
    $company_id = (int)$input['company_id'];
    
    // Validate amount
    if ($amount <= 0) {
        Response::error("Amount must be greater than 0", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Verify account exists
    $account = $db->fetchOne(
        "SELECT account_id, account_name, account_type FROM accounts WHERE account_id = ? AND company_id = ? AND is_active = 1",
        [$account_id, $company_id]
    );
    
    if (!$account) {
        Response::error("Account not found", 404);
    }
    
    // Get or create current accounting period
    $period = $db->fetchOne(
        "SELECT period_id FROM accounting_periods WHERE company_id = ? AND start_date <= ? AND end_date >= ?",
        [$company_id, $date, $date]
    );
    
    if (!$period) {
        // Create a simple period for this transaction
        $periodSql = "INSERT INTO accounting_periods (company_id, start_date, end_date, is_closed, created_at) VALUES (?, ?, ?, 0, NOW())";
        $db->query($periodSql, [$company_id, $date, $date]);
        $period_id = $db->lastInsertId();
    } else {
        $period_id = $period['period_id'];
    }
    
    // Generate transaction number
    $transactionNumber = 'TXN' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
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
    
    // Create transaction line
    $debitAmount = $type === 'debit' ? $amount : 0;
    $creditAmount = $type === 'credit' ? $amount : 0;
    
    $lineSql = "
        INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount)
        VALUES (?, ?, ?, ?, ?)
    ";
    
    $db->query($lineSql, [
        $transaction_id,
        $account_id,
        $description,
        $debitAmount,
        $creditAmount
    ]);
    
    // Update account balance
    $accountType = strtoupper($account['account_type']);
    $balanceChange = 0;
    
    if ($type === 'debit') {
        // Debit: Assets increase, Liabilities/Equity/Revenue decrease
        $balanceChange = ($accountType === 'ASSET') ? $amount : -$amount;
    } else {
        // Credit: Assets decrease, Liabilities/Equity/Revenue increase
        $balanceChange = ($accountType === 'ASSET') ? -$amount : $amount;
    }
    
    $db->query(
        "UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ?",
        [$balanceChange, $account_id]
    );
    
    // Get created transaction
    $createdTransaction = $db->fetchOne("SELECT * FROM transactions WHERE transaction_id = ?", [$transaction_id]);
    
    // Format response
    $transactionData = [
        'id' => (int)$createdTransaction['transaction_id'],
        'transaction_number' => $createdTransaction['transaction_number'],
        'date' => $createdTransaction['transaction_date'],
        'description' => $createdTransaction['description'],
        'amount' => (float)$createdTransaction['total_amount'],
        'type' => $type,
        'status' => $createdTransaction['status'],
        'account' => $account['account_name'],
        'account_id' => $account_id,
        'account_type' => strtolower($account['account_type'])
    ];
    
    Response::success($transactionData, "Transaction created successfully");
    
} catch (Exception $e) {
    error_log("Transaction creation error: " . $e->getMessage());
    Response::serverError("Failed to create transaction");
}
?>