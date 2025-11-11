
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../middleware/validation.php';

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
    $requiredFields = ['name', 'type', 'company_id'];
    $errors = Validation::required($input, $requiredFields);
    
    if (!empty($errors)) {
        Response::validationError($errors);
    }
    
    // Validate account type
    $validTypes = ['asset', 'liability', 'equity', 'revenue', 'expense'];
    if (!Validation::inArray($input['type'], $validTypes)) {
        Response::error("Invalid account type", 400);
    }
    
    // Validate company_id
    if (!Validation::numeric($input['company_id'])) {
        Response::error("Invalid company ID", 400);
    }
    
    // Sanitize input
    $name = Validation::sanitize($input['name']);
    $type = Validation::sanitize($input['type']);
    $description = Validation::sanitize($input['description'] ?? '');
    $company_id = (int)$input['company_id'];
    $balance = (float)($input['balance'] ?? 0);
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if company exists
    $companyCheck = $db->fetchOne("SELECT id FROM companies WHERE id = ? AND is_active = 1", [$company_id]);
    if (!$companyCheck) {
        Response::error("Company not found", 404);
    }
    
    // Generate account code if not provided
    $account_code = $input['code'] ?? '';
    if (empty($account_code)) {
        // Generate next available code for the account type
        $typePrefix = substr($type, 0, 1);
        $lastCode = $db->fetchOne(
            "SELECT account_code FROM accounts WHERE company_id = ? AND account_code LIKE ? ORDER BY account_code DESC LIMIT 1",
            [$company_id, $typePrefix . '%']
        );
        
        if ($lastCode) {
            $lastNumber = (int)substr($lastCode['account_code'], 1);
            $account_code = $typePrefix . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $account_code = $typePrefix . '001';
        }
    }
    
    // Insert account
    $sql = "
        INSERT INTO accounts (account_name, account_code, type, description, company_id, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ";
    
    $db->query($sql, [$name, $account_code, $type, $description, $company_id]);
    $account_id = $db->lastInsertId();
    
    // If initial balance provided, create opening balance transaction
    if ($balance != 0) {
        $transactionSql = "
            INSERT INTO transactions (company_id, transaction_date, description, type, amount, status, category, created_at)
            VALUES (?, CURDATE(), ?, ?, ?, 'completed', 'Opening Balance', NOW())
        ";
        
        $transactionType = ($type === 'asset' || $type === 'expense') ? 'debit' : 'credit';
        $db->query($transactionSql, [$company_id, "Opening balance for {$name}", $transactionType, abs($balance)]);
        
        $transaction_id = $db->lastInsertId();
        
        // Create transaction line
        $lineSql = "
            INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount)
            VALUES (?, ?, ?, ?, ?)
        ";
        
        $debitAmount = $transactionType === 'debit' ? abs($balance) : 0;
        $creditAmount = $transactionType === 'credit' ? abs($balance) : 0;
        
        $db->query($lineSql, [$transaction_id, $account_id, "Opening balance", $debitAmount, $creditAmount]);
    }
    
    // Get created account
    $createdAccount = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$account_id]);
    
    // Format response
    $accountData = [
        'id' => (int)$createdAccount['id'],
        'name' => $createdAccount['account_name'],
        'code' => $createdAccount['account_code'],
        'type' => $createdAccount['type'],
        'balance' => (float)$balance,
        'status' => $createdAccount['is_active'] ? 'active' : 'inactive',
        'description' => $createdAccount['description'] ?? ''
    ];
    
    Response::success($accountData, "Account created successfully");
    
} catch (Exception $e) {
    error_log("Account creation error: " . $e->getMessage());
    Response::serverError("Failed to create account");
}
?>