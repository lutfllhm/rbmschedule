<?php
/**
 * Path Configuration
 * 
 * Centralized path configuration for the application
 * 
 * @package RBM\Schedule
 * @author RBM Development Team
 * @version 1.0.0
 */

// Base URL configuration (auto-detect with optional override)
// Optional override via environment variable APP_BASE_URL.
if (!function_exists('normalizeBaseUrl')) {
    function normalizeBaseUrl($baseUrl) {
        $baseUrl = trim((string) $baseUrl);
        if ($baseUrl === '' || $baseUrl === '/') {
            return '';
        }
        $baseUrl = '/' . trim($baseUrl, '/');
        return $baseUrl;
    }
}

if (!function_exists('detectBaseUrlFromScript')) {
    function detectBaseUrlFromScript() {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptName === '') {
            return '';
        }

        $scriptName = str_replace('\\', '/', $scriptName);
        if (preg_match('#^(.*)/(pages|api|assets|includes)(/|$)#', $scriptName, $matches)) {
            return normalizeBaseUrl($matches[1]);
        }

        // Fallback for index.php in app root.
        $directory = dirname($scriptName);
        if ($directory === '/' || $directory === '\\' || $directory === '.') {
            return '';
        }

        return normalizeBaseUrl($directory);
    }
}

$baseUrlOverride = getenv('APP_BASE_URL');
define('BASE_URL', $baseUrlOverride !== false ? normalizeBaseUrl($baseUrlOverride) : detectBaseUrlFromScript());

// Asset paths
define('ASSETS_URL', BASE_URL . '/assets');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMG_URL', ASSETS_URL . '/img');

// Page paths
define('PAGES_URL', BASE_URL . '/pages');
define('API_URL', BASE_URL . '/api');

// Helper function to get full URL
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// Helper function to get asset URL
function asset($path = '') {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

// Helper function to get page URL
function page($path = '') {
    return PAGES_URL . '/' . ltrim($path, '/');
}

// Helper function to get API URL
function api($path = '') {
    return API_URL . '/' . ltrim($path, '/');
}

// Helper function to get path (alias for url)
function getPath($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}
?>
