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
            $companySql = "SELECT company_id FROM companies WHERE is_active = 1 LIMIT 1";
            $companyResult = $db->fetchOne($companySql);
            $company_id = $companyResult['company_id'] ?? 1;
        } catch (Exception $e) {
            $company_id = 1; // Fallback to company ID 1
        }
    }
    
    // Query for KPI data using main schema
    $revenueSql = "
        SELECT COALESCE(SUM(tl.credit_amount), 0) as totalRevenue
        FROM transactions t
        JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        JOIN accounts a ON tl.account_id = a.account_id
        WHERE t.company_id = ? AND t.status = 'posted' AND a.account_type = 'REVENUE'
    ";
    
    $expenseSql = "
        SELECT COALESCE(SUM(tl.debit_amount), 0) as totalExpenses
        FROM transactions t
        JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        JOIN accounts a ON tl.account_id = a.account_id
        WHERE t.company_id = ? AND t.status = 'posted' AND a.account_type = 'EXPENSE'
    ";
    
    $cashSql = "
        SELECT COALESCE(SUM(current_balance), 0) as cashBalance
        FROM accounts 
        WHERE company_id = ? AND is_active = 1 
        AND (account_name LIKE '%Cash%' OR account_name LIKE '%Bank%' OR account_name LIKE '%Checking%' OR account_name LIKE '%Savings%')
    ";
    
    // Additional queries for better KPI data
    $totalAssetsSql = "
        SELECT COALESCE(SUM(current_balance), 0) as totalAssets
        FROM accounts 
        WHERE company_id = ? AND is_active = 1 AND account_type = 'ASSET'
    ";
    
    $totalLiabilitiesSql = "
        SELECT COALESCE(SUM(current_balance), 0) as totalLiabilities
        FROM accounts 
        WHERE company_id = ? AND is_active = 1 AND account_type = 'LIABILITY'
    ";
    
    $revenueResult = $db->fetchOne($revenueSql, [$company_id]);
    $expenseResult = $db->fetchOne($expenseSql, [$company_id]);
    $cashResult = $db->fetchOne($cashSql, [$company_id]);
    $totalAssetsResult = $db->fetchOne($totalAssetsSql, [$company_id]);
    $totalLiabilitiesResult = $db->fetchOne($totalLiabilitiesSql, [$company_id]);
    
    $totalRevenue = (float)$revenueResult['totalRevenue'];
    $totalExpenses = (float)$expenseResult['totalExpenses'];
    $cashBalance = (float)$cashResult['cashBalance'];
    $totalAssets = (float)$totalAssetsResult['totalAssets'];
    $totalLiabilities = (float)$totalLiabilitiesResult['totalLiabilities'];
    $netProfit = $totalRevenue - $totalExpenses;
    $totalEquity = $totalAssets - abs($totalLiabilities); // Equity = Assets - Liabilities
    
    // Format KPI data for frontend
    $kpiData = [
        'totalRevenue' => $totalRevenue,
        'totalExpenses' => $totalExpenses,
        'netProfit' => $netProfit,
        'cashBalance' => $cashBalance,
        'totalAssets' => $totalAssets,
        'totalLiabilities' => $totalLiabilities,
        'totalEquity' => $totalEquity
    ];
    
    // Return success response
    Response::success($kpiData, "KPI data retrieved successfully");
    
} catch (Exception $e) {
    error_log("Dashboard KPI endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve KPI data", $e);
}
?>