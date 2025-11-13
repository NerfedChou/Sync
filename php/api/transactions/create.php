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
    
    // Map frontend status to database enum
    $frontendStatus = trim($input['status'] ?? 'posted');
    $status = 'draft';
    if ($frontendStatus === 'posted' || $frontendStatus === 'completed') {
        $status = 'posted';
    } else if ($frontendStatus === 'draft' || $frontendStatus === 'pending') {
        $status = 'draft';
    }
    
    $category = trim($input['category'] ?? 'General');
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
        $periodName = date('F Y', strtotime($date)); // e.g., "January 2025"
        $periodSql = "INSERT INTO accounting_periods (company_id, period_name, start_date, end_date, is_closed, created_at) VALUES (?, ?, ?, ?, 0, NOW())";
        $db->query($periodSql, [$company_id, $periodName, $date, $date]);
        $period_id = $db->lastInsertId();
    } else {
        $period_id = $period['period_id'];
    }
    
    // Generate transaction number
    $transactionNumber = 'TXN' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Insert transaction record
    $sql = "
        INSERT INTO transactions (company_id, period_id, transaction_number, transaction_date, description, total_amount, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ";
    
    $db->query($sql, [
        $company_id,
        $period_id,
        $transactionNumber,
        $date,
        $description,
        $amount,
        $status
    ]);
    
    $transaction_id = $db->lastInsertId();
    
    // INTUITIVE EXPENSE PAYMENT VALIDATION
    $accountTypeForValidation = strtoupper($account['account_type']);
    if ($accountTypeForValidation === 'EXPENSE' && $type === 'debit') {
        // Get fresh balance to ensure accuracy
        $freshAccount = $db->fetchOne("SELECT current_balance FROM accounts WHERE account_id = ?", [$account['account_id']]);
        $currentBalance = (float)$freshAccount['current_balance'];
        $maxPayment = abs($currentBalance); // Can only pay up to zero
        
        if ($amount > $maxPayment) {
            Response::error("Payment exceeds amount owed. Maximum payment: $" . number_format($maxPayment, 2) . ". Expense balance: $" . number_format($currentBalance, 2), 400);
        }
    }
    
    // Create proper double-entry transaction
    $debitAmount = $type === 'debit' ? $amount : 0;
    $creditAmount = $type === 'credit' ? $amount : 0;
    
    $lineSql = "
        INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount)
        VALUES (?, ?, ?, ?, ?)
    ";
    
    // Primary transaction line (user-specified account)
    $db->query($lineSql, [
        $transaction_id,
        $account_id,
        $description,
        $debitAmount,
        $creditAmount
    ]);
    
    // Get opposite account for double-entry accounting
    $frontendTransactionType = $input['transaction_type'] ?? '';
    $oppositeAccountId = getOppositeAccount($db, $account_id, $company_id, $type, $frontendTransactionType, $account_id);
    
    if ($oppositeAccountId) {
        // Create opposite entry
        $oppositeDebitAmount = $type === 'credit' ? $amount : 0;
        $oppositeCreditAmount = $type === 'debit' ? $amount : 0;
        
        $oppositeAccount = $db->fetchOne("SELECT * FROM accounts WHERE account_id = ?", [$oppositeAccountId]);
        $oppositeDescription = $description . " (offset for " . $account['account_name'] . ")";
        
        $db->query($lineSql, [
            $transaction_id,
            $oppositeAccountId,
            $oppositeDescription,
            $oppositeDebitAmount,
            $oppositeCreditAmount
        ]);
        
        // Update opposite account balance
        $oppositeType = ($type === 'debit') ? 'credit' : 'debit';
        updateAccountBalance($db, $oppositeAccount, $oppositeType, $amount);
    }
    
    // Update primary account balance
    updateAccountBalance($db, $account, $type, $amount);
    
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

/**
 * Get opposite account for double-entry accounting
 */
function getOppositeAccount($db, $primaryAccountId, $companyId, $transactionType, $frontendTransactionType = '', $excludeAccountId = null) {
    $primaryAccount = $db->fetchOne("SELECT account_type FROM accounts WHERE account_id = ?", [$primaryAccountId]);
    $primaryType = strtolower($primaryAccount['account_type'] ?? '');
    
    // Use frontend transaction type for better pairing if available
    if ($frontendTransactionType) {
        switch ($frontendTransactionType) {
            case 'expense-payment':
                // Pay expense: Debit expense, Credit asset
                $oppositeType = ($primaryType === 'expense') ? 'asset' : 'expense';
                break;
            case 'income':
                // Receive income: Debit asset, Credit revenue
                $oppositeType = ($primaryType === 'asset') ? 'revenue' : 'asset';
                break;
            case 'transfer':
                // Transfer: Both accounts are assets
                $oppositeType = 'asset';
                break;
            case 'add-expense':
                // Add expense: Credit expense (make more negative), Debit revenue/equity
                $oppositeType = ($primaryType === 'expense') ? 'revenue' : 'expense';
                // For expense accounts in "add-expense", force credit transaction
                if ($primaryType === 'expense') {
                    $type = 'credit';
                }
                break;
            case 'pay-debt':
                // Pay debt: Debit liability, Credit asset
                $oppositeType = ($primaryType === 'liability') ? 'asset' : 'liability';
                break;
            case 'owner-investment':
                // Owner investment: Debit asset, Credit equity
                $oppositeType = ($primaryType === 'equity') ? 'asset' : 'equity';
                break;
            case 'loan-received':
                // Loan received: Debit asset, Credit liability
                $oppositeType = ($primaryType === 'asset') ? 'liability' : 'asset';
                break;
            default:
                // Fallback to original logic
                $oppositeType = getDefaultOppositeType($primaryType, $transactionType);
        }
    } else {
        // Original logic for backward compatibility
        $oppositeType = getDefaultOppositeType($primaryType, $transactionType);
    }
    
    // Try to find an account of the opposite type, with fallbacks
    $oppositeAccount = $db->fetchOne(
        "SELECT account_id FROM accounts WHERE company_id = ? AND account_type = ? AND is_active = 1 AND account_id != ? LIMIT 1",
        [$companyId, strtoupper($oppositeType), $primaryAccountId]
    );
    
    // If not found, try fallback types
    if (!$oppositeAccount && $oppositeType !== 'asset') {
        $oppositeAccount = $db->fetchOne(
            "SELECT account_id FROM accounts WHERE company_id = ? AND account_type = 'ASSET' AND is_active = 1 AND account_id != ? LIMIT 1",
            [$companyId, $primaryAccountId]
        );
    }
    
    return $oppositeAccount ? $oppositeAccount['account_id'] : null;
}

/**
 * Get default opposite account type for backward compatibility
 */
function getDefaultOppositeType($primaryType, $transactionType) {
    // Find appropriate opposite account based on transaction type and primary account type
    if ($transactionType === 'debit') {
        // User is debiting an account, so we need to credit an opposite account
        switch ($primaryType) {
            case 'expense':
                // Debiting expense = paying expense, so credit cash/bank account
                return 'asset';
            case 'asset':
                // Debiting asset = receiving money, so credit revenue or equity
                return 'revenue';
            case 'liability':
                // Debiting liability = paying off debt, so credit cash/bank account
                return 'asset';
            case 'equity':
                // Debiting equity = owner withdrawal, so credit cash/bank account
                return 'asset';
            case 'revenue':
                // Debiting revenue = refund/return, so credit cash/bank account
                return 'asset';
            default:
                return 'asset';
        }
    } else {
        // User is crediting an account, so we need to debit an opposite account
        switch ($primaryType) {
            case 'revenue':
                // Crediting revenue = earning income, so debit cash/bank account
                return 'asset';
            case 'asset':
                // Crediting asset = spending money, so debit expense
                return 'expense';
            case 'liability':
                // Crediting liability = taking on debt, so debit cash/bank account
                return 'asset';
            case 'equity':
                // Crediting equity = owner investment, so debit cash/bank account
                return 'asset';
            case 'expense':
                // Crediting expense = incurring expense, so debit cash/bank account
                return 'asset';
            default:
                return 'asset';
        }
    }
}

/**
 * Update account balance with intuitive expense tracking
 */
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