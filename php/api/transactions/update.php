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
    $existingTransaction = $db->fetchOne("SELECT * FROM transactions WHERE transaction_id = ?", [$transactionId]);
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
            $updateFields[] = "total_amount = ?";
            $params[] = (float)$input['amount'];
        }
        
        if (isset($input['status'])) {
            $updateFields[] = "status = ?";
            $params[] = Validation::sanitize($input['status']);
        }
        
        if (empty($updateFields)) {
            Response::error("No fields to update", 400);
        }
        
        // Add transaction ID to params
        $params[] = $transactionId;
        
        // Update transaction
        $sql = "UPDATE transactions SET " . implode(", ", $updateFields) . " WHERE transaction_id = ?";
        $db->query($sql, $params);
        
        // Update transaction line if amount or type changed
        if (isset($input['amount']) || isset($input['type']) || isset($input['account_id'])) {
            // Get the account_id and type from input or existing transaction
            $accountId = isset($input['account_id']) ? (int)$input['account_id'] : $existingTransaction['account_id'];
            $type = isset($input['type']) ? $input['type'] : 'debit'; // Default to debit
            $amount = isset($input['amount']) ? (float)$input['amount'] : (float)$existingTransaction['total_amount'];
            $description = isset($input['description']) ? $input['description'] : $existingTransaction['description'];
            
            // Verify account exists if provided
            if (isset($input['account_id'])) {
                $accountCheck = $db->fetchOne(
                    "SELECT account_id, account_name, account_type FROM accounts WHERE account_id = ? AND company_id = ? AND is_active = 1",
                    [$accountId, $input['company_id'] ?? 1]
                );
                
                if (!$accountCheck) {
                    Response::error("Account not found", 404);
                }
            }
            
            // Update transaction line
            $lineUpdateSql = "
                UPDATE transaction_lines 
                SET description = ?, debit_amount = ?, credit_amount = ?
                WHERE transaction_id = ?
            ";
            
            $debitAmount = $type === 'debit' ? $amount : 0;
            $creditAmount = $type === 'credit' ? $amount : 0;
            
            $db->query($lineUpdateSql, [
                $description,
                $debitAmount,
                $creditAmount,
                $transactionId
            ]);
            
            // Update account balance if amount or account changed
            if (isset($input['amount']) || isset($input['account_id']) || isset($input['type'])) {
                // Get original transaction line
                $originalLine = $db->fetchOne(
                    "SELECT debit_amount, credit_amount FROM transaction_lines WHERE transaction_id = ?",
                    [$transactionId]
                );
                
                if ($originalLine) {
                    // Calculate original balance change
                    $originalDebit = (float)$originalLine['debit_amount'];
                    $originalCredit = (float)$originalLine['credit_amount'];
                    $originalAmount = $originalDebit > 0 ? $originalDebit : $originalCredit;
                    $originalType = $originalDebit > 0 ? 'debit' : 'credit';
                    
                    // Get account info for balance calculation
                    $accountInfo = $db->fetchOne(
                        "SELECT account_type FROM accounts WHERE account_id = ?",
                        [$accountId]
                    );
                    
                    if ($accountInfo) {
                        $accountType = strtoupper($accountInfo['account_type']);
                        
                        // Reverse original balance change
                        $originalBalanceChange = 0;
                        if ($originalType === 'debit') {
                            $originalBalanceChange = ($accountType === 'ASSET') ? $originalAmount : -$originalAmount;
                        } else {
                            $originalBalanceChange = ($accountType === 'ASSET') ? -$originalAmount : $originalAmount;
                        }
                        
                        // Apply new balance change
                        $newBalanceChange = 0;
                        if ($type === 'debit') {
                            $newBalanceChange = ($accountType === 'ASSET') ? $amount : -$amount;
                        } else {
                            $newBalanceChange = ($accountType === 'ASSET') ? -$amount : $amount;
                        }
                        
                        $netChange = $newBalanceChange - $originalBalanceChange;
                        
                        // Update account balance
                        $db->query(
                            "UPDATE accounts SET current_balance = current_balance + ? WHERE account_id = ?",
                            [$netChange, $accountId]
                        );
                    }
                }
            }
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
            t.transaction_id as id,
            t.transaction_date as date,
            t.description,
            t.total_amount as amount,
            t.status,
            tl.debit_amount,
            tl.credit_amount,
            a.account_name as account,
            a.account_id
        FROM transactions t
        LEFT JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        LEFT JOIN accounts a ON tl.account_id = a.account_id
        WHERE t.transaction_id = ?
    ", [$transactionId]);
    
    // Determine transaction type from line amounts
    $transactionType = $resultTransaction['debit_amount'] > 0 ? 'debit' : 'credit';
    
    // Format response
    $transactionData = [
        'id' => (int)$resultTransaction['id'],
        'date' => $resultTransaction['date'],
        'description' => $resultTransaction['description'],
        'type' => $transactionType,
        'amount' => (float)$resultTransaction['amount'],
        'status' => $resultTransaction['status'],
        'account' => $resultTransaction['account'] ?? 'Unknown Account',
        'account_id' => (int)$resultTransaction['account_id']
    ];
    
    Response::success($transactionData, "Transaction updated successfully");
    
} catch (Exception $e) {
    error_log("Transaction update error: " . $e->getMessage());
    Response::serverError("Failed to update transaction");
}
?>