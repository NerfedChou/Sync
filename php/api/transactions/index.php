<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get parameters
    $company_id = $_GET['company_id'] ?? null;
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 25);
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    
    if (!$company_id) {
        Response::error("company_id parameter is required", 400);
    }
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    // Build WHERE conditions
    $conditions = ["t.company_id = ?"];
    $params = [$company_id];
    
    if (!empty($search)) {
        $conditions[] = "(t.description LIKE ? OR a.account_name LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($status)) {
        $conditions[] = "t.status = ?";
        $params[] = $status;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $conditions);
    
    // Get total count
    $countSql = "
        SELECT COUNT(*) as total
        FROM transactions t
        LEFT JOIN transaction_lines tl ON t.transaction_id = tl.transaction_id
        LEFT JOIN accounts a ON tl.account_id = a.account_id
        {$whereClause}
    ";
    
    $countResult = $db->fetchOne($countSql, $params);
    $total = (int)$countResult['total'];
    $totalPages = ceil($total / $limit);
    
    // Get transactions - show each transaction once with primary account details
    $sql = "
        SELECT 
            t.transaction_id as id,
            t.transaction_number as name,
            t.transaction_date as date,
            t.description,
            t.total_amount as amount,
            t.status,
            t.created_at,
            -- Get the primary account (the one user specified in transaction)
            (
                SELECT a.account_name 
                FROM transaction_lines tl 
                JOIN accounts a ON tl.account_id = a.account_id 
                WHERE tl.transaction_id = t.transaction_id 
                LIMIT 1
            ) as account,
            -- Get debit/credit amounts for display
            (
                SELECT tl.debit_amount 
                FROM transaction_lines tl 
                WHERE tl.transaction_id = t.transaction_id AND tl.debit_amount > 0 
                LIMIT 1
            ) as debit_amount,
            (
                SELECT tl.credit_amount 
                FROM transaction_lines tl 
                WHERE tl.transaction_id = t.transaction_id AND tl.credit_amount > 0 
                LIMIT 1
            ) as credit_amount
        FROM transactions t
        {$whereClause}
        ORDER BY t.transaction_date DESC, t.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $transactions = $db->fetchAll($sql, $params);
    
    // Format transactions for frontend with INTUITIVE EXPENSE DISPLAY
    $formattedTransactions = [];
    foreach ($transactions as $transaction) {
        $debitAmount = (float)$transaction['debit_amount'];
        $creditAmount = (float)$transaction['credit_amount'];
        
        // Get account type for intuitive display
        $accountType = '';
        if (!empty($transaction['account'])) {
            $accountInfo = $db->fetchOne(
                "SELECT account_type FROM accounts WHERE account_name = ?",
                [$transaction['account']]
            );
            $accountType = strtolower($accountInfo['account_type'] ?? '');
        }
        
        // Determine amount and type with intuitive logic
        if ($debitAmount > 0) {
            $amount = $debitAmount;
            $transactionType = 'Debit';
            
            // INTUITIVE: For expense accounts, show debits as positive payments
            // This means "I paid $50 toward my expense"
            if ($accountType === 'expense') {
                $amount = $debitAmount; // Show as positive payment amount
                $transactionType = 'Payment'; // More intuitive than "Debit"
            }
        } else {
            $amount = $creditAmount; // Show credits as positive for consistency
            $transactionType = 'Credit';
            
            // INTUITIVE: For expense accounts, show credits as "adding to expense"
            if ($accountType === 'expense') {
                $transactionType = 'Added'; // More intuitive than "Credit"
            }
        }
        
        $formattedTransactions[] = [
            'id' => (int)$transaction['id'],
            'name' => $transaction['name'],
            'Date' => $transaction['date'],
            'Description' => $transaction['description'] ?? '',
            'Account' => $transaction['account'] ?? '',
            'Amount' => (float)$amount,
            'Type' => $transactionType,
            'Category' => 'General', // Default category since not in DB
            'Status' => $transaction['status'] ?? 'pending',
            'created_at' => $transaction['created_at']
        ];
    }
    
    // Return paginated response - match frontend expectations
    Response::success([
        'data' => $formattedTransactions,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages
        ]
    ], "Transactions retrieved successfully");
    
} catch (Exception $e) {
    error_log("Transactions endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve transactions");
}
?>