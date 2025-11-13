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
    
    // Start transaction for safe deletion
    $pdo->beginTransaction();
    
    try {
        // Get transaction details before deletion for balance adjustment
        $transaction = $db->fetchOne("
            SELECT t.transaction_id, t.total_amount, tl.debit_amount, tl.credit_amount, tl.account_id, a.account_type
            FROM transactions t
            LEFT JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
            LEFT JOIN accounts a ON tl.account_id = a.account_id
            WHERE t.transaction_id = ?
        ", [$transactionId]);
        
        if (!$transaction) {
            Response::notFound("Transaction not found");
        }
        
        // Delete transaction lines first (foreign key constraint)
        $db->query("DELETE FROM transaction_lines WHERE transaction_id = ?", [$transactionId]);
        
        // Delete the transaction
        $db->query("DELETE FROM transactions WHERE transaction_id = ?", [$transactionId]);
        
        // Adjust account balance (reverse the original transaction)
        if ($transaction['account_id'] && $transaction['account_type']) {
            $amount = (float)$transaction['total_amount'];
            $accountType = strtoupper($transaction['account_type']);
            $accountId = $transaction['account_id'];
            
            // Determine if this was a debit or credit transaction
            $isDebit = $transaction['debit_amount'] > 0;
            
            // Calculate the balance change to reverse
            $balanceChange = 0;
            if ($isDebit) {
                // Original debit: Assets increased, Liabilities/Equity decreased
                // To reverse: Assets decrease, Liabilities/Equity increase
                $balanceChange = ($accountType === 'ASSET') ? -$amount : $amount;
            } else {
                // Original credit: Assets decreased, Liabilities/Equity increased
                // To reverse: Assets increase, Liabilities/Equity decrease
                $balanceChange = ($accountType === 'ASSET') ? $amount : -$amount;
            }
            
            // Update account balance
            $db->query(
                "UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ?",
                [$balanceChange, $accountId]
            );
        }
        
        // Commit transaction
        $pdo->commit();
        
        Response::success(['id' => (int)$transactionId], "Transaction deleted successfully");
        
    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Transaction delete error: " . $e->getMessage());
    Response::serverError("Failed to delete transaction");
}
?>