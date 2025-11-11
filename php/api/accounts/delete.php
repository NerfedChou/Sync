
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Only allow DELETE requests
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        Response::error("Method not allowed", 405);
    }
    
    // Get account ID from URL
    $urlParts = explode('/', $_SERVER['REQUEST_URI']);
    $accountId = end($urlParts);
    
    if (!is_numeric($accountId)) {
        Response::error("Invalid account ID", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if account exists
    $existingAccount = $db->fetchOne("SELECT * FROM accounts WHERE id = ?", [$accountId]);
    if (!$existingAccount) {
        Response::notFound("Account not found");
    }
    
    // Check for existing transactions
    $transactionCheck = $db->fetchOne(
        "SELECT COUNT(*) as count FROM transaction_lines WHERE account_id = ? LIMIT 1",
        [$accountId]
    );
    
    if ($transactionCheck['count'] > 0) {
        Response::error("Cannot delete account with existing transactions. Consider deactivating it instead.", 409);
    }
    
    // Soft delete (set is_active = 0)
    $sql = "UPDATE accounts SET is_active = 0, updated_at = NOW() WHERE id = ?";
    $db->query($sql, [$accountId]);
    
    Response::success(null, "Account deleted successfully");
    
} catch (Exception $e) {
    error_log("Account deletion error: " . $e->getMessage());
    Response::serverError("Failed to delete account");
}
?>