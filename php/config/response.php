<?php
class Response {
    public static function success($data = null, $message = "Operation successful") {
        http_response_code(200);
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    public static function error($message = "Operation failed", $code = 400, $data = null) {
        http_response_code($code);
        $response = [
            'success' => false,
            'error' => $message,
            'code' => $code
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    public static function notFound($message = "Resource not found") {
        self::error($message, 404);
    }
    
    public static function unauthorized($message = "Unauthorized access") {
        self::error($message, 401);
    }
    
    public static function forbidden($message = "Access forbidden") {
        self::error($message, 403);
    }
    
    public static function validationError($errors = []) {
        $message = "Validation failed";
        if (!empty($errors)) {
            $message = implode(", ", $errors);
        }
        self::error($message, 422, $errors);
    }
    
    public static function serverError($message = "Internal server error", $exception = null) {
        if ($exception instanceof Exception) {
            $message .= ": " . $exception->getMessage();
        }
        self::error($message, 500);
    }
}
?>