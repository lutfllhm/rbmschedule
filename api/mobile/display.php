<?php
/**
 * Mobile API - Display board
 *
 * GET -> { success, schedules, server_time, timestamp }
 *
 * Mengikuti pages/display_32.php yang memakai status=active: seluruh
 * schedule Not Started & Running, Running tampil lebih dulu.
 * Dipakai untuk polling berkala di aplikasi.
 *
 * @package RBM\Schedule\Mobile
 */

require_once __DIR__ . '/_bootstrap.php';

[$user, $conn] = requireMobileUser();

$sql = "SELECT * FROM schedules WHERE status <> 'Finish'
    ORDER BY FIELD(status, 'Running', 'Not Started'), updated_at DESC, created_at DESC";

$result = $conn->query($sql);

$schedules = [];
$latestUpdate = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Papan display memakai label "Processing" untuk status Running,
        // sama seperti pages/display_32.php.
        if (($row['status'] ?? '') === 'Running') {
            $row['status'] = 'Processing';
        }
        $schedules[] = mobileScheduleRow($row);
        $rowUpdate = max(
            strtotime($row['created_at']),
            strtotime($row['updated_at'] ?? $row['created_at'])
        );
        if ($rowUpdate > $latestUpdate) {
            $latestUpdate = $rowUpdate;
        }
    }
}

// Statistik seluruh schedule untuk running text, mengikuti display_32.php.
$statusCounts = ['Not Started' => 0, 'Processing' => 0, 'Finish' => 0];
$totalSchedules = 0;
$statsResult = $conn->query("SELECT status, COUNT(*) as total FROM schedules GROUP BY status");
if ($statsResult) {
    while ($row = $statsResult->fetch_assoc()) {
        $status = $row['status'] ?? '';
        $count = (int) ($row['total'] ?? 0);
        $totalSchedules += $count;
        if ($status === 'Running') {
            $statusCounts['Processing'] = $count;
        } elseif (isset($statusCounts[$status])) {
            $statusCounts[$status] = $count;
        }
    }
}

closeDBConnection($conn);

mobileJson([
    'success' => true,
    'schedules' => $schedules,
    'ticker' => [
        'total' => $totalSchedules,
        'not_started' => $statusCounts['Not Started'],
        'processing' => $statusCounts['Processing'],
        'finish' => $statusCounts['Finish'],
    ],
    'server_time' => date('Y-m-d H:i:s'),
    'timestamp' => $latestUpdate ?: time(),
]);
