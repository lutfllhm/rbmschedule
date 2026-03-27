<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$conn = getDBConnection();

// Filter per bulan - sama seperti report.php
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';

// Validasi format bulan (Y-m)
$monthValid = DateTime::createFromFormat('Y-m', $selectedMonth);
if (!$monthValid) {
    $selectedMonth = date('Y-m');
}

// Parse bulan dan tahun
$monthYear = explode('-', $selectedMonth);
$year = (int)$monthYear[0];
$month = (int)$monthYear[1];

$whereClauses = [];
$types = '';
$params = [];

// Export hanya schedule yang sudah Finish (sama seperti report)
$whereClauses[] = "status = ?";
$types .= "s";
$params[] = 'Finish';

// Filter berdasarkan bulan (berdasarkan tanggal finish/updated_at)
$whereClauses[] = "YEAR(COALESCE(updated_at, created_at)) = ?";
$whereClauses[] = "MONTH(COALESCE(updated_at, created_at)) = ?";
$types .= "ii";
$params[] = $year;
$params[] = $month;

if ($searchTerm !== '') {
    $whereClauses[] = "(spk LIKE ? OR nama_barang LIKE ? OR customer LIKE ?)";
    $like = '%' . $searchTerm . '%';
    $types .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = ' WHERE ' . implode(' AND ', $whereClauses);
$sql = "SELECT spk, nama_barang, qty_order, customer, status, op_cetak, tanggal_mulai_cetak, op_slitting, tanggal_mulai_slitting, catatan, created_at, updated_at
    FROM schedules {$whereSql}
    ORDER BY 
        COALESCE(updated_at, created_at) DESC,
        created_at DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$monthDisplay = date('F_Y', mktime(0, 0, 0, $month, 1, $year));
$filename = 'rbm_schedule_report_' . $monthDisplay . '_' . date('His') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, [
    'SPK',
    'Nama Barang',
    'Qty Order',
    'Customer',
    'Status',
    'OP Cetak',
    'Mulai Cetak',
    'OP Slitting',
    'Mulai Slitting',
    'Catatan',
    'Dibuat',
    'Diupdate'
]);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['spk'],
        $row['nama_barang'],
        $row['qty_order'],
        $row['customer'],
        $row['status'],
        $row['op_cetak'],
        $row['tanggal_mulai_cetak'],
        $row['op_slitting'],
        $row['tanggal_mulai_slitting'],
        $row['catatan'],
        $row['created_at'],
        $row['updated_at']
    ]);
}

fclose($output);
$stmt->close();
closeDBConnection($conn);
exit();

