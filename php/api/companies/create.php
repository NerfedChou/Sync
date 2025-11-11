
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../middleware/validation.php';

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error("Method not allowed", 405);
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error("Invalid JSON input", 400);
    }
    
    // Validate required fields
    $requiredFields = ['company_name'];
    $errors = Validation::required($input, $requiredFields);
    
    if (!empty($errors)) {
        Response::validationError($errors);
    }
    
    // Sanitize input
    $company_name = Validation::sanitize($input['company_name']);
    $tax_id = Validation::sanitize($input['tax_id'] ?? '');
    $address = Validation::sanitize($input['address'] ?? '');
    $phone = Validation::sanitize($input['phone'] ?? '');
    $email = Validation::sanitize($input['email'] ?? '');
    $website = Validation::sanitize($input['website'] ?? '');
    $currency_code = Validation::sanitize($input['currency_code'] ?? 'USD');
    $fiscal_year_start = Validation::sanitize($input['fiscal_year_start'] ?? '2024-01-01');
    $is_active = isset($input['is_active']) ? (bool)$input['is_active'] : true;
    
    // Validate email if provided
    if (!empty($email) && !Validation::email($email)) {
        Response::error("Invalid email address", 400);
    }
    
    // Validate date format
    if (!Validation::date($fiscal_year_start)) {
        Response::error("Invalid fiscal year start date", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if company name already exists
    $existingCompany = $db->fetchOne("SELECT id FROM companies WHERE company_name = ?", [$company_name]);
    if ($existingCompany) {
        Response::error("Company name already exists", 409);
    }
    
    // Insert company
    $sql = "
        INSERT INTO companies (company_name, tax_id, address, phone, email, website, currency_code, fiscal_year_start, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";
    
    $db->query($sql, [
        $company_name,
        $tax_id,
        $address,
        $phone,
        $email,
        $website,
        $currency_code,
        $fiscal_year_start,
        $is_active
    ]);
    
    $company_id = $db->lastInsertId();
    
    // Get created company
    $createdCompany = $db->fetchOne("SELECT * FROM companies WHERE id = ?", [$company_id]);
    
    // Format response
    $companyData = [
        'id' => (int)$createdCompany['id'],
        'company_name' => $createdCompany['company_name'],
        'tax_id' => $createdCompany['tax_id'],
        'address' => $createdCompany['address'],
        'phone' => $createdCompany['phone'],
        'email' => $createdCompany['email'],
        'website' => $createdCompany['website'],
        'currency_code' => $createdCompany['currency_code'],
        'fiscal_year_start' => $createdCompany['fiscal_year_start'],
        'is_active' => (bool)$createdCompany['is_active'],
        'created_at' => $createdCompany['created_at']
    ];
    
    Response::success($companyData, "Company created successfully");
    
} catch (Exception $e) {
    error_log("Company creation error: " . $e->getMessage());
    Response::serverError("Failed to create company");
}
?>