<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get parameters
    $company_id = $_GET['company_id'] ?? null;
    $period = (int)($_GET['period'] ?? 365); // Default to 365 days to show full year data
    
    // For demo purposes, use first company if no company_id provided
    if (!$company_id) {
        try {
            $companySql = "SELECT id FROM companies WHERE is_active =1 LIMIT 1";
            $companyResult = $db->fetchOne($companySql);
            $company_id = $companyResult['id'] ?? 1;
        } catch (Exception $e) {
            $company_id = 1; // Fallback to company ID 1
        }
    }
    
    // Calculate date range
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$period} days"));
    
    // Query for expense breakdown by actual expense accounts/items
    // Include all expense transactions regardless of status for comprehensive view
    $sql = "
        SELECT 
            COALESCE(account, 'Uncategorized') as label,
            SUM(CASE 
                WHEN (category LIKE '%Expense%' OR account LIKE '%Expense%') 
                THEN ABS(amount) ELSE 0 END
            ) as data
        FROM transactions_simple 
        WHERE company_id = ? 
            AND date BETWEEN ? AND ?
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