<?php
/**
 * Validator Class
 * 
 * Provides input validation and sanitization methods
 * 
 * @package RBM\Schedule
 * @author RBM Development Team
 * @version 1.0.0
 */
class Validator {
    /**
     * Validate and sanitize string input
     * 
     * @param mixed $value Input value
     * @param int $maxLength Maximum length
     * @param bool $required Whether field is required
     * @return string|null Sanitized string or null if invalid
     */
    public static function sanitizeString($value, int $maxLength = 255, bool $required = false): ?string {
        if ($value === null || $value === '') {
            return $required ? null : '';
        }
        
        $sanitized = trim(strip_tags($value));
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        
        if (mb_strlen($sanitized) > $maxLength) {
            return null;
        }
        
        return $sanitized;
    }
    
    /**
     * Validate integer input
     * 
     * @param mixed $value Input value
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @param bool $required Whether field is required
     * @return int|null Valid integer or null if invalid
     */
    public static function validateInt($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX, bool $required = false): ?int {
        if ($value === null || $value === '') {
            return $required ? null : 0;
        }
        
        $intValue = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => $min,
                'max_range' => $max
            ]
        ]);
        
        return $intValue !== false ? $intValue : null;
    }
    
    /**
     * Validate email address
     * 
     * @param mixed $value Input value
     * @param bool $required Whether field is required
     * @return string|null Valid email or null if invalid
     */
    public static function validateEmail($value, bool $required = false): ?string {
        if ($value === null || $value === '') {
            return $required ? null : '';
        }
        
        $email = filter_var(trim($value), FILTER_VALIDATE_EMAIL);
        return $email !== false ? $email : null;
    }
    
    /**
     * Validate datetime string
     * 
     * @param mixed $value Input value
     * @param string $format Expected format (default: 'Y-m-d H:i:s')
     * @param bool $required Whether field is required
     * @return string|null Valid datetime string or null if invalid
     */
    public static function validateDateTime($value, string $format = 'Y-m-d H:i:s', bool $required = false): ?string {
        if ($value === null || $value === '') {
            return $required ? null : null;
        }
        
        $date = DateTime::createFromFormat($format, trim($value));
        if ($date && $date->format($format) === trim($value)) {
            return $date->format($format);
        }
        
        return null;
    }
    
    /**
     * Validate SPK format
     * 
     * @param mixed $value Input value
     * @param bool $required Whether field is required
     * @return string|null Valid SPK or null if invalid
     */
    public static function validateSPK($value, bool $required = false): ?string {
        if ($value === null || $value === '') {
            return $required ? null : '';
        }
        
        $spk = self::sanitizeString($value, 50, $required);
        if ($spk === null) {
            return null;
        }
        
        // SPK should be alphanumeric with dashes/underscores, min 3 chars
        if (!preg_match('/^[A-Za-z0-9\-_]{3,50}$/', $spk)) {
            return null;
        }
        
        return strtoupper($spk);
    }
    
    /**
     * Validate status value
     * 
     * @param mixed $value Input value
     * @param array $allowedStatuses Allowed status values
     * @return string|null Valid status or null if invalid
     */
    public static function validateStatus($value, array $allowedStatuses = ['Not Started', 'Running', 'Finish']): ?string {
        if ($value === null || $value === '') {
            return null;
        }
        
        $status = trim($value);
        return in_array($status, $allowedStatuses, true) ? $status : null;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string|null $token CSRF token from request
     * @return bool True if valid, false otherwise
     */
    public static function validateCSRF(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Validate request method
     * 
     * @param string $expectedMethod Expected HTTP method (GET, POST, etc.)
     * @return bool True if method matches
     */
    public static function validateMethod(string $expectedMethod): bool {
        return $_SERVER['REQUEST_METHOD'] === strtoupper($expectedMethod);
    }
    
    /**
     * Get validation errors as array
     * 
     * @param array $errors Array of error messages
     * @return array Formatted error array
     */
    public static function formatErrors(array $errors): array {
        return [
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $errors
        ];
    }
}


