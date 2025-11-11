
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
    
    // Get cash and cash equivalent accounts
    $cashAccountsSql = "
        SELECT id, account_name 
        FROM accounts 
        WHERE company_id = ? 
            AND is_active = 1
            AND (account_name LIKE '%cash%' 
                OR account_name LIKE '%bank%' 
                OR account_name LIKE '%checking%'
                OR account_name LIKE '%savings%'
                OR type = 'asset')
    ";
    
    $cashAccounts = $db->fetchAll($cashAccountsSql, [$company_id]);
    $cashAccountIds = array_column($cashAccounts, 'id');
    
    if (empty($cashAccountIds)) {
        Response::error("No cash accounts found for this company", 404);
    }
    
    // Build placeholders for cash account IDs
    $placeholders = str_repeat('?,', count($cashAccountIds) - 1) . '?';
    
    // Query cash transactions
    $cashFlowSql = "
        SELECT 
            t.id,
            t.transaction_date,
            t.description,
            t.amount,
            t.type,
            t.category,
            a.account_name,
            CASE 
                WHEN a.account_name LIKE '%operating%' OR t.category IN ('Sales', 'Expenses', 'Salaries', 'Rent', 'Utilities') THEN 'operating'
                WHEN a.account_name LIKE '%investing%' OR t.category IN ('Equipment', 'Property', 'Investments') THEN 'investing'
                WHEN a.account_name LIKE '%financing%' OR t.category IN ('Loans', 'Equity', 'Dividends') THEN 'financing'
                ELSE 'operating'
            END as activity_type
        FROM transactions t
        LEFT JOIN accounts a ON t.account_id = a.id
        WHERE t.company_id = ? 
            AND t.transaction_date BETWEEN ? AND ?
            AND t.status = 'completed'
            AND t.account_id IN ($placeholders)
        ORDER BY t.transaction_date DESC
    ";
    
    $params = array_merge([$company_id, $startDate, $endDate], $cashAccountIds);
    $transactions = $db->fetchAll($cashFlowSql, $params);
    
    // Categorize transactions by activity type
    $operating = [];
    $investing = [];
    $financing = [];
    
    $totalOperating = 0;
    $totalInvesting = 0;
    $totalFinancing = 0;
    
    foreach ($transactions as $transaction) {
        $amount = (float)$transaction['amount'];
        $transactionData = [
            'id' => (int)$transaction['id'],
            'date' => $transaction['transaction_date'],
            'description' => $transaction['description'],
            'amount' => $amount,
            'type' => $transaction['type'],
            'category' => $transaction['category'],
            'account' => $transaction['account_name']
        ];
        
        // Calculate cash flow effect
        $cashEffect = 0;
        if ($transaction['type'] === 'debit') {
            $cashEffect = -$amount; // Cash outflow
        } else {
            $cashEffect = $amount; // Cash inflow
        }
        
        switch ($transaction['activity_type']) {
            case 'operating':
                $operating[] = array_merge($transactionData, ['cash_effect' => $cashEffect]);
                $totalOperating += $cashEffect;
                break;
                
            case 'investing':
                $investing[] = array_merge($transactionData, ['cash_effect' => $cashEffect]);
                $totalInvesting += $cashEffect;
                break;
                
            case 'financing':
                $financing[] = array_merge($transactionData, ['cash_effect' => $cashEffect]);
                $totalFinancing += $cashEffect;
                break;
        }
    }
    
    // Calculate net cash flow
    $netCashFlow = $totalOperating + $totalInvesting + $totalFinancing;
    
    // Get beginning cash balance
    $beginningBalanceSql = "
        SELECT COALESCE(SUM(CASE 
            WHEN tl.debit_amount > 0 THEN tl.debit_amount 
            ELSE -tl.credit_amount 
        END), 0) as balance
        FROM transaction_lines tl
        LEFT JOIN transactions t ON tl.transaction_id = t.id
        WHERE t.company_id = ? 
            AND t.transaction_date < ?
            AND tl.account_id IN ($placeholders)
    ";
    
    $beginningParams = array_merge([$company_id, $startDate], $cashAccountIds);
    $beginningResult = $db->fetchOne($beginningBalanceSql, $beginningParams);
    $beginningCash = (float)$beginningResult['balance'];
    
    // Calculate ending cash balance
    $endingCash = $beginningCash + $netCashFlow;
    
    // Build response
    $cashFlowData = [
        'period' => [
            'start' => $startDate,
            'end' => $endDate,
            'type' => $period
        ],
        'cash_accounts' => array_map(function($account) {
            return [
                'id' => (int)$account['id'],
                'name' => $account['account_name']
            ];
        }, $cashAccounts),
        'activities' => [
            'operating' => [
                'transactions' => $operating,
                'total' => $totalOperating
            ],
            'investing' => [
                'transactions' => $investing,
                'total' => $totalInvesting
            ],
            'financing' => [
                'transactions' => $financing,
                'total' => $totalFinancing
            ]
        ],
        'summary' => [
            'beginning_cash' => $beginningCash,
            'operating_cash_flow' => $totalOperating,
            'investing_cash_flow' => $totalInvesting,
            'financing_cash_flow' => $totalFinancing,
            'net_cash_flow' => $netCashFlow,
            'ending_cash' => $endingCash
        ]
    ];
    
    Response::success($cashFlowData, "Cash Flow report generated successfully");
    
} catch (Exception $e) {
    error_log("Cash Flow report error: " . $e->getMessage());
    Response::serverError("Failed to generate Cash Flow report");
}
?>