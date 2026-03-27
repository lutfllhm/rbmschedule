<?php
/**
 * Reset Users Script
 * Script untuk reset user dan password ke default
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>RBM Schedule - Reset Users</h2>";
echo "<hr>";

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    echo "❌ Database connection failed!<br>";
    exit();
}

echo "<h3>Resetting users to default...</h3>";

// Delete existing users
$conn->query("DELETE FROM users");
echo "✓ Cleared existing users<br>";

// Insert default users with correct password hashes
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$operator_password = password_hash('operator123', PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");

// Insert admin
$username = 'admin';
$role = 'admin';
$stmt->bind_param("sss", $username, $admin_password, $role);
if ($stmt->execute()) {
    echo "✅ Admin user created successfully<br>";
    echo "   Username: <strong>admin</strong><br>";
    echo "   Password: <strong>admin123</strong><br>";
} else {
    echo "❌ Failed to create admin user: " . $stmt->error . "<br>";
}

// Insert operator
$username = 'operator';
$role = 'operator';
$stmt->bind_param("sss", $username, $operator_password, $role);
if ($stmt->execute()) {
    echo "✅ Operator user created successfully<br>";
    echo "   Username: <strong>operator</strong><br>";
    echo "   Password: <strong>operator123</strong><br>";
} else {
    echo "❌ Failed to create operator user: " . $stmt->error . "<br>";
}

$stmt->close();

echo "<hr>";

// Verify the users
echo "<h3>Verifying users...</h3>";
$result = $conn->query("SELECT id, username, role FROM users ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>Total users: <strong>" . $result->num_rows . "</strong><br>";
}

echo "<hr>";

// Test password verification
echo "<h3>Testing password verification...</h3>";
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
        }
    }
    $stmt->close();
}

closeDBConnection($conn);

echo "<hr>";
echo "<h3>✅ Users reset completed!</h3>";
echo "<p>You can now login with:</p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> username: admin, password: admin123</li>";
echo "<li><strong>Operator:</strong> username: operator, password: operator123</li>";
echo "</ul>";
echo "<p><a href='pages/login.php'>Go to Login Page</a></p>";
echo "<p><a href='check_database.php'>Check Database Status</a></p>";
?>
