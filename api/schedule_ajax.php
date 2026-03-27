<?php
// Set timezone ke Indonesia
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    echo json_encode(['success' => false, 'message' => 'Permintaan tidak sah. Muat ulang halaman dan coba lagi.']);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$conn = getDBConnection();

// Create or Update Schedule (Admin)
if ($action === 'save_schedule' && isAdmin()) {
    $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    $spk = isset($_POST['spk']) ? trim($_POST['spk']) : '';
    $nama_barang = isset($_POST['nama_barang']) ? trim($_POST['nama_barang']) : '';
    $qty_order = isset($_POST['qty_order']) ? intval($_POST['qty_order']) : 0;
    $customer = isset($_POST['customer']) ? trim($_POST['customer']) : '';
    // Admin dapat mengelola semua field termasuk tanggal
    $op_cetak = isset($_POST['op_cetak']) && !empty($_POST['op_cetak']) ? trim($_POST['op_cetak']) : null;
    $tanggal_mulai_cetak = isset($_POST['tanggal_mulai_cetak']) && !empty($_POST['tanggal_mulai_cetak']) ? $_POST['tanggal_mulai_cetak'] : null;
    $op_slitting = isset($_POST['op_slitting']) && !empty($_POST['op_slitting']) ? trim($_POST['op_slitting']) : null;
    $tanggal_mulai_slitting = isset($_POST['tanggal_mulai_slitting']) && !empty($_POST['tanggal_mulai_slitting']) ? $_POST['tanggal_mulai_slitting'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'Not Started';
    $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : null;
    
    if (empty($spk) || empty($nama_barang) || $qty_order <= 0 || empty($customer)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        closeDBConnection($conn);
        exit();
    }
    
    // Validasi tanggal: tanggal_mulai_slitting harus setelah tanggal_mulai_cetak
    if ($tanggal_mulai_cetak && $tanggal_mulai_slitting) {
        $dateCetak = strtotime($tanggal_mulai_cetak);
        $dateSlitting = strtotime($tanggal_mulai_slitting);
        if ($dateSlitting < $dateCetak) {
            echo json_encode(['success' => false, 'message' => 'Tanggal mulai slitting harus setelah tanggal mulai cetak']);
            closeDBConnection($conn);
            exit();
        }
    }
    
    // Catatan: Validasi dinonaktifkan - OP Slitting boleh diisi tanpa OP Cetak
    
    if ($schedule_id > 0) {
        $currentSchedule = null;
        $currentStmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
        $currentStmt->bind_param("i", $schedule_id);
        $currentStmt->execute();
        $existingResult = $currentStmt->get_result();
        $currentSchedule = $existingResult->fetch_assoc();
        $currentStmt->close();
        if (!$currentSchedule) {
            closeDBConnection($conn);
            echo json_encode(['success' => false, 'message' => 'Schedule tidak ditemukan']);
            exit();
        }

        // Validasi duplikasi SPK (kecuali untuk schedule yang sama)
        if ($spk !== $currentSchedule['spk']) {
            $checkStmt = $conn->prepare("SELECT id FROM schedules WHERE spk = ? AND id != ?");
            $checkStmt->bind_param("si", $spk, $schedule_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows > 0) {
                $checkStmt->close();
                closeDBConnection($conn);
                echo json_encode(['success' => false, 'message' => 'SPK "' . htmlspecialchars($spk) . '" sudah digunakan. Silakan gunakan SPK yang berbeda.']);
                exit();
            }
            $checkStmt->close();
        }

        // Update existing schedule (semua field)
        $stmt = $conn->prepare("UPDATE schedules SET spk = ?, nama_barang = ?, qty_order = ?, customer = ?, op_cetak = ?, tanggal_mulai_cetak = ?, op_slitting = ?, tanggal_mulai_slitting = ?, status = ?, catatan = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssisssssssi", $spk, $nama_barang, $qty_order, $customer, $op_cetak, $tanggal_mulai_cetak, $op_slitting, $tanggal_mulai_slitting, $status, $catatan, $schedule_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Get updated schedule
            $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
            $stmt->bind_param("i", $schedule_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $schedule = $result->fetch_assoc();
            $stmt->close();

            // Log update activity
            logScheduleActivity(
                $conn,
                $schedule_id,
                'updated',
                "Schedule {$schedule['spk']} diperbarui oleh admin",
                [
                    'before' => prepareScheduleSnapshot($currentSchedule),
                    'after' => prepareScheduleSnapshot($schedule)
                ]
            );
            
            closeDBConnection($conn);
            echo json_encode(['success' => true, 'message' => 'Schedule berhasil diupdate!', 'schedule' => $schedule, 'action' => 'updated']);
            exit();
        }
    } else {
        // Create new schedule
        $stmt = $conn->prepare("INSERT INTO schedules (spk, nama_barang, qty_order, customer, op_cetak, tanggal_mulai_cetak, op_slitting, tanggal_mulai_slitting, status, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisssssss", $spk, $nama_barang, $qty_order, $customer, $op_cetak, $tanggal_mulai_cetak, $op_slitting, $tanggal_mulai_slitting, $status, $catatan);
        
        // Validasi duplikasi SPK untuk create
        $checkStmt = $conn->prepare("SELECT id FROM schedules WHERE spk = ?");
        $checkStmt->bind_param("s", $spk);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            $stmt->close();
            closeDBConnection($conn);
            echo json_encode(['success' => false, 'message' => 'SPK "' . htmlspecialchars($spk) . '" sudah digunakan. Silakan gunakan SPK yang berbeda.']);
            exit();
        }
        $checkStmt->close();
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            $stmt->close();
            
            // Get new schedule
            $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
            $stmt->bind_param("i", $new_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $schedule = $result->fetch_assoc();
            $stmt->close();
            
            // Log create activity
            logScheduleActivity(
                $conn,
                $new_id,
                'created',
                "Schedule {$schedule['spk']} dibuat oleh admin",
                ['after' => prepareScheduleSnapshot($schedule)]
            );
            
            closeDBConnection($conn);
            echo json_encode(['success' => true, 'message' => 'Schedule berhasil ditambahkan!', 'schedule' => $schedule, 'action' => 'created']);
            exit();
        }
    }
    
    $errorMsg = 'Gagal menyimpan schedule';
    if (isset($stmt) && $stmt->error) {
        // Check for duplicate SPK error
        if ($stmt->errno == 1062 || strpos($stmt->error, 'Duplicate entry') !== false) {
            $errorMsg = 'SPK "' . htmlspecialchars($spk) . '" sudah digunakan. Silakan gunakan SPK yang berbeda.';
        } else {
            error_log("Database error: " . $stmt->error);
            $errorMsg = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
        }
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
    closeDBConnection($conn);
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit();
}

// Operator Update
if ($action === 'operator_update' && isOperator()) {
    $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    $op_cetak = isset($_POST['op_cetak']) ? trim($_POST['op_cetak']) : null;
    $op_slitting = isset($_POST['op_slitting']) ? trim($_POST['op_slitting']) : null;
    $mark_finish = isset($_POST['mark_finish']) ? true : false;
    
    if ($schedule_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID schedule tidak valid']);
        closeDBConnection($conn);
        exit();
    }
    
    // Get current schedule data
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current = $result->fetch_assoc();
    $stmt->close();
    
    if (!$current) {
        closeDBConnection($conn);
        echo json_encode(['success' => false, 'message' => 'Schedule tidak ditemukan']);
        exit();
    }
    
    $tanggal_mulai_cetak = null;
    $tanggal_mulai_slitting = null;
    $status = $current['status'];
    
    // Catatan: Validasi dinonaktifkan - OP Slitting boleh diisi tanpa OP Cetak
    
    // Auto-fill tanggal_mulai_cetak if op_cetak is being filled for the first time
    // Menggunakan waktu Indonesia (WIB)
    if (!empty($op_cetak) && empty($current['op_cetak'])) {
        $tanggal_mulai_cetak = date('Y-m-d H:i:s'); // Waktu Indonesia
        $status = 'Running';
    }
    
    // Auto-fill tanggal_mulai_slitting if op_slitting is being filled for the first time
    // Menggunakan waktu Indonesia (WIB)
    if (!empty($op_slitting) && empty($current['op_slitting'])) {
        $tanggal_mulai_slitting = date('Y-m-d H:i:s'); // Waktu Indonesia
        if ($status === 'Not Started') {
            $status = 'Running';
        }
    }
    
    // Mark as finish if checkbox is checked
    if ($mark_finish) {
        $status = 'Finish';
    }
    
    // Validasi tanggal: tanggal_mulai_slitting harus setelah tanggal_mulai_cetak
    $finalTanggalCetak = $tanggal_mulai_cetak ? $tanggal_mulai_cetak : $current['tanggal_mulai_cetak'];
    $finalTanggalSlitting = $tanggal_mulai_slitting ? $tanggal_mulai_slitting : $current['tanggal_mulai_slitting'];
    if ($finalTanggalCetak && $finalTanggalSlitting) {
        $dateCetak = strtotime($finalTanggalCetak);
        $dateSlitting = strtotime($finalTanggalSlitting);
        if ($dateSlitting < $dateCetak) {
            echo json_encode(['success' => false, 'message' => 'Tanggal mulai slitting harus setelah tanggal mulai cetak']);
            closeDBConnection($conn);
            exit();
        }
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
    
    $updates[] = "updated_at = NOW()";
    
    $types .= "i";
    $values[] = $schedule_id;
    
    $sql = "UPDATE schedules SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Get updated schedule
        $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedule = $result->fetch_assoc();
        $stmt->close();

        // Log activity - check if status changed to Finish
        if ($mark_finish && $current['status'] !== 'Finish') {
            logScheduleActivity(
                $conn,
                $schedule_id,
                'status_finish',
                "Schedule {$schedule['spk']} ditandai Finish",
                [
                    'before' => prepareScheduleSnapshot($current),
                    'after' => prepareScheduleSnapshot($schedule)
                ]
            );
        } else {
            // Log general operator update
            logScheduleActivity(
                $conn,
                $schedule_id,
                'operator_update',
                "Operator memperbarui schedule {$schedule['spk']}",
                [
                    'before' => prepareScheduleSnapshot($current),
                    'after' => prepareScheduleSnapshot($schedule)
                ]
            );
        }
        
        closeDBConnection($conn);
        echo json_encode(['success' => true, 'message' => 'Schedule berhasil diupdate!', 'schedule' => $schedule]);
        exit();
    }
    
    $stmt->close();
    closeDBConnection($conn);
    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate schedule']);
    exit();
}

// Tandai finish (Admin maupun Operator)
if ($action === 'mark_finish' && (isAdmin() || isOperator())) {
    $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    
    if ($schedule_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID schedule tidak valid']);
        closeDBConnection($conn);
        exit();
    }
    
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $before = $result->fetch_assoc();
    $stmt->close();

    if (!$before) {
        closeDBConnection($conn);
        echo json_encode(['success' => false, 'message' => 'Schedule tidak ditemukan']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE schedules SET status = 'Finish', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $schedule_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Ambil data schedule terbaru
        $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedule = $result->fetch_assoc();
        $stmt->close();
        
        closeDBConnection($conn);
        echo json_encode(['success' => true, 'message' => 'Status berhasil diubah menjadi Finish!', 'schedule' => $schedule, 'action' => 'finished']);
        exit();
    }
    
    $stmt->close();
    closeDBConnection($conn);
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status ke Finish']);
    exit();
}

// Delete Schedule (Admin)
if ($action === 'delete_schedule' && isAdmin()) {
    $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    
    // Helper function untuk mengirim JSON response
    $sendJsonResponse = function($success, $message) {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit();
    };
    
    if ($schedule_id <= 0) {
        closeDBConnection($conn);
        $sendJsonResponse(false, 'ID schedule tidak valid');
    }
    
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
    if (!$stmt) {
        closeDBConnection($conn);
        $sendJsonResponse(false, 'Gagal mempersiapkan query: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $schedule_id);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        closeDBConnection($conn);
        $sendJsonResponse(false, 'Gagal mencari schedule: ' . $error);
    }
    
    $result = $stmt->get_result();
    $schedule = $result->fetch_assoc();
    $stmt->close();

    if (!$schedule) {
        closeDBConnection($conn);
        $sendJsonResponse(false, 'Schedule tidak ditemukan');
    }

    $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
    if (!$stmt) {
        closeDBConnection($conn);
        $sendJsonResponse(false, 'Gagal mempersiapkan query delete: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $schedule_id);
    
    if ($stmt->execute()) {
        $stmt->close();

        // Log activity jika fungsi tersedia
        if (function_exists('logScheduleActivity')) {
            try {
                logScheduleActivity(
                    $conn,
                    $schedule_id,
                    'deleted',
                    "Schedule {$schedule['spk']} dihapus",
                    ['before' => function_exists('prepareScheduleSnapshot') ? prepareScheduleSnapshot($schedule) : $schedule]
                );
            } catch (Exception $e) {
                // Log error tapi jangan gagalkan delete
                error_log('Log activity error: ' . $e->getMessage());
            }
        }

        closeDBConnection($conn);
        $sendJsonResponse(true, 'Schedule berhasil dihapus!');
    }
    
    $errorMsg = $stmt->error ? $stmt->error : 'Gagal menghapus schedule dari database';
    $stmt->close();
    closeDBConnection($conn);
    $sendJsonResponse(false, $errorMsg);
}

closeDBConnection($conn);
echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
exit();

function prepareScheduleSnapshot(?array $schedule): ?array {
    if (!$schedule) {
        return null;
    }

    return [
        'id' => $schedule['id'] ?? null,
        'spk' => $schedule['spk'] ?? null,
        'nama_barang' => $schedule['nama_barang'] ?? null,
        'qty_order' => $schedule['qty_order'] ?? null,
        'customer' => $schedule['customer'] ?? null,
        'op_cetak' => $schedule['op_cetak'] ?? null,
        'op_slitting' => $schedule['op_slitting'] ?? null,
        'tanggal_mulai_cetak' => $schedule['tanggal_mulai_cetak'] ?? null,
        'tanggal_mulai_slitting' => $schedule['tanggal_mulai_slitting'] ?? null,
        'status' => $schedule['status'] ?? null,
        'catatan' => $schedule['catatan'] ?? null,
        'created_at' => $schedule['created_at'] ?? null,
        'updated_at' => $schedule['updated_at'] ?? null
    ];
}
?>