<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

$conn = getDBConnection();

// Get latest update info
$sql = "SELECT 
    MAX(GREATEST(
        UNIX_TIMESTAMP(created_at),
        COALESCE(UNIX_TIMESTAMP(updated_at), UNIX_TIMESTAMP(created_at))
    )) as latest_update,
    COUNT(*) as total_schedules
FROM schedules";

$result = $conn->query($sql);
$row = $result->fetch_assoc();
$latestUpdate = $row['latest_update'] ?? 0;
$totalSchedules = $row['total_schedules'] ?? 0;

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Real-Time Sync</title>
    <link rel="stylesheet" href="/rbmschedule/assets/css/style.css">
    <link rel="stylesheet" href="/rbmschedule/assets/css/airport-board.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .diagnostic-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .diagnostic-header {
            background: var(--airport-card-bg);
            border: 2px solid var(--airport-accent);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .diagnostic-header h1 {
            color: var(--airport-accent);
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-shadow: 0 0 20px var(--airport-glow);
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .status-box {
            background: var(--airport-card-bg);
            border: 2px solid var(--airport-border);
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .status-box h3 {
            color: var(--airport-accent);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: rgba(0, 212, 255, 0.05);
            border-radius: 6px;
        }
        
        .status-label {
            color: var(--airport-text-dim);
        }
        
        .status-value {
            color: var(--airport-text);
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }
        
        .status-value.success {
            color: var(--airport-running);
        }
        
        .status-value.error {
            color: #ef4444;
        }
        
        .log-box {
            background: #000;
            border: 2px solid var(--airport-border);
            border-radius: 12px;
            padding: 1.5rem;
            height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
        
        .log-entry {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-left: 3px solid var(--airport-accent);
            padding-left: 1rem;
        }
        
        .log-entry.success { border-left-color: var(--airport-running); color: #86efac; }
        .log-entry.error { border-left-color: #ef4444; color: #fca5a5; }
        .log-entry.warning { border-left-color: var(--airport-pending); color: #fde68a; }
        .log-entry.info { border-left-color: var(--airport-finish); color: #93c5fd; }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .pulse-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--airport-running);
            box-shadow: 0 0 10px var(--airport-running);
            animation: pulse 2s ease-in-out infinite;
            display: inline-block;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body class="airport-theme">
    <div class="diagnostic-container">
        <div class="diagnostic-header">
            <h1><i class="fas fa-stethoscope"></i> Real-Time Sync Diagnostic</h1>
            <p style="color: var(--airport-text-dim);">Monitor dan debug real-time synchronization</p>
        </div>
        
        <div class="status-grid">
            <div class="status-box">
                <h3><i class="fas fa-database"></i> Database Status</h3>
                <div class="status-item">
                    <span class="status-label">Total Schedules:</span>
                    <span class="status-value success"><?php echo $totalSchedules; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Latest Update:</span>
                    <span class="status-value"><?php echo $latestUpdate ? date('Y-m-d H:i:s', $latestUpdate) : 'N/A'; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Timestamp:</span>
                    <span class="status-value"><?php echo $latestUpdate; ?></span>
                </div>
            </div>
            
            <div class="status-box">
                <h3><i class="fas fa-signal"></i> Sync Status <span class="pulse-indicator" id="syncPulse"></span></h3>
                <div class="status-item">
                    <span class="status-label">Connection:</span>
                    <span class="status-value" id="connectionStatus">Checking...</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Last Check:</span>
                    <span class="status-value" id="lastCheckTime">-</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Sync Interval:</span>
                    <span class="status-value">2 seconds</span>
                </div>
            </div>
            
            <div class="status-box">
                <h3><i class="fas fa-chart-bar"></i> Statistics</h3>
                <div class="status-item">
                    <span class="status-label">Total Checks:</span>
                    <span class="status-value" id="totalChecks">0</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Updates Detected:</span>
                    <span class="status-value success" id="updatesDetected">0</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Errors:</span>
                    <span class="status-value error" id="errorCount">0</span>
                </div>
            </div>
        </div>
        
        <div class="status-box">
            <h3><i class="fas fa-list-alt"></i> Activity Log</h3>
            <div class="btn-group">
                <button class="btn btn-primary" onclick="manualCheck()">
                    <i class="fas fa-sync-alt"></i> Manual Check
                </button>
                <button class="btn btn-secondary" onclick="clearLog()">
                    <i class="fas fa-trash"></i> Clear Log
                </button>
                <button class="btn btn-info" onclick="testAPI()">
                    <i class="fas fa-vial"></i> Test API
                </button>
            </div>
            <div class="log-box" id="logBox"></div>
        </div>
        
        <div class="status-box" style="margin-top: 2rem;">
            <h3><i class="fas fa-info-circle"></i> Instructions</h3>
            <ol style="color: var(--airport-text); line-height: 2; margin-left: 1.5rem;">
                <li>Buka halaman ini di <strong>2 browser/tab berbeda</strong></li>
                <li>Buka <a href="/rbmschedule/pages/dashboard.php" style="color: var(--airport-accent);">Dashboard</a> di tab lain</li>
                <li>Tambah/edit schedule di dashboard</li>
                <li>Lihat log di halaman ini - harus muncul "NEW UPDATES DETECTED!" dalam 2 detik</li>
                <li>Jika tidak muncul, ada masalah dengan sync</li>
            </ol>
        </div>
    </div>
    
    <script>
        let lastCheckTimestamp = Math.floor(Date.now() / 1000);
        let syncInterval = null;
        let isSyncing = false;
        let stats = {
            totalChecks: 0,
            updatesDetected: 0,
            errorCount: 0
        };
        
        function addLog(message, type = 'info') {
            const logBox = document.getElementById('logBox');
            const time = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            entry.innerHTML = `<span style="color: var(--airport-text-dim);">[${time}]</span> ${message}`;
            logBox.insertBefore(entry, logBox.firstChild);
            
            while (logBox.children.length > 100) {
                logBox.removeChild(logBox.lastChild);
            }
        }
        
        function updateUI() {
            document.getElementById('totalChecks').textContent = stats.totalChecks;
            document.getElementById('updatesDetected').textContent = stats.updatesDetected;
            document.getElementById('errorCount').textContent = stats.errorCount;
            document.getElementById('lastCheckTime').textContent = new Date().toLocaleTimeString();
        }
        
        function checkForUpdates() {
            if (isSyncing) return;
            
            isSyncing = true;
            stats.totalChecks++;
            
            const url = `/rbmschedule/api/check_updates.php?last_check=${lastCheckTimestamp}`;
            addLog(`🔍 Checking: ${url}`, 'info');
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('connectionStatus').textContent = 'Connected ✓';
                    document.getElementById('connectionStatus').className = 'status-value success';
                    
                    addLog(`📊 Response: ${JSON.stringify(data)}`, 'info');
                    
                    if (data.success && data.has_updates) {
                        stats.updatesDetected++;
                        lastCheckTimestamp = data.timestamp;
                        addLog(`🔔 NEW UPDATES DETECTED! (${data.total_schedules} schedules)`, 'success');
                        addLog(`   └─ Last: ${data.debug.last_check_time}, Latest: ${data.debug.latest_update_time}`, 'success');
                    } else {
                        addLog(`✓ No updates (${data.total_schedules} schedules, diff: ${data.debug.difference_seconds}s)`, 'info');
                    }
                    
                    updateUI();
                })
                .catch(error => {
                    stats.errorCount++;
                    document.getElementById('connectionStatus').textContent = 'Error ✗';
                    document.getElementById('connectionStatus').className = 'status-value error';
                    addLog(`❌ Error: ${error.message}`, 'error');
                    updateUI();
                })
                .finally(() => {
                    isSyncing = false;
                });
        }
        
        function manualCheck() {
            addLog('🔄 Manual check triggered', 'warning');
            checkForUpdates();
        }
        
        function clearLog() {
            document.getElementById('logBox').innerHTML = '';
            addLog('🗑️ Log cleared', 'info');
        }
        
        function testAPI() {
            addLog('🧪 Testing API endpoints...', 'warning');
            
            // Test check_updates
            fetch('/rbmschedule/api/check_updates.php?last_check=0')
                .then(r => r.json())
                .then(data => {
                    addLog(`✅ check_updates.php: ${JSON.stringify(data)}`, 'success');
                })
                .catch(e => {
                    addLog(`❌ check_updates.php failed: ${e.message}`, 'error');
                });
            
            // Test get_schedules
            fetch('/rbmschedule/api/get_schedules.php')
                .then(r => r.json())
                .then(data => {
                    addLog(`✅ get_schedules.php: ${data.schedules.length} schedules`, 'success');
                })
                .catch(e => {
                    addLog(`❌ get_schedules.php failed: ${e.message}`, 'error');
                });
        }
        
        function startSync() {
            addLog('🚀 Real-time sync started', 'success');
            syncInterval = setInterval(checkForUpdates, 2000);
            checkForUpdates(); // Immediate first check
        }
        
        function stopSync() {
            if (syncInterval) {
                clearInterval(syncInterval);
                addLog('⏸️ Sync stopped', 'warning');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            addLog('🎯 Diagnostic page initialized', 'success');
            addLog(`📌 Initial timestamp: ${lastCheckTimestamp}`, 'info');
            startSync();
            
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    stopSync();
                } else {
                    lastCheckTimestamp = Math.floor(Date.now() / 1000);
                    startSync();
                }
            });
        });
        
        window.addEventListener('beforeunload', stopSync);
    </script>
</body>
</html>