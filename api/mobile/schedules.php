<?php
/**
 * Mobile API - Daftar schedule untuk halaman Manage
 *
 * GET ?q= -> { success, schedules, role }
 *
 * Mengikuti pages/manage.php: hanya schedule yang belum Finish,
 * diurutkan Not Started lebih dulu lalu Running, terbaru di atas.
 *
 * @package RBM\Schedule\Mobile
 */

require_once __DIR__ . '/_bootstrap.php';

[$user, $conn] = requireMobileUser();

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT * FROM schedules WHERE status != 'Finish'";
$types = '';
$params = [];

if ($searchTerm !== '') {
    $sql .= " AND (spk LIKE ? OR nama_barang LIKE ? OR customer LIKE ?)";
    $like = '%' . $searchTerm . '%';
    $types = 'sss';
    $params = [$like, $like, $like];
}

$sql .= " ORDER BY
    CASE status
        WHEN 'Not Started' THEN 1
        WHEN 'Running' THEN 2
    END,
    created_at DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = mobileScheduleRow($row);
}
$stmt->close();

closeDBConnection($conn);

mobileJson([
    'success' => true,
    'schedules' => $schedules,
    'role' => $user['role'],
]);
