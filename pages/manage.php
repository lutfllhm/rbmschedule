<?php
$pageTitle = 'Manage Schedule';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// Halaman manage dapat diakses admin & operator
requireLogin();

$conn = getDBConnection();

// Auto-hide schedule yang sudah Finish - hanya tampilkan yang belum finish
// Schedule yang finish akan otomatis masuk ke report per bulan
$sql = "SELECT * FROM schedules WHERE status != 'Finish' ORDER BY 
    CASE status 
        WHEN 'Not Started' THEN 1
        WHEN 'Running' THEN 2
    END,
    created_at DESC";
$result = $conn->query($sql);
$schedules = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}

closeDBConnection($conn);

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tasks"></i> Manage Schedule</h1>
        <?php if (isAdmin()): ?>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Tambah Schedule Baru
        </button>
        <?php endif; ?>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php 
        if ($success === 'created') echo 'Schedule berhasil ditambahkan!';
        else if ($success === 'updated') echo 'Schedule berhasil diupdate!';
        else if ($success === 'deleted') echo 'Schedule berhasil dihapus!';
        ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        Terjadi kesalahan. Silakan coba lagi!
    </div>
    <?php endif; ?>
    
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
            <h2><img src="/rbmschedule/assets/img/rbm.png" alt="RBM Logo" class="title-logo"> List Schedule Update</h2>
            <div class="table-actions">
                <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search SPK, Item, Customer...">
            </div>
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
                    <div class="board-actions" style="margin-top: 0.75rem;">
                        <?php if (isAdmin()): ?>
                            <button class="btn btn-sm btn-info" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $schedule['id']; ?>, '<?php echo htmlspecialchars($schedule['spk']); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <?php if ($schedule['status'] !== 'Finish'): ?>
                            <button class="btn btn-sm btn-success" onclick="markScheduleFinish(<?php echo $schedule['id']; ?>)">
                                <i class="fas fa-flag-checkered"></i> Finish
                            </button>
                            <?php endif; ?>
                        <?php elseif (isOperator()): ?>
                            <button class="btn btn-sm btn-info" onclick="openOperatorModal(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
                                <i class="fas fa-edit"></i> Update
                            </button>
                            <?php if ($schedule['status'] !== 'Finish'): ?>
                            <button class="btn btn-sm btn-success" onclick="markScheduleFinish(<?php echo $schedule['id']; ?>)">
                                <i class="fas fa-flag-checkered"></i> Finish
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
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
                    <th>Tanggal Mulai Cetak</th>
                    <th>OP Slitting</th>
                    <th>Tanggal Mulai Slitting</th>
                    <th>Status</th>
                    <th>Catatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="scheduleModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus"></i> Tambah Schedule Baru</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="scheduleForm" method="POST">
            <input type="hidden" name="schedule_id" id="schedule_id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="spk">SPK <span class="required">*</span></label>
                        <input type="text" name="spk" id="spk" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="nama_barang">Nama Barang <span class="required">*</span></label>
                        <input type="text" name="nama_barang" id="nama_barang" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="qty_order">Qty Order <span class="required">*</span></label>
                        <input type="number" name="qty_order" id="qty_order" class="form-control" required min="1">
                    </div>
                    <div class="form-group">
                        <label for="customer">Customer <span class="required">*</span></label>
                        <input type="text" name="customer" id="customer" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="catatan">Keterangan</label>
                    <textarea name="catatan" id="catatan" class="form-control" rows="3" placeholder="Masukkan keterangan (opsional)"></textarea>
                </div>
                
                <!-- Field untuk Edit Mode (disembunyikan saat tambah) -->
                <div id="editModeFields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="op_cetak">OP Cetak</label>
                            <input type="text" name="op_cetak" id="op_cetak" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="tanggal_mulai_cetak">Tanggal Mulai Cetak</label>
                            <input type="datetime-local" name="tanggal_mulai_cetak" id="tanggal_mulai_cetak" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="op_slitting">OP Slitting</label>
                            <input type="text" name="op_slitting" id="op_slitting" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="tanggal_mulai_slitting">Tanggal Mulai Slitting</label>
                            <input type="datetime-local" name="tanggal_mulai_slitting" id="tanggal_mulai_slitting" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="Not Started">Not Started</option>
                            <option value="Running">Running</option>
                            <option value="Finish">Finish</option>
                        </select>
                    </div>
                </div>
                
                <!-- Hidden field untuk status default saat tambah -->
                <input type="hidden" name="status" id="status_hidden" value="Not Started">
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

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
            <form id="deleteForm" method="POST" style="display: inline;">
                <input type="hidden" name="schedule_id" id="delete_schedule_id">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<?php if (isOperator()): ?>
<!-- Operator Update Modal (dipakai operator di halaman manage) -->
<div id="operatorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Update Schedule</h2>
            <button class="modal-close" onclick="closeOperatorModal()">&times;</button>
        </div>
        <form id="operatorForm" method="POST">
            <input type="hidden" name="schedule_id" id="schedule_id">
            <input type="hidden" name="action" value="operator_update">
            
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>