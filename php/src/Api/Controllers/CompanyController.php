<?php

namespace AccountingSystem\Api\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use AccountingSystem\Config\Database;

class CompanyController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get all companies
     */
    public function index(Request $request, Response $response): Response
    {
        // Mock data for testing - replace with real database query later
        $companies = [
            [
                'id' => 1,
                'company_name' => 'TechCorp Solutions Inc.',
                'tax_id' => '12-3456789',
                'address' => '123 Innovation Drive, Palo Alto, CA 94301',
                'phone' => '+1-650-555-0123',
                'email' => 'contact@techcorp.com',
                'website' => 'https://techcorp.com',
                'currency_code' => 'USD',
                'fiscal_year_start' => '2024-01-01',
                'is_active' => true,
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ],
            [
                'id' => 2,
                'company_name' => 'Global Retail LLC',
                'tax_id' => '98-7654321',
                'address' => '456 Commerce Boulevard, New York, NY 10001',
                'phone' => '+1-212-555-0456',
                'email' => 'info@globalretail.com',
                'website' => 'https://globalretail.com',
                'currency_code' => 'USD',
                'fiscal_year_start' => '2024-01-01',
                'is_active' => true,
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ],
            [
                'id' => 3,
                'company_name' => 'Digital Marketing Pro',
                'tax_id' => '55-1234567',
                'address' => '789 Creative Avenue, Austin, TX 78701',
                'phone' => '+1-512-555-0789',
                'email' => 'hello@digitalmarketingpro.com',
                'website' => 'https://digitalmarketingpro.com',
                'currency_code' => 'USD',
                'fiscal_year_start' => '2024-01-01',
                'is_active' => true,
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ],
            [
                'id' => 4,
                'company_name' => 'Consulting Partners Group',
                'tax_id' => '77-9876543',
                'address' => '321 Executive Plaza, Chicago, IL 60601',
                'phone' => '+1-312-555-0321',
                'email' => 'admin@consultingpartners.com',
                'website' => 'https://consultingpartners.com',
                'currency_code' => 'USD',
                'fiscal_year_start' => '2024-01-01',
                'is_active' => false,
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ]
        ];

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $companies
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get a specific company
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $companyId = $args['id'];
        
        // Mock data - in real implementation, fetch from database
        $companies = [
            1 => [
                'id' => 1,
                'company_name' => 'TechCorp Solutions Inc.',
                'tax_id' => '12-3456789',
                'address' => '123 Innovation Drive, Palo Alto, CA 94301',
                'phone' => '+1-650-555-0123',
                'email' => 'contact@techcorp.com',
                'website' => 'https://techcorp.com',
                'currency_code' => 'USD',
                'fiscal_year_start' => '2024-01-01',
                'is_active' => true,
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ],
            2 => [
                'id' => 2,
                'company_name' => 'Global Retail LLC',
                'tax_id' => '98-7654321',
                'address' => '456 Commerce Boulevard, New York, NY 10001',
                'phone' => '+1-212-555-0456',
                'email' => 'info@globalretail.com',
                'website' => 'https://globalretail.com',
                'currency_code' => 'USD',
                'fiscal_year_start' => '2024-01-01',
                'is_active' => true,
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00'
            ]
        ];

        if (!isset($companies[$companyId])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Company not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $companies[$companyId]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create a new company
     */
    public function store(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Mock creation - in real implementation, save to database
        $newCompany = [
            'id' => rand(1000, 9999),
            'company_name' => $data['company_name'] ?? 'New Company',
            'tax_id' => $data['tax_id'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'website' => $data['website'] ?? '',
            'currency_code' => $data['currency_code'] ?? 'USD',
            'fiscal_year_start' => $data['fiscal_year_start'] ?? '2024-01-01',
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $newCompany,
            'message' => 'Company created successfully'
        ]));

        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update a company
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $companyId = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        // Mock update - in real implementation, update database
        $updatedCompany = [
            'id' => $companyId,
            'company_name' => $data['company_name'] ?? 'Updated Company',
            'tax_id' => $data['tax_id'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'website' => $data['website'] ?? '',
            'currency_code' => $data['currency_code'] ?? 'USD',
            'fiscal_year_start' => $data['fiscal_year_start'] ?? '2024-01-01',
            'is_active' => $data['is_active'] ?? true,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $updatedCompany,
            'message' => 'Company updated successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Delete a company
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $companyId = $args['id'];

        // Mock deletion - in real implementation, delete from database
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => "Company {$companyId} deleted successfully"
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}