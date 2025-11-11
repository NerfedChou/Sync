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
    
    // Build query for simplified transactions
    $sql = "SELECT * FROM transactions_simple WHERE company_id = ?";
    $params = [$company_id];
    
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY date DESC, created_at DESC LIMIT ?";
    $params[] = (int)$limit;
    
    // Execute query
    $transactions = $db->fetchAll($sql, $params);
    
    // Format transactions for frontend
    $formattedTransactions = array_map(function($transaction) {
        return [
            'id' => (int)$transaction['id'],
            'Name' => $transaction['name'],
            'Date' => $transaction['date'],
            'Description' => $transaction['description'],
            'Category' => $transaction['category'],
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