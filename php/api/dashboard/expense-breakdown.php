<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get parameters
    $company_id = $_GET['company_id'] ?? null;
    $period = (int)($_GET['period'] ?? 30); // Default to 30 days
    
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    // Calculate date range
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$period} days"));
    
    // Query for expense breakdown by actual expense accounts/items
    $sql = "
        SELECT 
            COALESCE(account, 'Uncategorized') as label,
            SUM(CASE 
                WHEN status = 'completed' 
                AND (category LIKE '%Expense%' OR account LIKE '%Expense%') 
                THEN amount ELSE 0 END
            ) as data
        FROM transactions_simple 
        WHERE company_id = ? 
            AND date BETWEEN ? AND ?
            AND status = 'completed'
            AND (category LIKE '%Expense%' OR account LIKE '%Expense%')
        GROUP BY COALESCE(account, 'Uncategorized')
        ORDER BY data DESC
        LIMIT 10
    ";
    
    $results = $db->fetchAll($sql, [$company_id, $startDate, $endDate]);
    
    // Format data for Chart.js
    $labels = array_column($results, 'label');
    $data = array_map('floatval', array_column($results, 'data'));
    
    // If no data, provide empty structure
    if (empty($labels)) {
        $labels = ['No expenses'];
        $data = [0];
    }
    
    $expenseBreakdown = [
        'labels' => $labels,
        'data' => $data
    ];
    
    // Return success response
    Response::success($expenseBreakdown, "Expense breakdown retrieved successfully");
    
} catch (Exception $e) {
    error_log("Expense breakdown endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve expense breakdown");
}
?>