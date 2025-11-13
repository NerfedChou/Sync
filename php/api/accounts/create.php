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
    

    
    // Get company_id from query parameter
    $company_id = $_GET['company_id'] ?? null;
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    // Validate required fields
    $requiredFields = ['name', 'type'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            Response::error("Field '{$field}' is required", 400);
        }
    }
    

    
    // Sanitize input
    $name = trim($input['name']);
    $type = strtolower(trim($input['type']));
    $description = trim($input['description'] ?? '');
    $balance = (float)($input['balance'] ?? 0);
    
    // Validate account type
    $validTypes = ['asset', 'liability', 'equity', 'revenue', 'expense'];
    if (!in_array($type, $validTypes)) {
        Response::error("Invalid account type", 400);
    }
    
    // INTUITIVE EXPENSE TRACKING: For expense accounts, store negative balance
    // User enters positive budget amount (e.g., 100), but we store as negative (-100)
    // This way "paying" expenses moves balance toward zero (-100 → -50 → 0)
    if ($type === 'expense' && $balance > 0) {
        $balance = -$balance;
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Generate account code
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
    
    // Insert account
    $sql = "
        INSERT INTO accounts (account_name, account_code, account_type, description, opening_balance, current_balance, company_id, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ";
    
    $db->query($sql, [$name, $account_code, strtoupper($type), $description, $balance, $balance, $company_id]);
    $account_id = $db->lastInsertId();
    
    // Get created account
    $createdAccount = $db->fetchOne("SELECT * FROM accounts WHERE account_id = ?", [$account_id]);
    
    // Format response - for expense accounts, show intuitive display
    $displayBalance = (float)$createdAccount['current_balance'];
    if ($type === 'expense') {
        // For expense accounts, show as "budget to be used" 
        // If stored as -100, display as -$100 (meaning $100 to pay off)
        $displayBalance = $displayBalance; // Keep negative for intuitive display
    }
    
    $accountData = [
        'id' => (int)$createdAccount['account_id'],
        'Account Name' => $createdAccount['account_name'],
        'code' => $createdAccount['account_code'],
        'Type' => strtolower($createdAccount['account_type']),
        'Balance' => $displayBalance,
        'Status' => $createdAccount['is_active'] ? 'active' : 'inactive',
        'description' => $createdAccount['description'] ?? ''
    ];
    
    Response::success($accountData, "Account created successfully");
    
} catch (Exception $e) {
    error_log("Account creation error: " . $e->getMessage());
    Response::serverError("Failed to create account");
}
?>