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

// Base URL configuration
// For root domain deployment: define('BASE_URL', '');
// For subdirectory deployment: define('BASE_URL', '/rbmschedule');
define('BASE_URL', '');

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
