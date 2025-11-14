<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error("Method not allowed", 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error("Invalid JSON input", 400);
    }
    
    $requiredFields = ['liability_name', 'liability_type', 'amount', 'date', 'description', 'company_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            Response::error("Field '{$field}' is required", 400);
        }
    }
    
    $liabilityName = trim($input['liability_name']);
    $liabilityType = strtolower(trim($input['liability_type']));
    $amount = (float)($input['amount'] ?? 0);
    $interestRate = (float)($input['interest_rate'] ?? 0);
    $date = trim($input['date']);
    $description = trim($input['description']);
    $company_id = (int)($input['company_id']);
    
    // Normalize liability type (handle common variations)
    $typeMapping = [
        'tractor' => 'equipment',
        'car' => 'vehicle',
        'truck' => 'vehicle',
        'machinery' => 'equipment',
        'building' => 'mortgage'
    ];
    
    $liabilityType = strtolower(trim($input['liability_type']));
    $liabilityType = $typeMapping[$liabilityType] ?? $liabilityType;
    
    // Validate liability type
    $validTypes = ['loan', 'equipment', 'tools', 'vehicle', 'credit_line', 'mortgage', 'other'];
    if (!in_array($liabilityType, $validTypes)) {
        Response::error("Invalid liability type. Valid types: " . implode(', ', $validTypes), 400);
    }
    
    if ($amount <= 0) {
        Response::error("Amount must be greater than 0", 400);
    }
    
    $db = new Database();
    $pdo = $db->getConnection();

    // Get or create transaction type
    $transactionType = $db->fetchOne(
        "SELECT transaction_type_id FROM transaction_types WHERE type_name = 'liability_created'"
    );
    
    if (!$transactionType) {
        $db->query("INSERT INTO transaction_types (type_name, description) VALUES ('liability_created', 'Liability creation with corresponding asset')");
        $transactionTypeId = $db->lastInsertId();
        $transactionType = ['transaction_type_id' => $transactionTypeId];
    }
    
    // Get or create accounting period
    $period = $db->fetchOne(
        "SELECT period_id FROM accounting_periods WHERE company_id = ? AND start_date <= ? AND end_date >= ?",
        [$company_id, $date, $date]
    );
    
    if (!$period) {
        $periodName = date('F Y', strtotime($date));
        $periodSql = "INSERT INTO accounting_periods (company_id, period_name, start_date, end_date, is_closed, created_at) VALUES (?, ?, ?, ?, 0, NOW())";
        $db->query($periodSql, [$company_id, $periodName, $date, $date]);
        $period_id = $db->lastInsertId();
    } else {
        $period_id = $period['period_id'];
    }
    
    $transactionNumber = 'LIAB' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
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
            $amount,
            $liabilityName
        ]);
        
        $transaction_id = $db->lastInsertId();
        
        // Create or get liability account
        $liabilityAccountName = $liabilityName . " - " . ucfirst($liabilityType);
        $liabilityAccountId = createOrGetAccount($db, $company_id, $liabilityAccountName, 'LIABILITY', $amount, $description);
        
        // Create or get corresponding asset account
        $assetAccountName = ucfirst($liabilityType) . " - " . $liabilityName;
        $assetAccountId = createOrGetAccount($db, $company_id, $assetAccountName, 'ASSET', $amount, $description);

        // Create transaction lines (double-entry)
        $lineSql = "
            INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount)
            VALUES (?, ?, ?, ?, ?), (?, ?, ?, ?, ?)
        ";
        
        $db->query($lineSql, [
            $transaction_id,
            $assetAccountId,
            $description . " (asset received)",
            $amount,
            0,
            $transaction_id,
            $liabilityAccountId,
            $description . " (liability created)",
            0,
            $amount
        ]);

        // Update account balances
        updateAccountBalance($db, $assetAccountId, 'debit', $amount);
        updateAccountBalance($db, $liabilityAccountId, 'credit', $amount);
        
        $pdo->commit();
        
        Response::success([
            'transaction_id' => $transaction_id,
            'asset_account_id' => $assetAccountId,
            'liability_account_id' => $liabilityAccountId,
            'asset_account_name' => $assetAccountName,
            'liability_account_name' => $liabilityAccountName
        ], "Liability and corresponding asset created successfully");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Liability transaction error: " . $e->getMessage());
        Response::serverError("Failed to create liability transaction", $e);
    }
    
} catch (Exception $e) {
    error_log("Liability API error: " . $e->getMessage());
    Response::serverError("Failed to process liability", $e);
}

/**
 * Create or get account by name and type
 */
function createOrGetAccount($db, $companyId, $accountName, $accountType, $initialBalance, $description) {
    // Check if account already exists
    $existingAccount = $db->fetchOne(
        "SELECT account_id FROM accounts WHERE company_id = ? AND account_name = ? AND account_type = ? AND is_active = 1",
        [$companyId, $accountName, $accountType]
    );
    
    if ($existingAccount) {
        return $existingAccount['account_id'];
    }
    
    // Generate account code
    $typePrefix = substr($accountType, 0, 1);
    $lastCode = $db->fetchOne(
        "SELECT account_code FROM accounts WHERE company_id = ? AND account_code LIKE ? ORDER BY account_code DESC LIMIT 1",
        [$companyId, $typePrefix . '%']
    );
    
    if ($lastCode) {
        $lastNumber = (int)substr($lastCode['account_code'], 1);
        $account_code = $typePrefix . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $account_code = $typePrefix . '001';
    }
    
    // Set initial balance based on account type
    // For liabilities, balance is stored as positive number representing amount owed
    $balance = $initialBalance;
    
    // Insert new account
    $sql = "
        INSERT INTO accounts (company_id, account_name, account_code, account_type, current_balance, is_active, description, created_at)
        VALUES (?, ?, ?, ?, ?, 1, ?, NOW())
    ";
    
    $db->query($sql, [$companyId, $accountName, $account_code, $accountType, $balance, $description]);
    
    return $db->lastInsertId();
}

/**
 * Update account balance
 */
function updateAccountBalance($db, $accountId, $transactionType, $amount) {
    $account = $db->fetchOne("SELECT * FROM accounts WHERE account_id = ?", [$accountId]);
    $accountType = strtoupper($account['account_type']);
    $balanceChange = 0;
    
    if ($transactionType === 'debit') {
        // Debit: Assets increase, Liabilities/Equity/Revenue decrease
        if ($accountType === 'EXPENSE') {
            $balanceChange = $amount;
        } elseif ($accountType === 'LIABILITY') {
            // FIXED: Debit liability reduces debt (moves toward zero)
            // Since liabilities are stored as positive numbers (amounts owed),
            // debiting should REDUCE balance
            $balanceChange = -$amount;
        } else {
            $balanceChange = ($accountType === 'ASSET') ? $amount : -$amount;
        }
    } else {
        // Credit: Assets decrease, Liabilities/Equity/Revenue increase
        if ($accountType === 'EXPENSE') {
            $balanceChange = -$amount;
        } elseif ($accountType === 'LIABILITY') {
            // FIXED: Credit liability increases debt (moves away from zero)
            // Since liabilities are stored as positive numbers (amounts owed),
            // crediting should INCREASE balance
            $balanceChange = $amount;
        } else {
            $balanceChange = ($accountType === 'ASSET') ? -$amount : $amount;
        }
    }
    
    $db->query(
        "UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ?",
        [$balanceChange, $accountId]
    );
}
?>