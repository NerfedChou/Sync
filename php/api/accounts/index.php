<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get company_id from query parameter (required)
    $company_id = $_GET['company_id'] ?? null;
    
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    // Simple query for accounts
    $sql = "
        SELECT 
            account_id as id,
            account_name as name,
            account_code as code,
            account_type as type,
            current_balance as balance,
            is_active as status,
            description
        FROM accounts 
        WHERE company_id = ? 
        ORDER BY account_code, account_name
    ";
    
    $accounts = $db->fetchAll($sql, [$company_id]);
    
    // Simple format for frontend with INTUITIVE EXPENSE DISPLAY
    $formattedAccounts = array_map(function($account) {
        $balance = (float)$account['balance'];
        $accountType = strtolower($account['type']);
        
        // For expense accounts, keep negative balance for intuitive display
        // This shows "how much needs to be paid" rather than confusing accounting
        if ($accountType === 'expense') {
            // Keep the negative balance as-is for intuitive display
            // -$100 means "$100 still needs to be paid"
            $displayBalance = $balance;
        } else {
            $displayBalance = $balance;
        }
        
        return [
            'id' => (int)$account['id'],
            'Account Name' => $account['name'],
            'code' => $account['code'],
            'Type' => $accountType,
            'Balance' => $displayBalance,
            'Status' => $account['status'] ? 'active' : 'inactive',
            'description' => $account['description'] ?? ''
        ];
    }, $accounts);
    
    Response::success($formattedAccounts, "Accounts retrieved successfully");
    
} catch (Exception $e) {
    error_log("Accounts endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve accounts");
}
?>