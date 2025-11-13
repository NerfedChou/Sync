<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get query parameters
    $company_id = $_GET['company_id'] ?? 1;
    $status = $_GET['status'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    
    // Build query for simplified transactions using proper schema
    $sql = "
        SELECT 
            t.transaction_id as id,
            t.transaction_date as date,
            t.description,
            t.total_amount as amount,
            t.status,
            a.account_name as account,
            a.account_type as type
        FROM transactions t
        LEFT JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        LEFT JOIN accounts a ON tl.account_id = a.account_id
        WHERE t.company_id = ?
    ";
    $params = [$company_id];
    
    if ($status) {
        $sql .= " AND t.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC LIMIT ?";
    $params[] = (int)$limit;
    
    // Execute query
    $transactions = $db->fetchAll($sql, $params);
    
    // Format transactions for frontend
    $formattedTransactions = array_map(function($transaction) {
        return [
            'id' => (int)$transaction['id'],
            'Name' => $transaction['account'],
            'Date' => $transaction['date'],
            'Description' => $transaction['description'],
            'Category' => $transaction['type'],
            'Account' => $transaction['account'],
            'Amount' => (float)$transaction['amount'],
            'Status' => $transaction['status']
        ];
    }, $transactions);
    
    // Return success response
    Response::success($formattedTransactions, "Transactions retrieved successfully");
    
} catch (Exception $e) {
    error_log("Transactions endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve transactions");
}
?>