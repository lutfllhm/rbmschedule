<?php
// Test database connection
require_once __DIR__ . '/config/database.php';

try {
    $conn = getDBConnection();
    if ($conn) {
        echo "✓ Database connected successfully\n";
        
        // Check if users table exists
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows > 0) {
            echo "✓ Table 'users' exists\n";
            
            // Check if there are users
            $result = $conn->query("SELECT COUNT(*) as count FROM users");
            $row = $result->fetch_assoc();
            echo "✓ Found " . $row['count'] . " user(s) in database\n";
        } else {
            echo "✗ Table 'users' not found. Please import database.sql\n";
        }
        
        closeDBConnection($conn);
    } else {
        echo "✗ Failed to connect to database\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
