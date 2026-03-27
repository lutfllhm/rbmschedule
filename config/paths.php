<?php
/**
 * Path Configuration
 * 
 * Automatically detects the base path for the application
 * 
 * @package RBM\Schedule
 */

// Detect if running in subdirectory or root
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$baseDir = dirname(dirname($scriptName));

// If in root, baseDir will be '/' or empty
if ($baseDir === '/' || $baseDir === '\\' || $baseDir === '.') {
    $baseDir = '';
}

// Normalize path separators
$baseDir = str_replace('\\', '/', $baseDir);

// Define base path constant
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $baseDir);
}

/**
 * Get full URL path with base path
 * 
 * @param string $path Relative path
 * @return string Full path with base directory
 */
function getPath(string $path): string {
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}

/**
 * Get asset URL
 * 
 * @param string $asset Asset path relative to assets directory
 * @return string Full asset URL
 */
function asset(string $asset): string {
    return getPath('assets/' . ltrim($asset, '/'));
}
?>
