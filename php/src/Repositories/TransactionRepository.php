<?php

namespace AccountingSystem\Repositories;

use Illuminate\Database\Capsule\Manager as Capsule;

class TransactionRepository
{
    public function findByCompany(int $companyId, array $filters = []): array
    {
        $query = Capsule::table('transactions')
            ->where('company_id', $companyId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('transaction_number', 'desc');

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['period_id'])) {
            $query->where('period_id', $filters['period_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('transaction_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('transaction_date', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('reference', 'like', $search);
            });
        }

        if (!empty($filters['account_id'])) {
            $query->whereHas('lines', function ($q) use ($filters) {
                $q->where('account_id', $filters['account_id']);
            });
        }

        return $query->get()->toArray();
    }

    public function findByIdAndCompany(int $transactionId, int $companyId): ?array
    {
        $transaction = Capsule::table('transactions')
            ->where('transaction_id', $transactionId)
            ->where('company_id', $companyId)
            ->first();

        if ($transaction) {
            $transaction = (array) $transaction;
            $transaction['lines'] = $this->getLines($transactionId);
        }

        return $transaction;
    }

    public function findById(int $transactionId): ?array
    {
        $transaction = Capsule::table('transactions')
            ->where('transaction_id', $transactionId)
            ->first();

        if ($transaction) {
            $transaction = (array) $transaction;
            $transaction['lines'] = $this->getLines($transactionId);
        }

        return $transaction;
    }

    public function create(array $data): array
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = Capsule::table('transactions')->insertGetId($data);
        
        return $this->findById($id);
    }

    public function update(int $transactionId, array $data): ?array
    {
        $data['updated_at'] = now();

        $affected = Capsule::table('transactions')
            ->where('transaction_id', $transactionId)
            ->update($data);

        if ($affected) {
            return $this->findById($transactionId);
        }

        return null;
    }

    public function delete(int $transactionId): bool
    {
        // Delete transaction lines first
        $this->deleteLines($transactionId);

        $affected = Capsule::table('transactions')
            ->where('transaction_id', $transactionId)
            ->delete();

        return $affected > 0;
    }

    public function createLine(array $data): array
    {
        $data['created_at'] = now();

        $id = Capsule::table('transaction_lines')->insertGetId($data);
        
        return Capsule::table('transaction_lines')
            ->where('line_id', $id)
            ->first()
            ->toArray();
    }

    public function getLines(int $transactionId): array
    {
        return Capsule::table('transaction_lines')
            ->join('accounts', 'transaction_lines.account_id', '=', 'accounts.account_id')
            ->where('transaction_lines.transaction_id', $transactionId)
            ->select(
                'transaction_lines.*',
                'accounts.account_code',
                'accounts.account_name',
                'accounts.account_type'
            )
            ->orderBy('transaction_lines.line_id')
            ->get()
            ->toArray();
    }

    public function deleteLines(int $transactionId): void
    {
        Capsule::table('transaction_lines')
            ->where('transaction_id', $transactionId)
            ->delete();
    }

    public function getLastTransactionByDate(int $companyId, string $date): ?array
    {
        $transaction = Capsule::table('transactions')
            ->where('company_id', $companyId)
            ->where('transaction_date', $date)
            ->orderBy('transaction_number', 'desc')
            ->first();

        return $transaction ? (array) $transaction : null;
    }

    public function accountHasTransactions(int $accountId): bool
    {
        return Capsule::table('transaction_lines')
            ->where('account_id', $accountId)
            ->exists();
    }

    public function getAccountBalance(int $accountId, string $asOfDate = null): float
    {
        $query = Capsule::table('transaction_lines')
            ->join('transactions', 'transaction_lines.transaction_id', '=', 'transactions.transaction_id')
            ->where('transaction_lines.account_id', $accountId)
            ->where('transactions.status', 'posted');

        if ($asOfDate) {
            $query->where('transactions.transaction_date', '<=', $asOfDate);
        }

        $result = $query->selectRaw('SUM(debit_amount) - SUM(credit_amount) as balance')
            ->first();

        return $result ? (float) $result->balance : 0.00;
    }

    public function getTrialBalance(int $companyId, string $asOfDate): array
    {
        $accounts = Capsule::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get()
            ->toArray();

        $trialBalance = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->account_id, $asOfDate);
            
            // Get account to determine normal balance
            $accountData = (array) $account;
            $isContra = $account->is_contra;
            $accountType = $account->account_type;

            // Determine debit and credit amounts
            if ($this->isNormalDebitBalance($accountType, $isContra)) {
                $debitAmount = max(0, $balance);
                $creditAmount = max(0, -$balance);
            } else {
                $debitAmount = max(0, -$balance);
                $creditAmount = max(0, $balance);
            }

            $trialBalance[] = [
                'account_id' => $account->account_id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'debit_amount' => $debitAmount,
                'credit_amount' => $creditAmount,
                'balance' => $balance
            ];

            $totalDebits += $debitAmount;
            $totalCredits += $creditAmount;
        }

        return [
            'accounts' => $trialBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
            'as_of_date' => $asOfDate
        ];
    }

    public function getGeneralLedger(int $companyId, ?int $accountId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = Capsule::table('transactions')
            ->join('transaction_lines', 'transactions.transaction_id', '=', 'transaction_lines.transaction_id')
            ->join('accounts', 'transaction_lines.account_id', '=', 'accounts.account_id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.status', 'posted')
            ->orderBy('transactions.transaction_date')
            ->orderBy('transactions.transaction_number')
            ->orderBy('transaction_lines.line_id');

        if ($accountId) {
            $query->where('transaction_lines.account_id', $accountId);
        }

        if ($fromDate) {
            $query->where('transactions.transaction_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('transactions.transaction_date', '<=', $toDate);
        }

        $transactions = $query->get()->toArray();

        // Group by transaction
        $ledger = [];
        foreach ($transactions as $transaction) {
            $transId = $transaction->transaction_id;
            
            if (!isset($ledger[$transId])) {
                $ledger[$transId] = [
                    'transaction_id' => $transaction->transaction_id,
                    'transaction_number' => $transaction->transaction_number,
                    'transaction_date' => $transaction->transaction_date,
                    'description' => $transaction->description,
                    'reference' => $transaction->reference,
                    'lines' => []
                ];
            }

            $ledger[$transId]['lines'][] = [
                'account_id' => $transaction->account_id,
                'account_code' => $transaction->account_code,
                'account_name' => $transaction->account_name,
                'account_type' => $transaction->account_type,
                'description' => $transaction->description,
                'debit_amount' => $transaction->debit_amount,
                'credit_amount' => $transaction->credit_amount
            ];
        }

        return array_values($ledger);
    }

    private function isNormalDebitBalance(string $accountType, bool $isContra): bool
    {
        $normalDebitAccounts = ['ASSET', 'EXPENSE'];
        
        if ($isContra) {
            return !in_array($accountType, $normalDebitAccounts);
        }
        
        return in_array($accountType, $normalDebitAccounts);
    }

    public function getTransactionsByAccount(int $accountId, string $fromDate, string $toDate): array
    {
        return Capsule::table('transactions')
            ->join('transaction_lines', 'transactions.transaction_id', '=', 'transaction_lines.transaction_id')
            ->where('transaction_lines.account_id', $accountId)
            ->where('transactions.transaction_date', '>=', $fromDate)
            ->where('transactions.transaction_date', '<=', $toDate)
            ->where('transactions.status', 'posted')
            ->orderBy('transactions.transaction_date')
            ->orderBy('transactions.transaction_number')
            ->select(
                'transactions.*',
                'transaction_lines.debit_amount',
                'transaction_lines.credit_amount',
                'transaction_lines.description as line_description'
            )
            ->get()
            ->toArray();
    }
}