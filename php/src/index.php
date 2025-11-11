<?php

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the request path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove /api prefix if present
$path = str_replace('/api', '', $path);

// Route the request
switch ($path) {
    case '/':
        echo json_encode([
            'message' => 'Accounting API is working',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'GET /api/' => 'API Information',
                'GET /api/dashboard' => 'Dashboard data',
                'GET /api/dashboard/kpi' => 'KPI data',
                'GET /api/accounts' => 'List accounts',
                'POST /api/accounts' => 'Create account',
                'GET /api/transactions' => 'List transactions',
                'POST /api/transactions' => 'Create transaction'
            ]
        ]);
        break;
        
    case '/dashboard':
        echo json_encode([
            'message' => 'Dashboard data',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;
        
    case '/dashboard/kpi':
        echo json_encode([
            'totalRevenue' => 125430.50,
            'totalExpenses' => 87320.75,
            'netProfit' => 38109.75,
            'cashBalance' => 62500.00
        ]);
        break;
        
    case '/dashboard/revenue-trends':
        $period = $_GET['period'] ?? 30;
        $days = min($period, 90);
        $data = [];
        $labels = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = new DateTime();
            $date->sub(new DateInterval("P{$i}D"));
            $labels[] = $date->format('M j');
            $data[] = rand(2000, 7000);
        }
        
        echo json_encode([
            'labels' => $labels,
            'data' => $data
        ]);
        break;
        
    case '/dashboard/expense-breakdown':
        echo json_encode([
            'labels' => ['Salaries', 'Rent', 'Marketing', 'Utilities', 'Supplies', 'Other'],
            'data' => [35000, 12000, 8500, 4500, 3200, 5800]
        ]);
        break;
        
    case '/accounts':
        $accounts = [
            [
                'id' => 1,
                'name' => 'Business Checking',
                'type' => 'asset',
                'balance' => 45230.50,
                'currency' => 'USD',
                'status' => 'active',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T10:30:00Z'
            ],
            [
                'id' => 2,
                'name' => 'Business Savings',
                'type' => 'asset',
                'balance' => 125000.00,
                'currency' => 'USD',
                'status' => 'active',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-14T15:45:00Z'
            ],
            [
                'id' => 3,
                'name' => 'Business Credit Card',
                'type' => 'liability',
                'balance' => -3250.75,
                'currency' => 'USD',
                'status' => 'active',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T09:20:00Z'
            ],
            [
                'id' => 4,
                'name' => 'Payroll Account',
                'type' => 'asset',
                'balance' => 18500.00,
                'currency' => 'USD',
                'status' => 'active',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-12T14:10:00Z'
            ],
            [
                'id' => 5,
                'name' => 'Tax Reserve',
                'type' => 'asset',
                'balance' => 25000.00,
                'currency' => 'USD',
                'status' => 'active',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-10T11:30:00Z'
            ],
            [
                'id' => 6,
                'name' => 'Office Expense Account',
                'type' => 'expense',
                'balance' => 0,
                'currency' => 'USD',
                'status' => 'active',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-08T16:45:00Z'
            ]
        ];
        
        echo json_encode($accounts);
        break;
        
    case '/transactions':
        $transactions = [
            [
                'id' => 1,
                'date' => '2024-01-15',
                'description' => 'Client Payment - ABC Corp',
                'category' => 'Revenue',
                'account' => 'Business Checking',
                'debit_account' => null,
                'credit_account' => 'Business Checking',
                'amount' => 12500.00,
                'type' => 'credit',
                'status' => 'completed',
                'created_at' => '2024-01-15T10:30:00Z'
            ],
            [
                'id' => 2,
                'date' => '2024-01-14',
                'description' => 'Office Rent',
                'category' => 'Expenses',
                'account' => 'Business Checking',
                'debit_account' => 'Office Expense Account',
                'credit_account' => 'Business Checking',
                'amount' => 3500.00,
                'type' => 'debit',
                'status' => 'completed',
                'created_at' => '2024-01-14T09:15:00Z'
            ],
            [
                'id' => 3,
                'date' => '2024-01-13',
                'description' => 'Software Subscription',
                'category' => 'Expenses',
                'account' => 'Business Credit Card',
                'debit_account' => 'Office Expense Account',
                'credit_account' => 'Business Credit Card',
                'amount' => 299.00,
                'type' => 'debit',
                'status' => 'completed',
                'created_at' => '2024-01-13T14:20:00Z'
            ],
            [
                'id' => 4,
                'date' => '2024-01-12',
                'description' => 'Client Payment - XYZ Ltd',
                'category' => 'Revenue',
                'account' => 'Business Checking',
                'debit_account' => null,
                'credit_account' => 'Business Checking',
                'amount' => 8750.00,
                'type' => 'credit',
                'status' => 'pending',
                'created_at' => '2024-01-12T16:45:00Z'
            ],
            [
                'id' => 5,
                'date' => '2024-01-11',
                'description' => 'Employee Salaries',
                'category' => 'Expenses',
                'account' => 'Business Checking',
                'debit_account' => 'Payroll Account',
                'credit_account' => 'Business Checking',
                'amount' => 12500.00,
                'type' => 'debit',
                'status' => 'completed',
                'created_at' => '2024-01-11T11:30:00Z'
            ],
            [
                'id' => 6,
                'date' => '2024-01-10',
                'description' => 'Equipment Purchase',
                'category' => 'Assets',
                'account' => 'Business Credit Card',
                'debit_account' => 'Office Expense Account',
                'credit_account' => 'Business Credit Card',
                'amount' => 2500.00,
                'type' => 'debit',
                'status' => 'completed',
                'created_at' => '2024-01-10T13:15:00Z'
            ],
            [
                'id' => 7,
                'date' => '2024-01-09',
                'description' => 'Consulting Revenue',
                'category' => 'Revenue',
                'account' => 'Business Checking',
                'debit_account' => null,
                'credit_account' => 'Business Checking',
                'amount' => 6200.00,
                'type' => 'credit',
                'status' => 'completed',
                'created_at' => '2024-01-09T15:30:00Z'
            ],
            [
                'id' => 8,
                'date' => '2024-01-08',
                'description' => 'Utility Bills',
                'category' => 'Expenses',
                'account' => 'Business Checking',
                'debit_account' => 'Office Expense Account',
                'credit_account' => 'Business Checking',
                'amount' => 850.00,
                'type' => 'debit',
                'status' => 'completed',
                'created_at' => '2024-01-08T10:45:00Z'
            ]
        ];
        
        echo json_encode($transactions);
        break;
        
    case '/transactions/recent':
        $transactions = [
            [
                'id' => 1,
                'date' => '2024-01-15',
                'description' => 'Client Payment - ABC Corp',
                'category' => 'Revenue',
                'account' => 'Business Checking',
                'amount' => 12500.00,
                'type' => 'credit',
                'status' => 'completed'
            ],
            [
                'id' => 2,
                'date' => '2024-01-14',
                'description' => 'Office Rent',
                'category' => 'Expenses',
                'account' => 'Business Checking',
                'amount' => -3500.00,
                'type' => 'debit',
                'status' => 'completed'
            ],
            [
                'id' => 3,
                'date' => '2024-01-13',
                'description' => 'Software Subscription',
                'category' => 'Expenses',
                'account' => 'Business Credit Card',
                'amount' => -299.00,
                'type' => 'debit',
                'status' => 'completed'
            ],
            [
                'id' => 4,
                'date' => '2024-01-12',
                'description' => 'Client Payment - XYZ Ltd',
                'category' => 'Revenue',
                'account' => 'Business Checking',
                'amount' => 8750.00,
                'type' => 'credit',
                'status' => 'pending'
            ],
            [
                'id' => 5,
                'date' => '2024-01-11',
                'description' => 'Employee Salaries',
                'category' => 'Expenses',
                'account' => 'Business Checking',
                'amount' => -12500.00,
                'type' => 'debit',
                'status' => 'completed'
            ]
        ];
        
        echo json_encode($transactions);
        break;
        
    case '/reports/profit-loss':
        $period = $_GET['period'] ?? 'monthly';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        echo json_encode([
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'revenue' => [
                'total' => 125430.50,
                'categories' => [
                    'Consulting' => 45000.00,
                    'Product Sales' => 35000.00,
                    'Services' => 28500.00,
                    'Other Revenue' => 16930.50
                ]
            ],
            'expenses' => [
                'total' => 87320.75,
                'categories' => [
                    'Salaries' => 35000.00,
                    'Rent' => 12000.00,
                    'Marketing' => 8500.00,
                    'Utilities' => 4500.00,
                    'Supplies' => 3200.00,
                    'Other Expenses' => 24120.75
                ]
            ],
            'net_profit' => 38109.75,
            'profit_margin' => 30.4
        ]);
        break;
        
    case '/reports/balance-sheet':
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        
        echo json_encode([
            'as_of_date' => $asOfDate,
            'assets' => [
                'current_assets' => [
                    'Cash and Cash Equivalents' => 62500.00,
                    'Accounts Receivable' => 18500.00,
                    'Inventory' => 8500.00,
                    'Prepaid Expenses' => 2500.00
                ],
                'fixed_assets' => [
                    'Equipment' => 45000.00,
                    'Furniture' => 12000.00,
                    'Buildings' => 250000.00,
                    'Accumulated Depreciation' => -45000.00
                ],
                'total_assets' => 356000.00
            ],
            'liabilities' => [
                'current_liabilities' => [
                    'Accounts Payable' => 12500.00,
                    'Short-term Debt' => 8500.00,
                    'Accrued Expenses' => 3200.00
                ],
                'long_term_liabilities' => [
                    'Bank Loan' => 125000.00,
                    'Mortgage' => 75000.00
                ],
                'total_liabilities' => 219200.00
            ],
            'equity' => [
                'Owner\'s Equity' => 125000.00,
                'Retained Earnings' => 11800.00,
                'total_equity' => 136800.00
            ]
        ]);
        break;
        
    case '/reports/cash-flow':
        $period = $_GET['period'] ?? 'monthly';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        echo json_encode([
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'operating_activities' => [
                'Net Income' => 38109.75,
                'Depreciation' => 2500.00,
                'Accounts Receivable Change' => -3500.00,
                'Accounts Payable Change' => 1200.00,
                'Net Operating Cash Flow' => 38309.75
            ],
            'investing_activities' => [
                'Equipment Purchase' => -8500.00,
                'Sale of Assets' => 1200.00,
                'Net Investing Cash Flow' => -7300.00
            ],
            'financing_activities' => [
                'Loan Proceeds' => 25000.00,
                'Loan Repayments' => -8500.00,
                'Owner Drawings' => -5000.00,
                'Net Financing Cash Flow' => 11500.00
            ],
            'net_cash_flow' => 42509.75,
            'beginning_cash_balance' => 20000.00,
            'ending_cash_balance' => 62509.75
        ]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode([
            'error' => 'Endpoint not found',
            'path' => $path,
            'available_endpoints' => [
                'GET /api/' => 'API Information',
                'GET /api/dashboard' => 'Dashboard data',
                'GET /api/dashboard/kpi' => 'KPI data',
                'GET /api/accounts' => 'List accounts',
                'POST /api/accounts' => 'Create account',
                'GET /api/transactions' => 'List transactions',
                'POST /api/transactions' => 'Create transaction',
                'GET /api/reports/profit-loss' => 'Profit & Loss report',
                'GET /api/reports/balance-sheet' => 'Balance Sheet report',
                'GET /api/reports/cash-flow' => 'Cash Flow report'
            ]
        ]);
        break;
}

?>