<?php

namespace AccountingSystem\Api\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AccountingSystem\Services\AnalyticsService;

class AnalyticsController
{
    private AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function dashboard(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'period_id' => $queryParams['period_id'] ?? null,
                'from_date' => $queryParams['from_date'] ?? null,
                'to_date' => $queryParams['to_date'] ?? null
            ];

            $analytics = $this->analyticsService->getDashboardMetrics($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $analytics
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to get dashboard analytics: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function revenueTrends(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $period = $queryParams['period'] ?? 30; // Default to 30 days
            $filters = [
                'period_id' => $queryParams['period_id'] ?? null,
                'from_date' => $queryParams['from_date'] ?? null,
                'to_date' => $queryParams['to_date'] ?? null
            ];

            $trends = $this->analyticsService->getRevenueTrends($companyId, $period, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $trends
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to get revenue trends: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function expenseAnalysis(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'period_id' => $queryParams['period_id'] ?? null,
                'from_date' => $queryParams['from_date'] ?? null,
                'to_date' => $queryParams['to_date'] ?? null,
                'category' => $queryParams['category'] ?? null
            ];

            $analysis = $this->analyticsService->getExpenseAnalysis($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $analysis
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to get expense analysis: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function cashFlowTrends(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $period = $queryParams['period'] ?? 30; // Default to 30 days
            $filters = [
                'period_id' => $queryParams['period_id'] ?? null,
                'from_date' => $queryParams['from_date'] ?? null,
                'to_date' => $queryParams['to_date'] ?? null
            ];

            $trends = $this->analyticsService->getCashFlowTrends($companyId, $period, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $trends
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to get cash flow trends: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function profitability(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'period_id' => $queryParams['period_id'] ?? null,
                'from_date' => $queryParams['from_date'] ?? null,
                'to_date' => $queryParams['to_date'] ?? null
            ];

            $profitability = $this->analyticsService->getProfitabilityMetrics($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $profitability
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to get profitability metrics: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function budgetVariance(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $companyId = $request->getAttribute('company_id');
            
            $filters = [
                'period_id' => $queryParams['period_id'] ?? null,
                'account_id' => $queryParams['account_id'] ?? null
            ];

            $variance = $this->analyticsService->getBudgetVariance($companyId, $filters);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $variance
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to get budget variance: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}