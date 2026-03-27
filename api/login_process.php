<?php
/**
 * Login Process API
 * 
 * Handles user authentication
 * 
 * @package RBM\Schedule
 * @author RBM Development Team
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Check request method and CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getPath('pages/login.php?error=method'));
    exit();
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    if (class_exists('Logger')) {
        Logger::warning('CSRF token validation failed', ['ip' => getClientIP()]);
    }
    header('Location: ' . getPath('pages/login.php?error=csrf'));
    exit();
}

// Validate and sanitize input
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password)) {
    if (class_exists('Logger')) {
        Logger::warning('Login attempt with empty credentials', ['username' => $username]);
    }
    header('Location: ' . getPath('pages/login.php?error=required'));
    exit();
}

// Sanitize username
$username = filter_var($username, FILTER_SANITIZE_STRING);

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    if (class_exists('Logger')) {
        Logger::error('Database connection failed during login');
    }
    header('Location: ' . getPath('pages/login.php?error=database'));
    exit();
}

// Prepare and execute query
$stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
if (!$stmt) {
    if (class_exists('Logger')) {
        Logger::error('Failed to prepare login statement', ['error' => $conn->error]);
    }
    closeDBConnection($conn);
    header('Location: ' . getPath('pages/login.php?error=database'));
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        // Regenerate CSRF token for security
        regenerateCsrfToken();
        
        // Log successful login
        if (class_exists('Logger')) {
            Logger::info('User logged in successfully', [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]);
        }
        
        $stmt->close();
        closeDBConnection($conn);
        
        header('Location: ' . getPath('pages/dashboard.php'));
        exit();
    } else {
        // Log failed login attempt
        if (class_exists('Logger')) {
            Logger::warning('Failed login attempt - invalid password', [
                'username' => $username,
                'ip' => getClientIP()
            ]);
        }
    }
} else {
    // Log failed login attempt - user not found
    if (class_exists('Logger')) {
        Logger::warning('Failed login attempt - user not found', [
            'username' => $username,
            'ip' => getClientIP()
        ]);
    }
}

$stmt->close();
closeDBConnection($conn);

// Redirect with error (don't reveal if username exists)
header('Location: ' . getPath('pages/login.php?error=invalid'));
exit();
?>