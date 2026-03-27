<?php
/**
 * Database Check Script
 * Script untuk memeriksa koneksi database dan user yang ada
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>RBM Schedule - Database Check</h2>";
echo "<hr>";

// 1. Check database connection
echo "<h3>1. Database Connection</h3>";
$conn = getDBConnection();
if ($conn) {
    echo "✅ Database connection: <strong>SUCCESS</strong><br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Database: " . DB_NAME . "<br>";
} else {
    echo "❌ Database connection: <strong>FAILED</strong><br>";
    echo "Please check your database configuration in config/database.php<br>";
    exit();
}

echo "<hr>";

// 2. Check if users table exists
echo "<h3>2. Users Table</h3>";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows > 0) {
    echo "✅ Users table: <strong>EXISTS</strong><br>";
} else {
    echo "❌ Users table: <strong>NOT FOUND</strong><br>";
    echo "Please import database.sql file<br>";
    closeDBConnection($conn);
    exit();
}

echo "<hr>";

// 3. Check users in database
echo "<h3>3. Users in Database</h3>";
$result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>Total users: <strong>" . $result->num_rows . "</strong><br>";
} else {
    echo "❌ No users found in database<br>";
    echo "Database might not be properly initialized<br>";
}

echo "<hr>";

// 4. Test password verification
echo "<h3>4. Password Verification Test</h3>";
$testUsers = [
    ['username' => 'admin', 'password' => 'admin123'],
    ['username' => 'operator', 'password' => 'operator123']
];

foreach ($testUsers as $testUser) {
    $stmt = $conn->prepare("SELECT username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $testUser['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $passwordMatch = password_verify($testUser['password'], $user['password']);
        
        if ($passwordMatch) {
            echo "✅ User '<strong>" . htmlspecialchars($testUser['username']) . "</strong>': Password verification <strong>SUCCESS</strong><br>";
        } else {
            echo "❌ User '<strong>" . htmlspecialchars($testUser['username']) . "</strong>': Password verification <strong>FAILED</strong><br>";
            echo "Stored hash: " . htmlspecialchars(substr($user['password'], 0, 30)) . "...<br>";
        }
    } else {
        echo "❌ User '<strong>" . htmlspecialchars($testUser['username']) . "</strong>': <strong>NOT FOUND</strong><br>";
    }
    $stmt->close();
}

echo "<hr>";

// 5. Check schedules table
echo "<h3>5. Schedules Table</h3>";
$result = $conn->query("SHOW TABLES LIKE 'schedules'");
if ($result && $result->num_rows > 0) {
    echo "✅ Schedules table: <strong>EXISTS</strong><br>";
    
    $countResult = $conn->query("SELECT COUNT(*) as total FROM schedules");
    if ($countResult) {
        $count = $countResult->fetch_assoc();
        echo "Total schedules: <strong>" . $count['total'] . "</strong><br>";
    }
} else {
    echo "❌ Schedules table: <strong>NOT FOUND</strong><br>";
}

echo "<hr>";

// 6. Check schedule_logs table
echo "<h3>6. Schedule Logs Table</h3>";
$result = $conn->query("SHOW TABLES LIKE 'schedule_logs'");
if ($result && $result->num_rows > 0) {
    echo "✅ Schedule logs table: <strong>EXISTS</strong><br>";
} else {
    echo "❌ Schedule logs table: <strong>NOT FOUND</strong><br>";
}

echo "<hr>";

// 7. PHP Configuration
echo "<h3>7. PHP Configuration</h3>";
echo "PHP Version: <strong>" . phpversion() . "</strong><br>";
echo "Session support: <strong>" . (function_exists('session_start') ? 'YES' : 'NO') . "</strong><br>";
echo "MySQLi extension: <strong>" . (extension_loaded('mysqli') ? 'YES' : 'NO') . "</strong><br>";
echo "Password hashing: <strong>" . (function_exists('password_hash') ? 'YES' : 'NO') . "</strong><br>";

echo "<hr>";

closeDBConnection($conn);

echo "<h3>✅ Database check completed!</h3>";
echo "<p><a href='pages/login.php'>Go to Login Page</a></p>";
?>
