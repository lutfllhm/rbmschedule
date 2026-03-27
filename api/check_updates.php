<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

require_once __DIR__ . '/../config/database.php';
// Display mode doesn't require login
// require_once __DIR__ . '/../includes/auth.php';
// requireLogin();

$lastCheck = isset($_GET['last_check']) ? intval($_GET['last_check']) : 0;
$lastCount = isset($_GET['last_count']) ? intval($_GET['last_count']) : null;

$conn = getDBConnection();

// Get the latest modification time from schedules
$sql = "SELECT MAX(GREATEST(
    UNIX_TIMESTAMP(created_at),
    COALESCE(UNIX_TIMESTAMP(updated_at), UNIX_TIMESTAMP(created_at))
)) as latest_update FROM schedules";

$result = $conn->query($sql);
$row = $result->fetch_assoc();
// If no schedules exist, use current time to ensure deletions are detected
$latestUpdate = intval($row['latest_update'] ?? time());

// Get count for detecting deletions
$countSql = "SELECT COUNT(*) as total FROM schedules";
$countResult = $conn->query($countSql);
$countRow = $countResult->fetch_assoc();
$totalSchedules = intval($countRow['total']);

closeDBConnection($conn);

// Check for updates: timestamp changed OR count changed (detects deletions)
$hasUpdates = ($latestUpdate > $lastCheck);
if ($lastCount !== null && $totalSchedules !== $lastCount) {
    $hasUpdates = true; // Count changed, means schedule was added or deleted
}

// Log for debugging (optional - can be removed in production)
error_log("Check Updates - Last Check: $lastCheck, Latest Update: $latestUpdate, Has Updates: " . ($hasUpdates ? 'YES' : 'NO') . ", Total Schedules: $totalSchedules");

echo json_encode([
    'success' => true,
    'has_updates' => $hasUpdates,
    'timestamp' => $latestUpdate,
    'last_check' => $lastCheck,
    'total_schedules' => $totalSchedules,
    'last_count' => $lastCount,
    'debug' => [
        'last_check_time' => date('Y-m-d H:i:s', $lastCheck),
        'latest_update_time' => date('Y-m-d H:i:s', $latestUpdate),
        'difference_seconds' => $latestUpdate - $lastCheck,
        'count_changed' => ($lastCount !== null && $totalSchedules !== $lastCount)
    ]
]);
exit();
?>