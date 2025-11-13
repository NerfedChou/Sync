<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get query parameters
    $company_id = $_GET['company_id'] ?? 1;
    $account_type = $_GET['type'] ?? null;
    
    // Build query for simplified accounts
    $sql = "SELECT account_id, account_name, account_type, current_balance, is_active 
            FROM accounts 
            WHERE company_id = ? AND is_active = 1";
    $params = [$company_id];
    
    if ($account_type) {
        $sql .= " AND account_type = ?";
        $params[] = $account_type;
    }
    
    $sql .= " ORDER BY account_type, account_name";
    
    // Execute query
    $accounts = $db->fetchAll($sql, $params);
    
    // Format accounts for frontend
    $formattedAccounts = array_map(function($account) {
        return [
            'id' => (int)$account['account_id'],
            'Account Name' => $account['account_name'],
            'Type' => strtolower($account['account_type']),
            'Balance' => (float)$account['current_balance'],
            'Status' => $account['is_active'] ? 'active' : 'inactive'
        ];
    }, $accounts);
    
    // Return success response
    Response::success($formattedAccounts, "Accounts retrieved successfully");
    
} catch (Exception $e) {
    error_log("Accounts endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve accounts");
}
?>