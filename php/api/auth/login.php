
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';

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
    
    // Get credentials
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        Response::error("Username and password are required", 400);
    }
    
    // Get database connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Query admin user (simple authentication)
    $sql = "SELECT id, name FROM admin WHERE name = ? LIMIT 1";
    $admin = $db->fetchOne($sql, [$username]);
    
    if (!$admin) {
        Response::error("Invalid credentials", 401);
    }
    
    // For now, accept any non-empty password (simplified for development)
    // In production, you would hash and verify passwords
    if (strlen($password) < 3) {
        Response::error("Invalid credentials", 401);
    }
    
    // Create simple session token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store token in session (simplified - in production use Redis or database)
    session_start();
    $_SESSION['auth_token'] = $token;
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['expires'] = $expires;
    
    // Return success response with token
    Response::success([
        'token' => $token,
        'user' => [
            'id' => $admin['id'],
            'name' => $admin['name']
        ],
        'expires' => $expires
    ], "Login successful");
    
} catch (Exception $e) {
    error_log("Login endpoint error: " . $e->getMessage());
    Response::serverError("Login failed");
}
?>