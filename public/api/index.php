<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$endpoint = end($path_parts);

// Remove file extension if present
$endpoint = preg_replace('/\.[^.]+$/', '', $endpoint);

// Load mock data
$mock_data = json_decode(file_get_contents(__DIR__ . '/mock-data.json'), true);

// Route requests
switch ($endpoint) {
    case 'companies':
        handleCompanies($method, $mock_data);
        break;
    
    case 'dashboard':
        handleDashboard($mock_data);
        break;
    
    case 'transactions':
        handleTransactions($method, $mock_data);
        break;
    
    case 'accounts':
        handleAccounts($method, $mock_data);
        break;
    
    case 'reports':
        handleReports($mock_data);
        break;
    
    case 'auth':
        handleAuth($method);
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

function handleCompanies($method, $data) {
    switch ($method) {
        case 'GET':
            echo json_encode([
                'success' => true,
                'data' => $data['companies']
            ]);
            break;
        
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $new_company = [
                'id' => count($data['companies']) + 1,
                'name' => $input['name'] ?? 'New Company',
                'description' => $input['description'] ?? '',
                'industry' => $input['industry'] ?? 'Other',
                'created_at' => date('Y-m-d H:i:s'),
                'is_active' => true
            ];
            echo json_encode([
                'success' => true,
                'data' => $new_company
            ]);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleDashboard($data) {
    $company_id = $_GET['company_id'] ?? 1;
    
    // Filter data by company
    $company_transactions = array_filter($data['transactions'], function($t) use ($company_id) {
        return $t['company_id'] == $company_id;
    });
    
    $company_accounts = array_filter($data['accounts'], function($a) use ($company_id) {
        return $a['company_id'] == $company_id;
    });
    
    // Calculate metrics
    $total_revenue = array_sum(array_map(function($t) {
        return $t['type'] === 'revenue' ? $t['amount'] : 0;
    }, $company_transactions));
    
    $total_expenses = array_sum(array_map(function($t) {
        return $t['type'] === 'expense' ? $t['amount'] : 0;
    }, $company_transactions));
    
    $net_income = $total_revenue - $total_expenses;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'metrics' => [
                'total_revenue' => $total_revenue,
                'total_expenses' => $total_expenses,
                'net_income' => $net_income,
                'total_accounts' => count($company_accounts),
                'total_transactions' => count($company_transactions)
            ],
            'recent_transactions' => array_slice(array_values($company_transactions), 0, 5),
            'account_summary' => array_values($company_accounts)
        ]
    ]);
}

function handleTransactions($method, $data) {
    $company_id = $_GET['company_id'] ?? 1;
    
    $company_transactions = array_filter($data['transactions'], function($t) use ($company_id) {
        return $t['company_id'] == $company_id;
    });
    
    switch ($method) {
        case 'GET':
            echo json_encode([
                'success' => true,
                'data' => array_values($company_transactions)
            ]);
            break;
        
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $new_transaction = [
                'id' => count($data['transactions']) + 1,
                'company_id' => $company_id,
                'date' => $input['date'] ?? date('Y-m-d'),
                'description' => $input['description'] ?? 'New Transaction',
                'amount' => $input['amount'] ?? 0,
                'type' => $input['type'] ?? 'expense',
                'account_id' => $input['account_id'] ?? 1,
                'category' => $input['category'] ?? 'Other',
                'created_at' => date('Y-m-d H:i:s')
            ];
            echo json_encode([
                'success' => true,
                'data' => $new_transaction
            ]);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleAccounts($method, $data) {
    $company_id = $_GET['company_id'] ?? 1;
    
    $company_accounts = array_filter($data['accounts'], function($a) use ($company_id) {
        return $a['company_id'] == $company_id;
    });
    
    switch ($method) {
        case 'GET':
            echo json_encode([
                'success' => true,
                'data' => array_values($company_accounts)
            ]);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleReports($data) {
    $company_id = $_GET['company_id'] ?? 1;
    $report_type = $_GET['type'] ?? 'profit_loss';
    
    $company_transactions = array_filter($data['transactions'], function($t) use ($company_id) {
        return $t['company_id'] == $company_id;
    });
    
    $report_data = [];
    
    switch ($report_type) {
        case 'profit_loss':
            $revenue_by_category = [];
            $expense_by_category = [];
            
            foreach ($company_transactions as $t) {
                if ($t['type'] === 'revenue') {
                    $revenue_by_category[$t['category']] = ($revenue_by_category[$t['category']] ?? 0) + $t['amount'];
                } else {
                    $expense_by_category[$t['category']] = ($expense_by_category[$t['category']] ?? 0) + $t['amount'];
                }
            }
            
            $report_data = [
                'revenue' => $revenue_by_category,
                'expenses' => $expense_by_category,
                'total_revenue' => array_sum($revenue_by_category),
                'total_expenses' => array_sum($expense_by_category),
                'net_income' => array_sum($revenue_by_category) - array_sum($expense_by_category)
            ];
            break;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $report_data
    ]);
}

function handleAuth($method) {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Mock authentication - always succeeds for testing
            echo json_encode([
                'success' => true,
                'data' => [
                    'token' => 'mock_jwt_token_' . time(),
                    'user' => [
                        'id' => 1,
                        'email' => $input['email'] ?? 'admin@example.com',
                        'name' => 'Administrator'
                    ]
                ]
            ]);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}
?>