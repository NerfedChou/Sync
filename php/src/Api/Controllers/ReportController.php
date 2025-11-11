<?php

namespace AccountingSystem\Api\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AccountingSystem\Services\ReportService;

class ReportController
{
    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function profitLoss(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'from_date' => $queryParams['from_date'] ?? null,
                'to_date' => $queryParams['to_date'] ?? null,
                'period_id' => $queryParams['period_id'] ?? null,
                'include_drafts' => $queryParams['include_drafts'] ?? false
            ];

            $report = $this->reportService->generateProfitLoss($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $report
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to generate profit & loss: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function balanceSheet(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'as_of_date' => $queryParams['as_of_date'] ?? null,
                'period_id' => $queryParams['period_id'] ?? null,
                'include_drafts' => $queryParams['include_drafts'] ?? false
            ];

            $report = $this->reportService->generateBalanceSheet($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $report
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to generate balance sheet: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function cashFlow(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'from_date' => $queryParams['from_date'] ?? null,
                'to_date' => $queryParams['to_date'] ?? null,
                'period_id' => $queryParams['period_id'] ?? null,
                'include_drafts' => $queryParams['include_drafts'] ?? false
            ];

            $report = $this->reportService->generateCashFlow($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $report
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to generate cash flow: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function trialBalance(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'as_of_date' => $queryParams['as_of_date'] ?? null,
                'period_id' => $queryParams['period_id'] ?? null,
                'include_drafts' => $queryParams['include_drafts'] ?? false
            ];

            $report = $this->reportService->generateTrialBalance($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $report
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to generate trial balance: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function generalLedger(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'from_date' => $queryParams['from_date'] ?? null,
                'to_date' => $queryParams['to_date'] ?? null,
                'account_id' => $queryParams['account_id'] ?? null,
                'limit' => $queryParams['limit'] ?? 100,
                'offset' => $queryParams['offset'] ?? 0
            ];

            $ledger = $this->reportService->generateGeneralLedger($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $ledger
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to generate general ledger: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function agedReceivables(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $asOfDate = $queryParams['as_of_date'] ?? date('Y-m-d');
            $report = $this->reportService->generateAgedReceivables($companyId, $asOfDate);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $report
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to generate aged receivables: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function agedPayables(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $asOfDate = $queryParams['as_of_date'] ?? date('Y-m-d');
            $report = $this->reportService->generateAgedPayables($companyId, $asOfDate);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $report
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to generate aged payables: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}