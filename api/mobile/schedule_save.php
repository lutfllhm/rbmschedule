<?php
/**
 * Mobile API - Tambah / ubah schedule (Admin)
 *
 * POST { schedule_id?, spk, nama_barang, qty_order, customer,
 *        op_cetak?, tanggal_mulai_cetak?, op_slitting?,
 *        tanggal_mulai_slitting?, status?, catatan? }
 * -> { success, message, schedule, action }
 *
 * Aturan validasi mengikuti api/schedule_ajax.php (action=save_schedule).
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
$spk = isset($input['spk']) ? trim($input['spk']) : '';
$nama_barang = isset($input['nama_barang']) ? trim($input['nama_barang']) : '';
$qty_order = isset($input['qty_order']) ? intval($input['qty_order']) : 0;
$customer = isset($input['customer']) ? trim($input['customer']) : '';
$op_cetak = !empty($input['op_cetak']) ? trim($input['op_cetak']) : null;
$tanggal_mulai_cetak = !empty($input['tanggal_mulai_cetak']) ? $input['tanggal_mulai_cetak'] : null;
$op_slitting = !empty($input['op_slitting']) ? trim($input['op_slitting']) : null;
$tanggal_mulai_slitting = !empty($input['tanggal_mulai_slitting']) ? $input['tanggal_mulai_slitting'] : null;
$status = isset($input['status']) ? $input['status'] : 'Not Started';
$catatan = isset($input['catatan']) && trim($input['catatan']) !== '' ? trim($input['catatan']) : null;

if ($spk === '' || $nama_barang === '' || $qty_order <= 0 || $customer === '') {
    closeDBConnection($conn);
    mobileFail('Data tidak lengkap', 422);
}

if (!in_array($status, ['Not Started', 'Running', 'Finish'], true)) {
    $status = 'Not Started';
}

// Slitting tidak boleh mendahului cetak.
if ($tanggal_mulai_cetak && $tanggal_mulai_slitting) {
    if (strtotime($tanggal_mulai_slitting) < strtotime($tanggal_mulai_cetak)) {
        closeDBConnection($conn);
        mobileFail('Tanggal mulai slitting harus setelah tanggal mulai cetak', 422);
    }
}

if ($schedule_id > 0) {
    // --- Update ---
    $currentStmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
    $currentStmt->bind_param("i", $schedule_id);
    $currentStmt->execute();
    $currentSchedule = $currentStmt->get_result()->fetch_assoc();
    $currentStmt->close();

    if (!$currentSchedule) {
        closeDBConnection($conn);
        mobileFail('Schedule tidak ditemukan', 404);
    }

    if ($spk !== $currentSchedule['spk']) {
        $checkStmt = $conn->prepare("SELECT id FROM schedules WHERE spk = ? AND id != ?");
        $checkStmt->bind_param("si", $spk, $schedule_id);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();

        if ($exists) {
            closeDBConnection($conn);
            mobileFail('SPK "' . $spk . '" sudah digunakan. Silakan gunakan SPK yang berbeda.', 409);
        }
    }

    $stmt = $conn->prepare("UPDATE schedules SET spk = ?, nama_barang = ?, qty_order = ?, customer = ?, op_cetak = ?, tanggal_mulai_cetak = ?, op_slitting = ?, tanggal_mulai_slitting = ?, status = ?, catatan = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssisssssssi", $spk, $nama_barang, $qty_order, $customer, $op_cetak, $tanggal_mulai_cetak, $op_slitting, $tanggal_mulai_slitting, $status, $catatan, $schedule_id);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        closeDBConnection($conn);
        error_log('Mobile schedule update error: ' . $err);
        mobileFail('Terjadi kesalahan saat menyimpan data. Silakan coba lagi.', 500);
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $schedule = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    logScheduleActivity(
        $conn,
        $schedule_id,
        'updated',
        "Schedule {$schedule['spk']} diperbarui oleh admin (mobile)",
        [
            'before' => mobileScheduleRow($currentSchedule),
            'after' => mobileScheduleRow($schedule),
        ]
    );

    closeDBConnection($conn);
    mobileJson([
        'success' => true,
        'message' => 'Schedule berhasil diupdate!',
        'schedule' => mobileScheduleRow($schedule),
        'action' => 'updated',
    ]);
}

// --- Create ---
$checkStmt = $conn->prepare("SELECT id FROM schedules WHERE spk = ?");
$checkStmt->bind_param("s", $spk);
$checkStmt->execute();
$exists = $checkStmt->get_result()->num_rows > 0;
$checkStmt->close();

if ($exists) {
    closeDBConnection($conn);
    mobileFail('SPK "' . $spk . '" sudah digunakan. Silakan gunakan SPK yang berbeda.', 409);
}

$stmt = $conn->prepare("INSERT INTO schedules (spk, nama_barang, qty_order, customer, op_cetak, tanggal_mulai_cetak, op_slitting, tanggal_mulai_slitting, status, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssisssssss", $spk, $nama_barang, $qty_order, $customer, $op_cetak, $tanggal_mulai_cetak, $op_slitting, $tanggal_mulai_slitting, $status, $catatan);

if (!$stmt->execute()) {
    $errno = $stmt->errno;
    $err = $stmt->error;
    $stmt->close();
    closeDBConnection($conn);

    if ($errno == 1062 || strpos($err, 'Duplicate entry') !== false) {
        mobileFail('SPK "' . $spk . '" sudah digunakan. Silakan gunakan SPK yang berbeda.', 409);
    }
    error_log('Mobile schedule create error: ' . $err);
    mobileFail('Terjadi kesalahan saat menyimpan data. Silakan coba lagi.', 500);
}

$new_id = $conn->insert_id;
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->bind_param("i", $new_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();
$stmt->close();

logScheduleActivity(
    $conn,
    $new_id,
    'created',
    "Schedule {$schedule['spk']} dibuat oleh admin (mobile)",
    ['after' => mobileScheduleRow($schedule)]
);

closeDBConnection($conn);
mobileJson([
    'success' => true,
    'message' => 'Schedule berhasil ditambahkan!',
    'schedule' => mobileScheduleRow($schedule),
    'action' => 'created',
]);
