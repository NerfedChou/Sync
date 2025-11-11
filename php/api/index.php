<?php
// Include configuration files
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../config/database.php';

// Set CORS headers
CORS::setHeaders();

// Get the request path and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string from URI
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove /api prefix if present
$path = str_replace('/api', '', $path);

// Remove leading and trailing slashes
$path = trim($path, '/');

// If path is empty, show API info
if (empty($path)) {
    Response::success([
        'api' => 'Accounting System API',
        'version' => '1.0.0',
        'endpoints' => [
            'GET /companies' => 'List all companies',
            'GET /companies/{id}' => 'Get company by ID',
            'GET /dashboard/kpi' => 'Get dashboard KPI data',
            'GET /accounts' => 'List all accounts',
            'GET /transactions' => 'List all transactions',
            'GET /transactions/recent' => 'Get recent transactions'
        ]
    ], 'API is running');
}

// Parse the path into segments
$segments = explode('/', $path);

// Route the request
try {
    $resource = $segments[0] ?? '';
    $action = $segments[1] ?? 'index';
    $id = $segments[2] ?? null;

    switch ($resource) {
        case 'companies':
            routeToController('companies', $action, $id);
            break;
            
        case 'accounts':
            routeToController('accounts', $action, $id);
            break;
            
        case 'transactions':
            routeToController('transactions', $action, $id);
            break;
            
        case 'dashboard':
            routeToController('dashboard', $action, $id);
            break;
            
        case 'profit-loss':
            routeToController('dashboard', 'profit-loss', $id);
            break;
            
        case 'reports':
            routeToController('reports', $action, $id);
            break;
            
        case 'analytics':
            routeToController('analytics', $action, $id);
            break;
            
        case 'auth':
            routeToController('auth', $action, $id);
            break;
            
        default:
            Response::notFound("Endpoint not found: /" . $path);
    }
    
} catch (Exception $e) {
    error_log("Router error: " . $e->getMessage());
    Response::serverError("Internal server error");
}

/**
 * Route to appropriate controller file
 */
function routeToController($resource, $action, $id) {
    $controllerPath = __DIR__ . "/{$resource}/";
    
    // Check if resource directory exists
    if (!is_dir($controllerPath)) {
        Response::notFound("Resource not found: {$resource}");
    }
    
    // Determine the file to include
    $file = null;
    
    switch ($action) {
        case 'index':
            $file = $controllerPath . 'index.php';
            break;
        case 'create':
            $file = $controllerPath . 'create.php';
            break;
        case 'update':
            $file = $controllerPath . 'update.php';
            break;
        case 'delete':
            $file = $controllerPath . 'delete.php';
            break;
        case 'recent':
            $file = $controllerPath . 'recent.php';
            break;
        case 'simple':
            $file = $controllerPath . 'simple.php';
            break;
        case 'kpi':
            $file = $controllerPath . 'kpi.php';
            break;
        case 'revenue-trends':
            $file = $controllerPath . 'revenue-trends.php';
            break;
        case 'expense-breakdown':
            $file = $controllerPath . 'expense-breakdown.php';
            break;
        case 'profit-loss':
            $file = $controllerPath . 'profit-loss.php';
            break;
        case 'balance-sheet':
            $file = $controllerPath . 'balance-sheet.php';
            break;
        case 'cash-flow':
            $file = $controllerPath . 'cash-flow.php';
            break;
        case 'trial-balance':
            $file = $controllerPath . 'trial-balance.php';
            break;
        case 'categories':
            $file = $controllerPath . 'categories.php';
            break;
        case 'login':
            $file = $controllerPath . 'login.php';
            break;
        default:
            Response::notFound("Action not found: {$action}");
    }
    
    // Check if file exists
    if (!file_exists($file)) {
        Response::notFound("Controller file not found: {$action}");
    }
    
    // Include the controller file
    require_once $file;
}
?>