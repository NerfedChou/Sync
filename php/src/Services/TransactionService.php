<?php

namespace AccountingSystem\Services;

use AccountingSystem\Repositories\TransactionRepository;
use AccountingSystem\Repositories\AccountRepository;

class TransactionService
{
    private TransactionRepository $transactionRepository;
    private AccountRepository $accountRepository;

    public function __construct(
        TransactionRepository $transactionRepository,
        AccountRepository $accountRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->accountRepository = $accountRepository;
    }

    public function getTransactions(int $companyId, array $filters = []): array
    {
        return $this->transactionRepository->findByCompany($companyId, $filters);
    }

    public function getTransaction(int $transactionId, int $companyId): ?array
    {
        return $this->transactionRepository->findByIdAndCompany($transactionId, $companyId);
    }

    public function createTransaction(int $companyId, array $data): array
    {
        // Validate double-entry bookkeeping
        $this->validateTransactionLines($data['lines'], $companyId);

        // Generate transaction number
        $transactionNumber = $this->generateTransactionNumber($companyId);

        $transactionData = [
            'company_id' => $companyId,
            'period_id' => $data['period_id'],
            'transaction_number' => $transactionNumber,
            'transaction_date' => $data['transaction_date'],
            'description' => $data['description'],
            'reference' => $data['reference'] ?? null,
            'total_amount' => $data['total_amount'],
            'status' => 'draft'
        ];

        $transaction = $this->transactionRepository->create($transactionData);

        // Create transaction lines
        foreach ($data['lines'] as $line) {
            $lineData = [
                'transaction_id' => $transaction['transaction_id'],
                'account_id' => $line['account_id'],
                'description' => $line['description'] ?? null,
                'debit_amount' => $line['debit_amount'] ?? 0.00,
                'credit_amount' => $line['credit_amount'] ?? 0.00
            ];

            $this->transactionRepository->createLine($lineData);
        }

        return $this->transactionRepository->findById($transaction['transaction_id']);
    }

    public function updateTransaction(int $transactionId, int $companyId, array $data): ?array
    {
        $transaction = $this->transactionRepository->findByIdAndCompany($transactionId, $companyId);
        
        if (!$transaction) {
            return null;
        }

        // Cannot update posted transactions
        if ($transaction['status'] === 'posted') {
            throw new \Exception('Cannot update posted transaction');
        }

        // Validate transaction lines if provided
        if (isset($data['lines'])) {
            $this->validateTransactionLines($data['lines'], $companyId);
        }

        $updateData = array_intersect_key($data, [
            'period_id' => null,
            'transaction_date' => null,
            'description' => null,
            'reference' => null,
            'total_amount' => null
        ]);

        $transaction = $this->transactionRepository->update($transactionId, $updateData);

        // Update transaction lines if provided
        if (isset($data['lines'])) {
            // Delete existing lines
            $this->transactionRepository->deleteLines($transactionId);

            // Create new lines
            foreach ($data['lines'] as $line) {
                $lineData = [
                    'transaction_id' => $transactionId,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit_amount' => $line['debit_amount'] ?? 0.00,
                    'credit_amount' => $line['credit_amount'] ?? 0.00
                ];

                $this->transactionRepository->createLine($lineData);
            }
        }

        return $this->transactionRepository->findById($transactionId);
    }

    public function deleteTransaction(int $transactionId, int $companyId): bool
    {
        $transaction = $this->transactionRepository->findByIdAndCompany($transactionId, $companyId);
        
        if (!$transaction) {
            return false;
        }

        // Cannot delete posted transactions
        if ($transaction['status'] === 'posted') {
            throw new \Exception('Cannot delete posted transaction');
        }

        return $this->transactionRepository->delete($transactionId);
    }

    public function postTransaction(int $transactionId, int $companyId, int $userId): ?array
    {
        $transaction = $this->transactionRepository->findByIdAndCompany($transactionId, $companyId);
        
        if (!$transaction) {
            return null;
        }

        if ($transaction['status'] === 'posted') {
            throw new \Exception('Transaction already posted');
        }

        // Validate transaction balance
        $lines = $this->transactionRepository->getLines($transactionId);
        $this->validateTransactionBalance($lines);

        // Update transaction status
        $transaction = $this->transactionRepository->update($transactionId, [
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $userId
        ]);

        // Update account balances
        $this->updateAccountBalances($lines);

        return $transaction;
    }

    public function voidTransaction(int $transactionId, int $companyId, int $userId, string $reason): ?array
    {
        $transaction = $this->transactionRepository->findByIdAndCompany($transactionId, $companyId);
        
        if (!$transaction) {
            return null;
        }

        if ($transaction['status'] === 'void') {
            throw new \Exception('Transaction already voided');
        }

        // Get lines before voiding to reverse balances
        $lines = $this->transactionRepository->getLines($transactionId);

        // Update transaction status
        $transaction = $this->transactionRepository->update($transactionId, [
            'status' => 'void',
            'voided_at' => now(),
            'voided_by' => $userId,
            'void_reason' => $reason
        ]);

        // Reverse account balances
        $this->reverseAccountBalances($lines);

        return $transaction;
    }

    private function validateTransactionLines(array $lines, int $companyId): void
    {
        if (empty($lines)) {
            throw new \Exception('Transaction must have at least one line');
        }

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($lines as $line) {
            // Validate account exists and belongs to company
            $account = $this->accountRepository->findByIdAndCompany($line['account_id'], $companyId);
            if (!$account) {
                throw new \Exception('Account not found or does not belong to company');
            }

            // Validate amounts
            $debitAmount = $line['debit_amount'] ?? 0;
            $creditAmount = $line['credit_amount'] ?? 0;

            if ($debitAmount <= 0 && $creditAmount <= 0) {
                throw new \Exception('Each line must have either a debit or credit amount greater than zero');
            }

            if ($debitAmount > 0 && $creditAmount > 0) {
                throw new \Exception('Each line can only have either a debit or credit amount, not both');
            }

            $totalDebits += $debitAmount;
            $totalCredits += $creditAmount;
        }

        // Check if transaction balances
        if (abs($totalDebits - $totalCredits) > 0.01) {
            throw new \Exception('Transaction must balance: total debits must equal total credits');
        }
    }

    private function validateTransactionBalance(array $lines): void
    {
        $totalDebits = array_sum(array_column($lines, 'debit_amount'));
        $totalCredits = array_sum(array_column($lines, 'credit_amount'));

        if (abs($totalDebits - $totalCredits) > 0.01) {
            throw new \Exception('Transaction does not balance');
        }
    }

    private function updateAccountBalances(array $lines): void
    {
        foreach ($lines as $line) {
            $this->accountRepository->updateBalance($line['account_id']);
        }
    }

    private function reverseAccountBalances(array $lines): void
    {
        foreach ($lines as $line) {
            $this->accountRepository->updateBalance($line['account_id']);
        }
    }

    private function generateTransactionNumber(int $companyId): string
    {
        $prefix = 'TRX';
        $date = date('Ymd');
        
        // Get last transaction number for today
        $lastTransaction = $this->transactionRepository->getLastTransactionByDate($companyId, date('Y-m-d'));
        
        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction['transaction_number'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getTrialBalance(int $companyId, string $asOfDate): array
    {
        return $this->transactionRepository->getTrialBalance($companyId, $asOfDate);
    }

    public function getGeneralLedger(int $companyId, ?int $accountId = null, string $fromDate = null, string $toDate = null): array
    {
        return $this->transactionRepository->getGeneralLedger($companyId, $accountId, $fromDate, $toDate);
    }
}