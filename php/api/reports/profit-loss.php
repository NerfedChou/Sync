<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get parameters
    $company_id = $_GET['company_id'] ?? null;
    $period = $_GET['period'] ?? 'monthly';
    
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    // Calculate date range based on period
    $endDate = date('Y-m-d');
    switch ($period) {
        case 'monthly':
            $startDate = date('Y-m-01'); // First day of current month
            break;
        case 'quarterly':
            $quarter = ceil(date('n') / 3);
            $startDate = date('Y-m-d', mktime(0, 0, 0, ($quarter - 1) * 3 + 1, 1, date('Y')));
            break;
        case 'yearly':
            $startDate = date('Y-01-01'); // First day of current year
            break;
        default:
            $startDate = date('Y-m-01');
    }
    
    // Query revenue data from simplified transactions
    $revenueSql = "
        SELECT 
            COALESCE(SUM(amount), 0) as total,
            category
        FROM transactions_simple
        WHERE company_id = ? 
            AND date BETWEEN ? AND ?
            AND (category LIKE '%Revenue%' OR category LIKE '%Income%' OR account LIKE '%Revenue%')
        GROUP BY category
        ORDER BY total DESC
    ";
    
    $revenueResults = $db->fetchAll($revenueSql, [$company_id, $startDate, $endDate]);
    
    // Query expense data from simplified transactions
    $expenseSql = "
        SELECT 
            COALESCE(SUM(ABS(amount)), 0) as total,
            category
        FROM transactions_simple
        WHERE company_id = ? 
            AND date BETWEEN ? AND ?
            AND (category LIKE '%Expense%' OR category LIKE '%Cost%' OR account LIKE '%Expense%')
        GROUP BY category
        ORDER BY total DESC
    ";
    
    $expenseResults = $db->fetchAll($expenseSql, [$company_id, $startDate, $endDate]);
    
    // Calculate totals
    $totalRevenue = array_sum(array_column($revenueResults, 'total'));
    $totalExpenses = array_sum(array_column($expenseResults, 'total'));
    $netProfit = $totalRevenue - $totalExpenses;
    $profitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0;
    
    // Format revenue categories
    $revenueCategories = [];
    foreach ($revenueResults as $row) {
        $revenueCategories[$row['category']] = (float)$row['total'];
    }
    
    // Format expense categories
    $expenseCategories = [];
    foreach ($expenseResults as $row) {
        $expenseCategories[$row['category']] = (float)$row['total'];
    }
    
    // Build response
    $profitLossData = [
        'period' => [
            'start' => $startDate,
            'end' => $endDate,
            'type' => $period
        ],
        'revenue' => [
            'total' => (float)$totalRevenue,
            'categories' => $revenueCategories
        ],
        'expenses' => [
            'total' => (float)$totalExpenses,
            'categories' => $expenseCategories
        ],
        'net_profit' => (float)$netProfit,
        'profit_margin' => $profitMargin,
        'summary' => [
            'totalRevenue' => (float)$totalRevenue,
            'totalExpenses' => (float)$totalExpenses,
            'netIncome' => (float)$netProfit,
            'profitMargin' => $profitMargin
        ]
    ];
    
    Response::success($profitLossData, "Profit & Loss report generated successfully");
    
} catch (Exception $e) {
    error_log("Profit & Loss report error: " . $e->getMessage());
    Response::serverError("Failed to generate Profit & Loss report");
}
?>