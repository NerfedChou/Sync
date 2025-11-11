<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    $company_id = $_GET['company_id'] ?? 1;
    
    // Get financial summary from accounts
    $accountsSql = "SELECT 
                        account_type,
                        SUM(CASE WHEN is_active = 1 THEN current_balance ELSE 0 END) as total_balance
                    FROM accounts 
                    WHERE company_id = ? 
                    GROUP BY account_type";
    
    $accountsData = $db->fetchAll($accountsSql, [$company_id]);
    
    // Calculate totals
    $totalAssets = 0;
    $totalLiabilities = 0;
    $totalEquity = 0;
    $totalRevenue = 0;
    $totalExpenses = 0;
    
    foreach ($accountsData as $account) {
        switch ($account['account_type']) {
            case 'ASSET':
                $totalAssets += (float)$account['total_balance'];
                break;
            case 'LIABILITY':
                $totalLiabilities += (float)$account['total_balance'];
                break;
            case 'EQUITY':
                $totalEquity += (float)$account['total_balance'];
                break;
            case 'REVENUE':
                $totalRevenue += (float)$account['total_balance'];
                break;
            case 'EXPENSE':
                $totalExpenses += (float)$account['total_balance'];
                break;
        }
    }
    
    // Get transaction summary
    $transactionSql = "SELECT 
                          COUNT(*) as total_transactions,
                          SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
                          SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                          SUM(amount) as total_amount
                      FROM transactions_simple 
                      WHERE company_id = ?";
    
    $transactionData = $db->fetchOne($transactionSql, [$company_id]);
    
    // Get recent transactions
    $recentSql = "SELECT name, date, amount, status 
                  FROM transactions_simple 
                  WHERE company_id = ? 
                  ORDER BY date DESC, created_at DESC 
                  LIMIT 5";
    
    $recentTransactions = $db->fetchAll($recentSql, [$company_id]);
    
    // Build dashboard data
    $dashboardData = [
        'financial_summary' => [
            'Total Assets' => $totalAssets,
            'Total Liabilities' => $totalLiabilities,
            'Total Equity' => $totalEquity,
            'Net Income' => $totalRevenue - $totalExpenses
        ],
        'transaction_summary' => [
            'Total Transactions' => (int)$transactionData['total_transactions'],
            'Completed Amount' => (float)$transactionData['completed_amount'],
            'Pending Amount' => (float)$transactionData['pending_amount'],
            'Total Amount' => (float)$transactionData['total_amount']
        ],
        'recent_transactions' => array_map(function($transaction) {
            return [
                'Name' => $transaction['name'],
                'Date' => $transaction['date'],
                'Amount' => (float)$transaction['amount'],
                'Status' => $transaction['status']
            ];
        }, $recentTransactions)
    ];
    
    // Return success response
    Response::success($dashboardData, "Dashboard data retrieved successfully");
    
} catch (Exception $e) {
    error_log("Dashboard endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve dashboard data");
}
?>