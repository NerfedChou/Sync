<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get company_id from query parameter (optional for demo)
    $company_id = $_GET['company_id'] ?? null;
    
    // For demo purposes, use first company if no company_id provided
    if (!$company_id) {
        try {
            $companySql = "SELECT id FROM companies WHERE is_active = 1 LIMIT 1";
            $companyResult = $db->fetchOne($companySql);
            $company_id = $companyResult['id'] ?? 1;
        } catch (Exception $e) {
            $company_id = 1; // Fallback to company ID 1
        }
    }
    
    // Query for KPI data using simplified structure
    $revenueSql = "
        SELECT COALESCE(SUM(amount), 0) as totalRevenue
        FROM transactions_simple 
        WHERE company_id = ? AND status = 'completed' 
        AND (category LIKE '%Revenue%' OR account LIKE '%Revenue%')
    ";
    
    $expenseSql = "
        SELECT COALESCE(SUM(amount), 0) as totalExpenses
        FROM transactions_simple 
        WHERE company_id = ? AND status = 'completed' 
        AND (category LIKE '%Expense%' OR account LIKE '%Expense%')
    ";
    
    $cashSql = "
        SELECT COALESCE(SUM(current_balance), 0) as cashBalance
        FROM accounts 
        WHERE company_id = ? AND is_active = 1 
        AND (account_name LIKE '%Cash%' OR account_name LIKE '%Bank%' OR account_type = 'ASSET')
    ";
    
    $revenueResult = $db->fetchOne($revenueSql, [$company_id]);
    $expenseResult = $db->fetchOne($expenseSql, [$company_id]);
    $cashResult = $db->fetchOne($cashSql, [$company_id]);
    
    $totalRevenue = (float)$revenueResult['totalRevenue'];
    $totalExpenses = (float)$expenseResult['totalExpenses'];
    $cashBalance = (float)$cashResult['cashBalance'];
    $netProfit = $totalRevenue - $totalExpenses;
    
    // Format KPI data for frontend
    $kpiData = [
        'totalRevenue' => $totalRevenue,
        'totalExpenses' => $totalExpenses,
        'netProfit' => $netProfit,
        'cashBalance' => $cashBalance
    ];
    
    // Return success response
    Response::success($kpiData, "KPI data retrieved successfully");
    
} catch (Exception $e) {
    error_log("Dashboard KPI endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve KPI data");
}
?>