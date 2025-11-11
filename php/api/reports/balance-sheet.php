
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get parameters
    $company_id = $_GET['company_id'] ?? null;
    $as_of_date = $_GET['as_of_date'] ?? date('Y-m-d');
    
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    // Validate date
    if (!DateTime::createFromFormat('Y-m-d', $as_of_date)) {
        Response::error("Invalid date format. Use Y-m-d", 400);
    }
    
    // Get account balances from simplified accounts table
    $balanceSql = "
        SELECT 
            account_id as id,
            account_name,
            account_type,
            current_balance as balance
        FROM accounts 
        WHERE company_id = ? 
            AND is_active = 1
        ORDER BY account_type, account_name
    ";
    
    $accounts = $db->fetchAll($balanceSql, [$company_id]);
    
    // Categorize accounts
    $assets = [];
    $liabilities = [];
    $equity = [];
    
    $totalAssets = 0;
    $totalLiabilities = 0;
    $totalEquity = 0;
    
    foreach ($accounts as $account) {
        $balance = (float)$account['balance'];
        $accountData = [
            'id' => (int)$account['id'],
            'name' => $account['account_name'],
            'code' => '',
            'balance' => $balance
        ];
        
        switch ($account['account_type']) {
            case 'asset':
                // Assets normally have debit balances
                $assets[] = $accountData;
                $totalAssets += $balance;
                break;
                
            case 'liability':
                // Liabilities normally have credit balances (negative in our calculation)
                $liabilities[] = $accountData;
                $totalLiabilities += abs($balance);
                break;
                
            case 'equity':
                // Equity normally has credit balances (negative in our calculation)
                $equity[] = $accountData;
                $totalEquity += abs($balance);
                break;
                
            case 'revenue':
                // Revenue accounts affect equity
                $equity[] = array_merge($accountData, ['name' => $account['account_name'] . ' (Revenue)']);
                $totalEquity += abs($balance);
                break;
                
            case 'expense':
                // Expense accounts affect equity
                $equity[] = array_merge($accountData, ['name' => $account['account_name'] . ' (Expense)']);
                $totalEquity -= $balance; // Expenses reduce equity
                break;
        }
    }
    
    // Calculate totals and verify balance sheet equation
    $totalLiabilitiesPlusEquity = $totalLiabilities + $totalEquity;
    $isBalanced = abs($totalAssets - $totalLiabilitiesPlusEquity) < 0.01; // Allow for rounding
    
    // Build response
    $balanceSheetData = [
        'as_of_date' => $as_of_date,
        'company_id' => (int)$company_id,
        'assets' => [
            'accounts' => $assets,
            'total' => $totalAssets
        ],
        'liabilities' => [
            'accounts' => $liabilities,
            'total' => $totalLiabilities
        ],
        'equity' => [
            'accounts' => $equity,
            'total' => $totalEquity
        ],
        'totals' => [
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'liabilities_plus_equity' => $totalLiabilitiesPlusEquity,
            'is_balanced' => $isBalanced,
            'difference' => $totalAssets - $totalLiabilitiesPlusEquity
        ],
        'summary' => [
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'isBalanced' => $isBalanced
        ]
    ];
    
    Response::success($balanceSheetData, "Balance Sheet report generated successfully");
    
} catch (Exception $e) {
    error_log("Balance Sheet report error: " . $e->getMessage());
    Response::serverError("Failed to generate Balance Sheet report");
}
?>