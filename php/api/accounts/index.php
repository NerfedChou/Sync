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
    
    // Query for accounts with calculated balances
    $sql = "
        SELECT 
            a.id,
            a.account_name as name,
            a.account_code as code,
            a.type,
            a.description,
            a.is_active as status,
            COALESCE(
                (SELECT SUM(CASE 
                    WHEN tl.debit_amount > 0 THEN tl.debit_amount 
                    ELSE -tl.credit_amount 
                END)
                FROM transaction_lines tl 
                WHERE tl.account_id = a.id), 0
            ) as balance
        FROM accounts a 
        WHERE a.company_id = ? AND a.is_active = 1
        ORDER BY a.account_code, a.account_name
    ";
    
    $accounts = $db->fetchAll($sql, [$company_id]);
    
    // Format accounts for frontend
    $formattedAccounts = array_map(function($account) {
        return [
            'id' => (int)$account['id'],
            'name' => $account['name'],
            'code' => $account['code'] ?? '',
            'type' => $account['type'],
            'balance' => (float)$account['balance'],
            'status' => $account['status'] ? 'active' : 'inactive',
            'description' => $account['description'] ?? ''
        ];
    }, $accounts);
    
    // Return success response
    Response::success($formattedAccounts, "Accounts retrieved successfully");
    
} catch (Exception $e) {
    error_log("Accounts endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve accounts");
}
?>