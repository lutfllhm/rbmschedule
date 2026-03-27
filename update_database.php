<?php
require_once __DIR__ . '/config/database.php';

echo "Starting database update...\n";

$conn = getDBConnection();

// Check if updated_at column exists
$result = $conn->query("SHOW COLUMNS FROM schedules LIKE 'updated_at'");

if ($result->num_rows == 0) {
    echo "Adding updated_at column...\n";
    
    // Add updated_at column
    $sql = "ALTER TABLE schedules 
            ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP";
    
    if ($conn->query($sql)) {
        echo "✓ Column updated_at added successfully\n";
        
        // Set initial values
        $sql = "UPDATE schedules SET updated_at = created_at WHERE updated_at IS NULL";
        if ($conn->query($sql)) {
            echo "✓ Initial values set successfully\n";
        } else {
            echo "✗ Error setting initial values: " . $conn->error . "\n";
        }
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "✓ Column updated_at already exists\n";
}

closeDBConnection($conn);

echo "\nDatabase update completed!\n";
echo "\nYou can now use the real-time sync feature.\n";
echo "Changes made on one computer will automatically appear on other computers within 3 seconds.\n";
?>