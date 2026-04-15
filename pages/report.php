<?php
$pageTitle = 'Report Schedule';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// Admin dan operator boleh akses laporan
// Halaman report menggunakan tampilan yang sama untuk admin dan operator
// Perbedaan: operator tidak bisa menghapus schedule (tombol delete disembunyikan)
requireLogin();

$conn = getDBConnection();

// Filter per bulan - default bulan dan tahun saat ini
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

// Hitung tanggal awal dan akhir bulan
$dateFrom = sprintf('%04d-%02d-01', $year, $month);
$dateTo = date('Y-m-t', strtotime($dateFrom)); // Tanggal terakhir bulan

$whereClauses = [];
$types = '';
$params = [];

// Report hanya menampilkan schedule yang sudah Finish
$whereClauses[] = "status = ?";
$types .= "s";
$params[] = 'Finish';

// Filter berdasarkan bulan (berdasarkan tanggal finish/updated_at)
// Gunakan updated_at jika ada, jika tidak gunakan created_at
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
$sql = "SELECT * FROM schedules {$whereSql} ORDER BY 
    COALESCE(updated_at, created_at) DESC,
    created_at DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$schedules = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}
$stmt->close();

$filterParams = [];
$filterParams['month'] = $selectedMonth;
if ($searchTerm !== '') {
    $filterParams['q'] = $searchTerm;
}

$filtersApplied = $searchTerm !== '';

// Format bulan untuk display
$monthDisplay = date('F Y', strtotime($dateFrom));
$monthDisplayId = date('F Y', mktime(0, 0, 0, $month, 1, $year));

$exportUrl = getPath('api/report_export.php');
$exportExcelUrl = getPath('api/report_export_excel.php');
if (!empty($filterParams)) {
    $exportUrl .= '?' . http_build_query($filterParams);
    $exportExcelUrl .= '?' . http_build_query($filterParams);
}

closeDBConnection($conn);
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-chart-bar"></i> Report Schedule</h1>
    </div>
    
    <style>
        .title-logo {
            height: 36px;
            width: auto;
            vertical-align: middle;
            margin-right: 0.5rem;
            filter: brightness(0) invert(1);
        }
    </style>
    
    <div class="schedule-table-container">
        <div class="table-header">
            <h2><img src="<?php echo IMG_URL; ?>/rbm.png" alt="RBM Logo" class="title-logo"> Report Schedule - <?php echo $monthDisplayId; ?></h2>
            <div class="table-actions">
                <form class="filter-form" method="GET" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <label style="color: var(--airport-text-dim); font-weight: 600;">Pilih Bulan:</label>
                    <input type="month" name="month" class="filter-select" value="<?php echo htmlspecialchars($selectedMonth); ?>" style="min-width: 150px;">
                    <input type="text" id="searchInput" name="q" class="search-input" placeholder="🔍 Search SPK, Item, Customer..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <i class="fas fa-filter"></i> Terapkan
                    </button>
                    <?php if ($filtersApplied): ?>
                    <a href="<?php echo getPath('pages/report.php'); ?>" class="btn btn-link btn-sm">Reset</a>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars($exportUrl); ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                    <a href="<?php echo htmlspecialchars($exportExcelUrl); ?>" class="btn btn-primary btn-sm" target="_blank" rel="noopener">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                </form>
            </div>
        </div>
        
        <div class="table-meta-info">
            <span>Total data: <strong><?php echo count($schedules); ?></strong> schedule selesai pada <strong><?php echo $monthDisplayId; ?></strong></span>
            <?php if ($filtersApplied): ?>
            <span>Filter pencarian aktif</span>
            <?php endif; ?>
        </div>
        
        <div class="airport-board" id="airportBoard">
            <?php if (empty($schedules)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No schedules available</p>
            </div>
            <?php else: ?>
            <?php foreach ($schedules as $schedule): ?>
            <div class="board-row" data-schedule-id="<?php echo $schedule['id']; ?>" data-status="<?php echo str_replace(' ', '-', strtolower($schedule['status'])); ?>">
                <div class="board-cell">
                    <div class="board-label">SPK Number</div>
                    <div class="board-value large"><?php echo htmlspecialchars($schedule['spk']); ?></div>
                </div>
                
                <div class="board-cell">
                    <div class="board-label">Item & Customer</div>
                    <div class="board-value"><?php echo htmlspecialchars($schedule['nama_barang']); ?></div>
                    <div class="board-value small" style="color: var(--airport-text-dim);">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($schedule['customer']); ?>
                    </div>
                </div>
                
                <div class="board-cell">
                    <div class="board-label">Quantity</div>
                    <div class="board-value"><?php echo number_format($schedule['qty_order']); ?> pcs</div>
                </div>
                
                <div class="board-cell">
                    <div class="board-label">Production Info</div>
                    <div class="board-value small">
                        <i class="fas fa-print"></i> Cetak: 
                        <?php echo $schedule['op_cetak'] ? htmlspecialchars($schedule['op_cetak']) : '<span class="text-muted">-</span>'; ?>
                    </div>
                    <div class="digital-time">
                        <?php echo $schedule['tanggal_mulai_cetak'] ? date('d/m/Y H:i', strtotime($schedule['tanggal_mulai_cetak'])) : '-'; ?>
                    </div>
                    <div class="board-value small" style="margin-top: 0.5rem;">
                        <i class="fas fa-cut"></i> Slitting: 
                        <?php echo $schedule['op_slitting'] ? htmlspecialchars($schedule['op_slitting']) : '<span class="text-muted">-</span>'; ?>
                    </div>
                    <div class="digital-time">
                        <?php echo $schedule['tanggal_mulai_slitting'] ? date('d/m/Y H:i', strtotime($schedule['tanggal_mulai_slitting'])) : '-'; ?>
                    </div>
                </div>
                
                <div class="board-cell">
                    <div class="board-label">Status & Action</div>
                    <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($schedule['status'])); ?>">
                        <?php 
                        if ($schedule['status'] === 'Running') {
                            echo '<i class="fas fa-spinner fa-spin"></i> ';
                        } else if ($schedule['status'] === 'Finish') {
                            echo '<i class="fas fa-check-circle"></i> ';
                        } else {
                            echo '<i class="fas fa-clock"></i> ';
                        }
                        echo $schedule['status']; 
                        ?>
                    </span>
                    <?php if (isAdmin()): ?>
                    <div class="board-actions" style="margin-top: 0.75rem;">
                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $schedule['id']; ?>, '<?php echo htmlspecialchars($schedule['spk']); ?>')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                    <?php endif; ?>
                    <?php if ($schedule['catatan']): ?>
                    <div class="board-value small" style="margin-top: 0.5rem; color: var(--airport-text-dim);">
                        <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($schedule['catatan']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Hidden table untuk kompatibilitas dengan JavaScript yang mencari scheduleTable -->
        <table class="schedule-table" id="scheduleTable" style="display: none;">
            <thead>
                <tr>
                    <th>SPK</th>
                    <th>Nama Barang</th>
                    <th>Qty Order</th>
                    <th>Customer</th>
                    <th>OP Cetak</th>
                    <th>Tgl Mulai Cetak</th>
                    <th>OP Slitting</th>
                    <th>Tgl Mulai Slitting</th>
                    <th>Status</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<?php if (isAdmin()): ?>
<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h2>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus schedule <strong id="deleteSPK"></strong>?</p>
            <p class="text-muted">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
            <form id="deleteForm" method="POST" action="<?php echo getPath('api/schedule_delete.php'); ?>" style="display: inline;">
                <input type="hidden" name="schedule_id" id="delete_schedule_id">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>




