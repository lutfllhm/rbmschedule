<?php
/**
 * Database Configuration
 * 
 * Handles database connections and configuration
 * 
 * @package RBM\Schedule
 * @author RBM Development Team
 * @version 1.0.0
 */

// Set timezone ke Indonesia (WIB - Waktu Indonesia Barat)
date_default_timezone_set('Asia/Jakarta');

// Load autoloader
require_once __DIR__ . '/autoload.php';

// Load .env values (if available) for non-container deployments.
$envFile = __DIR__ . '/../.env';
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $trimmed, 2);
        $name = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        // Remove wrapping quotes if present.
        if (
            (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

/**
 * Read environment value with fallback.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function envValue(string $key, $default = null) {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

// Database configuration
define('DB_HOST', envValue('DB_HOST', 'localhost'));
define('DB_USER', envValue('DB_USER', 'root'));
define('DB_PASS', envValue('DB_PASS', ''));
define('DB_NAME', envValue('DB_NAME', 'rbm_schedule'));

// Development mode (set DEBUG_MODE=false in production)
define('DEBUG_MODE', filter_var(envValue('DEBUG_MODE', 'true'), FILTER_VALIDATE_BOOLEAN));

/**
 * Get database connection
 * 
 * Creates and returns a MySQLi connection with proper error handling
 * 
 * @return mysqli|null Database connection or null on failure
 * @throws Exception If connection fails
 */
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            $errorMsg = "Database connection failed: " . $conn->connect_error;
            
            // Log error
            if (class_exists('Logger')) {
                Logger::error($errorMsg, [
                    'host' => DB_HOST,
                    'database' => DB_NAME
                ]);
            } else {
                error_log($errorMsg);
            }
            
            throw new Exception("Koneksi database gagal. Silakan hubungi administrator.");
        }
        
        // Set charset to UTF-8
        $conn->set_charset("utf8mb4");
        
        // Set timezone untuk MySQL
        $conn->query("SET time_zone = '+07:00'");
        
        // Set SQL mode for better compatibility
        $conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        
        return $conn;
    } catch (Exception $e) {
        $errorMsg = "Database error: " . $e->getMessage();
        
        // Log error
        if (class_exists('Logger')) {
            Logger::error($errorMsg);
        } else {
            error_log($errorMsg);
        }
        
        // In production, don't expose detailed error messages
        if (!DEBUG_MODE) {
            return null;
        }
        
        throw $e;
    }
}

/**
 * Close database connection
 * 
 * @param mysqli|null $conn Database connection to close
 */
function closeDBConnection($conn) {
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
}

/**
 * Execute prepared statement with error handling
 * 
 * @param mysqli $conn Database connection
 * @param string $sql SQL query with placeholders
 * @param string $types Parameter types
 * @param array $params Parameters to bind
 * @return mysqli_result|bool Query result or false on failure
 */
function executeQuery($conn, string $sql, string $types = '', array $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        if (class_exists('Logger')) {
            Logger::error("Query execution failed", [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
        }
        
        return false;
    }
}
?>