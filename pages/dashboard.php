<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$conn = getDBConnection();

$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'active';
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

$perPage = max(5, min($perPage, 100));
$page = max(1, $page);
$isOperator = isOperator();

if ($isOperator && in_array($filterStatus, ['Finish', 'all'], true)) {
    $filterStatus = 'active';
}

// Fetch statistics via aggregation
$stats = [
    'total' => 0,
    'not started' => 0,
    'processing' => 0,
    'finish' => 0
];
$statsResult = $conn->query("SELECT status, COUNT(*) as total FROM schedules GROUP BY status");
if ($statsResult) {
    while ($row = $statsResult->fetch_assoc()) {
        $statusKey = strtolower($row['status']);
        if ($statusKey === 'running') $statusKey = 'processing';
        $count = intval($row['total']);
        if (isset($stats[$statusKey])) {
            $stats[$statusKey] += $count;
        }
        $stats['total'] += $count;
    }
}

$whereClauses = [];
$types = '';
$params = [];

if ($filterStatus === 'active') {
    $whereClauses[] = "status <> 'Finish'";
} elseif (in_array($filterStatus, ['Not Started', 'Running', 'Finish'], true)) {
    $whereClauses[] = "status = ?";
    $types .= "s";
    $params[] = $filterStatus === 'Processing' ? 'Running' : $filterStatus;
}

if ($searchTerm !== '') {
    $whereClauses[] = "(spk LIKE ? OR nama_barang LIKE ? OR customer LIKE ?)";
    $like = '%' . $searchTerm . '%';
    $types .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = $whereClauses ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Count total rows for pagination
$countSql = "SELECT COUNT(*) as total FROM schedules{$whereSql}";
$countStmt = $conn->prepare($countSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = intval(($countResult->fetch_assoc()['total'] ?? 0));
$countStmt->close();

$totalPages = max(1, ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$querySql = "SELECT * FROM schedules{$whereSql}
    ORDER BY FIELD(status, 'Processing', 'Not Started', 'Finish'), updated_at DESC, created_at DESC
    LIMIT ? OFFSET ?";

$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$perPage, $offset]);

$stmt = $conn->prepare($querySql);
$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}
$stmt->close();

$firstItem = $totalRows > 0 ? $offset + 1 : 0;
$lastItem = min($offset + $perPage, $totalRows);
$filtersApplied = $filterStatus !== 'active' || $searchTerm !== '' || $perPage !== 20;

$filterParams = [
    'status' => $filterStatus,
    'per_page' => $perPage
];
if ($searchTerm !== '') {
    $filterParams['q'] = $searchTerm;
}
$paginationStart = max(1, $page - 2);
$paginationEnd = min($totalPages, $paginationStart + 4);
$paginationStart = max(1, $paginationEnd - 4);

// ...existing code...
$statusOptions = [
    'active' => 'Aktif (Not Started & Processing)',
    'all' => 'Semua status',
    'Processing' => 'Processing saja',
    'Not Started' => 'Not Started saja',
    'Finish' => 'Finish saja'
];
if ($isOperator) {
    unset($statusOptions['all'], $statusOptions['Finish']);
}
$perPageOptions = [10, 20, 25, 50, 100];

closeDBConnection($conn);
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="header-left">
            <h1><img src="<?php echo IMG_URL; ?>/rbm.png" alt="RBM Logo" class="title-logo"> Dashboard Schedule</h1>
        </div>
        <div class="header-right">
            <div class="digital-clock-wrapper">
                <div class="clock-label">Waktu Indonesia</div>
                <div class="digital-clock" id="dashboardClock">
                    <span id="clockTime">--:--:--</span>
                    <span id="clockDate">--/--/----</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-header">
                <div class="stat-icon stat-total">
                    <svg class="empty-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" style="width:40px;height:40px;">
                        <rect x="14" y="10" width="36" height="44" rx="4" ry="4" fill="currentColor" stroke="currentColor" stroke-width="2" />
                        <rect x="22" y="4" width="20" height="8" rx="2" ry="2" fill="currentColor" stroke="currentColor" stroke-width="2" />
                        <line x1="22" y1="24" x2="42" y2="24" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <line x1="22" y1="32" x2="42" y2="32" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <line x1="22" y1="40" x2="42" y2="40" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label">Total Schedule</div>
        </div>
        
        <div class="stat-card stat-pending">
            <div class="stat-header">
                <div class="stat-icon stat-pending">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo number_format($stats['not started']); ?></div>
            <div class="stat-label">Not Started</div>
        </div>
        
        <div class="stat-card stat-processing">
            <div class="stat-header">
                <div class="stat-icon stat-processing">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo number_format($stats['processing']); ?></div>
            <div class="stat-label">Processing</div>
        </div>
        
        <div class="stat-card stat-finish">
            <div class="stat-header">
                <div class="stat-icon stat-finish">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo number_format($stats['finish']); ?></div>
            <div class="stat-label">Finish</div>
        </div>
    </div>
    
    <div class="schedule-table-container">
        <div class="table-header">
            <h2><img src="<?php echo IMG_URL; ?>/rbm.png" alt="RBM Logo" class="title-logo"> List Schedule Update</h2>
            <div class="table-actions">
                <form class="filter-form" method="GET">
                    <input type="hidden" name="page" value="1">
                    <input type="text" id="searchInput" name="q" class="search-input" placeholder="🔍 Search SPK, Item, Customer..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <select name="status" class="filter-select">
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $filterStatus === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="per_page" class="filter-select">
                        <?php foreach ($perPageOptions as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo (int)$perPage === (int)$option ? 'selected' : ''; ?>>
                                <?php echo $option; ?> / halaman
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <i class="fas fa-filter"></i> Terapkan
                    </button>
                    <?php if ($filtersApplied): ?>
                    <a href="<?php echo getPath('pages/dashboard.php'); ?>" class="btn btn-link btn-sm">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="table-meta-info">
            <?php if ($totalRows > 0): ?>
            <span>Menampilkan <strong><?php echo $firstItem; ?>-<?php echo $lastItem; ?></strong> dari <strong><?php echo $totalRows; ?></strong> schedule</span>
            <span>Halaman <?php echo $page; ?> / <?php echo $totalPages; ?></span>
            <?php else: ?>
            <span>Tidak ada data untuk filter saat ini</span>
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
            <div class="board-row" data-schedule-id="<?php echo $schedule['id']; ?>">
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
                    <span class="status-badge status-<?php echo str_replace([' ', 'Running'], ['-', 'Processing'], strtolower($schedule['status'])); ?>">
                        <?php 
                        if ($schedule['status'] === 'Running' || $schedule['status'] === 'Processing') {
                            echo '<i class="fas fa-spinner fa-spin"></i> Processing';
                        } else if ($schedule['status'] === 'Finish') {
                            echo '<i class="fas fa-check-circle"></i> Finish';
                        } else {
                            echo '<i class="fas fa-clock"></i> Not Started';
                        }
                        ?>
                    </span>
                    <?php if (isOperator()): ?>
                    <div class="board-actions" style="margin-top: 0.75rem;">
                        <button class="btn btn-sm btn-info" onclick="openOperatorModal(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
                            <i class="fas fa-edit"></i> Update
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
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination-controls">
            <?php if ($page > 1): ?>
            <a class="page-btn" href="?<?php echo http_build_query(array_merge($filterParams, ['page' => $page - 1])); ?>">&laquo; Prev</a>
            <?php endif; ?>
            
            <?php for ($i = $paginationStart; $i <= $paginationEnd; $i++): ?>
                <a class="page-number <?php echo $i === $page ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($filterParams, ['page' => $i])); ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a class="page-btn" href="?<?php echo http_build_query(array_merge($filterParams, ['page' => $page + 1])); ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isOperator()): ?>
<!-- Operator Update Modal -->
<div id="operatorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Update Schedule</h2>
            <button class="modal-close" onclick="closeOperatorModal()">&times;</button>
        </div>
        <form id="operatorForm" method="POST">
            <input type="hidden" name="schedule_id" id="schedule_id">
            <input type="hidden" name="action" value="operator_update">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>SPK</label>
                        <input type="text" id="modal_spk" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" id="modal_nama_barang" class="form-control" readonly>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="op_cetak">OP Cetak</label>
                        <select name="op_cetak" id="op_cetak" class="form-control">
                            <option value="">-- Pilih Operator Cetak --</option>
                            <option value="Rudi">Rudi</option>
                            <option value="Febri">Febri</option>
                            <option value="Rohmad">Rohmad</option>
                            <option value="Andre">Andre</option>
                            <option value="Rio">Rio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="op_slitting">OP Slitting</label>
                        <select name="op_slitting" id="op_slitting" class="form-control">
                            <option value="">-- Pilih Operator Slitting --</option>
                            <option value="Addin">Addin</option>
                            <option value="Arvian">Arvian</option>
                            <option value="Ibrahim">Ibrahim</option>
                            <option value="Roji">Roji</option>
                            <option value="Faruq">Faruq</option>
                            <option value="Ari">Ari</option>
                            <option value="Risky">Risky</option>
                            <option value="Wahyu">Wahyu</option>
                            <option value="Irfan">Irfan</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Status Saat Ini</label>
                    <div id="current_status"></div>
                </div>
                
                <div class="form-group" id="finish_section" style="display: none;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="mark_finish" id="mark_finish" value="1">
                        <span>Tandai sebagai Selesai (Finish)</span>
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeOperatorModal()">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
    .title-logo {
        height: 36px;
        width: auto;
        vertical-align: middle;
        margin-right: 0.5rem;
        filter: brightness(0) invert(1);
    }
    
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .header-left {
        flex: 1;
    }
    
    .header-right {
        display: flex;
        align-items: center;
    }
    
    .digital-clock-wrapper {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
    }
    
    .clock-label {
        font-size: 0.75rem;
        color: var(--airport-text-dim);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }
    
    .digital-clock {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.25rem;
        font-family: 'Orbitron', 'Inter', monospace;
    }
    
    .digital-clock #clockTime {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--airport-accent);
        text-shadow: 0 0 10px var(--airport-glow);
        letter-spacing: 2px;
    }
    
    .digital-clock #clockDate {
        font-size: 0.9rem;
        color: var(--airport-text);
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .header-right {
            width: 100%;
        }
        
        .digital-clock-wrapper {
            align-items: flex-start;
        }
        
        .digital-clock {
            align-items: flex-start;
        }
        
    }
</style>

<script>
    // Update jam digital Indonesia (WIB)
    function updateDashboardClock() {
        const now = new Date();
        
        // Konversi ke timezone Indonesia (Asia/Jakarta = UTC+7)
        const indonesiaTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
        
        // Format waktu: HH:MM:SS
        const hours = String(indonesiaTime.getHours()).padStart(2, '0');
        const minutes = String(indonesiaTime.getMinutes()).padStart(2, '0');
        const seconds = String(indonesiaTime.getSeconds()).padStart(2, '0');
        const timeString = `${hours}:${minutes}:${seconds}`;
        
        // Format tanggal: DD/MM/YYYY
        const day = String(indonesiaTime.getDate()).padStart(2, '0');
        const month = String(indonesiaTime.getMonth() + 1).padStart(2, '0');
        const year = indonesiaTime.getFullYear();
        const dateString = `${day}/${month}/${year}`;
        
        // Update DOM
        const clockTime = document.getElementById('clockTime');
        const clockDate = document.getElementById('clockDate');
        
        if (clockTime) {
            clockTime.textContent = timeString;
        }
        if (clockDate) {
            clockDate.textContent = dateString;
        }
    }
    
    // Update jam setiap detik
    document.addEventListener('DOMContentLoaded', function() {
        updateDashboardClock();
        setInterval(updateDashboardClock, 1000);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>