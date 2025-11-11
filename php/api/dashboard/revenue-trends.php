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
    
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    // Calculate date range
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$period} days"));
    
    // Determine date format and grouping based on period
    $dateFormat = getDateFormat($period);
    $groupBy = getGroupBy($period);
    
    // Query for revenue trends from simplified transactions
    $sql = "
        SELECT 
            DATE_FORMAT(date, '{$dateFormat}') as label,
            SUM(CASE 
                WHEN status = 'completed' 
                AND (category LIKE '%Revenue%' OR account LIKE '%Revenue%') 
                THEN amount ELSE 0 END
            ) as data
        FROM transactions_simple 
        WHERE company_id = ? 
            AND date BETWEEN ? AND ?
            AND status = 'completed'
            AND (category LIKE '%Revenue%' OR account LIKE '%Revenue%')
        GROUP BY {$groupBy}, date
        ORDER BY date
        LIMIT 30
    ";
    
    $results = $db->fetchAll($sql, [$company_id, $startDate, $endDate]);
    
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