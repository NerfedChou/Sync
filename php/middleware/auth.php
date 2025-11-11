# Authentication Middleware
<?php
class Auth {
    public static function check() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if token exists and is valid
        $token = $_SESSION['auth_token'] ?? null;
        $expires = $_SESSION['expires'] ?? null;
        
        if (!$token) {
            Response::unauthorized("No authentication token provided");
        }
        
        if ($expires && strtotime($expires) < time()) {
            // Token expired
            session_destroy();
            Response::unauthorized("Authentication token expired");
        }
        
        return true;
    }
    
    public static function getUserId() {
        self::check();
        return $_SESSION['admin_id'] ?? null;
    }
    
    public static function getUserName() {
        self::check();
        return $_SESSION['admin_name'] ?? null;
    }
    
    public static function logout() {
        session_start();
        session_destroy();
        Response::success(null, "Logged out successfully");
    }
}
?>