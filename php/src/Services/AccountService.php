<?php

namespace AccountingSystem\Services;

use AccountingSystem\Repositories\AccountRepository;
use AccountingSystem\Repositories\TransactionRepository;

class AccountService
{
    private AccountRepository $accountRepository;
    private TransactionRepository $transactionRepository;

    public function __construct(
        AccountRepository $accountRepository,
        TransactionRepository $transactionRepository
    ) {
        $this->accountRepository = $accountRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function getAccounts(int $companyId, array $filters = []): array
    {
        return $this->accountRepository->findByCompany($companyId, $filters);
    }

    public function getAccount(int $accountId, int $companyId): ?array
    {
        return $this->accountRepository->findByIdAndCompany($accountId, $companyId);
    }

    public function createAccount(int $companyId, array $data): array
    {
        // Validate account code uniqueness
        if ($this->accountRepository->codeExists($data['account_code'], $companyId)) {
            throw new \Exception('Account code already exists');
        }

        // Validate parent account if specified
        if (!empty($data['parent_account_id'])) {
            $parent = $this->accountRepository->findByIdAndCompany(
                $data['parent_account_id'], 
                $companyId
            );
            if (!$parent) {
                throw new \Exception('Parent account not found');
            }
        }

        $accountData = [
            'company_id' => $companyId,
            'account_code' => $data['account_code'],
            'account_name' => $data['account_name'],
            'account_type' => $data['account_type'],
            'parent_account_id' => $data['parent_account_id'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0.00,
            'current_balance' => $data['opening_balance'] ?? 0.00,
            'description' => $data['description'] ?? null,
            'tax_rate' => $data['tax_rate'] ?? null,
            'is_contra' => $data['is_contra'] ?? false,
            'is_active' => $data['is_active'] ?? true
        ];

        return $this->accountRepository->create($accountData);
    }

    public function updateAccount(int $accountId, int $companyId, array $data): ?array
    {
        $account = $this->accountRepository->findByIdAndCompany($accountId, $companyId);
        
        if (!$account) {
            return null;
        }

        // Validate account code uniqueness if changed
        if (isset($data['account_code']) && $data['account_code'] !== $account['account_code']) {
            if ($this->accountRepository->codeExists($data['account_code'], $companyId)) {
                throw new \Exception('Account code already exists');
            }
        }

        // Validate parent account if changed
        if (isset($data['parent_account_id']) && $data['parent_account_id'] !== $account['parent_account_id']) {
            if ($data['parent_account_id']) {
                $parent = $this->accountRepository->findByIdAndCompany(
                    $data['parent_account_id'], 
                    $companyId
                );
                if (!$parent) {
                    throw new \Exception('Parent account not found');
                }
                
                // Prevent circular reference
                if ($this->wouldCreateCircularReference($accountId, $data['parent_account_id'])) {
                    throw new \Exception('Cannot create circular reference in account hierarchy');
                }
            }
        }

        // Cannot change account type if account has transactions
        if (isset($data['account_type']) && $data['account_type'] !== $account['account_type']) {
            $hasTransactions = $this->transactionRepository->accountHasTransactions($accountId);
            if ($hasTransactions) {
                throw new \Exception('Cannot change account type when account has transactions');
            }
        }

        $updateData = array_intersect_key($data, [
            'account_code' => null,
            'account_name' => null,
            'account_type' => null,
            'parent_account_id' => null,
            'description' => null,
            'tax_rate' => null,
            'is_contra' => null,
            'is_active' => null
        ]);

        return $this->accountRepository->update($accountId, $updateData);
    }

    public function deleteAccount(int $accountId, int $companyId): bool
    {
        $account = $this->accountRepository->findByIdAndCompany($accountId, $companyId);
        
        if (!$account) {
            return false;
        }

        // Check if account has transactions
        $hasTransactions = $this->transactionRepository->accountHasTransactions($accountId);
        if ($hasTransactions) {
            throw new \Exception('Cannot delete account with existing transactions');
        }

        // Check if account has child accounts
        $hasChildren = $this->accountRepository->hasChildren($accountId);
        if ($hasChildren) {
            throw new \Exception('Cannot delete account with child accounts');
        }

        return $this->accountRepository->delete($accountId);
    }

    public function getAccountTree(int $companyId, array $filters = []): array
    {
        $accounts = $this->accountRepository->findByCompany($companyId, $filters);
        return $this->buildTree($accounts);
    }

    public function getAccountBalances(int $companyId, string $asOfDate, ?string $accountType = null): array
    {
        return $this->accountRepository->getBalances($companyId, $asOfDate, $accountType);
    }

    public function updateAccountBalance(int $accountId): void
    {
        $balance = $this->transactionRepository->getAccountBalance($accountId);
        $this->accountRepository->updateBalance($accountId, $balance);
    }

    private function buildTree(array $accounts, int $parentId = null): array
    {
        $tree = [];
        
        foreach ($accounts as $account) {
            if ($account['parent_account_id'] == $parentId) {
                $account['children'] = $this->buildTree($accounts, $account['account_id']);
                $tree[] = $account;
            }
        }
        
        return $tree;
    }

    private function wouldCreateCircularReference(int $accountId, int $parentId): bool
    {
        // Check if the parent is already a descendant of the account
        $currentId = $parentId;
        
        while ($currentId) {
            if ($currentId == $accountId) {
                return true;
            }
            
            $parent = $this->accountRepository->findById($currentId);
            $currentId = $parent['parent_account_id'] ?? null;
        }
        
        return false;
    }

    public function generateAccountCode(int $companyId, string $accountType): string
    {
        $prefix = $this->getAccountTypePrefix($accountType);
        $lastAccount = $this->accountRepository->getLastAccountByType($companyId, $accountType);
        
        if ($lastAccount) {
            $lastNumber = (int) substr($lastAccount['account_code'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1000;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    private function getAccountTypePrefix(string $accountType): string
    {
        $prefixes = [
            'ASSET' => '1',
            'LIABILITY' => '2',
            'EQUITY' => '3',
            'REVENUE' => '4',
            'EXPENSE' => '5'
        ];
        
        return $prefixes[$accountType] ?? '9';
    }
}