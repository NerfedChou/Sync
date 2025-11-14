<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

/**
 * Create Asset API
 * Creates a new asset account with $0 starting balance
 */

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error("Method not allowed", 405);
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error("Invalid JSON input", 400);
    }
    
    // Validate required fields
    $requiredFields = ['asset_name', 'asset_type', 'date', 'description', 'company_id'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            Response::error("Field '{$field}' is required", 400);
        }
    }
    
    // Sanitize input
    $assetName = trim($input['asset_name']);
    $assetType = strtolower(trim($input['asset_type']));
    $date = trim($input['date']);
    $description = trim($input['description']);
    $company_id = (int)($input['company_id']);
    
    // Validate asset type
    $validTypes = ['cash', 'accounts_receivable', 'inventory', 'equipment', 'vehicles', 'property', 'investments', 'other'];
    if (!in_array($assetType, $validTypes)) {
        Response::error("Invalid asset type. Valid types: " . implode(', ', $validTypes), 400);
    }
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Generate account code
    $typePrefix = 'A'; // A for Asset
    $lastCode = $db->fetchOne(
        "SELECT account_code FROM accounts WHERE company_id = ? AND account_code LIKE ? ORDER BY account_code DESC LIMIT 1",
        [$company_id, 'A%']
    );
    
    if ($lastCode) {
        $lastNumber = (int)substr($lastCode['account_code'], 1);
        $account_code = 'A' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $account_code = 'A001';
    }
    
    // Insert asset account with $0 balance
    $sql = "
        INSERT INTO accounts (company_id, account_name, account_code, account_type, opening_balance, current_balance, is_active, description, created_at)
        VALUES (?, ?, ?, 'ASSET', 0, 0, 1, ?, NOW())
    ";
    
    $db->query($sql, [$company_id, $assetName, $account_code, $description]);
    $account_id = $db->lastInsertId();
    
    // Get created account
    $createdAccount = $db->fetchOne("SELECT * FROM accounts WHERE account_id = ?", [$account_id]);
    
    // Format response
    $accountData = [
        'id' => (int)$createdAccount['account_id'],
        'Account Name' => $createdAccount['account_name'],
        'code' => $createdAccount['account_code'],
        'Type' => 'asset',
        'Balance' => 0.00, // Assets always start at zero
        'Status' => 'active',
        'description' => $createdAccount['description'] ?? ''
    ];
    
    Response::success($accountData, "Asset account created successfully with $0 balance");
    
} catch (Exception $e) {
    error_log("Create Asset API error: " . $e->getMessage());
    Response::serverError("Failed to create asset account");
}
?>