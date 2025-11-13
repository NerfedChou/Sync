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
    
    // Get transactions
    $sql = "
        SELECT 
            t.transaction_id as id,
            t.transaction_number as name,
            t.transaction_date as date,
            t.description,
            a.account_name as account,
            COALESCE(tl.debit_amount, 0) as debit_amount,
            COALESCE(tl.credit_amount, 0) as credit_amount,
            t.total_amount as amount,
            t.status,
            t.created_at
        FROM transactions t
        LEFT JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        LEFT JOIN accounts a ON tl.account_id = a.account_id
        {$whereClause}
        ORDER BY t.transaction_date DESC, t.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $transactions = $db->fetchAll($sql, $params);
    
    // Format transactions for frontend
    $formattedTransactions = array_map(function($transaction) {
        $amount = $transaction['debit_amount'] > 0 ? $transaction['debit_amount'] : -$transaction['credit_amount'];
        return [
            'id' => (int)$transaction['id'],
            'name' => $transaction['name'],
            'Date' => $transaction['date'],
            'Description' => $transaction['description'] ?? '',
            'Account' => $transaction['account'] ?? '',
            'Amount' => (float)$amount,
            'Category' => 'General', // Default category since not in DB
            'Status' => $transaction['status'] ?? 'pending',
            'created_at' => $transaction['created_at']
        ];
    }, $transactions);
    
    // Return paginated response
    $response = [
        'data' => $formattedTransactions,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages
        ]
    ];
    
    Response::success($response, "Transactions retrieved successfully");
    
} catch (Exception $e) {
    error_log("Transactions endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve transactions");
}
?>