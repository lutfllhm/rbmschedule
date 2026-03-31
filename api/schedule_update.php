<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . getPath('pages/dashboard.php'));
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;

if ($schedule_id <= 0) {
    header('Location: ' . getPath('pages/dashboard.php?error=invalid'));
    exit();
}

$conn = getDBConnection();

// Operator update
if ($action === 'operator_update' && isOperator()) {
    $op_cetak = isset($_POST['op_cetak']) ? trim($_POST['op_cetak']) : null;
    $op_slitting = isset($_POST['op_slitting']) ? trim($_POST['op_slitting']) : null;
    $mark_finish = isset($_POST['mark_finish']) ? true : false;
    
    // Get current schedule data
    $stmt = $conn->prepare("SELECT op_cetak, op_slitting, status FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current = $result->fetch_assoc();
    $stmt->close();
    
    if (!$current) {
        closeDBConnection($conn);
        header('Location: ' . getPath('pages/dashboard.php?error=notfound'));
        exit();
    }
    
    $tanggal_mulai_cetak = null;
    $tanggal_mulai_slitting = null;
    $status = $current['status'];
    
    // Auto-fill tanggal_mulai_cetak if op_cetak is being filled for the first time
    if (!empty($op_cetak) && empty($current['op_cetak'])) {
        $tanggal_mulai_cetak = date('Y-m-d H:i:s');
        $status = 'Running';
    }
    
    // Auto-fill tanggal_mulai_slitting if op_slitting is being filled for the first time
    if (!empty($op_slitting) && empty($current['op_slitting'])) {
        $tanggal_mulai_slitting = date('Y-m-d H:i:s');
    }
    
    // Mark as finish if checkbox is checked
    if ($mark_finish) {
        $status = 'Finish';
    }
    
    // Build update query
    $updates = [];
    $types = "";
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
    
    $types .= "i";
    $values[] = $schedule_id;
    
    $sql = "UPDATE schedules SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDBConnection($conn);
        header('Location: ' . getPath('pages/dashboard.php?success=updated'));
        exit();
    }
    
    $stmt->close();
    closeDBConnection($conn);
    header('Location: ' . getPath('pages/dashboard.php?error=failed'));
    exit();
}

// Admin update (handled by schedule_create.php)
if (isAdmin()) {
    header('Location: ' . getPath('api/schedule_create.php'));
    exit();
}

closeDBConnection($conn);
header('Location: ' . getPath('pages/dashboard.php?error=unauthorized'));
exit();
?>