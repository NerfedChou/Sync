<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get parameters
    $company_id = $_GET['company_id'] ?? null;
    $expense_account = $_GET['expense_account'] ?? null;
    $period = (int)($_GET['period'] ?? 30); // Default to 30 days
    
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    if (!$expense_account) {
        Response::error("expense_account parameter is required", 400);
    }
    
    // Calculate date range
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$period} days"));
    
    // Query for expense timeline data
    $sql = "
        SELECT 
            DATE_FORMAT(date, '%b %d') as label,
            date as full_date,
            SUM(CASE 
                WHEN status = 'completed' 
                AND (category LIKE '%Expense%' OR account LIKE '%Expense%')
                AND account = ?
                THEN amount ELSE 0 END
            ) as amount
        FROM transactions_simple 
        WHERE company_id = ? 
            AND date BETWEEN ? AND ?
            AND status = 'completed'
            AND (category LIKE '%Expense%' OR account LIKE '%Expense%')
            AND account = ?
        GROUP BY DATE_FORMAT(date, '%b %d'), date, account
        ORDER BY date
        LIMIT 30
    ";
    
    $results = $db->fetchAll($sql, [$expense_account, $company_id, $startDate, $endDate, $expense_account]);
    
    // Format data for Chart.js
    $labels = array_column($results, 'label');
    $amounts = array_map('floatval', array_column($results, 'amount'));
    
    // If no data, provide empty structure
    if (empty($labels)) {
        $labels = ['No data'];
        $amounts = [0];
    }
    
    $expenseTimeline = [
        'labels' => $labels,
        'data' => $amounts,
        'expense_account' => $expense_account,
        'total_amount' => array_sum($amounts)
    ];
    
    // Return success response
    Response::success($expenseTimeline, "Expense timeline retrieved successfully");
    
} catch (Exception $e) {
    error_log("Expense timeline endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve expense timeline");
}
?>