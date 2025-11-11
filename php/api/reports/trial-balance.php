
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
    
    // Query all accounts with their balances
    $trialBalanceSql = "
        SELECT 
            a.id,
            a.account_name,
            a.account_code,
            a.type as account_type,
            COALESCE(
                SUM(CASE WHEN tl.debit_amount > 0 THEN tl.debit_amount ELSE 0 END), 0
            ) as total_debits,
            COALESCE(
                SUM(CASE WHEN tl.credit_amount > 0 THEN tl.credit_amount ELSE 0 END), 0
            ) as total_credits,
            COALESCE(SUM(CASE 
                WHEN tl.debit_amount > 0 THEN tl.debit_amount 
                ELSE -tl.credit_amount 
            END), 0) as balance
        FROM accounts a
        LEFT JOIN transaction_lines tl ON a.id = tl.account_id
        LEFT JOIN transactions t ON tl.transaction_id = t.id
        WHERE a.company_id = ? 
            AND a.is_active = 1
            AND (t.transaction_date <= ? OR t.transaction_date IS NULL)
        GROUP BY a.id, a.account_name, a.account_code, a.type
        ORDER BY a.account_code
    ";
    
    $accounts = $db->fetchAll($trialBalanceSql, [$company_id, $as_of_date]);
    
    // Prepare trial balance data
    $trialBalanceEntries = [];
    $totalDebits = 0;
    $totalCredits = 0;
    
    foreach ($accounts as $account) {
        $debits = (float)$account['total_debits'];
        $credits = (float)$account['total_credits'];
        $balance = (float)$account['balance'];
        
        // Determine debit or credit balance based on account type
        $debitBalance = 0;
        $creditBalance = 0;
        
        switch ($account['account_type']) {
            case 'asset':
            case 'expense':
                // Normally have debit balances
                if ($balance >= 0) {
                    $debitBalance = $balance;
                } else {
                    $creditBalance = abs($balance);
                }
                break;
                
            case 'liability':
            case 'equity':
            case 'revenue':
                // Normally have credit balances
                if ($balance >= 0) {
                    $creditBalance = $balance;
                } else {
                    $debitBalance = abs($balance);
                }
                break;
        }
        
        // Only include accounts with non-zero balances
        if ($debitBalance > 0 || $creditBalance > 0) {
            $trialBalanceEntries[] = [
                'id' => (int)$account['id'],
                'account_code' => $account['account_code'],
                'account_name' => $account['account_name'],
                'account_type' => $account['account_type'],
                'debit_balance' => $debitBalance,
                'credit_balance' => $creditBalance,
                'total_debits' => $debits,
                'total_credits' => $credits,
                'balance' => $balance
            ];
            
            $totalDebits += $debitBalance;
            $totalCredits += $creditBalance;
        }
    }
    
    // Verify trial balance is in balance (debits should equal credits)
    $difference = $totalDebits - $totalCredits;
    $isBalanced = abs($difference) < 0.01; // Allow for rounding
    
    // Group by account type for summary
    $summaryByType = [];
    foreach ($trialBalanceEntries as $entry) {
        $type = $entry['account_type'];
        if (!isset($summaryByType[$type])) {
            $summaryByType[$type] = [
                'debit_total' => 0,
                'credit_total' => 0,
                'count' => 0
            ];
        }
        
        $summaryByType[$type]['debit_total'] += $entry['debit_balance'];
        $summaryByType[$type]['credit_total'] += $entry['credit_balance'];
        $summaryByType[$type]['count']++;
    }
    
    // Build response
    $trialBalanceData = [
        'as_of_date' => $as_of_date,
        'company_id' => (int)$company_id,
        'entries' => $trialBalanceEntries,
        'totals' => [
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'difference' => $difference,
            'is_balanced' => $isBalanced
        ],
        'summary_by_type' => $summaryByType,
        'summary' => [
            'totalAccounts' => count($trialBalanceEntries),
            'totalDebits' => $totalDebits,
            'totalCredits' => $totalCredits,
            'isBalanced' => $isBalanced,
            'difference' => $difference
        ]
    ];
    
    Response::success($trialBalanceData, "Trial Balance report generated successfully");
    
} catch (Exception $e) {
    error_log("Trial Balance report error: " . $e->getMessage());
    Response::serverError("Failed to generate Trial Balance report");
}
?>