<?php
/**
 * Mobile API - Report bulanan
 *
 * GET ?month=YYYY-MM&q= -> { success, month, month_display, schedules, summary }
 *
 * Mengikuti pages/report.php: hanya schedule berstatus Finish pada bulan
 * yang dipilih (berdasarkan updated_at, fallback created_at).
 *
 * @package RBM\Schedule\Mobile
 */

require_once __DIR__ . '/_bootstrap.php';

[$user, $conn] = requireMobileUser();

$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!DateTime::createFromFormat('Y-m', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

[$yearStr, $monthStr] = explode('-', $selectedMonth);
$year = (int) $yearStr;
$month = (int) $monthStr;

$whereClauses = ["status = ?"];
$types = 's';
$params = ['Finish'];

$whereClauses[] = "YEAR(COALESCE(updated_at, created_at)) = ?";
$whereClauses[] = "MONTH(COALESCE(updated_at, created_at)) = ?";
$types .= 'ii';
$params[] = $year;
$params[] = $month;

if ($searchTerm !== '') {
    $whereClauses[] = "(spk LIKE ? OR nama_barang LIKE ? OR customer LIKE ?)";
    $like = '%' . $searchTerm . '%';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = "SELECT * FROM schedules WHERE " . implode(' AND ', $whereClauses) . "
    ORDER BY COALESCE(updated_at, created_at) DESC, created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
$totalQty = 0;
while ($row = $result->fetch_assoc()) {
    $schedules[] = mobileScheduleRow($row);
    $totalQty += (int) $row['qty_order'];
}
$stmt->close();

closeDBConnection($conn);

$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

mobileJson([
    'success' => true,
    'month' => $selectedMonth,
    'month_display' => ($monthNames[$month] ?? $month) . ' ' . $year,
    'schedules' => $schedules,
    'summary' => [
        'total_schedule' => count($schedules),
        'total_qty' => $totalQty,
    ],
]);
