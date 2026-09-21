<?php
/**
 * Mobile API - Tandai schedule selesai (Admin & Operator)
 *
 * POST { schedule_id } -> { success, message, schedule }
 *
 * @package RBM\Schedule\Mobile
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobileFail('Metode request tidak valid.', 405);
}

[$user, $conn] = requireMobileUser();

if (!in_array($user['role'], ['admin', 'operator'], true)) {
    closeDBConnection($conn);
    mobileFail('Akses ditolak.', 403);
}

$input = mobileInput();
$schedule_id = isset($input['schedule_id']) ? intval($input['schedule_id']) : 0;

if ($schedule_id <= 0) {
    closeDBConnection($conn);
    mobileFail('ID schedule tidak valid', 422);
}

$stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$before = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$before) {
    closeDBConnection($conn);
    mobileFail('Schedule tidak ditemukan', 404);
}

$stmt = $conn->prepare("UPDATE schedules SET status = 'Finish', updated_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $schedule_id);

if (!$stmt->execute()) {
    $stmt->close();
    closeDBConnection($conn);
    mobileFail('Gagal mengubah status ke Finish', 500);
}
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($before['status'] !== 'Finish') {
    logScheduleActivity(
        $conn,
        $schedule_id,
        'status_finish',
        "Schedule {$schedule['spk']} ditandai Finish (mobile)",
        ['before' => mobileScheduleRow($before), 'after' => mobileScheduleRow($schedule)]
    );
}

closeDBConnection($conn);
mobileJson([
    'success' => true,
    'message' => 'Status berhasil diubah menjadi Finish!',
    'schedule' => mobileScheduleRow($schedule),
]);
