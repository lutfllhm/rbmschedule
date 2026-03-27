<?php
/**
 * Authentication Functions
 * 
 * Handles user authentication, session management, and authorization
 * 
 * @package RBM\Schedule
 * @author RBM Development Team
 * @version 1.0.0
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Session timeout configuration (30 minutes)
 */
define('SESSION_TIMEOUT', 30 * 60); // 30 minutes in seconds

/**
 * Check if session has timed out
 * 
 * Automatically destroys session if last activity was more than SESSION_TIMEOUT seconds ago
 * Updates last activity time if session is still valid
 * 
 * @return bool True if session is valid, false if expired
 */
function checkSessionTimeout(): bool {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            // Session expired
            session_unset();
            session_destroy();
            return false;
        }
    }
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check if user is currently logged in
 * 
 * Verifies that session contains user_id and username, and checks session timeout
 * 
 * @return bool True if user is logged in and session is valid
 */
function isLoggedIn(): bool {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        return false;
    }
    // Check session timeout
    return checkSessionTimeout();
}

/**
 * Check if current user has admin role
 * 
 * @return bool True if user is admin
 */
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if current user has operator role
 * 
 * @return bool True if user is operator
 */
function isOperator(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'operator';
}

/**
 * Require user to be logged in
 * 
 * Redirects to login page if user is not logged in
 * 
 * @return void
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        // Load paths config if not already loaded
        if (!defined('BASE_PATH')) {
            require_once __DIR__ . '/../config/paths.php';
        }
        header('Location: ' . getPath('pages/login.php'));
        exit();
    }
}

/**
 * Require user to have admin role
 * 
 * Redirects to login page if not logged in, or to dashboard if not admin
 * 
 * @return void
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        // Load paths config if not already loaded
        if (!defined('BASE_PATH')) {
            require_once __DIR__ . '/../config/paths.php';
        }
        header('Location: ' . getPath('pages/dashboard.php'));
        exit();
    }
}

/**
 * Get current logged in user information
 * 
 * @return array|null User data array with id, username, and role, or null if not logged in
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Logout current user
 * 
 * Destroys session and redirects to login page
 * 
 * @return void
 */
function logout(): void {
    session_unset();
    session_destroy();
    // Load paths config if not already loaded
    if (!defined('BASE_PATH')) {
        require_once __DIR__ . '/../config/paths.php';
    }
    header('Location: ' . getPath('pages/login.php'));
    exit();
}
?>