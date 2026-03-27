<?php
/**
 * Response Class
 * 
 * Handles JSON responses and error formatting
 * 
 * @package RBM\Schedule
 * @author RBM Development Team
 * @version 1.0.0
 */
class Response {
    /**
     * Send JSON success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $httpCode HTTP status code
     */
    public static function success($data = null, string $message = 'Berhasil', int $httpCode = 200): void {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Send JSON error response
     * 
     * @param string $message Error message
     * @param int $httpCode HTTP status code
     * @param array $errors Additional error details
     */
    public static function error(string $message = 'Terjadi kesalahan', int $httpCode = 400, array $errors = []): void {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Send JSON response with custom structure
     * 
     * @param array $data Response data
     * @param int $httpCode HTTP status code
     */
    public static function json(array $data, int $httpCode = 200): void {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Redirect to URL
     * 
     * @param string $url Target URL
     * @param int $httpCode HTTP status code (301, 302, etc.)
     */
    public static function redirect(string $url, int $httpCode = 302): void {
        http_response_code($httpCode);
        header("Location: {$url}");
        exit();
    }
    
    /**
     * Send unauthorized response
     * 
     * @param string $message Error message
     */
    public static function unauthorized(string $message = 'Akses ditolak'): void {
        self::error($message, 401);
    }
    
    /**
     * Send forbidden response
     * 
     * @param string $message Error message
     */
    public static function forbidden(string $message = 'Anda tidak memiliki izin untuk aksi ini'): void {
        self::error($message, 403);
    }
    
    /**
     * Send not found response
     * 
     * @param string $message Error message
     */
    public static function notFound(string $message = 'Data tidak ditemukan'): void {
        self::error($message, 404);
    }
    
    /**
     * Send validation error response
     * 
     * @param array $errors Validation errors
     * @param string $message Error message
     */
    public static function validationError(array $errors, string $message = 'Validasi gagal'): void {
        self::error($message, 422, $errors);
    }
}


