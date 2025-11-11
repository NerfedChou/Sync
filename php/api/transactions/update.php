
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../middleware/validation.php';

try {
    // Only allow PUT requests
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        Response::error("Method not allowed", 405);
    }
    
    // Get transaction ID from URL
    $urlParts = explode('/', $_SERVER['REQUEST_URI']);
    $transactionId = end($urlParts);
    
    if (!is_numeric($transactionId)) {
        Response::error("Invalid transaction ID", 400);
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error("Invalid JSON input", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if transaction exists
    $existingTransaction = $db->fetchOne("SELECT * FROM transactions WHERE id = ?", [$transactionId]);
    if (!$existingTransaction) {
        Response::notFound("Transaction not found");
    }
    
    // Validate fields if provided
    $errors = [];
    
    if (isset($input['date'])) {
        if (!Validation::date($input['date'])) {
            $errors[] = "Invalid date format";
        }
    }
    
    if (isset($input['description'])) {
        if (empty(trim($input['description']))) {
            $errors[] = "Description cannot be empty";
        }
    }
    
    if (isset($input['amount'])) {
        if (!Validation::numeric($input['amount']) || $input['amount'] <= 0) {
            $errors[] = "Amount must be a positive number";
        }
    }
    
    if (isset($input['type'])) {
        $validTypes = ['credit', 'debit'];
        if (!Validation::inArray($input['type'], $validTypes)) {
            $errors[] = "Invalid transaction type";
        }
    }
    
    if (isset($input['account_id'])) {
        if (!Validation::numeric($input['account_id'])) {
            $errors[] = "Invalid account ID";
        }
    }
    
    if (!empty($errors)) {
        Response::validationError($errors);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Build update query
        $updateFields = [];
        $params = [];
        
        if (isset($input['date'])) {
            $updateFields[] = "transaction_date = ?";
            $params[] = Validation::sanitize($input['date']);
        }
        
        if (isset($input['description'])) {
            $updateFields[] = "description = ?";
            $params[] = Validation::sanitize($input['description']);
        }
        
        if (isset($input['amount'])) {
            $updateFields[] = "amount = ?";
            $params[] = (float)$input['amount'];
        }
        
        if (isset($input['type'])) {
            $updateFields[] = "type = ?";
            $params[] = Validation::sanitize($input['type']);
        }
        
        if (isset($input['category'])) {
            $updateFields[] = "category = ?";
            $params[] = Validation::sanitize($input['category']);
        }
        
        if (isset($input['notes'])) {
            $updateFields[] = "notes = ?";
            $params[] = Validation::sanitize($input['notes']);
        }
        
        if (isset($input['status'])) {
            $updateFields[] = "status = ?";
            $params[] = Validation::sanitize($input['status']);
        }
        
        if (isset($input['account_id'])) {
            // Verify new account exists
            $accountCheck = $db->fetchOne(
                "SELECT id, account_name FROM accounts WHERE id = ? AND is_active = 1",
                [(int)$input['account_id']]
            );
            
            if (!$accountCheck) {
                Response::error("Account not found", 404);
            }
            
            $updateFields[] = "account_id = ?";
            $params[] = (int)$input['account_id'];
        }
        
        if (empty($updateFields)) {
            Response::error("No fields to update", 400);
        }
        
        // Add transaction ID to params
        $params[] = $transactionId;
        
        // Update transaction
        $sql = "UPDATE transactions SET " . implode(", ", $updateFields) . ", updated_at = NOW() WHERE id = ?";
        $db->query($sql, $params);
        
        // Update transaction line if account or amount changed
        if (isset($input['account_id']) || isset($input['amount']) || isset($input['type'])) {
            // Get updated transaction for line update
            $updatedTransaction = $db->fetchOne("SELECT * FROM transactions WHERE id = ?", [$transactionId]);
            
            $lineUpdateSql = "
                UPDATE transaction_lines 
                SET description = ?, debit_amount = ?, credit_amount = ?
                WHERE transaction_id = ?
            ";
            
            $debitAmount = $updatedTransaction['type'] === 'debit' ? $updatedTransaction['amount'] : 0;
            $creditAmount = $updatedTransaction['type'] === 'credit' ? $updatedTransaction['amount'] : 0;
            
            $db->query($lineUpdateSql, [
                $updatedTransaction['description'],
                $debitAmount,
                $creditAmount,
                $transactionId
            ]);
        }
        
        // Commit transaction
        $pdo->commit();
        
    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollback();
        throw $e;
    }
    
    // Get updated transaction with account name
    $resultTransaction = $db->fetchOne("
        SELECT 
            t.id,
            t.transaction_date as date,
            t.description,
            t.type,
            t.amount,
            t.status,
            t.category,
            t.notes,
            a.account_name as account,
            a.id as account_id
        FROM transactions t
        LEFT JOIN accounts a ON t.account_id = a.id
        WHERE t.id = ?
    ", [$transactionId]);
    
    // Format response
    $transactionData = [
        'id' => (int)$resultTransaction['id'],
        'date' => $resultTransaction['date'],
        'description' => $resultTransaction['description'],
        'type' => $resultTransaction['type'],
        'amount' => (float)$resultTransaction['amount'],
        'status' => $resultTransaction['status'],
        'category' => $resultTransaction['category'] ?? 'Uncategorized',
        'account' => $resultTransaction['account'] ?? 'Unknown Account',
        'account_id' => (int)$resultTransaction['account_id'],
        'notes' => $resultTransaction['notes'] ?? ''
    ];
    
    Response::success($transactionData, "Transaction updated successfully");
    
} catch (Exception $e) {
    error_log("Transaction update error: " . $e->getMessage());
    Response::serverError("Failed to update transaction");
}
?>