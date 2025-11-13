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
            $companySql = "SELECT company_id FROM companies WHERE is_active =1 LIMIT 1";
            $companyResult = $db->fetchOne($companySql);
            $company_id = $companyResult['company_id'] ?? 1;
        } catch (Exception $e) {
            $company_id = 1; // Fallback to company ID 1
        }
    }
    
    // Calculate date range
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$period} days"));
    
    // Query for expense breakdown using main schema
    $sql = "
        SELECT 
            a.account_name as label,
            SUM(tl.debit_amount) as data
        FROM transactions t
        JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        JOIN accounts a ON tl.account_id = a.account_id
        WHERE t.company_id = ? 
            AND t.transaction_date BETWEEN ? AND ?
            AND t.status = 'posted'
            AND a.account_type = 'EXPENSE'
        GROUP BY a.account_id, a.account_name
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