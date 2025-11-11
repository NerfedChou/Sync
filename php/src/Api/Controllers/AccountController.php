<?php

namespace AccountingSystem\Api\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AccountingSystem\Services\AccountService;
use AccountingSystem\Validators\AccountValidator;

class AccountController
{
    private AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'account_type' => $queryParams['account_type'] ?? null,
                'is_active' => $queryParams['is_active'] ?? null,
                'search' => $queryParams['search'] ?? null,
                'parent_id' => $queryParams['parent_id'] ?? null
            ];

            $accounts = $this->accountService->getAccounts($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $accounts
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch accounts: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $companyId = $request->getAttribute('company_id');

            // Validate input
            $validator = new AccountValidator();
            $validation = $validator->validateCreate($data);

            if (!$validation->isValid()) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $account = $this->accountService->createAccount($companyId, $data);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Account created successfully',
                'data' => $account
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create account: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $accountId = (int) $args['id'];
            $companyId = $request->getAttribute('company_id');

            $account = $this->accountService->getAccount($accountId, $companyId);

            if (!$account) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Account not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $account
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch account: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $accountId = (int) $args['id'];
            $data = $request->getParsedBody();
            $companyId = $request->getAttribute('company_id');

            // Validate input
            $validator = new AccountValidator();
            $validation = $validator->validateUpdate($data);

            if (!$validation->isValid()) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation->getErrors()
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $account = $this->accountService->updateAccount($accountId, $companyId, $data);

            if (!$account) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Account not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Account updated successfully',
                'data' => $account
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update account: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $accountId = (int) $args['id'];
            $companyId = $request->getAttribute('company_id');

            $result = $this->accountService->deleteAccount($accountId, $companyId);

            if (!$result) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Account not found or cannot be deleted'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete account: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function tree(Request $request, Response $response): Response
    {
        try {
            $companyId = $request->getAttribute('company_id');
            $queryParams = $request->getQueryParams();
            
            $filters = [
                'account_type' => $queryParams['account_type'] ?? null,
                'is_active' => $queryParams['is_active'] ?? true
            ];

            $tree = $this->accountService->getAccountTree($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $tree
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch account tree: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function balances(Request $request, Response $response): Response
    {
        try {
            $companyId = $request->getAttribute('company_id');
            $queryParams = $request->getQueryParams();
            
            $asOfDate = $queryParams['as_of_date'] ?? date('Y-m-d');
            $accountType = $queryParams['account_type'] ?? null;

            $balances = $this->accountService->getAccountBalances($companyId, $asOfDate, $accountType);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $balances,
                'as_of_date' => $asOfDate
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch account balances: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}