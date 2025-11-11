<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

try {
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get company_id from query parameter (optional)
    $company_id = $_GET['company_id'] ?? null;
    
    // Build query
    $sql = "SELECT * FROM companies WHERE is_active = 1";
    $params = [];
    
    if ($company_id) {
        $sql .= " AND id = ?";
        $params[] = $company_id;
    }
    
    $sql .= " ORDER BY company_name";
    
    // Execute query
    $companies = $db->fetchAll($sql, $params);
    
    // Format companies for frontend
    $formattedCompanies = array_map(function($company) {
        return [
            'id' => (int)$company['company_id'],
            'company_name' => $company['company_name'],
            'tax_id' => $company['tax_id'],
            'address' => $company['address'],
            'phone' => $company['phone'],
            'email' => $company['email'],
            'website' => $company['website'],
            'currency_code' => $company['currency_code'],
            'fiscal_year_start' => $company['fiscal_year_start'],
            'is_active' => (bool)$company['is_active'],
            'created_at' => $company['created_at']
        ];
    }, $companies);
    
    // Return success response
    Response::success($formattedCompanies, "Companies retrieved successfully");
    
} catch (Exception $e) {
    error_log("Companies endpoint error: " . $e->getMessage());
    Response::serverError("Failed to retrieve companies");
}
?>