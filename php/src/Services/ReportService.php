<?php

namespace AccountingSystem\Services;

use AccountingSystem\Repositories\TransactionRepository;
use AccountingSystem\Repositories\AccountRepository;

class ReportService
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

    public function generateProfitLoss(int $companyId, array $filters = []): array
    {
        // Get revenue accounts
        $revenueAccounts = $this->accountRepository->findByCompany($companyId, [
            'account_type' => 'REVENUE',
            'is_active' => true
        ]);

        // Get expense accounts
        $expenseAccounts = $this->accountRepository->findByCompany($companyId, [
            'account_type' => 'EXPENSE',
            'is_active' => true
        ]);

        // Calculate totals from transactions
        $totalRevenue = $this->calculateAccountTotals($companyId, $revenueAccounts, 'credit', $filters);
        $totalExpenses = $this->calculateAccountTotals($companyId, $expenseAccounts, 'debit', $filters);

        $grossProfit = $totalRevenue - $totalExpenses;

        return [
            'period' => $this->getPeriodDescription($filters),
            'revenue' => [
                'total' => $totalRevenue,
                'accounts' => $this->getAccountBreakdown($companyId, $revenueAccounts, 'credit', $filters)
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'accounts' => $this->getAccountBreakdown($companyId, $expenseAccounts, 'debit', $filters)
            ],
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit // Simplified - would include other expenses in real implementation
        ];
    }

    public function generateBalanceSheet(int $companyId, array $filters = []): array
    {
        // Get asset accounts
        $assetAccounts = $this->accountRepository->findByCompany($companyId, [
            'account_type' => 'ASSET',
            'is_active' => true
        ]);

        // Get liability accounts
        $liabilityAccounts = $this->accountRepository->findByCompany($companyId, [
            'account_type' => 'LIABILITY',
            'is_active' => true
        ]);

        // Get equity accounts
        $equityAccounts = $this->accountRepository->findByCompany($companyId, [
            'account_type' => 'EQUITY',
            'is_active' => true
        ]);

        $totalAssets = $this->calculateAccountBalances($companyId, $assetAccounts, $filters);
        $totalLiabilities = $this->calculateAccountBalances($companyId, $liabilityAccounts, $filters);
        $totalEquity = $this->calculateAccountBalances($companyId, $equityAccounts, $filters);

        return [
            'as_of_date' => $filters['as_of_date'] ?? date('Y-m-d'),
            'assets' => [
                'total' => $totalAssets,
                'accounts' => $this->getAccountBalanceBreakdown($companyId, $assetAccounts, $filters)
            ],
            'liabilities' => [
                'total' => $totalLiabilities,
                'accounts' => $this->getAccountBalanceBreakdown($companyId, $liabilityAccounts, $filters)
            ],
            'equity' => [
                'total' => $totalEquity,
                'accounts' => $this->getAccountBalanceBreakdown($companyId, $equityAccounts, $filters)
            ],
            'total_liabilities_equity' => $totalLiabilities + $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01
        ];
    }

    public function generateCashFlow(int $companyId, array $filters = []): array
    {
        // Get cash flow from transactions
        $transactions = $this->transactionRepository->findByCompany($companyId, array_merge($filters, [
            'status' => 'posted'
        ]));

        $operatingCashFlow = 0;
        $investingCashFlow = 0;
        $financingCashFlow = 0;

        foreach ($transactions as $transaction) {
            $amount = $transaction['total_amount'];
            
            // Simplified cash flow categorization
            // In real implementation, this would be based on account types
            if ($this->isOperatingActivity($transaction)) {
                $operatingCashFlow += $amount;
            } elseif ($this->isInvestingActivity($transaction)) {
                $investingCashFlow += $amount;
            } elseif ($this->isFinancingActivity($transaction)) {
                $financingCashFlow += $amount;
            }
        }

        $netCashFlow = $operatingCashFlow + $investingCashFlow + $financingCashFlow;

        return [
            'period' => $this->getPeriodDescription($filters),
            'operating_cash_flow' => $operatingCashFlow,
            'investing_cash_flow' => $investingCashFlow,
            'financing_cash_flow' => $financingCashFlow,
            'net_cash_flow' => $netCashFlow
        ];
    }

    public function generateTrialBalance(int $companyId, array $filters = []): array
    {
        $accounts = $this->accountRepository->findByCompany($companyId, [
            'is_active' => true
        ]);

        $trialBalance = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $balance = $this->calculateAccountBalance($companyId, $account['account_id'], $filters);
            
            $debitBalance = 0;
            $creditBalance = 0;

            if ($this->isDebitNormalAccount($account['account_type'])) {
                $debitBalance = max($balance, 0);
                $creditBalance = abs(min($balance, 0));
            } else {
                $creditBalance = max($balance, 0);
                $debitBalance = abs(min($balance, 0));
            }

            $trialBalance[] = [
                'account_code' => $account['account_code'],
                'account_name' => $account['account_name'],
                'account_type' => $account['account_type'],
                'debit_balance' => $debitBalance,
                'credit_balance' => $creditBalance
            ];

            $totalDebits += $debitBalance;
            $totalCredits += $creditBalance;
        }

        return [
            'as_of_date' => $filters['as_of_date'] ?? date('Y-m-d'),
            'accounts' => $trialBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
            'difference' => $totalDebits - $totalCredits
        ];
    }

    public function generateGeneralLedger(int $companyId, array $filters = []): array
    {
        $transactions = $this->transactionRepository->findByCompany($companyId, $filters);
        
        $ledger = [];
        foreach ($transactions as $transaction) {
            $lines = $this->transactionRepository->getTransactionLines($transaction['transaction_id']);
            
            $ledger[] = [
                'transaction_id' => $transaction['transaction_id'],
                'transaction_number' => $transaction['transaction_number'],
                'transaction_date' => $transaction['transaction_date'],
                'description' => $transaction['description'],
                'reference' => $transaction['reference'],
                'status' => $transaction['status'],
                'lines' => $lines
            ];
        }

        return [
            'period' => $this->getPeriodDescription($filters),
            'transactions' => $ledger
        ];
    }

    public function generateAgedReceivables(int $companyId, string $asOfDate): array
    {
        // Get revenue accounts with credit balances
        $revenueAccounts = $this->accountRepository->findByCompany($companyId, [
            'account_type' => 'REVENUE',
            'is_active' => true
        ]);

        $agedReceivables = [];
        $totalOutstanding = 0;

        foreach ($revenueAccounts as $account) {
            $balance = $this->calculateAccountBalance($companyId, $account['account_id'], [
                'as_of_date' => $asOfDate
            ]);

            if ($balance > 0) {
                $aging = $this->calculateAging($balance, $asOfDate);
                $agedReceivables[] = [
                    'account_name' => $account['account_name'],
                    'account_code' => $account['account_code'],
                    'total_outstanding' => $balance,
                    'current' => $aging['current'],
                    'days_1_30' => $aging['1_30'],
                    'days_31_60' => $aging['31_60'],
                    'days_61_90' => $aging['61_90'],
                    'days_over_90' => $aging['over_90']
                ];

                $totalOutstanding += $balance;
            }
        }

        return [
            'as_of_date' => $asOfDate,
            'total_outstanding' => $totalOutstanding,
            'receivables' => $agedReceivables
        ];
    }

    public function generateAgedPayables(int $companyId, string $asOfDate): array
    {
        // Get expense accounts with debit balances
        $expenseAccounts = $this->accountRepository->findByCompany($companyId, [
            'account_type' => 'EXPENSE',
            'is_active' => true
        ]);

        $agedPayables = [];
        $totalOutstanding = 0;

        foreach ($expenseAccounts as $account) {
            $balance = $this->calculateAccountBalance($companyId, $account['account_id'], [
                'as_of_date' => $asOfDate
            ]);

            if ($balance > 0) {
                $aging = $this->calculateAging($balance, $asOfDate);
                $agedPayables[] = [
                    'account_name' => $account['account_name'],
                    'account_code' => $account['account_code'],
                    'total_outstanding' => $balance,
                    'current' => $aging['current'],
                    'days_1_30' => $aging['1_30'],
                    'days_31_60' => $aging['31_60'],
                    'days_61_90' => $aging['61_90'],
                    'days_over_90' => $aging['over_90']
                ];

                $totalOutstanding += $balance;
            }
        }

        return [
            'as_of_date' => $asOfDate,
            'total_outstanding' => $totalOutstanding,
            'payables' => $agedPayables
        ];
    }

    // Helper methods
    private function calculateAccountTotals(int $companyId, array $accounts, string $type, array $filters): float
    {
        $total = 0;
        foreach ($accounts as $account) {
            $total += $this->calculateAccountTotal($companyId, $account['account_id'], $type, $filters);
        }
        return $total;
    }

    private function calculateAccountBalances(int $companyId, array $accounts, array $filters): float
    {
        $total = 0;
        foreach ($accounts as $account) {
            $total += $this->calculateAccountBalance($companyId, $account['account_id'], $filters);
        }
        return $total;
    }

    private function calculateAccountTotal(int $companyId, int $accountId, string $type, array $filters): float
    {
        // Simplified calculation - in real implementation, this would query transaction lines
        return rand(1000, 50000); // Mock data for now
    }

    private function calculateAccountBalance(int $companyId, int $accountId, array $filters): float
    {
        // Simplified calculation - in real implementation, this would query transaction lines
        return rand(1000, 50000); // Mock data for now
    }

    private function getAccountBreakdown(int $companyId, array $accounts, string $type, array $filters): array
    {
        $breakdown = [];
        foreach ($accounts as $account) {
            $breakdown[] = [
                'account_id' => $account['account_id'],
                'account_name' => $account['account_name'],
                'account_code' => $account['account_code'],
                'total' => $this->calculateAccountTotal($companyId, $account['account_id'], $type, $filters)
            ];
        }
        return $breakdown;
    }

    private function getAccountBalanceBreakdown(int $companyId, array $accounts, array $filters): array
    {
        $breakdown = [];
        foreach ($accounts as $account) {
            $breakdown[] = [
                'account_id' => $account['account_id'],
                'account_name' => $account['account_name'],
                'account_code' => $account['account_code'],
                'balance' => $this->calculateAccountBalance($companyId, $account['account_id'], $filters)
            ];
        }
        return $breakdown;
    }

    private function getPeriodDescription(array $filters): string
    {
        if (isset($filters['from_date']) && isset($filters['to_date'])) {
            return $filters['from_date'] . ' to ' . $filters['to_date'];
        }
        return 'Current Period';
    }

    private function isDebitNormalAccount(string $accountType): bool
    {
        return in_array($accountType, ['ASSET', 'EXPENSE']);
    }

    private function isOperatingActivity(array $transaction): bool
    {
        // Simplified logic - would be based on account types in real implementation
        return true;
    }

    private function isInvestingActivity(array $transaction): bool
    {
        return false;
    }

    private function isFinancingActivity(array $transaction): bool
    {
        return false;
    }

    private function calculateAging(float $balance, string $asOfDate): array
    {
        // Simplified aging calculation
        return [
            'current' => $balance * 0.3,
            '1_30' => $balance * 0.25,
            '31_60' => $balance * 0.2,
            '61_90' => $balance * 0.15,
            'over_90' => $balance * 0.1
        ];
    }
}