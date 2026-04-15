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
$filename = 'rbm_schedule_report_' . $monthDisplay . '_' . date('His') . '.xls';

// Set headers untuk Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Mulai output HTML table yang akan dibaca sebagai Excel
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
echo '<!--[if gte mso 9]>';
echo '<xml>';
echo '<x:ExcelWorkbook>';
echo '<x:ExcelWorksheets>';
echo '<x:ExcelWorksheet>';
echo '<x:Name>Schedule Report</x:Name>';
echo '<x:WorksheetOptions>';
echo '<x:Print>';
echo '<x:ValidPrinterInfo/>';
echo '</x:Print>';
echo '</x:WorksheetOptions>';
echo '</x:ExcelWorksheet>';
echo '</x:ExcelWorksheets>';
echo '</x:ExcelWorkbook>';
echo '</xml>';
echo '<![endif]-->';
echo '<style>';
echo 'table { border-collapse: collapse; width: 100%; }';
echo 'th { background-color: #4f46e5; color: white; font-weight: bold; padding: 10px; border: 1px solid #ddd; text-align: left; }';
echo 'td { padding: 8px; border: 1px solid #ddd; }';
echo 'tr:nth-child(even) { background-color: #f8fafc; }';
echo '.number { mso-number-format: "0"; }';
echo '.date { mso-number-format: "dd/mm/yyyy hh:mm:ss"; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Header info
echo '<h2>RBM Schedule Report - ' . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . '</h2>';
echo '<p>Generated: ' . date('d/m/Y H:i:s') . '</p>';
echo '<p>Total Records: ' . $result->num_rows . '</p>';
echo '<br/>';

// Table
echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th>No</th>';
echo '<th>SPK</th>';
echo '<th>Nama Barang</th>';
echo '<th>Qty Order</th>';
echo '<th>Customer</th>';
echo '<th>Status</th>';
echo '<th>OP Cetak</th>';
echo '<th>Mulai Cetak</th>';
echo '<th>OP Slitting</th>';
echo '<th>Mulai Slitting</th>';
echo '<th>Catatan</th>';
echo '<th>Dibuat</th>';
echo '<th>Diupdate</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$no = 1;
while ($row = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td class="number">' . $no++ . '</td>';
    echo '<td>' . htmlspecialchars($row['spk']) . '</td>';
    echo '<td>' . htmlspecialchars($row['nama_barang']) . '</td>';
    echo '<td class="number">' . htmlspecialchars($row['qty_order']) . '</td>';
    echo '<td>' . htmlspecialchars($row['customer']) . '</td>';
    echo '<td>' . htmlspecialchars($row['status']) . '</td>';
    echo '<td>' . htmlspecialchars($row['op_cetak'] ?? '-') . '</td>';
    echo '<td class="date">' . ($row['tanggal_mulai_cetak'] ? date('d/m/Y H:i:s', strtotime($row['tanggal_mulai_cetak'])) : '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['op_slitting'] ?? '-') . '</td>';
    echo '<td class="date">' . ($row['tanggal_mulai_slitting'] ? date('d/m/Y H:i:s', strtotime($row['tanggal_mulai_slitting'])) : '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['catatan'] ?? '-') . '</td>';
    echo '<td class="date">' . ($row['created_at'] ? date('d/m/Y H:i:s', strtotime($row['created_at'])) : '-') . '</td>';
    echo '<td class="date">' . ($row['updated_at'] ? date('d/m/Y H:i:s', strtotime($row['updated_at'])) : '-') . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</body>';
echo '</html>';

$stmt->close();
closeDBConnection($conn);
exit();
