<?php
// Debug database connection
echo "<h2>Database Connection Debug</h2>";

// Load .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "<p>✓ .env file found</p>";
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', trim($line), 2);
        putenv($name . '=' . $value);
    }
} else {
    echo "<p>✗ .env file not found</p>";
}

// Show config
echo "<h3>Configuration:</h3>";
echo "<pre>";
echo "DB_HOST: " . getenv('DB_HOST') . "\n";
echo "DB_USER: " . getenv('DB_USER') . "\n";
echo "DB_PASS: " . (getenv('DB_PASS') ? '***' : 'empty') . "\n";
echo "DB_NAME: " . getenv('DB_NAME') . "\n";
echo "</pre>";

// Try connection
echo "<h3>Connection Test:</h3>";
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db = getenv('DB_NAME') ?: 'rbm_schedule';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        echo "<p style='color:red'>✗ Connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color:green'>✓ Connected successfully!</p>";
        
        // Test query
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p style='color:green'>✓ Found " . $row['count'] . " users in database</p>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Exception: " . $e->getMessage() . "</p>";
}
?>
