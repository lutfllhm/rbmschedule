<?php
/**
 * Mobile API - Update oleh operator
 *
 * POST { schedule_id, op_cetak?, op_slitting?, mark_finish? }
 * -> { success, message, schedule }
 *
 * Mengikuti api/schedule_ajax.php (action=operator_update): tanggal mulai
 * terisi otomatis dengan waktu WIB saat operator pertama kali diisi, dan
 * status naik ke Running.
 *
 * @package RBM\Schedule\Mobile
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobileFail('Metode request tidak valid.', 405);
}

[$user, $conn] = requireMobileUser();

if ($user['role'] !== 'operator') {
    closeDBConnection($conn);
    mobileFail('Aksi ini hanya untuk operator.', 403);
}

$input = mobileInput();
$schedule_id = isset($input['schedule_id']) ? intval($input['schedule_id']) : 0;
$op_cetak = isset($input['op_cetak']) ? trim($input['op_cetak']) : null;
$op_slitting = isset($input['op_slitting']) ? trim($input['op_slitting']) : null;
$mark_finish = !empty($input['mark_finish']);

if ($schedule_id <= 0) {
    closeDBConnection($conn);
    mobileFail('ID schedule tidak valid', 422);
}

$stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current) {
    closeDBConnection($conn);
    mobileFail('Schedule tidak ditemukan', 404);
}

$tanggal_mulai_cetak = null;
$tanggal_mulai_slitting = null;
$status = $current['status'];

// Isi tanggal otomatis saat operator cetak diisi pertama kali.
if (!empty($op_cetak) && empty($current['op_cetak'])) {
    $tanggal_mulai_cetak = date('Y-m-d H:i:s');
    $status = 'Running';
}

// Idem untuk slitting.
if (!empty($op_slitting) && empty($current['op_slitting'])) {
    $tanggal_mulai_slitting = date('Y-m-d H:i:s');
    if ($status === 'Not Started') {
        $status = 'Running';
    }
}

if ($mark_finish) {
    $status = 'Finish';
}

$finalTanggalCetak = $tanggal_mulai_cetak ?: $current['tanggal_mulai_cetak'];
$finalTanggalSlitting = $tanggal_mulai_slitting ?: $current['tanggal_mulai_slitting'];
if ($finalTanggalCetak && $finalTanggalSlitting) {
    if (strtotime($finalTanggalSlitting) < strtotime($finalTanggalCetak)) {
        closeDBConnection($conn);
        mobileFail('Tanggal mulai slitting harus setelah tanggal mulai cetak', 422);
    }
}

$updates = [];
$types = '';
$values = [];

if (!empty($op_cetak)) {
    $updates[] = "op_cetak = ?";
    $types .= "s";
    $values[] = $op_cetak;
}
if ($tanggal_mulai_cetak) {
    $updates[] = "tanggal_mulai_cetak = ?";
    $types .= "s";
    $values[] = $tanggal_mulai_cetak;
}
if (!empty($op_slitting)) {
    $updates[] = "op_slitting = ?";
    $types .= "s";
    $values[] = $op_slitting;
}
if ($tanggal_mulai_slitting) {
    $updates[] = "tanggal_mulai_slitting = ?";
    $types .= "s";
    $values[] = $tanggal_mulai_slitting;
}

$updates[] = "status = ?";
$types .= "s";
$values[] = $status;

$updates[] = "updated_at = NOW()";

$types .= "i";
$values[] = $schedule_id;

$stmt = $conn->prepare("UPDATE schedules SET " . implode(", ", $updates) . " WHERE id = ?");
$stmt->bind_param($types, ...$values);

if (!$stmt->execute()) {
    $stmt->close();
    closeDBConnection($conn);
    mobileFail('Gagal mengupdate schedule', 500);
}
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($mark_finish && $current['status'] !== 'Finish') {
    logScheduleActivity(
        $conn,
        $schedule_id,
        'status_finish',
        "Schedule {$schedule['spk']} ditandai Finish (mobile)",
        ['before' => mobileScheduleRow($current), 'after' => mobileScheduleRow($schedule)]
    );
} else {
    logScheduleActivity(
        $conn,
        $schedule_id,
        'operator_update',
        "Operator memperbarui schedule {$schedule['spk']} (mobile)",
        ['before' => mobileScheduleRow($current), 'after' => mobileScheduleRow($schedule)]
    );
}

closeDBConnection($conn);
mobileJson([
    'success' => true,
    'message' => 'Schedule berhasil diupdate!',
    'schedule' => mobileScheduleRow($schedule),
]);
