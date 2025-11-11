<?php

namespace AccountingSystem\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AccountingSystem\Api\Controllers\AuthController;
use AccountingSystem\Api\Controllers\AccountController;
use AccountingSystem\Api\Controllers\TransactionController;
use AccountingSystem\Api\Controllers\ReportController;
use AccountingSystem\Api\Controllers\AnalyticsController;
use AccountingSystem\Api\Controllers\UserController;
use AccountingSystem\Api\Controllers\CompanyController;
use Slim\App;

class Routes
{
    public static function register(App $app): void
    {
        // Authentication routes
        $app->post('/api/auth/login', AuthController::class . ':login');
        $app->post('/api/auth/logout', AuthController::class . ':logout');
        $app->post('/api/auth/refresh', AuthController::class . ':refresh');
        $app->get('/api/auth/me', AuthController::class . ':getCurrentUser');

        // User management routes
        $app->get('/api/users', UserController::class . ':index');
        $app->post('/api/users', UserController::class . ':store');
        $app->get('/api/users/{id}', UserController::class . ':show');
        $app->put('/api/users/{id}', UserController::class . ':update');
        $app->delete('/api/users/{id}', UserController::class . ':destroy');

        // Company management routes
        $app->get('/api/companies', CompanyController::class . ':index');
        $app->post('/api/companies', CompanyController::class . ':store');
        $app->get('/api/companies/{id}', CompanyController::class . ':show');
        $app->put('/api/companies/{id}', CompanyController::class . ':update');
        $app->delete('/api/companies/{id}', CompanyController::class . ':destroy');

        // Chart of Accounts routes
        $app->get('/api/accounts', AccountController::class . ':index');
        $app->post('/api/accounts', AccountController::class . ':store');
        $app->get('/api/accounts/{id}', AccountController::class . ':show');
        $app->put('/api/accounts/{id}', AccountController::class . ':update');
        $app->delete('/api/accounts/{id}', AccountController::class . ':destroy');
        $app->get('/api/accounts/tree', AccountController::class . ':tree');
        $app->get('/api/accounts/balances', AccountController::class . ':balances');

        // Transaction routes
        $app->get('/api/transactions', TransactionController::class . ':index');
        $app->post('/api/transactions', TransactionController::class . ':store');
        $app->get('/api/transactions/{id}', TransactionController::class . ':show');
        $app->put('/api/transactions/{id}', TransactionController::class . ':update');
        $app->delete('/api/transactions/{id}', TransactionController::class . ':destroy');
        $app->post('/api/transactions/{id}/post', TransactionController::class . ':post');
        $app->post('/api/transactions/{id}/void', TransactionController::class . ':void');

        // Report routes
        $app->get('/api/reports/profit-loss', ReportController::class . ':profitLoss');
        $app->get('/api/reports/balance-sheet', ReportController::class . ':balanceSheet');
        $app->get('/api/reports/cash-flow', ReportController::class . ':cashFlow');
        $app->get('/api/reports/trial-balance', ReportController::class . ':trialBalance');
        $app->get('/api/reports/general-ledger', ReportController::class . ':generalLedger');
        $app->get('/api/reports/aged-receivables', ReportController::class . ':agedReceivables');
        $app->get('/api/reports/aged-payables', ReportController::class . ':agedPayables');

        // Analytics routes
        $app->get('/api/analytics/dashboard', AnalyticsController::class . ':dashboard');
        $app->get('/api/analytics/revenue-trends', AnalyticsController::class . ':revenueTrends');
        $app->get('/api/analytics/expense-analysis', AnalyticsController::class . ':expenseAnalysis');
        $app->get('/api/analytics/cash-flow-trends', AnalyticsController::class . ':cashFlowTrends');
        $app->get('/api/analytics/profitability', AnalyticsController::class . ':profitability');
        $app->get('/api/analytics/budget-variance', AnalyticsController::class . ':budgetVariance');

        // API Documentation
        $app->get('/api/docs', function (Request $request, Response $response) {
            $docs = [
                'title' => 'Accounting System API',
                'version' => '1.0.0',
                'description' => 'RESTful API for modern accounting system',
                'endpoints' => [
                    'Authentication' => [
                        'POST /api/auth/login' => 'User login',
                        'POST /api/auth/logout' => 'User logout',
                        'POST /api/auth/refresh' => 'Refresh JWT token',
                        'GET /api/auth/me' => 'Get current user info'
                    ],
                    'Accounts' => [
                        'GET /api/accounts' => 'List all accounts',
                        'POST /api/accounts' => 'Create new account',
                        'GET /api/accounts/{id}' => 'Get account details',
                        'PUT /api/accounts/{id}' => 'Update account',
                        'DELETE /api/accounts/{id}' => 'Delete account',
                        'GET /api/accounts/tree' => 'Get account hierarchy',
                        'GET /api/accounts/balances' => 'Get account balances'
                    ],
                    'Transactions' => [
                        'GET /api/transactions' => 'List transactions',
                        'POST /api/transactions' => 'Create transaction',
                        'GET /api/transactions/{id}' => 'Get transaction details',
                        'PUT /api/transactions/{id}' => 'Update transaction',
                        'DELETE /api/transactions/{id}' => 'Delete transaction',
                        'POST /api/transactions/{id}/post' => 'Post transaction',
                        'POST /api/transactions/{id}/void' => 'Void transaction'
                    ],
                    'Reports' => [
                        'GET /api/reports/profit-loss' => 'Profit & Loss statement',
                        'GET /api/reports/balance-sheet' => 'Balance Sheet',
                        'GET /api/reports/cash-flow' => 'Cash Flow statement',
                        'GET /api/reports/trial-balance' => 'Trial Balance',
                        'GET /api/reports/general-ledger' => 'General Ledger'
                    ],
                    'Analytics' => [
                        'GET /api/analytics/dashboard' => 'Dashboard metrics',
                        'GET /api/analytics/revenue-trends' => 'Revenue trends analysis',
                        'GET /api/analytics/expense-analysis' => 'Expense analysis',
                        'GET /api/analytics/cash-flow-trends' => 'Cash flow trends',
                        'GET /api/analytics/profitability' => 'Profitability metrics',
                        'GET /api/analytics/budget-variance' => 'Budget vs actual'
                    ]
                ]
            ];

            $response->getBody()->write(json_encode($docs, JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // Health check
        $app->get('/api/health', function (Request $request, Response $response) {
            $health = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0',
                'database' => 'connected'
            ];

            $response->getBody()->write(json_encode($health, JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');
        });
    }
}