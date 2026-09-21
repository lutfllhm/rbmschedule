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

closeDBConnection($conn);

mobileJson([
    'success' => true,
    'schedules' => $schedules,
    'server_time' => date('Y-m-d H:i:s'),
    'timestamp' => $latestUpdate ?: time(),
]);
