<?php

namespace AccountingSystem\Api\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AccountingSystem\Services\TransactionService;
use AccountingSystem\Validators\TransactionValidator;

class TransactionController
{
    private TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'status' => $queryParams['status'] ?? null,
                'period_id' => $queryParams['period_id'] ?? null,
                'from_date' => $queryParams['from_date'] ?? null,
                'to_date' => $queryParams['to_date'] ?? null,
                'account_id' => $queryParams['account_id'] ?? null,
                'search' => $queryParams['search'] ?? null,
                'limit' => $queryParams['limit'] ?? 50,
                'offset' => $queryParams['offset'] ?? 0
            ];

            $transactions = $this->transactionService->getTransactions($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $transactions
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch transactions: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $transactionId = (int) $args['id'];
            $companyId = $request->getAttribute('company_id');

            $transaction = $this->transactionService->getTransaction($transactionId, $companyId);

            if (!$transaction) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Transaction not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $transaction
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch transaction: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $companyId = $request->getAttribute('company_id');

            // Validate transaction data
            $validator = new TransactionValidator();
            $validationResult = $validator->validate($data);

            if (!$validationResult['isValid']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validationResult['errors']
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $transaction = $this->transactionService->createTransaction($companyId, $data);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $transaction
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to create transaction: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $transactionId = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            $companyId = $request->getAttribute('company_id');

            // Validate transaction data
            $validator = new TransactionValidator();
            $validationResult = $validator->validate($data);

            if (!$validationResult['isValid']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validationResult['errors']
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $transaction = $this->transactionService->updateTransaction($transactionId, $companyId, $data);

            if (!$transaction) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Transaction not found or cannot be updated'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Transaction updated successfully',
                'data' => $transaction
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update transaction: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $transactionId = (int) $args['id'];
            $companyId = $request->getAttribute('company_id');

            $success = $this->transactionService->deleteTransaction($transactionId, $companyId);

            if (!$success) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Transaction not found or cannot be deleted'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Transaction deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to delete transaction: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function post(Request $request, Response $response, array $args): Response
    {
        try {
            $transactionId = (int) $args['id'];
            $companyId = $request->getAttribute('company_id');
            $userId = $request->getAttribute('user_id');

            $transaction = $this->transactionService->postTransaction($transactionId, $companyId, $userId);

            if (!$transaction) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Transaction not found or cannot be posted'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Transaction posted successfully',
                'data' => $transaction
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to post transaction: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function void(Request $request, Response $response, array $args): Response
    {
        try {
            $transactionId = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);
            $companyId = $request->getAttribute('company_id');
            $userId = $request->getAttribute('user_id');

            $transaction = $this->transactionService->voidTransaction(
                $transactionId, 
                $companyId, 
                $userId, 
                $data['void_reason'] ?? null
            );

            if (!$transaction) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Transaction not found or cannot be voided'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Transaction voided successfully',
                'data' => $transaction
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to void transaction: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}