<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get parameters
    $company_id = $_GET['company_id'] ?? null;
    $limit = (int)($_GET['limit'] ?? 5);
    
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    if ($limit > 50) {
        $limit = 50; // Cap at 50 for performance
    }
    
    // Query for recent transactions from simplified table
    $sql = "
        SELECT 
            id,
            date,
            description,
            category,
            account,
            amount,
            status,
            CASE 
                WHEN category LIKE '%Revenue%' OR account LIKE '%Revenue%' THEN 'credit'
                ELSE 'debit'
            END as type
        FROM transactions_simple
        WHERE company_id = ?
        ORDER BY date DESC, id DESC
        LIMIT ?
    ";
    
    $transactions = $db->fetchAll($sql, [$company_id, $limit]);
    
    // Format transactions for frontend
    $formattedTransactions = array_map(function($transaction) {
        return [
            'id' => (int)$transaction['id'],
            'Date' => $transaction['date'],
            'Description' => $transaction['description'],
            'Category' => $transaction['category'] ?? 'Uncategorized',
            'Account' => $transaction['account'] ?? 'Unknown Account',
            'Amount' => (float)$transaction['amount'],
            'Status' => $transaction['status'],
            'Type' => $transaction['type']
        ];
    }, $transactions);
    
    // Return success response
    Response::success($formattedTransactions, "Recent transactions retrieved successfully");
    
} catch (Exception $e) {
    error_log("Recent transactions endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve recent transactions");
}
?>