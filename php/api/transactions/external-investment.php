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
    

    $requiredFields = ['date', 'description', 'investor_name', 'amount', 'ownership_percentage', 'target_asset_id', 'company_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            Response::error("Field '{$field}' is required", 400);
        }
    }
    

    $date = trim($input['date']);
    $description = trim($input['description']);
    $investorName = trim($input['investor_name']);
    $amount = (float)($input['amount'] ?? 0);
    $ownershipPercentage = (float)($input['ownership_percentage'] ?? 0);
    $targetAssetId = (int)($input['target_asset_id']);
    $company_id = (int)($input['company_id']);
    

    if ($amount <= 0) {
        Response::error("Amount must be greater than 0", 400);
    }
    

    if ($ownershipPercentage <= 0 || $ownershipPercentage > 100) {
        Response::error("Ownership percentage must be between 0 and 100", 400);
    }
    

    $db = new Database();
    $pdo = $db->getConnection();

    $transactionType = $db->fetchOne(
        "SELECT transaction_type_id FROM transaction_types WHERE type_name = 'external_investment'"
    );
    
    if (!$transactionType) {
        Response::error("Transaction type not found", 500);
    }
    

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
    

    $transactionNumber = 'EXT' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    

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
            $investorName
        ]);
        
        $transaction_id = $db->lastInsertId();
        
        // Verify target asset exists
        $targetAsset = $db->fetchOne(
            "SELECT * FROM accounts WHERE account_id = ? AND company_id = ? AND account_type = 'ASSET' AND is_active = 1",
            [$targetAssetId, $company_id]
        );
        
        if (!$targetAsset) {
            $pdo->rollBack();
            Response::error("Target asset account not found", 404);
        }
        
        $assetAccountId = $targetAssetId;
        
        // Create equity account for investor
        $equityAccountName = $investorName . " - Equity Stake";
        $equitySql = "
            INSERT INTO accounts (company_id, account_name, account_code, account_type, current_balance, investor_name, ownership_percentage, is_active, description, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ";
        
        $db->query($equitySql, [
            $company_id,
            $equityAccountName,
            'EQU' . str_pad($transaction_id, 4, '0', STR_PAD_LEFT),
            'EQUITY',
            $amount,
            $investorName,
            $ownershipPercentage,
            $description
        ]);
        
        $equityAccountId = $db->lastInsertId();

        $lineSql = "
            INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount)
            VALUES (?, ?, ?, ?, ?), (?, ?, ?, ?, ?)
        ";
        
        $db->query($lineSql, [
            $transaction_id,
            $assetAccountId,
            $description . " (investment from " . $investorName . ")",
            $amount,
            0,
            $transaction_id,
            $equityAccountId,
            $description . " (equity stake created)",
            0,
            $amount
        ]);
        // Update asset account balance
        $updateAssetSql = "UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ? AND company_id = ?";
        $db->query($updateAssetSql, [$amount, $assetAccountId, $company_id]);
        // Commit transaction
        $pdo->commit();

        // Get created transaction details
        $createdTransaction = $db->fetchOne("SELECT * FROM transactions WHERE transaction_id = ?", [$transaction_id]);
        
        // Get created accounts
        $assetAccount = $db->fetchOne("SELECT * FROM accounts WHERE account_id = ?", [$assetAccountId]);
        $equityAccount = $db->fetchOne("SELECT * FROM accounts WHERE account_id = ?", [$equityAccountId]);
        
        // Format response
        $transactionData = [
            'id' => (int)$createdTransaction['transaction_id'],
            'transaction_number' => $createdTransaction['transaction_number'],
            'date' => $createdTransaction['transaction_date'],
            'description' => $createdTransaction['description'],
            'amount' => (float)$createdTransaction['total_amount'],
            'transaction_type' => 'external_investment',
            'external_source' => $createdTransaction['external_source'],
            'status' => $createdTransaction['status'],
            'accounts_created' => [
                'asset_account' => [
                    'id' => (int)$assetAccountId,
                    'name' => $assetAccount['account_name'],
                    'type' => strtolower($assetAccount['account_type']),
                    'balance' => (float)$assetAccount['current_balance'],
                    'investor_name' => $assetAccount['investor_name']
                ],
                'equity_account' => [
                    'id' => (int)$equityAccountId,
                    'name' => $equityAccount['account_name'],
                    'type' => strtolower($equityAccount['account_type']),
                    'balance' => (float)$equityAccount['current_balance'],
                    'investor_name' => $equityAccount['investor_name'],
                    'ownership_percentage' => (float)$equityAccount['ownership_percentage']
                ]
            ]
        ];
        
        Response::success($transactionData, "External investment transaction created successfully");
        
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("External investment transaction error: " . $e->getMessage());
    error_log("External investment transaction trace: " . $e->getTraceAsString());
    Response::serverError("Failed to create external investment transaction: " . $e->getMessage());
}
    
} catch (Exception $e) {
    error_log("External investment API error: " . $e->getMessage());
    Response::serverError("Failed to process external investment");
}
?>