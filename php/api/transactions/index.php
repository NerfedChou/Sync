<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get parameters
    $company_id = $_GET['company_id'] ?? null;
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 25);
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    // Build WHERE conditions
    $conditions = ["t.company_id = ?"];
    $params = [$company_id];
    
    if (!empty($search)) {
        $conditions[] = "(t.description LIKE ? OR a.account_name LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($status)) {
        $conditions[] = "t.status = ?";
        $params[] = $status;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $conditions);
    
    // Get total count
    $countSql = "
        SELECT COUNT(*) as total
        FROM transactions t
        LEFT JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        LEFT JOIN accounts a ON tl.account_id = a.account_id
        {$whereClause}
    ";
    
    $countResult = $db->fetchOne($countSql, $params);
    $total = (int)$countResult['total'];
    $totalPages = ceil($total / $limit);
    
    // Get transactions with basic info first
    $sql = "
        SELECT 
            t.transaction_id as id,
            t.transaction_number as name,
            t.transaction_date as date,
            t.description,
            t.total_amount as amount,
            t.status,
            t.created_at
        FROM transactions t
        {$whereClause}
        ORDER BY t.transaction_date DESC, t.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $transactions = $db->fetchAll($sql, $params);
    
        // Format transactions for frontend
    $formattedTransactions = [];
    foreach ($transactions as $transaction) {
        $formattedTransactions[] = [
            'id' => (int)$transaction['id'],
            'name' => $transaction['name'],
            'Date' => $transaction['date'],
            'Description' => $transaction['description'] ?? '',
            'from_account_name' => '', // Will be populated separately if needed
            'to_account_name' => '', // Will be populated separately if needed
            'Amount' => (float)$transaction['amount'],
            'Type' => 'Transaction',
            'Category' => 'General',
            'Status' => $transaction['status'] ?? 'pending',
            'created_at' => $transaction['created_at']
        ];
    }
    
    // Return paginated response - match frontend expectations
    Response::success([
        'data' => $formattedTransactions,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages
        ]
    ], "Transactions retrieved successfully");
    
} catch (Exception $e) {
    error_log("Transactions endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve transactions");
}
?>