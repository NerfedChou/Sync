<?php

namespace AccountingSystem\Repositories;

use Illuminate\Database\Capsule\Manager as Capsule;

class AccountRepository
{
    public function findByCompany(int $companyId, array $filters = []): array
    {
        $query = Capsule::table('accounts')
            ->where('company_id', $companyId)
            ->orderBy('account_code');

        // Apply filters
        if (!empty($filters['account_type'])) {
            $query->where('account_type', $filters['account_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', $search)
                  ->orWhere('account_name', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        if (!empty($filters['parent_id'])) {
            $query->where('parent_account_id', $filters['parent_id']);
        }

        return $query->get()->toArray();
    }

    public function findByIdAndCompany(int $accountId, int $companyId): ?array
    {
        $account = Capsule::table('accounts')
            ->where('account_id', $accountId)
            ->where('company_id', $companyId)
            ->first();

        return $account ? (array) $account : null;
    }

    public function findById(int $accountId): ?array
    {
        $account = Capsule::table('accounts')
            ->where('account_id', $accountId)
            ->first();

        return $account ? (array) $account : null;
    }

    public function create(array $data): array
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = Capsule::table('accounts')->insertGetId($data);
        
        return $this->findById($id);
    }

    public function update(int $accountId, array $data): ?array
    {
        $data['updated_at'] = now();

        $affected = Capsule::table('accounts')
            ->where('account_id', $accountId)
            ->update($data);

        if ($affected) {
            return $this->findById($accountId);
        }

        return null;
    }

    public function delete(int $accountId): bool
    {
        $affected = Capsule::table('accounts')
            ->where('account_id', $accountId)
            ->delete();

        return $affected > 0;
    }

    public function codeExists(string $code, int $companyId, ?int $excludeId = null): bool
    {
        $query = Capsule::table('accounts')
            ->where('account_code', $code)
            ->where('company_id', $companyId);

        if ($excludeId) {
            $query->where('account_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function hasChildren(int $accountId): bool
    {
        return Capsule::table('accounts')
            ->where('parent_account_id', $accountId)
            ->exists();
    }

    public function getBalances(int $companyId, string $asOfDate, ?string $accountType = null): array
    {
        $query = Capsule::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true);

        if ($accountType) {
            $query->where('account_type', $accountType);
        }

        $accounts = $query->get()->toArray();

        foreach ($accounts as &$account) {
            $account['current_balance'] = $this->calculateBalance($account['account_id'], $asOfDate);
        }

        return $accounts;
    }

    public function updateBalance(int $accountId, float $balance): void
    {
        Capsule::table('accounts')
            ->where('account_id', $accountId)
            ->update([
                'current_balance' => $balance,
                'updated_at' => now()
            ]);
    }

    public function getLastAccountByType(int $companyId, string $accountType): ?array
    {
        $account = Capsule::table('accounts')
            ->where('company_id', $companyId)
            ->where('account_type', $accountType)
            ->orderBy('account_code', 'desc')
            ->first();

        return $account ? (array) $account : null;
    }

    public function getAccountsWithTransactions(int $companyId, string $fromDate, string $toDate): array
    {
        return Capsule::table('accounts')
            ->join('transaction_lines', 'accounts.account_id', '=', 'transaction_lines.account_id')
            ->join('transactions', 'transaction_lines.transaction_id', '=', 'transactions.transaction_id')
            ->where('accounts.company_id', $companyId)
            ->where('transactions.transaction_date', '>=', $fromDate)
            ->where('transactions.transaction_date', '<=', $toDate)
            ->where('transactions.status', 'posted')
            ->distinct()
            ->select('accounts.*')
            ->orderBy('accounts.account_code')
            ->get()
            ->toArray();
    }

    private function calculateBalance(int $accountId, string $asOfDate): float
    {
        $result = Capsule::table('transaction_lines')
            ->join('transactions', 'transaction_lines.transaction_id', '=', 'transactions.transaction_id')
            ->where('transaction_lines.account_id', $accountId)
            ->where('transactions.transaction_date', '<=', $asOfDate)
            ->where('transactions.status', 'posted')
            ->selectRaw('SUM(debit_amount) - SUM(credit_amount) as balance')
            ->first();

        $balance = $result ? (float) $result->balance : 0.00;

        // Get account to determine if it's a contra account
        $account = $this->findById($accountId);
        
        if ($account && $account['is_contra']) {
            $balance = -$balance;
        }

        // For liability, equity, revenue accounts, reverse the balance
        if ($account && in_array($account['account_type'], ['LIABILITY', 'EQUITY', 'REVENUE'])) {
            $balance = -$balance;
        }

        return $balance;
    }

    public function getAccountSummary(int $companyId, string $fromDate, string $toDate): array
    {
        return Capsule::table('accounts')
            ->leftJoin('transaction_lines', function ($join) use ($fromDate, $toDate) {
                $join->on('accounts.account_id', '=', 'transaction_lines.account_id')
                     ->join('transactions', 'transaction_lines.transaction_id', '=', 'transactions.transaction_id')
                     ->where('transactions.transaction_date', '>=', $fromDate)
                     ->where('transactions.transaction_date', '<=', $toDate)
                     ->where('transactions.status', 'posted');
            })
            ->where('accounts.company_id', $companyId)
            ->where('accounts.is_active', true)
            ->groupBy('accounts.account_id', 'accounts.account_code', 'accounts.account_name', 'accounts.account_type')
            ->selectRaw('
                accounts.account_id,
                accounts.account_code,
                accounts.account_name,
                accounts.account_type,
                accounts.opening_balance,
                COALESCE(SUM(transaction_lines.debit_amount), 0) as total_debits,
                COALESCE(SUM(transaction_lines.credit_amount), 0) as total_credits
            ')
            ->orderBy('accounts.account_code')
            ->get()
            ->toArray();
    }
}