
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../middleware/validation.php';

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
    $requiredFields = ['date', 'description', 'account_id', 'amount', 'type', 'company_id'];
    $errors = Validation::required($input, $requiredFields);
    
    if (!empty($errors)) {
        Response::validationError($errors);
    }
    
    // Validate transaction type
    $validTypes = ['credit', 'debit'];
    if (!Validation::inArray($input['type'], $validTypes)) {
        Response::error("Invalid transaction type", 400);
    }
    
    // Validate date
    if (!Validation::date($input['date'])) {
        Response::error("Invalid date format", 400);
    }
    
    // Validate amount
    if (!Validation::numeric($input['amount']) || $input['amount'] <= 0) {
        Response::error("Amount must be a positive number", 400);
    }
    
    // Sanitize input
    $date = Validation::sanitize($input['date']);
    $description = Validation::sanitize($input['description']);
    $account_id = (int)$input['account_id'];
    $amount = (float)$input['amount'];
    $type = Validation::sanitize($input['type']);
    $category = Validation::sanitize($input['category'] ?? 'Uncategorized');
    $notes = Validation::sanitize($input['notes'] ?? '');
    $company_id = (int)$input['company_id'];
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Verify account exists and belongs to company
    $accountCheck = $db->fetchOne(
        "SELECT id, account_name FROM accounts WHERE id = ? AND company_id = ? AND is_active = 1",
        [$account_id, $company_id]
    );
    
    if (!$accountCheck) {
        Response::error("Account not found", 404);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert transaction
        $sql = "
            INSERT INTO transactions (company_id, account_id, transaction_date, description, type, amount, category, notes, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())
        ";
        
        $db->query($sql, [
            $company_id,
            $account_id,
            $date,
            $description,
            $type,
            $amount,
            $category,
            $notes
        ]);
        
        $transaction_id = $db->lastInsertId();
        
        // Create transaction line (simplified single-entry for now)
        $lineSql = "
            INSERT INTO transaction_lines (transaction_id, account_id, description, debit_amount, credit_amount)
            VALUES (?, ?, ?, ?, ?)
        ";
        
        $debitAmount = $type === 'debit' ? $amount : 0;
        $creditAmount = $type === 'credit' ? $amount : 0;
        
        $db->query($lineSql, [
            $transaction_id,
            $account_id,
            $description,
            $debitAmount,
            $creditAmount
        ]);
        
        // Commit transaction
        $pdo->commit();
        
    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollback();
        throw $e;
    }
    
    // Get created transaction
    $createdTransaction = $db->fetchOne("SELECT * FROM transactions WHERE id = ?", [$transaction_id]);
    
    // Format response
    $transactionData = [
        'id' => (int)$createdTransaction['id'],
        'date' => $createdTransaction['transaction_date'],
        'description' => $createdTransaction['description'],
        'type' => $createdTransaction['type'],
        'amount' => (float)$createdTransaction['amount'],
        'status' => $createdTransaction['status'],
        'category' => $createdTransaction['category'],
        'account' => $accountCheck['account_name'],
        'account_id' => (int)$createdTransaction['account_id'],
        'notes' => $createdTransaction['notes']
    ];
    
    Response::success($transactionData, "Transaction created successfully");
    
} catch (Exception $e) {
    error_log("Transaction creation error: " . $e->getMessage());
    Response::serverError("Failed to create transaction");
}
?>