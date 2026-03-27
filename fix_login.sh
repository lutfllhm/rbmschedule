#!/bin/bash

echo "=== RBM Schedule Login Fix Script ==="
echo ""

# Find the web root directory
if [ -d "/var/www/html" ]; then
    WEB_ROOT="/var/www/html"
elif [ -d "/var/www" ]; then
    WEB_ROOT="/var/www"
else
    echo "❌ Cannot find web root directory"
    exit 1
fi

echo "Web root: $WEB_ROOT"
echo ""

# Check if we're in the right directory
if [ ! -f "config/database.php" ]; then
    echo "❌ Not in RBM Schedule directory"
    echo "Please cd to your RBM Schedule installation directory first"
    exit 1
fi

echo "✅ Found RBM Schedule installation"
echo ""

# Create the fix script
cat > fix_users_now.php << 'EOFPHP'
<?php
// Quick fix for login issue
$conn = new mysqli('localhost', 'root', '', 'rbm_schedule');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database\n";

// Clear and recreate users
$conn->query("DELETE FROM users");
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$operator_pass = password_hash('operator123', PASSWORD_DEFAULT);

$conn->query("INSERT INTO users (username, password, role) VALUES ('admin', '$admin_pass', 'admin')");
$conn->query("INSERT INTO users (username, password, role) VALUES ('operator', '$operator_pass', 'operator')");

echo "Users created:\n";
echo "- admin / admin123\n";
echo "- operator / operator123\n";

$conn->close();
EOFPHP

echo "Running fix script..."
php fix_users_now.php

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Fix completed successfully!"
    echo ""
    echo "You can now login with:"
    echo "  Username: admin"
    echo "  Password: admin123"
    echo ""
    echo "  Username: operator"
    echo "  Password: operator123"
    echo ""
    
    # Clean up
    rm -f fix_users_now.php
    echo "Cleanup done."
else
    echo ""
    echo "❌ Fix failed. Please check database configuration."
fi
