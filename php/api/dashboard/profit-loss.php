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
    
    // Query for revenue data from main schema
    $revenueSql = "
        SELECT 
            DATE_FORMAT(t.transaction_date, '%b %d') as label,
            SUM(tl.credit_amount) as revenue
        FROM transactions t
        JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        JOIN accounts a ON tl.account_id = a.account_id
        WHERE t.company_id = ? 
            AND t.transaction_date BETWEEN ? AND ?
            AND t.status = 'posted'
            AND a.account_type = 'REVENUE'
        GROUP BY DATE_FORMAT(t.transaction_date, '%b %d'), t.transaction_date
        ORDER BY t.transaction_date
        LIMIT 30
    ";
    
    $revenueResults = $db->fetchAll($revenueSql, [$company_id, $startDate, $endDate]);
    
    // Query for expense data from main schema
    $expenseSql = "
        SELECT 
            DATE_FORMAT(t.transaction_date, '%b %d') as label,
            SUM(tl.debit_amount) as expenses
        FROM transactions t
        JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        JOIN accounts a ON tl.account_id = a.account_id
        WHERE t.company_id = ? 
            AND t.transaction_date BETWEEN ? AND ?
            AND t.status = 'posted'
            AND a.account_type = 'EXPENSE'
        GROUP BY DATE_FORMAT(t.transaction_date, '%b %d'), t.transaction_date
        ORDER BY t.transaction_date
        LIMIT 30
    ";
    
    $expenseResults = $db->fetchAll($expenseSql, [$company_id, $startDate, $endDate]);
    
    // Combine revenue and expense data by date
    $combinedData = [];
    $labels = [];
    
    // Get all unique dates from both datasets
    $allDates = array_unique(array_merge(
        array_column($revenueResults, 'label'),
        array_column($expenseResults, 'label')
    ));
    
    sort($allDates);
    
    foreach ($allDates as $date) {
        // Find revenue for this date
        $revenueRow = array_filter($revenueResults, function($row) use ($date) {
            return $row['label'] === $date;
        });
        $revenue = !empty($revenueRow) ? array_values($revenueRow)[0]['revenue'] : 0;
        
        // Find expense for this date
        $expenseRow = array_filter($expenseResults, function($row) use ($date) {
            return $row['label'] === $date;
        });
        $expense = !empty($expenseRow) ? array_values($expenseRow)[0]['expenses'] : 0;
        
        $labels[] = $date;
        $combinedData[] = [
            'revenue' => (float)$revenue,
            'expenses' => (float)$expense,
            'profit' => (float)($revenue - abs($expense))
        ];
    }
    
    // If no data, provide empty structure
    if (empty($labels)) {
        $labels = ['No data'];
        $combinedData = [
            ['revenue' => 0, 'expenses' => 0, 'profit' => 0]
        ];
    }
    
    $profitLossData = [
        'labels' => $labels,
        'revenue' => array_column($combinedData, 'revenue'),
        'expenses' => array_column($combinedData, 'expenses'),
        'profit' => array_column($combinedData, 'profit')
    ];
    
    // Return success response
    Response::success($profitLossData, "Profit and Loss data retrieved successfully");
    
} catch (Exception $e) {
    error_log("Profit and Loss endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve Profit and Loss data", $e);
}
?>