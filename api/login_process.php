<?php
/**
 * Login Process API
 * 
 * Handles user authentication
 * 
 * @package RBM\Schedule
 * @author RBM Development Team
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required files
require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getPath('pages/login.php?error=method'));
    exit();
}

// Verify CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    header('Location: ' . getPath('pages/login.php?error=csrf'));
    exit();
}

// Validate input
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($username) || empty($password)) {
    header('Location: ' . getPath('pages/login.php?error=required'));
    exit();
}

// Get database connection
try {
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if user exists and verify password
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Regenerate CSRF token
            regenerateCsrfToken();
            
            $stmt->close();
            closeDBConnection($conn);
            
            header('Location: ' . getPath('pages/dashboard.php'));
            exit();
        }
    }
    
    $stmt->close();
    closeDBConnection($conn);
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    header('Location: ' . getPath('pages/login.php?error=database'));
    exit();
}

// Invalid credentials
header('Location: ' . getPath('pages/login.php?error=invalid'));
exit();
?>