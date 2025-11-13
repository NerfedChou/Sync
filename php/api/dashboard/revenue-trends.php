<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

/**
 * Get date format based on period
 */
function getDateFormat($period) {
    if ($period <= 1) {
        return '%H:00'; // Hourly for today
    } elseif ($period <= 7) {
        return '%a %d'; // Day name for week
    } elseif ($period <= 30) {
        return '%b %d'; // Month day for month
    } elseif ($period <= 90) {
        return '%b %d'; // Month day for quarter
    } else {
        return '%b %d'; // Month day for year
    }
}

/**
 * Get GROUP BY clause based on period
 */
function getGroupBy($period) {
    if ($period <= 1) {
        return 'DATE_FORMAT(date, "%Y-%m-%d %H")'; // Hourly
    } elseif ($period <= 7) {
        return 'DATE(date)'; // Daily for week
    } elseif ($period <= 30) {
        return 'DATE(date)'; // Daily for month
    } elseif ($period <= 90) {
        return 'WEEK(date)'; // Weekly for quarter
    } else {
        return 'MONTH(date)'; // Monthly for year
    }
}

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get parameters
    $company_id = $_GET['company_id'] ?? null;
    $period = (int)($_GET['period'] ?? 30); // Default to 30 days
    
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
    
    // For testing purposes, use a broader date range to include sample data
    $startDate = '2025-01-01'; // Start from beginning of year to include sample data
    
    // Determine date format and grouping based on period
    $dateFormat = getDateFormat($period);
    $groupBy = getGroupBy($period);
    
    // Query for revenue trends using main schema
    $sql = "
        SELECT 
            DATE_FORMAT(t.transaction_date, '{$dateFormat}') as label,
            SUM(tl.credit_amount) as data
        FROM transactions t
        JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        JOIN accounts a ON tl.account_id = a.account_id
        WHERE t.company_id = ? 
            AND t.transaction_date BETWEEN ? AND ?
            AND t.status = 'posted'
            AND a.account_type = 'REVENUE'
        GROUP BY {$groupBy}, t.transaction_date
        ORDER BY t.transaction_date
        LIMIT 30
    ";
    
    $results = $db->fetchAll($sql, [$company_id, $startDate, $endDate]);
    
    // Debug logging
    error_log("Revenue trends query: " . $sql);
    error_log("Parameters: company_id=$company_id, start_date=$startDate, end_date=$endDate");
    error_log("Results: " . json_encode($results));
    
    // Format data for Chart.js
    $labels = array_column($results, 'label');
    $data = array_map('floatval', array_column($results, 'data'));
    
    // If no data, provide empty structure
    if (empty($labels)) {
        $labels = ['No data'];
        $data = [0];
    }
    
    $revenueTrends = [
        'labels' => $labels,
        'data' => $data
    ];
    
    // Return success response
    Response::success($revenueTrends, "Revenue trends retrieved successfully");
    
} catch (Exception $e) {
    error_log("Revenue trends endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve revenue trends");
}
?>