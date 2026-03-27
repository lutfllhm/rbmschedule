<?php
// Public Display View - untuk perangkat lain (tidak perlu login)
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

// Get schedule aktif (Not Started & Running) untuk display board - minimal 5-6 schedule
$sql = "SELECT * FROM schedules WHERE status != 'Finish' ORDER BY 
    CASE status 
        WHEN 'Running' THEN 1 
        WHEN 'Not Started' THEN 2 
    END,
    updated_at DESC,
    created_at DESC
    LIMIT 10";
$result = $conn->query($sql);
$schedules = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Display - RBM Production</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: #0a0e27;
            font-family: 'Orbitron', 'Inter', sans-serif;
        }
        
        .display-view-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: #0a0e27;
            color: #00d4ff;
        }
        
        /* Header */
        .display-view-header {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(0, 212, 255, 0.05));
            border-bottom: 3px solid #00d4ff;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 212, 255, 0.3);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .header-logo {
            height: 60px;
            width: auto;
            animation: pulse 2s infinite;
        }
        
        .header-logo img {
            height: 100%;
            width: auto;
            object-fit: contain;
            filter: brightness(0) saturate(100%) invert(82%) sepia(44%) saturate(1284%) hue-rotate(152deg) brightness(107%) contrast(101%) drop-shadow(0 0 14px rgba(0, 212, 255, 0.75));
            animation: neon-pulse 2.8s ease-in-out infinite;
        }
        
        @keyframes neon-pulse {
            0%, 100% {
                filter: brightness(0) saturate(100%) invert(82%) sepia(44%) saturate(1284%) hue-rotate(152deg) brightness(107%) contrast(101%) drop-shadow(0 0 10px rgba(0, 212, 255, 0.6));
            }
            50% {
                filter: brightness(0) saturate(100%) invert(82%) sepia(44%) saturate(1284%) hue-rotate(152deg) brightness(107%) contrast(101%) drop-shadow(0 0 20px rgba(0, 212, 255, 1));
            }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .header-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #00d4ff;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
            letter-spacing: 2px;
            margin: 0;
        }
        
        .header-title .subtitle {
            font-size: 0.9rem;
            color: #7c8db5;
            margin-top: 0.25rem;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .live-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(0, 212, 255, 0.1);
            border: 2px solid #00d4ff;
            border-radius: 25px;
        }
        
        .live-dot {
            width: 12px;
            height: 12px;
            background: #00ff00;
            border-radius: 50%;
            animation: blink 1s infinite;
            box-shadow: 0 0 10px #00ff00;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .live-text {
            font-weight: 600;
            color: #00d4ff;
            font-size: 0.9rem;
        }
        
        .header-clock {
            font-family: 'Orbitron', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            color: #00d4ff;
            text-shadow: 0 0 15px rgba(0, 212, 255, 0.8);
        }
        
        .header-date {
            font-size: 0.9rem;
            color: #7c8db5;
            margin-top: 0.25rem;
        }
        
        /* Board Container */
        .display-view-board {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        /* Schedule Row */
        .schedule-row-view {
            background: linear-gradient(135deg, rgba(20, 27, 61, 0.9), rgba(26, 35, 81, 0.9));
            border: 2px solid #1a3a5c;
            border-radius: 12px;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1.5fr 1.5fr 1.5fr;
            gap: 1.5rem;
            align-items: center;
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .schedule-row-view:hover {
            border-color: #00d4ff;
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .schedule-row-view.updated {
            animation: highlightUpdate 1s ease;
        }
        
        @keyframes highlightUpdate {
            0%, 100% { border-color: #1a3a5c; }
            50% { border-color: #00d4ff; box-shadow: 0 0 40px rgba(0, 212, 255, 0.6); }
        }
        
        .schedule-row-view.new-entry {
            animation: newEntry 0.8s ease;
        }
        
        @keyframes newEntry {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-30px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        /* Cell Styles */
        .cell-view {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .cell-label-view {
            font-size: 0.75rem;
            color: #7c8db5;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .cell-value-view {
            font-size: 1.1rem;
            color: #00d4ff;
            font-weight: 600;
        }
        
        .cell-value-view.large {
            font-size: 1.5rem;
            font-weight: 700;
            text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
        }
        
        .cell-value-view.small {
            font-size: 0.9rem;
            color: #7c8db5;
        }
        
        .digital-time-view {
            font-family: 'Orbitron', monospace;
            font-size: 0.85rem;
            color: #00d4ff;
            margin-top: 0.25rem;
        }
        
        /* Status Badge */
        .status-badge-view {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-badge-view.running {
            background: rgba(0, 255, 0, 0.1);
            border: 2px solid #00ff00;
            color: #00ff00;
        }
        
        .status-badge-view.pending,
        .status-badge-view.not-started {
            background: rgba(255, 193, 7, 0.1);
            border: 2px solid #ffc107;
            color: #ffc107;
        }
        
        .status-badge-view.finish {
            background: rgba(108, 117, 125, 0.1);
            border: 2px solid #6c757d;
            color: #6c757d;
        }
        
        /* Empty State */
        .empty-display-view {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #7c8db5;
            gap: 1rem;
        }
        
        .empty-display-view i {
            font-size: 4rem;
            opacity: 0.5;
        }
        
        .empty-display-view p {
            font-size: 1.2rem;
        }
        
        /* Scrollbar */
        .display-view-board::-webkit-scrollbar {
            width: 8px;
        }
        
        .display-view-board::-webkit-scrollbar-track {
            background: rgba(0, 212, 255, 0.1);
        }
        
        .display-view-board::-webkit-scrollbar-thumb {
            background: #00d4ff;
            border-radius: 4px;
        }
        
        .display-view-board::-webkit-scrollbar-thumb:hover {
            background: #00b8e6;
        }
        
        /* Responsive */
        @media (max-width: 1600px) {
            .schedule-row-view {
                grid-template-columns: 1fr 2fr 1fr 1.2fr 1.2fr 1.5fr;
            }
        }
        
        @media (max-width: 1200px) {
            .schedule-row-view {
                grid-template-columns: 1fr 1.5fr 1fr 1fr 1fr 1fr;
                gap: 1rem;
                padding: 1rem;
            }
            
            .header-title h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="display-view-container">
        <!-- Header -->
        <div class="display-view-header">
            <div class="header-left">
                <div class="header-logo">
                    <img src="/rbmschedule/assets/img/iw.png" alt="RBM Logo">
                </div>
                <div class="header-title">
                    <h1>PRODUCTION SCHEDULE BOARD</h1>
                    <div class="header-date" id="viewDate"></div>
                </div>
            </div>
            <div class="header-right">
                <div class="header-clock" id="viewClock">--:--:--</div>
                <div class="live-indicator">
                    <div class="live-dot"></div>
                    <span class="live-text">LIVE</span>
                </div>
            </div>
        </div>
        
        <!-- Board Content -->
        <div class="display-view-board" id="viewBoard">
            <?php if (empty($schedules)): ?>
            <div class="empty-display-view">
                <svg class="empty-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                    <rect x="14" y="10" width="36" height="44" rx="4" ry="4" />
                    <rect x="22" y="4" width="20" height="8" rx="2" ry="2" />
                    <line x1="22" y1="24" x2="42" y2="24" />
                    <line x1="22" y1="32" x2="42" y2="32" />
                    <line x1="22" y1="40" x2="42" y2="40" />
                </svg>
                <p>No Schedules Available</p>
            </div>
            <?php else: ?>
            <?php foreach ($schedules as $schedule): ?>
            <div class="schedule-row-view" data-schedule-id="<?php echo $schedule['id']; ?>" data-status="<?php echo str_replace(' ', '-', strtolower($schedule['status'])); ?>">
                <!-- SPK -->
                <div class="cell-view">
                    <div class="cell-label-view">SPK Number</div>
                    <div class="cell-value-view large"><?php echo htmlspecialchars($schedule['spk']); ?></div>
                </div>
                
                <!-- Item & Customer -->
                <div class="cell-view">
                    <div class="cell-label-view">Item & Customer</div>
                    <div class="cell-value-view"><?php echo htmlspecialchars($schedule['nama_barang']); ?></div>
                    <div class="cell-value-view small">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($schedule['customer']); ?>
                    </div>
                </div>
                
                <!-- Quantity -->
                <div class="cell-view">
                    <div class="cell-label-view">Quantity</div>
                    <div class="cell-value-view"><?php echo number_format($schedule['qty_order']); ?></div>
                    <div class="cell-value-view small">pieces</div>
                </div>
                
                <!-- Cetak -->
                <div class="cell-view">
                    <div class="cell-label-view">Cetak</div>
                    <div class="cell-value-view small">
                        <i class="fas fa-print"></i> <?php echo $schedule['op_cetak'] ? htmlspecialchars($schedule['op_cetak']) : '-'; ?>
                    </div>
                    <div class="digital-time-view">
                        <?php echo $schedule['tanggal_mulai_cetak'] ? date('d/m/Y H:i', strtotime($schedule['tanggal_mulai_cetak'])) : '-'; ?>
                    </div>
                </div>
                
                <!-- Slitting -->
                <div class="cell-view">
                    <div class="cell-label-view">Slitting</div>
                    <div class="cell-value-view small">
                        <i class="fas fa-cut"></i> <?php echo $schedule['op_slitting'] ? htmlspecialchars($schedule['op_slitting']) : '-'; ?>
                    </div>
                    <div class="digital-time-view">
                        <?php echo $schedule['tanggal_mulai_slitting'] ? date('d/m/Y H:i', strtotime($schedule['tanggal_mulai_slitting'])) : '-'; ?>
                    </div>
                </div>
                
                <!-- Status -->
                <div class="cell-view">
                    <div class="cell-label-view">Status</div>
                    <span class="status-badge-view <?php echo str_replace(' ', '-', strtolower($schedule['status'])); ?>">
                        <?php 
                        if ($schedule['status'] === 'Running') {
                            echo '<i class="fas fa-spinner fa-spin"></i>';
                        } else if ($schedule['status'] === 'Finish') {
                            echo '<i class="fas fa-check-circle"></i>';
                        } else {
                            echo '<i class="fas fa-clock"></i>';
                        }
                        echo ' ' . $schedule['status']; 
                        ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Clock and Date
        function updateViewClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const dateString = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            document.getElementById('viewClock').textContent = timeString;
            document.getElementById('viewDate').textContent = dateString;
        }
        
        setInterval(updateViewClock, 1000);
        updateViewClock();
        
        // Real-time Sync
        let lastCheckTimestamp = Math.floor(Date.now() / 1000);
        let syncInterval = null;
        let isSyncing = false;
        let previousSchedules = new Map();
        
        // Initialize previous schedules
        <?php foreach ($schedules as $schedule): ?>
        previousSchedules.set(<?php echo $schedule['id']; ?>, {
            id: <?php echo $schedule['id']; ?>,
            status: '<?php echo strtolower($schedule['status']); ?>',
            spk: '<?php echo htmlspecialchars($schedule['spk'], ENT_QUOTES); ?>',
            nama_barang: '<?php echo htmlspecialchars($schedule['nama_barang'] ?? '', ENT_QUOTES); ?>',
            customer: '<?php echo htmlspecialchars($schedule['customer'] ?? '', ENT_QUOTES); ?>',
            qty_order: '<?php echo $schedule['qty_order'] ?? ''; ?>',
            op_cetak: '<?php echo htmlspecialchars($schedule['op_cetak'] ?? '', ENT_QUOTES); ?>',
            op_slitting: '<?php echo htmlspecialchars($schedule['op_slitting'] ?? '', ENT_QUOTES); ?>',
            tanggal_mulai_cetak: '<?php echo $schedule['tanggal_mulai_cetak'] ?? ''; ?>',
            tanggal_mulai_slitting: '<?php echo $schedule['tanggal_mulai_slitting'] ?? ''; ?>'
        });
        <?php endforeach; ?>
        
        // Start real-time sync
        function startRealtimeSync() {
            if (syncInterval) return;
            
            syncInterval = setInterval(checkForUpdates, 2000); // Check every 2 seconds
            checkForUpdates(); // Immediate first check
        }
        
        // Stop real-time sync
        function stopRealtimeSync() {
            if (syncInterval) {
                clearInterval(syncInterval);
                syncInterval = null;
            }
        }
        
        // Check for updates
        function checkForUpdates() {
            if (isSyncing) return;
            
            isSyncing = true;
            
            fetch(`/rbmschedule/api/check_updates.php?last_check=${lastCheckTimestamp}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.has_updates) {
                        lastCheckTimestamp = data.timestamp;
                        refreshViewBoard();
                    }
                })
                .catch(error => {
                    console.error('Error checking updates:', error);
                })
                .finally(() => {
                    isSyncing = false;
                });
        }
        
        // Refresh board with latest data
        function refreshViewBoard() {
            fetch('/rbmschedule/api/get_schedules.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Filter hanya yang tidak Finish dan ambil maksimal 10
                        const activeSchedules = data.schedules
                            .filter(s => s.status !== 'Finish')
                            .sort((a, b) => {
                                const statusOrder = { 'Running': 1, 'Not Started': 2 };
                                const aOrder = statusOrder[a.status] || 3;
                                const bOrder = statusOrder[b.status] || 3;
                                if (aOrder !== bOrder) return aOrder - bOrder;
                                const aTime = new Date(a.updated_at || a.created_at || 0);
                                const bTime = new Date(b.updated_at || b.created_at || 0);
                                return bTime - aTime;
                            })
                            .slice(0, 10);
                        
                        updateViewBoard(activeSchedules);
                    }
                })
                .catch(error => {
                    console.error('Error fetching schedules:', error);
                });
        }
        
        // Update board with new data
        function updateViewBoard(schedules) {
            const board = document.getElementById('viewBoard');
            const currentSchedules = new Map();
            
            schedules.forEach(schedule => {
                currentSchedules.set(parseInt(schedule.id), schedule);
            });
            
            // Remove deleted schedules
            previousSchedules.forEach((value, id) => {
                if (!currentSchedules.has(id)) {
                    const row = board.querySelector(`[data-schedule-id="${id}"]`);
                    if (row) {
                        row.style.animation = 'slideOut 0.5s ease';
                        setTimeout(() => row.remove(), 500);
                    }
                }
            });
            
            if (schedules.length === 0) {
                board.innerHTML = `
                    <div class="empty-display-view">
                        <svg class="empty-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                            <rect x="14" y="10" width="36" height="44" rx="4" ry="4" />
                            <rect x="22" y="4" width="20" height="8" rx="2" ry="2" />
                            <line x1="22" y1="24" x2="42" y2="24" />
                            <line x1="22" y1="32" x2="42" y2="32" />
                            <line x1="22" y1="40" x2="42" y2="40" />
                        </svg>
                        <p>No Schedules Available</p>
                    </div>
                `;
                previousSchedules.clear();
                return;
            }
            
            // Update or create rows
            schedules.forEach(schedule => {
                const existingRow = board.querySelector(`[data-schedule-id="${schedule.id}"]`);
                const prevSchedule = previousSchedules.get(parseInt(schedule.id));
                const isNew = !prevSchedule;
                const isUpdated = prevSchedule && (
                    prevSchedule.status !== schedule.status.toLowerCase() ||
                    prevSchedule.op_cetak !== (schedule.op_cetak || '') ||
                    prevSchedule.op_slitting !== (schedule.op_slitting || '') ||
                    prevSchedule.tanggal_mulai_cetak !== (schedule.tanggal_mulai_cetak || '') ||
                    prevSchedule.tanggal_mulai_slitting !== (schedule.tanggal_mulai_slitting || '')
                );
                
                if (existingRow) {
                    if (isUpdated) {
                        existingRow.classList.add('updated');
                        updateRowContent(existingRow, schedule);
                        
                        // Move to top if updated
                        if (existingRow !== board.firstChild) {
                            board.insertBefore(existingRow, board.firstChild);
                        }
                        
                        setTimeout(() => {
                            existingRow.classList.remove('updated');
                        }, 1000);
                    }
                } else {
                    // Create new row - put at top
                    const row = createViewRow(schedule);
                    row.classList.add('new-entry');
                    board.insertBefore(row, board.firstChild);
                }
            });
            
            // Update previous schedules
            previousSchedules.clear();
            schedules.forEach(schedule => {
                previousSchedules.set(parseInt(schedule.id), {
                    id: schedule.id,
                    status: schedule.status.toLowerCase(),
                    spk: schedule.spk,
                    nama_barang: schedule.nama_barang || '',
                    customer: schedule.customer || '',
                    qty_order: schedule.qty_order || '',
                    op_cetak: schedule.op_cetak || '',
                    op_slitting: schedule.op_slitting || '',
                    tanggal_mulai_cetak: schedule.tanggal_mulai_cetak || '',
                    tanggal_mulai_slitting: schedule.tanggal_mulai_slitting || ''
                });
            });
        }
        
        // Create schedule row HTML
        function createViewRow(schedule) {
            const row = document.createElement('div');
            row.className = 'schedule-row-view';
            row.setAttribute('data-schedule-id', schedule.id);
            row.setAttribute('data-status', schedule.status.toLowerCase().replace(/\s+/g, '-'));
            
            const statusIcon = schedule.status === 'Running' 
                ? '<i class="fas fa-spinner fa-spin"></i>' 
                : schedule.status === 'Finish' 
                ? '<i class="fas fa-check-circle"></i>' 
                : '<i class="fas fa-clock"></i>';
            
            const cetakTime = schedule.tanggal_mulai_cetak 
                ? new Date(schedule.tanggal_mulai_cetak).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + 
                  new Date(schedule.tanggal_mulai_cetak).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                : '-';
            
            const slittingTime = schedule.tanggal_mulai_slitting 
                ? new Date(schedule.tanggal_mulai_slitting).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + 
                  new Date(schedule.tanggal_mulai_slitting).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                : '-';
            
            row.innerHTML = `
                <div class="cell-view">
                    <div class="cell-label-view">SPK Number</div>
                    <div class="cell-value-view large">${escapeHtml(schedule.spk)}</div>
                </div>
                <div class="cell-view">
                    <div class="cell-label-view">Item & Customer</div>
                    <div class="cell-value-view">${escapeHtml(schedule.nama_barang || '')}</div>
                    <div class="cell-value-view small">
                        <i class="fas fa-building"></i> ${escapeHtml(schedule.customer || '')}
                    </div>
                </div>
                <div class="cell-view">
                    <div class="cell-label-view">Quantity</div>
                    <div class="cell-value-view">${parseInt(schedule.qty_order || 0).toLocaleString()}</div>
                    <div class="cell-value-view small">pieces</div>
                </div>
                <div class="cell-view">
                    <div class="cell-label-view">Cetak</div>
                    <div class="cell-value-view small">
                        <i class="fas fa-print"></i> ${schedule.op_cetak ? escapeHtml(schedule.op_cetak) : '-'}
                    </div>
                    <div class="digital-time-view">${cetakTime}</div>
                </div>
                <div class="cell-view">
                    <div class="cell-label-view">Slitting</div>
                    <div class="cell-value-view small">
                        <i class="fas fa-cut"></i> ${schedule.op_slitting ? escapeHtml(schedule.op_slitting) : '-'}
                    </div>
                    <div class="digital-time-view">${slittingTime}</div>
                </div>
                <div class="cell-view">
                    <div class="cell-label-view">Status</div>
                    <span class="status-badge-view ${schedule.status.toLowerCase().replace(/\s+/g, '-')}">
                        ${statusIcon} ${schedule.status}
                    </span>
                </div>
            `;
            
            return row;
        }
        
        // Update row content
        function updateRowContent(row, schedule) {
            const statusIcon = schedule.status === 'Running' 
                ? '<i class="fas fa-spinner fa-spin"></i>' 
                : schedule.status === 'Finish' 
                ? '<i class="fas fa-check-circle"></i>' 
                : '<i class="fas fa-clock"></i>';
            
            const cetakTime = schedule.tanggal_mulai_cetak 
                ? new Date(schedule.tanggal_mulai_cetak).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + 
                  new Date(schedule.tanggal_mulai_cetak).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                : '-';
            
            const slittingTime = schedule.tanggal_mulai_slitting 
                ? new Date(schedule.tanggal_mulai_slitting).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + 
                  new Date(schedule.tanggal_mulai_slitting).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                : '-';
            
            // Update SPK
            const spkCell = row.querySelector('.cell-view:first-child .cell-value-view.large');
            if (spkCell) spkCell.textContent = schedule.spk;
            
            // Update Item & Customer
            const itemCell = row.querySelectorAll('.cell-view')[1];
            if (itemCell) {
                itemCell.querySelector('.cell-value-view').textContent = schedule.nama_barang || '';
                itemCell.querySelector('.cell-value-view.small').innerHTML = `<i class="fas fa-building"></i> ${escapeHtml(schedule.customer || '')}`;
            }
            
            // Update Quantity
            const qtyCell = row.querySelectorAll('.cell-view')[2];
            if (qtyCell) {
                qtyCell.querySelector('.cell-value-view').textContent = parseInt(schedule.qty_order || 0).toLocaleString();
            }
            
            // Update Cetak
            const cetakCell = row.querySelectorAll('.cell-view')[3];
            if (cetakCell) {
                cetakCell.querySelector('.cell-value-view.small').innerHTML = `<i class="fas fa-print"></i> ${schedule.op_cetak ? escapeHtml(schedule.op_cetak) : '-'}`;
                cetakCell.querySelector('.digital-time-view').textContent = cetakTime;
            }
            
            // Update Slitting
            const slittingCell = row.querySelectorAll('.cell-view')[4];
            if (slittingCell) {
                slittingCell.querySelector('.cell-value-view.small').innerHTML = `<i class="fas fa-cut"></i> ${schedule.op_slitting ? escapeHtml(schedule.op_slitting) : '-'}`;
                slittingCell.querySelector('.digital-time-view').textContent = slittingTime;
            }
            
            // Update Status
            const statusCell = row.querySelectorAll('.cell-view')[5];
            if (statusCell) {
                const badge = statusCell.querySelector('.status-badge-view');
                if (badge) {
                    badge.className = `status-badge-view ${schedule.status.toLowerCase().replace(/\s+/g, '-')}`;
                    badge.innerHTML = `${statusIcon} ${schedule.status}`;
                }
            }
            
            // Update data attributes
            row.setAttribute('data-status', schedule.status.toLowerCase().replace(/\s+/g, '-'));
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Start sync on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Display View loaded - Starting real-time sync...');
            startRealtimeSync();
            
            // Pause sync when page is hidden
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    stopRealtimeSync();
                    console.log('👁️ Page hidden - sync paused');
                } else {
                    console.log('👁️ Page visible - resuming sync');
                    lastCheckTimestamp = Math.floor(Date.now() / 1000);
                    startRealtimeSync();
                    checkForUpdates();
                }
            });
        });
        
        // Clean up on page unload
        window.addEventListener('beforeunload', function() {
            stopRealtimeSync();
        });
    </script>
</body>
</html>

