<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Build query for simplified companies
    $sql = "SELECT 
                company_id as id,
                company_name as 'Company Name',
                tax_id as 'Tax ID',
                CONCAT(phone, ' | ', email) as 'Contact',
                currency_code as 'Currency',
                status as 'Status',
                created_at as 'Created'
            FROM companies 
            WHERE is_active = 1 
            ORDER BY company_name";
    
    // Execute query
    $companies = $db->fetchAll($sql);
    
    // Format companies for frontend
    $formattedCompanies = array_map(function($company) {
        return [
            'id' => (int)$company['id'],
            'Company Name' => $company['Company Name'],
            'Tax ID' => $company['Tax ID'],
            'Contact' => $company['Contact'],
            'Currency' => $company['Currency'],
            'Status' => $company['Status'],
            'Created' => $company['Created']
        ];
    }, $companies);
    
    // Return success response
    Response::success($formattedCompanies, "Companies retrieved successfully");
    
} catch (Exception $e) {
    error_log("Companies endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve companies");
}
?>