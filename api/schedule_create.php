<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /rbmschedule/pages/manage.php');
    exit();
}

$schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
$spk = isset($_POST['spk']) ? trim($_POST['spk']) : '';
$nama_barang = isset($_POST['nama_barang']) ? trim($_POST['nama_barang']) : '';
$qty_order = isset($_POST['qty_order']) ? intval($_POST['qty_order']) : 0;
$customer = isset($_POST['customer']) ? trim($_POST['customer']) : '';
$op_cetak = isset($_POST['op_cetak']) ? trim($_POST['op_cetak']) : null;
$tanggal_mulai_cetak = isset($_POST['tanggal_mulai_cetak']) && !empty($_POST['tanggal_mulai_cetak']) ? $_POST['tanggal_mulai_cetak'] : null;
$op_slitting = isset($_POST['op_slitting']) ? trim($_POST['op_slitting']) : null;
$tanggal_mulai_slitting = isset($_POST['tanggal_mulai_slitting']) && !empty($_POST['tanggal_mulai_slitting']) ? $_POST['tanggal_mulai_slitting'] : null;
$status = isset($_POST['status']) ? $_POST['status'] : 'Not Started';
$catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : null;

if (empty($spk) || empty($nama_barang) || $qty_order <= 0 || empty($customer)) {
    header('Location: /rbmschedule/pages/manage.php?error=required');
    exit();
}

$conn = getDBConnection();

if ($schedule_id > 0) {
    // Update existing schedule
    $stmt = $conn->prepare("UPDATE schedules SET spk = ?, nama_barang = ?, qty_order = ?, customer = ?, op_cetak = ?, tanggal_mulai_cetak = ?, op_slitting = ?, tanggal_mulai_slitting = ?, status = ?, catatan = ? WHERE id = ?");
    $stmt->bind_param("ssisssssssi", $spk, $nama_barang, $qty_order, $customer, $op_cetak, $tanggal_mulai_cetak, $op_slitting, $tanggal_mulai_slitting, $status, $catatan, $schedule_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDBConnection($conn);
        header('Location: /rbmschedule/pages/manage.php?success=updated');
        exit();
    }
} else {
    // Create new schedule
    $stmt = $conn->prepare("INSERT INTO schedules (spk, nama_barang, qty_order, customer, op_cetak, tanggal_mulai_cetak, op_slitting, tanggal_mulai_slitting, status, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssssss", $spk, $nama_barang, $qty_order, $customer, $op_cetak, $tanggal_mulai_cetak, $op_slitting, $tanggal_mulai_slitting, $status, $catatan);
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDBConnection($conn);
        header('Location: /rbmschedule/pages/manage.php?success=created');
        exit();
    }
}

$stmt->close();
closeDBConnection($conn);
header('Location: /rbmschedule/pages/manage.php?error=failed');
exit();
?>