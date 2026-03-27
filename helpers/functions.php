<?php
/**
 * Helper Functions
 * 
 * Common utility functions used throughout the application
 * 
 * @package RBM\Schedule
 * @author RBM Development Team
 * @version 1.0.0
 */

/**
 * Format datetime to Indonesian format
 * 
 * @param string|null $datetime Datetime string
 * @param bool $includeTime Whether to include time
 * @return string Formatted datetime string
 */
function formatDateTimeID(?string $datetime, bool $includeTime = true): string {
    if (empty($datetime)) {
        return '-';
    }
    
    try {
        $date = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));
        $format = $includeTime ? 'd/m/Y H:i' : 'd/m/Y';
        return $date->format($format);
    } catch (Exception $e) {
        return '-';
    }
}

/**
 * Format number with thousand separator
 * 
 * @param int|float $number Number to format
 * @param int $decimals Number of decimal places
 * @return string Formatted number
 */
function formatNumber($number, int $decimals = 0): string {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Get current timestamp in Indonesia timezone
 * 
 * @return string Formatted datetime string
 */
function getCurrentTimestamp(): string {
    $date = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    return $date->format('Y-m-d H:i:s');
}

/**
 * Escape HTML output
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if string is empty or null
 * 
 * @param mixed $value Value to check
 * @return bool True if empty
 */
function isEmpty($value): bool {
    return $value === null || $value === '' || (is_string($value) && trim($value) === '');
}

/**
 * Get status badge HTML
 * 
 * @param string $status Status value
 * @return string HTML badge
 */
function getStatusBadge(string $status): string {
    $statusLower = strtolower($status);
    $icons = [
        'running' => '<i class="fas fa-spinner fa-spin"></i>',
        'finish' => '<i class="fas fa-check-circle"></i>',
        'not started' => '<i class="fas fa-clock"></i>'
    ];
    
    $icon = $icons[$statusLower] ?? '<i class="fas fa-circle"></i>';
    
    return sprintf(
        '<span class="status-badge status-%s">%s %s</span>',
        str_replace(' ', '-', $statusLower),
        $icon,
        e($status)
    );
}

/**
 * Generate pagination array
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param int $range Number of pages to show on each side
 * @return array Pagination data
 */
function generatePagination(int $currentPage, int $totalPages, int $range = 2): array {
    $start = max(1, $currentPage - $range);
    $end = min($totalPages, $start + ($range * 2));
    $start = max(1, $end - ($range * 2));
    
    return [
        'current' => $currentPage,
        'total' => $totalPages,
        'start' => $start,
        'end' => $end,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev' => max(1, $currentPage - 1),
        'next' => min($totalPages, $currentPage + 1)
    ];
}

/**
 * Sanitize filename
 * 
 * @param string $filename Original filename
 * @return string Sanitized filename
 */
function sanitizeFilename(string $filename): string {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return substr($filename, 0, 255);
}

/**
 * Get file extension
 * 
 * @param string $filename Filename
 * @return string File extension
 */
function getFileExtension(string $filename): string {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if request is AJAX
 * 
 * @return bool True if AJAX request
 */
function isAjaxRequest(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function getClientIP(): string {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}


