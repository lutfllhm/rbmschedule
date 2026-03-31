<?php
// Display mode - bisa diupdate oleh operator dan admin
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Login required untuk bisa update
requireLogin();

$conn = getDBConnection();

// Get hanya schedule aktif (Not Started & Running) untuk display board
$sql = "SELECT * FROM schedules WHERE status != 'Finish' ORDER BY 
    CASE status 
        WHEN 'Running' THEN 1 
        WHEN 'Not Started' THEN 2 
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Schedule Update - RBM</title>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/airport-board.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        // Base URL untuk JavaScript
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
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
        
        .airport-display-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: #0a0e27;
        }
        
        /* Header Bar */
        .display-header {
            background: linear-gradient(135deg, #141b3d, #1a2351);
            border-bottom: 3px solid var(--airport-accent);
            padding: 1.5rem 3rem;
            box-shadow: 0 4px 30px rgba(0, 212, 255, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .display-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--airport-accent), transparent);
            animation: scanline 3s linear infinite;
        }
        
        @keyframes scanline {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .header-title h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--airport-accent);
            text-shadow: 0 0 30px var(--airport-glow);
            margin: 0;
            letter-spacing: 3px;
        }
        
        .header-logo {
            height: 60px;
            width: auto;
        }
        
        .header-logo img {
            height: 100%;
            width: auto;
            object-fit: contain;
            filter: brightness(0) saturate(100%) invert(82%) sepia(44%) saturate(1284%) hue-rotate(152deg) brightness(107%) contrast(101%) drop-shadow(0 0 14px rgba(0, 212, 255, 0.75));
            animation: neon-pulse 2.8s ease-in-out infinite;
        }
        
        .header-info {
            display: flex;
            align-items: center;
            gap: 3rem;
        }
        
        .header-clock {
            font-family: 'Orbitron', monospace;
            font-size: 2rem;
            color: var(--airport-text);
            font-weight: 700;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }
        
        .header-date {
            font-size: 1.1rem;
            color: var(--airport-text-dim);
            font-weight: 500;
        }
        
        .live-indicator {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid var(--airport-running);
            border-radius: 50px;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
        }
        
        .live-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--airport-running);
            box-shadow: 0 0 15px var(--airport-running);
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        @keyframes neon-pulse {
            0%, 100% {
                filter: brightness(0) saturate(100%) invert(82%) sepia(44%) saturate(1284%) hue-rotate(152deg) brightness(107%) contrast(101%) drop-shadow(0 0 10px rgba(0, 212, 255, 0.6));
            }
            50% {
                filter: brightness(0) saturate(100%) invert(82%) sepia(44%) saturate(1284%) hue-rotate(152deg) brightness(107%) contrast(101%) drop-shadow(0 0 20px rgba(0, 212, 255, 1));
            }
        }
        
        .live-text {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            color: var(--airport-running);
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        /* Board Content - Full Screen */
        .display-board {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem 2rem;
            scrollbar-width: thin;
            scrollbar-color: var(--airport-accent) var(--airport-bg);
        }
        
        .display-board::-webkit-scrollbar {
            width: 8px;
        }
        
        .display-board::-webkit-scrollbar-track {
            background: var(--airport-bg);
        }
        
        .display-board::-webkit-scrollbar-thumb {
            background: var(--airport-accent);
            border-radius: 4px;
        }
        
        /* Board Row - 6 Columns */
        .display-row {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.05), transparent);
            border: 2px solid var(--airport-border);
            border-radius: 8px;
            margin-bottom: 0.8rem;
            padding: 1rem 1.5rem;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1.5fr 1.5fr 1.2fr;
            gap: 1.5rem;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .display-row::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 6px;
            height: 100%;
            background: var(--airport-accent);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .display-row:hover {
            background: rgba(0, 212, 255, 0.1);
            border-color: var(--airport-accent);
            transform: translateX(8px);
            box-shadow: 0 8px 40px rgba(0, 212, 255, 0.2);
        }
        
        .display-row:hover::before {
            opacity: 1;
        }
        
        /* Animasi untuk schedule baru */
        .display-row.new-entry {
            animation: slideInFromTop 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes slideInFromTop {
            0% {
                opacity: 0;
                transform: translateY(-100px) scale(0.8);
                box-shadow: 0 0 0 rgba(0, 212, 255, 0);
            }
            50% {
                box-shadow: 0 0 60px rgba(0, 212, 255, 0.8);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
                box-shadow: 0 0 0 rgba(0, 212, 255, 0);
            }
        }
        
        /* Animasi untuk update schedule */
        .display-row.updated {
            animation: updateFlash 0.6s ease;
        }
        
        @keyframes updateFlash {
            0%, 100% {
                background: linear-gradient(135deg, rgba(0, 212, 255, 0.05), transparent);
            }
            25% {
                background: linear-gradient(135deg, rgba(0, 212, 255, 0.3), transparent);
                box-shadow: 0 0 50px rgba(0, 212, 255, 0.6);
            }
            50% {
                background: linear-gradient(135deg, rgba(0, 212, 255, 0.15), transparent);
            }
            75% {
                background: linear-gradient(135deg, rgba(0, 212, 255, 0.25), transparent);
            }
        }
        
        /* Animasi untuk perubahan status */
        .display-row.status-change {
            animation: statusChange 1s ease;
        }
        
        @keyframes statusChange {
            0% {
                filter: brightness(1);
            }
            25% {
                filter: brightness(1.5);
                transform: scale(1.02);
            }
            50% {
                filter: brightness(1.2);
            }
            100% {
                filter: brightness(1);
                transform: scale(1);
            }
        }
        
        .display-cell {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .cell-label {
            font-size: 0.75rem;
            color: var(--airport-text-dim);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
        }
        
        .cell-value {
            font-size: 1.1rem;
            color: var(--airport-text);
            font-weight: 600;
            line-height: 1.4;
            transition: all 0.3s ease;
        }
        
        .cell-value.spk {
            font-family: 'Orbitron', monospace;
            font-size: 1.8rem;
            color: var(--airport-accent);
            text-shadow: 0 0 15px var(--airport-glow);
            font-weight: 900;
        }
        
        .cell-value.qty {
            font-family: 'Orbitron', monospace;
            font-size: 1.5rem;
            color: var(--airport-text);
        }
        
        .cell-value.small {
            font-size: 0.9rem;
            color: var(--airport-text-dim);
        }
        
        .cell-value.update-highlight {
            animation: valueUpdate 0.5s ease;
        }
        
        @keyframes valueUpdate {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); color: var(--airport-accent); }
        }
        
        .digital-time-display {
            font-family: 'Orbitron', monospace;
            color: var(--airport-accent);
            font-size: 0.9rem;
            letter-spacing: 1px;
            margin-top: 0.25rem;
        }
        
        /* Status Badge */
        .status-badge-display {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            border: 3px solid;
            box-shadow: 0 0 25px currentColor;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .status-badge-display i {
            font-size: 1.3rem;
        }
        
        .status-badge-display.pending,
        .status-badge-display.not-started {
            background: rgba(251, 191, 36, 0.15);
            color: var(--airport-pending);
            border-color: var(--airport-pending);
        }
        
        .status-badge-display.running {
            background: rgba(16, 185, 129, 0.15);
            color: var(--airport-running);
            border-color: var(--airport-running);
            animation: pulse 2s ease-in-out infinite;
        }
        
        .status-badge-display.finish {
            background: rgba(59, 130, 246, 0.15);
            color: var(--airport-finish);
            border-color: var(--airport-finish);
        }
        
        .status-badge-display.status-update {
            animation: statusBadgeUpdate 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes statusBadgeUpdate {
            0% {
                transform: scale(1) rotate(0deg);
            }
            50% {
                transform: scale(1.2) rotate(5deg);
                box-shadow: 0 0 40px currentColor;
            }
            100% {
                transform: scale(1) rotate(0deg);
            }
        }
        
        /* Empty State */
        .empty-display {
            text-align: center;
            padding: 6rem 2rem;
            color: var(--airport-text-dim);
        }
        
        .empty-display i {
            font-size: 6rem;
            margin-bottom: 2rem;
            opacity: 0.3;
        }
        
        .empty-display p {
            font-size: 1.8rem;
            font-family: 'Orbitron', sans-serif;
        }
        
        /* Update Notification */
        .update-flash {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.95), rgba(0, 184, 230, 0.95));
            color: var(--airport-bg);
            padding: 3rem 5rem;
            border-radius: 20px;
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 3px;
            box-shadow: 0 0 60px rgba(0, 212, 255, 0.8);
            z-index: 10000;
            animation: flashNotification 1.5s ease;
            pointer-events: none;
        }
        
        @keyframes flashNotification {
            0%, 100% { 
                opacity: 0; 
                transform: translate(-50%, -50%) scale(0.8) rotateY(90deg);
            }
            10%, 90% { 
                opacity: 1; 
                transform: translate(-50%, -50%) scale(1) rotateY(0deg);
            }
        }
        
        /* Modal Styles untuk Display */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: linear-gradient(135deg, #141b3d, #1a2351);
            border: 3px solid var(--airport-accent);
            border-radius: 12px;
            padding: 0;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 8px 40px rgba(0, 212, 255, 0.5);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), transparent);
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--airport-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            color: var(--airport-accent);
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }
        
        .modal-close {
            background: transparent;
            border: 2px solid var(--airport-accent);
            color: var(--airport-accent);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: var(--airport-accent);
            color: var(--airport-bg);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 2px solid var(--airport-border);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            color: var(--airport-text);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .form-control {
            background: rgba(0, 212, 255, 0.05);
            border: 2px solid var(--airport-border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: var(--airport-text);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--airport-accent);
            background: rgba(0, 212, 255, 0.1);
            box-shadow: 0 0 15px rgba(0, 212, 255, 0.3);
        }
        
        .form-control[readonly] {
            background: rgba(0, 212, 255, 0.02);
            color: var(--airport-text-dim);
            cursor: not-allowed;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            color: var(--airport-text);
        }
        
        .checkbox-label input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: var(--airport-accent);
            border-color: var(--airport-accent);
            color: var(--airport-bg);
        }
        
        .btn-primary:hover {
            background: transparent;
            color: var(--airport-accent);
            box-shadow: 0 0 20px var(--airport-glow);
        }
        
        .btn-secondary {
            background: transparent;
            border-color: var(--airport-border);
            color: var(--airport-text);
        }
        
        .btn-secondary:hover {
            background: rgba(0, 212, 255, 0.1);
            border-color: var(--airport-accent);
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .btn-info {
            background: rgba(59, 130, 246, 0.2);
            border-color: #3b82f6;
            color: #3b82f6;
        }
        
        .btn-info:hover {
            background: #3b82f6;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 1600px) {
            .display-row {
                grid-template-columns: 1fr 2fr 1fr 1.2fr 1.2fr 1fr;
                gap: 1rem;
            }
        }
        
        @media (max-width: 1200px) {
            .display-row {
                grid-template-columns: 1fr 1.5fr 1fr;
                gap: 1rem;
            }
            
            .display-cell:nth-child(4),
            .display-cell:nth-child(5),
            .display-cell:nth-child(6) {
                grid-column: span 3;
            }
        }
    </style>
</head>
<body class="airport-theme">
    <div class="airport-display-container">
        <!-- Header -->
        <div class="display-header">
            <div class="header-content">
                <div class="header-title">
                    <div class="header-logo">
                        <img src="<?php echo IMG_URL; ?>/iw.png" alt="RBM Logo">
                    </div>
                    <div>
                        <h1>FLIGHT SCHEDULE BOARD</h1>
                        <div class="header-date" id="displayDate"></div>
                    </div>
                </div>
                <div class="header-info">
                    <div class="header-clock" id="displayClock"></div>
                    <div class="live-indicator">
                        <div class="live-dot"></div>
                        <span class="live-text">Live</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Board Content -->
        <div class="display-board" id="displayBoard">
            <?php if (empty($schedules)): ?>
            <div class="empty-display">
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
            <div class="display-row" data-schedule-id="<?php echo $schedule['id']; ?>" data-status="<?php echo str_replace(' ', '-', strtolower($schedule['status'])); ?>">
                <!-- Column 1: SPK Number -->
                <div class="display-cell">
                    <div class="cell-label">SPK Number</div>
                    <div class="cell-value spk"><?php echo htmlspecialchars($schedule['spk']); ?></div>
                </div>
                
                <!-- Column 2: Item & Customer -->
                <div class="display-cell">
                    <div class="cell-label">Item & Customer</div>
                    <div class="cell-value"><?php echo htmlspecialchars($schedule['nama_barang']); ?></div>
                    <div class="cell-value small">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($schedule['customer']); ?>
                    </div>
                </div>
                
                <!-- Column 3: Quantity -->
                <div class="display-cell">
                    <div class="cell-label">Quantity</div>
                    <div class="cell-value qty"><?php echo number_format($schedule['qty_order']); ?></div>
                    <div class="cell-value small">pieces</div>
                </div>
                
                <!-- Column 4: Cetak -->
                <div class="display-cell">
                    <div class="cell-label">Cetak</div>
                    <div class="cell-value small">
                        <i class="fas fa-print"></i> <?php echo $schedule['op_cetak'] ? htmlspecialchars($schedule['op_cetak']) : '-'; ?>
                    </div>
                    <div class="digital-time-display">
                        <?php echo $schedule['tanggal_mulai_cetak'] ? date('d/m/Y H:i', strtotime($schedule['tanggal_mulai_cetak'])) : '-'; ?>
                    </div>
                </div>
                
                <!-- Column 5: Slitting -->
                <div class="display-cell">
                    <div class="cell-label">Slitting</div>
                    <div class="cell-value small">
                        <i class="fas fa-cut"></i> <?php echo $schedule['op_slitting'] ? htmlspecialchars($schedule['op_slitting']) : '-'; ?>
                    </div>
                    <div class="digital-time-display">
                        <?php echo $schedule['tanggal_mulai_slitting'] ? date('d/m/Y H:i', strtotime($schedule['tanggal_mulai_slitting'])) : '-'; ?>
                    </div>
                </div>
                
                <!-- Column 6: Status & Action -->
                <div class="display-cell">
                    <div class="cell-label">Status & Action</div>
                    <span class="status-badge-display <?php echo str_replace(' ', '-', strtolower($schedule['status'])); ?>">
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
                    <?php if (isOperator() || isAdmin()): ?>
                    <div style="margin-top: 0.75rem;">
                        <button class="btn btn-sm btn-info" onclick="openDisplayUpdateModal(<?php echo htmlspecialchars(json_encode($schedule)); ?>)" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                            <i class="fas fa-edit"></i> Update
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isOperator() || isAdmin()): ?>
    <!-- Update Modal untuk Display -->
    <div id="displayUpdateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Schedule</h2>
                <button class="modal-close" onclick="closeDisplayUpdateModal()">&times;</button>
            </div>
            <form id="displayUpdateForm" method="POST">
                <input type="hidden" name="schedule_id" id="display_schedule_id">
                <input type="hidden" name="action" value="operator_update">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>SPK</label>
                            <input type="text" id="display_modal_spk" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Nama Barang</label>
                            <input type="text" id="display_modal_nama_barang" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="display_op_cetak">OP Cetak</label>
                            <select name="op_cetak" id="display_op_cetak" class="form-control">
                                <option value="">-- Pilih Operator Cetak --</option>
                                <option value="Rudi">Rudi</option>
                                <option value="Febri">Febri</option>
                                <option value="Rohmad">Rohmad</option>
                                <option value="Andre">Andre</option>
                                <option value="Rio">Rio</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="display_op_slitting">OP Slitting</label>
                            <select name="op_slitting" id="display_op_slitting" class="form-control">
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
                        <div id="display_current_status"></div>
                    </div>
                    
                    <div class="form-group" id="display_finish_section" style="display: none;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="mark_finish" id="display_mark_finish" value="1">
                            <span>Tandai sebagai Selesai (Finish)</span>
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDisplayUpdateModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="<?php echo JS_URL; ?>/script.js"></script>
    <script>
        // Clock and Date
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const dateString = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            document.getElementById('displayClock').textContent = timeString;
            document.getElementById('displayDate').textContent = dateString;
        }
        
        setInterval(updateClock, 1000);
        updateClock();
        
        // Real-time Sync dengan animasi canggih
        let lastCheckTimestamp = Math.floor(Date.now() / 1000);
        let syncInterval = null;
        let isSyncing = false;
        let previousSchedules = new Map();
        
        // Initialize previous schedules
        <?php foreach ($schedules as $schedule): ?>
        previousSchedules.set(<?php echo $schedule['id']; ?>, {
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
        
        function showUpdateFlash(message) {
            // Remove existing flash
            const existingFlash = document.querySelector('.update-flash');
            if (existingFlash) {
                existingFlash.remove();
            }
            
            const flash = document.createElement('div');
            flash.className = 'update-flash';
            flash.innerHTML = `<i class="fas fa-sync-alt fa-spin"></i> ${message}`;
            document.body.appendChild(flash);
            
            setTimeout(() => {
                flash.style.animation = 'flashNotification 0.5s ease';
                setTimeout(() => {
                    flash.remove();
                }, 500);
            }, 2000);
        }
        
        function checkForUpdates() {
            if (isSyncing) {
                console.log('⏳ Update check already in progress, skipping...');
                return;
            }
            
            isSyncing = true;
            
            fetch(`${BASE_URL}/api/check_updates.php?last_check=${lastCheckTimestamp}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.has_updates) {
                        console.log('🔔 Updates detected! Refreshing display...', data);
                        lastCheckTimestamp = data.timestamp;
                        refreshDisplay();
                    } else {
                        console.log('✓ No updates - data is current');
                    }
                })
                .catch(error => {
                    console.error('❌ Error checking updates:', error);
                })
                .finally(() => {
                    isSyncing = false;
                });
        }
        
        function refreshDisplay() {
            console.log('🔄 Refreshing display data...');
            fetch(`${BASE_URL}/api/get_schedules.php`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateDisplayBoard(data.schedules);
                        console.log('✅ Display updated successfully');
                    } else {
                        console.error('❌ Failed to get schedules:', data);
                    }
                })
                .catch(error => {
                    console.error('❌ Error refreshing display:', error);
                });
        }
        
        function updateDisplayBoard(schedules) {
            const board = document.getElementById('displayBoard');
            const currentSchedules = new Map();
            
            // Sort schedules: Running > Not Started > Finish, lalu yang lebih baru di atas
            schedules.sort((a, b) => {
                const statusOrder = { 'Running': 1, 'Not Started': 2, 'Finish': 3 };
                const aOrder = statusOrder[a.status] || 4;
                const bOrder = statusOrder[b.status] || 4;
                if (aOrder !== bOrder) {
                    return aOrder - bOrder;
                }
                // Jika status sama, yang lebih baru di atas
                const aTime = new Date(a.updated_at || a.created_at || 0);
                const bTime = new Date(b.updated_at || b.created_at || 0);
                return bTime - aTime;
            });
            
            if (schedules.length === 0) {
                board.innerHTML = `
                    <div class="empty-display">
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
                return;
            }
            
            // Create new schedule map
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
            
            // Update or create rows
            schedules.forEach(schedule => {
                const existingRow = board.querySelector(`[data-schedule-id="${schedule.id}"]`);
                const prevSchedule = previousSchedules.get(parseInt(schedule.id));
                const isNew = !prevSchedule;
                const isUpdated = prevSchedule && (
                    prevSchedule.status !== schedule.status.toLowerCase() ||
                    prevSchedule.spk !== (schedule.spk || '') ||
                    prevSchedule.nama_barang !== (schedule.nama_barang || '') ||
                    prevSchedule.customer !== (schedule.customer || '') ||
                    prevSchedule.qty_order !== (schedule.qty_order || '') ||
                    prevSchedule.op_cetak !== (schedule.op_cetak || '') ||
                    prevSchedule.op_slitting !== (schedule.op_slitting || '') ||
                    prevSchedule.tanggal_mulai_cetak !== (schedule.tanggal_mulai_cetak || '') ||
                    prevSchedule.tanggal_mulai_slitting !== (schedule.tanggal_mulai_slitting || '')
                );
                const statusChanged = prevSchedule && prevSchedule.status !== schedule.status.toLowerCase();
                const isNewSchedule = isNew;
                
                if (existingRow) {
                    // Update existing row
                    if (isUpdated) {
                        existingRow.classList.add('updated');
                        if (statusChanged) {
                            existingRow.classList.add('status-change');
                            const badge = existingRow.querySelector('.status-badge-display');
                            if (badge) {
                                badge.classList.add('status-update');
                            }
                        }
                        updateRowContent(existingRow, schedule);
                        
                        // Pindahkan ke paling atas jika diupdate
                        if (existingRow !== board.firstChild) {
                            board.insertBefore(existingRow, board.firstChild);
                        }
                        
                        setTimeout(() => {
                            existingRow.classList.remove('updated', 'status-change');
                            const badge = existingRow.querySelector('.status-badge-display');
                            if (badge) {
                                badge.classList.remove('status-update');
                            }
                        }, 1000);
                    }
                } else {
                    // Create new row - taruh di paling atas
                    const row = createDisplayRow(schedule);
                    if (isNewSchedule) {
                        row.classList.add('new-entry');
                        // Show notification for new schedule
                        showUpdateFlash('📋 Schedule baru ditambahkan!');
                    }
                    board.insertBefore(row, board.firstChild);
                }
            });
            
            // Update previous schedules
            previousSchedules.clear();
            schedules.forEach(schedule => {
                previousSchedules.set(parseInt(schedule.id), {
                    status: schedule.status.toLowerCase(),
                    spk: schedule.spk || '',
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
        
        function updateRowContent(row, schedule) {
            const statusIcon = schedule.status === 'Running' ? '<i class="fas fa-spinner fa-spin"></i>' :
                              schedule.status === 'Finish' ? '<i class="fas fa-check-circle"></i>' :
                              '<i class="fas fa-clock"></i>';
            
            const cetakTime = schedule.tanggal_mulai_cetak ? formatDateTime(schedule.tanggal_mulai_cetak) : '-';
            const slittingTime = schedule.tanggal_mulai_slitting ? formatDateTime(schedule.tanggal_mulai_slitting) : '-';
            
            // Update status badge
            const statusBadge = row.querySelector('.status-badge-display');
            if (statusBadge) {
                statusBadge.className = `status-badge-display ${schedule.status.toLowerCase().replace(/\s+/g, '-')}`;
                statusBadge.innerHTML = `${statusIcon} ${schedule.status}`;
            }
            
            // Update all fields
            const cells = row.querySelectorAll('.display-cell');
            cells.forEach((cell, index) => {
                if (index === 0) { // SPK Number
                    const spkCell = cell.querySelector('.cell-value.spk');
                    if (spkCell) {
                        spkCell.textContent = schedule.spk || '-';
                        spkCell.classList.add('update-highlight');
                        setTimeout(() => spkCell.classList.remove('update-highlight'), 500);
                    }
                }
                if (index === 1) { // Item & Customer
                    const itemCell = cell.querySelector('.cell-value:not(.small)');
                    const customerCell = cell.querySelector('.cell-value.small');
                    if (itemCell) {
                        itemCell.textContent = schedule.nama_barang || '-';
                        itemCell.classList.add('update-highlight');
                        setTimeout(() => itemCell.classList.remove('update-highlight'), 500);
                    }
                    if (customerCell) {
                        customerCell.innerHTML = `<i class="fas fa-building"></i> ${escapeHtml(schedule.customer || '-')}`;
                    }
                }
                if (index === 2) { // Quantity
                    const qtyCell = cell.querySelector('.cell-value.qty');
                    if (qtyCell) {
                        qtyCell.textContent = Number(schedule.qty_order || 0).toLocaleString();
                        qtyCell.classList.add('update-highlight');
                        setTimeout(() => qtyCell.classList.remove('update-highlight'), 500);
                    }
                }
                if (index === 3) { // Cetak column
                    const opCell = cell.querySelector('.cell-value.small');
                    const timeCell = cell.querySelector('.digital-time-display');
                    if (opCell) {
                        opCell.innerHTML = `<i class="fas fa-print"></i> ${schedule.op_cetak || '-'}`;
                        opCell.classList.add('update-highlight');
                        setTimeout(() => opCell.classList.remove('update-highlight'), 500);
                    }
                    if (timeCell) {
                        timeCell.textContent = cetakTime;
                        if (cetakTime !== '-') {
                            timeCell.classList.add('update-highlight');
                            setTimeout(() => timeCell.classList.remove('update-highlight'), 500);
                        }
                    }
                }
                if (index === 4) { // Slitting column
                    const opCell = cell.querySelector('.cell-value.small');
                    const timeCell = cell.querySelector('.digital-time-display');
                    if (opCell) {
                        opCell.innerHTML = `<i class="fas fa-cut"></i> ${schedule.op_slitting || '-'}`;
                        opCell.classList.add('update-highlight');
                        setTimeout(() => opCell.classList.remove('update-highlight'), 500);
                    }
                    if (timeCell) {
                        timeCell.textContent = slittingTime;
                        if (slittingTime !== '-') {
                            timeCell.classList.add('update-highlight');
                            setTimeout(() => timeCell.classList.remove('update-highlight'), 500);
                        }
                    }
                }
            });
            
            row.setAttribute('data-status', schedule.status.toLowerCase().replace(/\s+/g, '-'));
        }
        
        function createDisplayRow(schedule) {
            const row = document.createElement('div');
            row.className = 'display-row';
            row.setAttribute('data-schedule-id', schedule.id);
            row.setAttribute('data-status', schedule.status.toLowerCase().replace(/\s+/g, '-'));
            
            const statusIcon = schedule.status === 'Running' ? '<i class="fas fa-spinner fa-spin"></i>' :
                              schedule.status === 'Finish' ? '<i class="fas fa-check-circle"></i>' :
                              '<i class="fas fa-clock"></i>';
            
            const cetakTime = schedule.tanggal_mulai_cetak ? formatDateTime(schedule.tanggal_mulai_cetak) : '-';
            const slittingTime = schedule.tanggal_mulai_slitting ? formatDateTime(schedule.tanggal_mulai_slitting) : '-';
            
            row.innerHTML = `
                <div class="display-cell">
                    <div class="cell-label">SPK Number</div>
                    <div class="cell-value spk">${escapeHtml(schedule.spk)}</div>
                </div>
                
                <div class="display-cell">
                    <div class="cell-label">Item & Customer</div>
                    <div class="cell-value">${escapeHtml(schedule.nama_barang)}</div>
                    <div class="cell-value small">
                        <i class="fas fa-building"></i> ${escapeHtml(schedule.customer)}
                    </div>
                </div>
                
                <div class="display-cell">
                    <div class="cell-label">Quantity</div>
                    <div class="cell-value qty">${Number(schedule.qty_order).toLocaleString()}</div>
                    <div class="cell-value small">pieces</div>
                </div>
                
                <div class="display-cell">
                    <div class="cell-label">Cetak</div>
                    <div class="cell-value small">
                        <i class="fas fa-print"></i> ${schedule.op_cetak || '-'}
                    </div>
                    <div class="digital-time-display">${cetakTime}</div>
                </div>
                
                <div class="display-cell">
                    <div class="cell-label">Slitting</div>
                    <div class="cell-value small">
                        <i class="fas fa-cut"></i> ${schedule.op_slitting || '-'}
                    </div>
                    <div class="digital-time-display">${slittingTime}</div>
                </div>
                
                <div class="display-cell">
                    <div class="cell-label">Status</div>
                    <span class="status-badge-display ${schedule.status.toLowerCase().replace(/\s+/g, '-')}">
                        ${statusIcon} ${schedule.status}
                    </span>
                </div>
            `;
            
            return row;
        }
        
        function formatDateTime(datetime) {
            const date = new Date(datetime);
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
                   date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Start real-time sync dengan interval cepat (2 detik - sama dengan dashboard)
        function startRealtimeSync() {
            // Hentikan interval yang sudah ada jika ada
            if (syncInterval) {
                clearInterval(syncInterval);
            }
            console.log('🚀 Display mode - Real-time sync started (checking every 2 seconds)');
            syncInterval = setInterval(checkForUpdates, 2000);
        }
        
        function stopRealtimeSync() {
            if (syncInterval) {
                clearInterval(syncInterval);
                console.log('⏸️ Real-time sync stopped');
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Display page loaded - Starting real-time sync...');
            
            // Mulai real-time sync
            startRealtimeSync();
            
            // Check update pertama kali saat halaman dibuka
            // Tunggu sebentar untuk memastikan DOM sudah siap
            setTimeout(() => {
                console.log('🔍 First update check on page load...');
                checkForUpdates();
            }, 500);
            
            // Pause/resume on visibility change
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    stopRealtimeSync();
                    console.log('👁️ Page hidden - sync paused');
                } else {
                    console.log('👁️ Page visible - resuming sync');
                    lastCheckTimestamp = Math.floor(Date.now() / 1000);
                    startRealtimeSync();
                    // Check update segera saat tab kembali aktif
                    checkForUpdates();
                }
            });
        });
        
        // Mencegah perubahan halaman - tetap pada satu halaman
        // 1. Mencegah refresh halaman dengan keyboard shortcut (F5, Ctrl+R, Ctrl+F5, Ctrl+T, Ctrl+W)
        document.addEventListener('keydown', function(e) {
            // Mencegah F5
            if (e.key === 'F5') {
                e.preventDefault();
                e.stopPropagation();
                console.log('⚠️ Refresh diblokir - halaman tetap pada satu halaman');
                return false;
            }
            // Mencegah Ctrl+R atau Ctrl+F5
            if ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('⚠️ Refresh diblokir - halaman tetap pada satu halaman');
                return false;
            }
            // Mencegah Ctrl+T (buka tab baru)
            if ((e.ctrlKey || e.metaKey) && (e.key === 't' || e.key === 'T')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('⚠️ Buka tab baru diblokir - tetap pada halaman display');
                return false;
            }
            // Mencegah Ctrl+W atau Ctrl+F4 (tutup tab)
            if ((e.ctrlKey || e.metaKey) && (e.key === 'w' || e.key === 'W' || e.key === 'F4')) {
                // Biarkan tutup tab, tapi bisa dicegah jika diperlukan
                // e.preventDefault();
                // e.stopPropagation();
            }
        }, true);
        
        // 2. Mencegah perubahan URL
        let currentUrl = window.location.href;
        setInterval(function() {
            if (window.location.href !== currentUrl) {
                window.history.replaceState(null, null, currentUrl);
                console.log('⚠️ Perubahan URL diblokir - kembali ke halaman display');
            }
        }, 100);
        
        // 3. Mencegah navigasi dengan history API (tombol back/forward)
        window.addEventListener('popstate', function(e) {
            window.history.pushState(null, null, currentUrl);
            console.log('⚠️ Navigasi history diblokir - tetap pada halaman display');
        });
        
        // Push state awal untuk mencegah back button
        window.history.pushState(null, null, currentUrl);
        
        // 4. Mencegah semua link yang menyebabkan navigasi (termasuk target="_blank")
        document.addEventListener('click', function(e) {
            const target = e.target.closest('a');
            if (target && target.href) {
                try {
                    const linkUrl = new URL(target.href, window.location.origin);
                    const currentUrl = new URL(window.location.href);
                    
                    // Hanya izinkan anchor link (#) yang mengarah ke elemen di halaman yang sama
                    // Contoh: /rbmschedule/pages/display.php#top (anchor di halaman yang sama)
                    const isAnchorOnly = linkUrl.hash && 
                                        linkUrl.pathname === currentUrl.pathname && 
                                        linkUrl.search === currentUrl.search;
                    
                    // Blokir semua link yang bukan anchor di halaman yang sama
                    // Termasuk link dengan target="_blank" atau link ke halaman lain
                    if (!isAnchorOnly) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        console.log('⚠️ Navigasi link diblokir - tetap pada halaman display:', target.href);
                        return false;
                    }
                } catch (err) {
                    // Jika URL tidak valid, blokir juga
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    console.log('⚠️ Link dengan URL tidak valid diblokir:', target.href);
                    return false;
                }
            }
        }, true);
        
        // 4b. Mencegah window.open (untuk link dengan target="_blank" atau JavaScript)
        const originalWindowOpen = window.open;
        window.open = function(url, target, features) {
            console.log('⚠️ window.open diblokir - tetap pada halaman display:', url);
            return null; // Return null untuk mencegah membuka window baru
        };
        
        // 5. Mencegah form submission yang menyebabkan reload (kecuali form update)
        document.addEventListener('submit', function(e) {
            // Izinkan form update untuk submit
            if (e.target.id === 'displayUpdateForm') {
                return; // Biarkan form update submit
            }
            e.preventDefault();
            e.stopPropagation();
            console.log('⚠️ Form submission diblokir - tetap pada halaman display');
            return false;
        }, true);
        
        // 6. Mencegah reload programmatic
        const originalReload = window.location.reload;
        window.location.reload = function(forcedReload) {
            console.log('⚠️ Reload diblokir - halaman tetap pada satu halaman');
            // Tidak melakukan reload, hanya refresh data via AJAX
            if (typeof refreshDisplay === 'function') {
                refreshDisplay();
            }
        };
        
        // 6b. Mencegah assignment ke window.location (redirect)
        const originalLocation = window.location;
        let locationProxy = new Proxy(originalLocation, {
            set: function(target, property, value) {
                if (property === 'href' || property === 'pathname' || property === 'search' || property === 'hash') {
                    console.log('⚠️ Redirect diblokir - tetap pada halaman display:', value);
                    return false; // Mencegah perubahan location
                }
                target[property] = value;
                return true;
            }
        });
        
        // Override window.location dengan proxy
        try {
            Object.defineProperty(window, 'location', {
                get: function() {
                    return locationProxy;
                },
                configurable: false
            });
        } catch (e) {
            console.warn('Tidak dapat override window.location:', e);
        }
        
        // 7. Cleanup saat beforeunload
        window.addEventListener('beforeunload', function(e) {
            stopRealtimeSync();
        });
        
        // 8. Pastikan semua update dilakukan via AJAX tanpa reload
        console.log('✅ Mode Display Aktif - Halaman akan tetap pada satu halaman tanpa refresh');
        
        // Add slideOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                0% {
                    opacity: 1;
                    transform: translateX(0);
                }
                100% {
                    opacity: 0;
                    transform: translateX(-100px);
                }
            }
        `;
        document.head.appendChild(style);
        
        // Display Update Modal Functions
        <?php if (isOperator() || isAdmin()): ?>
        function openDisplayUpdateModal(schedule) {
            const modal = document.getElementById('displayUpdateModal');
            
            document.getElementById('display_schedule_id').value = schedule.id;
            document.getElementById('display_modal_spk').value = schedule.spk;
            document.getElementById('display_modal_nama_barang').value = schedule.nama_barang;
            
            // Set OP Cetak - disable jika sudah ada value
            const displayOpCetak = document.getElementById('display_op_cetak');
            if (displayOpCetak) {
                displayOpCetak.value = schedule.op_cetak || '';
                // Disable jika sudah ada value (tidak bisa diubah)
                if (schedule.op_cetak && schedule.op_cetak.trim() !== '') {
                    displayOpCetak.disabled = true;
                    displayOpCetak.title = 'Operator cetak sudah dipilih dan tidak bisa diubah';
                } else {
                    displayOpCetak.disabled = false;
                    displayOpCetak.title = '';
                }
            }
            
            // Set OP Slitting - disable jika sudah ada value
            const displayOpSlitting = document.getElementById('display_op_slitting');
            if (displayOpSlitting) {
                displayOpSlitting.value = schedule.op_slitting || '';
                // Disable jika sudah ada value (tidak bisa diubah)
                if (schedule.op_slitting && schedule.op_slitting.trim() !== '') {
                    displayOpSlitting.disabled = true;
                    displayOpSlitting.title = 'Operator slitting sudah dipilih dan tidak bisa diubah';
                } else {
                    displayOpSlitting.disabled = false;
                    displayOpSlitting.title = '';
                }
            }
            
            // Display current status
            let statusHTML = '<span class="status-badge status-' + schedule.status.toLowerCase().replace(/\s+/g, '-') + '">';
            if (schedule.status === 'Running') {
                statusHTML += '<i class="fas fa-spinner"></i> ';
            } else if (schedule.status === 'Finish') {
                statusHTML += '<i class="fas fa-check"></i> ';
            } else {
                statusHTML += '<i class="fas fa-clock"></i> ';
            }
            statusHTML += schedule.status + '</span>';
            document.getElementById('display_current_status').innerHTML = statusHTML;
            
            // Show finish checkbox only if status is Running
            const finishSection = document.getElementById('display_finish_section');
            if (schedule.status === 'Running') {
                finishSection.style.display = 'block';
            } else {
                finishSection.style.display = 'none';
            }
            document.getElementById('display_mark_finish').checked = false;
            
            modal.classList.add('active');
        }
        
        function closeDisplayUpdateModal() {
            const modal = document.getElementById('displayUpdateModal');
            if (modal) {
                // Reset disabled state saat modal ditutup
                const displayOpCetak = document.getElementById('display_op_cetak');
                const displayOpSlitting = document.getElementById('display_op_slitting');
                if (displayOpCetak) displayOpCetak.disabled = false;
                if (displayOpSlitting) displayOpSlitting.disabled = false;
                modal.classList.remove('active');
            }
        }
        
        // Handle display update form submission with AJAX
        function handleDisplayUpdateFormSubmit(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            
            fetch(`${BASE_URL}/api/schedule_ajax.php`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeDisplayUpdateModal();
                    
                    // Update timestamp to force sync on other clients
                    if (typeof lastCheckTimestamp !== 'undefined') {
                        lastCheckTimestamp = Math.floor(Date.now() / 1000);
                    }
                    
                    // Refresh display untuk update data
                    if (typeof refreshDisplay === 'function') {
                        refreshDisplay();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi!');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        }
        
        // Attach form handler saat DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                const displayUpdateForm = document.getElementById('displayUpdateForm');
                if (displayUpdateForm) {
                    displayUpdateForm.addEventListener('submit', handleDisplayUpdateFormSubmit);
                }
            });
        } else {
            const displayUpdateForm = document.getElementById('displayUpdateForm');
            if (displayUpdateForm) {
                displayUpdateForm.addEventListener('submit', handleDisplayUpdateFormSubmit);
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>




