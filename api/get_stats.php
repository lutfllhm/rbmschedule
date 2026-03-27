<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$conn = getDBConnection();

// Aggregate counts grouped by status
$sql = "SELECT status, COUNT(*) as total FROM schedules GROUP BY status";
$result = $conn->query($sql);

$stats = [
    'total' => 0,
    'not started' => 0,
    'running' => 0,
    'finish' => 0
];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $status = strtolower($row['status']);
        $count = intval($row['total']);
        if (isset($stats[$status])) {
            $stats[$status] += $count;
        }
        $stats['total'] += $count;
    }
}

closeDBConnection($conn);

echo json_encode(['success' => true, 'stats' => $stats]);
exit();
?>