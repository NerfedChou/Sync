
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../middleware/validation.php';

try {
    // Only allow PUT requests
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        Response::error("Method not allowed", 405);
    }
    
    // Get account ID from URL
    $urlParts = explode('/', $_SERVER['REQUEST_URI']);
    $accountId = end($urlParts);
    
    if (!is_numeric($accountId)) {
        Response::error("Invalid account ID", 400);
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
    $existingAccount = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);
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
        $updateFields[] = "type = ?";
        $params[] = Validation::sanitize($input['type']);
    }
    
    if (isset($input['description'])) {
        $updateFields[] = "description = ?";
        $params[] = Validation::sanitize($input['description']);
    }
    
    if (isset($input['is_active'])) {
        $updateFields[] = "is_active = ?";
        $params[] = (bool)$input['is_active'];
    }
    
    if (empty($updateFields)) {
        Response::error("No fields to update", 400);
    }
    
    // Add account ID to params
    $params[] = $accountId;
    
    // Update account
    $sql = "UPDATE accounts SET " . implode(", ", $updateFields) . ", updated_at = NOW() WHERE id = ?";
    $db->query($sql, $params);
    
    // Get updated account
    $updatedAccount = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);
    
    // Calculate current balance
    $balanceSql = "
        SELECT COALESCE(SUM(CASE 
            WHEN tl.debit_amount > 0 THEN tl.debit_amount 
            ELSE -tl.credit_amount 
        END), 0) as balance
        FROM transaction_lines tl 
        WHERE tl.account_id = ?
    ";
    $balanceResult = $db->fetchOne($balanceSql, [$accountId]);
    
    // Format response
    $accountData = [
        'id' => (int)$updatedAccount['id'],
        'name' => $updatedAccount['account_name'],
        'code' => $updatedAccount['account_code'],
        'type' => $updatedAccount['type'],
        'balance' => (float)$balanceResult['balance'],
        'status' => $updatedAccount['is_active'] ? 'active' : 'inactive',
        'description' => $updatedAccount['description'] ?? ''
    ];
    
    Response::success($accountData, "Account updated successfully");
    
} catch (Exception $e) {
    error_log("Account update error: " . $e->getMessage());
    Response::serverError("Failed to update account");
}
?>