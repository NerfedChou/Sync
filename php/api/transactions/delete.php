
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Only allow DELETE requests
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        Response::error("Method not allowed", 405);
    }
    
    // Get transaction ID from URL
    $urlParts = explode('/', $_SERVER['REQUEST_URI']);
    $transactionId = end($urlParts);
    
    if (!is_numeric($transactionId)) {
        Response::error("Invalid transaction ID", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if transaction exists
    $existingTransaction = $db->fetchOne("SELECT * FROM transactions WHERE id = ?", [$transactionId]);
    if (!$existingTransaction) {
        Response::notFound("Transaction not found");
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Delete transaction lines first (foreign key constraint)
        $deleteLinesSql = "DELETE FROM transaction_lines WHERE transaction_id = ?";
        $db->query($deleteLinesSql, [$transactionId]);
        
        // Delete transaction
        $deleteTransactionSql = "DELETE FROM transactions WHERE id = ?";
        $db->query($deleteTransactionSql, [$transactionId]);
        
        // Commit transaction
        $pdo->commit();
        
    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollback();
        throw $e;
    }
    
    Response::success(null, "Transaction deleted successfully");
    
} catch (Exception $e) {
    error_log("Transaction deletion error: " . $e->getMessage());
    Response::serverError("Failed to delete transaction");
}
?>