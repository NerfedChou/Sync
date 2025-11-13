<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../middleware/validation.php';

try {
    // Only allow PUT requests
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        Response::error("Method not allowed", 405);
    }
    
    // Get account ID from URL - remove query parameters first
    $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $urlParts = explode('/', $urlPath);
    $accountId = end($urlParts);
    
    if (!is_numeric($accountId)) {
        Response::error("Invalid account ID: $accountId", 400);
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error("Invalid JSON input", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if account exists
    $existingAccount = $db->fetchOne("SELECT * FROM accounts WHERE account_id = ?", [$accountId]);
    if (!$existingAccount) {
        Response::notFound("Account not found");
    }
    
    // Validate fields if provided
    $errors = [];
    
    if (isset($input['name'])) {
        if (empty(trim($input['name']))) {
            $errors[] = "Account name cannot be empty";
        }
    }
    
    if (isset($input['type'])) {
        $validTypes = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        if (!Validation::inArray($input['type'], $validTypes)) {
            $errors[] = "Invalid account type";
        }
    }
    
    if (!empty($errors)) {
        Response::validationError($errors);
    }
    
    // Build update query
    $updateFields = [];
    $params = [];
    
    if (isset($input['name'])) {
        $updateFields[] = "account_name = ?";
        $params[] = Validation::sanitize($input['name']);
    }
    
    if (isset($input['type'])) {
        $updateFields[] = "account_type = ?";
        $params[] = Validation::sanitize($input['type']);
    }
    
    if (isset($input['description'])) {
        $updateFields[] = "description = ?";
        $params[] = Validation::sanitize($input['description']);
    }
    
    if (isset($input['balance'])) {
        $balance = (float)$input['balance'];
        
        // INTUITIVE EXPENSE TRACKING: For expense accounts, store negative balance
        // User enters positive budget amount (e.g., 10), but we store as negative (-10)
        $accountType = strtolower($existingAccount['account_type'] ?? '');
        error_log("Account type: $accountType, Original balance: $balance");
        if ($accountType === 'expense' && $balance > 0) {
            $balance = -$balance;
            error_log("Updated balance for expense account: $balance");
        }
        
        $updateFields[] = "current_balance = ?";
        $updateFields[] = "opening_balance = ?";
        $params[] = $balance;
        $params[] = $balance;
    }
    
    if (isset($input['is_active'])) {
        $updateFields[] = "is_active = ?";
        $params[] = $input['is_active'] === 'true' || $input['is_active'] === true ? 1 : 0;
    }
    
    if (empty($updateFields)) {
        Response::error("No fields to update", 400);
    }
    
    // Add account ID to params
    $params[] = $accountId;
    
    // Update account
    $sql = "UPDATE accounts SET " . implode(", ", $updateFields) . ", updated_at = NOW() WHERE account_id = ?";
    error_log("Update SQL: " . $sql);
    error_log("Update params: " . json_encode($params));
    $db->query($sql, $params);
    
    // Get updated account
    $updatedAccount = $db->fetchOne("SELECT * FROM accounts WHERE account_id = ?", [$accountId]);
    
    // Get the balance that was updated or current balance
    $balance = isset($input['balance']) ? (float)$input['balance'] : (float)$updatedAccount['current_balance'];
    
    // Format response
    $accountData = [
        'id' => (int)$updatedAccount['account_id'],
        'name' => $updatedAccount['account_name'],
        'code' => $updatedAccount['account_code'],
        'type' => strtolower($updatedAccount['account_type']),
        'balance' => $balance,
        'status' => $updatedAccount['is_active'] ? 'active' : 'inactive',
        'description' => $updatedAccount['description'] ?? ''
    ];
    
    Response::success($accountData, "Account updated successfully");
    
} catch (Exception $e) {
    error_log("Account update error: " . $e->getMessage());
    Response::serverError("Failed to update account");
}
?>