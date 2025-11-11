
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
    $account_filter = $_GET['account_id'] ?? null;
    $category_filter = $_GET['category'] ?? null;
    $date_range = $_GET['date_range'] ?? null;
    
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    // Calculate offset for pagination
    $offset = ($page - 1) * $limit;
    
    // Build WHERE conditions
    $whereConditions = ["t.company_id = ?"];
    $params = [$company_id];
    
    if ($account_filter) {
        $whereConditions[] = "t.account_id = ?";
        $params[] = $account_filter;
    }
    
    if ($category_filter) {
        $whereConditions[] = "t.category = ?";
        $params[] = $category_filter;
    }
    
    if ($date_range && $date_range !== 'custom') {
        $days = (int)$date_range;
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $whereConditions[] = "t.transaction_date >= ?";
        $params[] = $startDate;
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    // Query for transactions with pagination
    $sql = "
        SELECT 
            t.id,
            t.transaction_date as date,
            t.description,
            t.type,
            t.amount,
            t.status,
            t.category,
            t.notes,
            a.account_name as account,
            a.id as account_id
        FROM transactions t
        LEFT JOIN accounts a ON t.account_id = a.id
        WHERE {$whereClause}
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $transactions = $db->fetchAll($sql, $params);
    
    // Get total count for pagination
    $countSql = "
        SELECT COUNT(*) as total
        FROM transactions t
        WHERE {$whereClause}
    ";
    
    $countParams = array_slice($params, 0, -2); // Remove limit and offset
    $countResult = $db->fetchOne($countSql, $countParams);
    $total = (int)$countResult['total'];
    
    // Format transactions for frontend
    $formattedTransactions = array_map(function($transaction) {
        return [
            'id' => (int)$transaction['id'],
            'date' => $transaction['date'],
            'description' => $transaction['description'],
            'type' => $transaction['type'],
            'amount' => (float)$transaction['amount'],
            'status' => $transaction['status'],
            'category' => $transaction['category'] ?? 'Uncategorized',
            'account' => $transaction['account'] ?? 'Unknown Account',
            'account_id' => (int)$transaction['account_id'],
            'notes' => $transaction['notes'] ?? ''
        ];
    }, $transactions);
    
    // Return paginated response
    $response = [
        'data' => $formattedTransactions,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => ceil($total / $limit)
        ]
    ];
    
    Response::success($response, "Transactions retrieved successfully");
    
} catch (Exception $e) {
    error_log("Transactions endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve transactions");
}
?>