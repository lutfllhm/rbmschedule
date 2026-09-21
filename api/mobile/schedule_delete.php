<?php
/**
 * Mobile API - Hapus schedule (Admin)
 *
 * POST { schedule_id } -> { success, message }
 *
 * @package RBM\Schedule\Mobile
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobileFail('Metode request tidak valid.', 405);
}

[$user, $conn] = requireMobileUser();
requireMobileAdmin($user, $conn);

$input = mobileInput();
$schedule_id = isset($input['schedule_id']) ? intval($input['schedule_id']) : 0;

if ($schedule_id <= 0) {
    closeDBConnection($conn);
    mobileFail('ID schedule tidak valid', 422);
}

$stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$schedule) {
    closeDBConnection($conn);
    mobileFail('Schedule tidak ditemukan', 404);
}

$stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
$stmt->bind_param("i", $schedule_id);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    closeDBConnection($conn);
    error_log('Mobile schedule delete error: ' . $err);
    mobileFail('Gagal menghapus schedule dari database', 500);
}
$stmt->close();

try {
    logScheduleActivity(
        $conn,
        $schedule_id,
        'deleted',
        "Schedule {$schedule['spk']} dihapus (mobile)",
        ['before' => mobileScheduleRow($schedule)]
    );
} catch (Exception $e) {
    // Gagal mencatat log tidak boleh membatalkan penghapusan.
    error_log('Log activity error: ' . $e->getMessage());
}

closeDBConnection($conn);
mobileJson(['success' => true, 'message' => 'Schedule berhasil dihapus!']);
