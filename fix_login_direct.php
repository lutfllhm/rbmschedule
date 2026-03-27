<?php
/**
 * Direct Login Fix Script
 * Run this via command line: php fix_login_direct.php
 */

// Database configuration - adjust if needed
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'rbm_schedule';

echo "=== RBM Schedule Login Fix ===\n\n";

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error . "\n");
}

echo "✅ Database connected successfully\n\n";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "❌ Users table not found. Creating table...\n";
    
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'operator') NOT NULL DEFAULT 'operator',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "✅ Users table created\n";
    } else {
        die("❌ Failed to create users table: " . $conn->error . "\n");
    }
}

// Delete existing users
$conn->query("DELETE FROM users");
echo "✓ Cleared existing users\n";

// Create new users with correct password hashes
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$operator_password = password_hash('operator123', PASSWORD_DEFAULT);

// Insert admin
$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$username = 'admin';
$role = 'admin';
$stmt->bind_param("sss", $username, $admin_password, $role);

if ($stmt->execute()) {
    echo "✅ Admin user created\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n\n";
} else {
    echo "❌ Failed to create admin: " . $stmt->error . "\n";
}

// Insert operator
$username = 'operator';
$role = 'operator';
$stmt->bind_param("sss", $username, $operator_password, $role);

if ($stmt->execute()) {
    echo "✅ Operator user created\n";
    echo "   Username: operator\n";
    echo "   Password: operator123\n\n";
} else {
    echo "❌ Failed to create operator: " . $stmt->error . "\n";
}

$stmt->close();

// Verify users
echo "=== Verifying Users ===\n";
$result = $conn->query("SELECT id, username, role FROM users");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Username: {$row['username']}, Role: {$row['role']}\n";
}

// Test password verification
echo "\n=== Testing Password Verification ===\n";
$tests = [
    ['username' => 'admin', 'password' => 'admin123'],
    ['username' => 'operator', 'password' => 'operator123']
];

foreach ($tests as $test) {
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $test['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $match = password_verify($test['password'], $user['password']);
        echo ($match ? "✅" : "❌") . " {$test['username']}: " . ($match ? "OK" : "FAILED") . "\n";
    }
    $stmt->close();
}

$conn->close();

echo "\n=== Fix Completed ===\n";
echo "You can now login with:\n";
echo "- Admin: admin / admin123\n";
echo "- Operator: operator / operator123\n";
?>
