<?php
class CORS {
    public static function setHeaders() {
        // Allow any origin during development
        header("Access-Control-Allow-Origin: *");
        
        // Allow specific methods
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        
        // Allow specific headers
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        
        // Allow credentials
        header("Access-Control-Allow-Credentials: true");
        
        // Set content type for JSON responses
        header("Content-Type: application/json; charset=UTF-8");
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}
?>