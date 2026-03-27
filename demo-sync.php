<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Real-Time Sync - RBM Schedule</title>
    <link rel="stylesheet" href="/rbmschedule/assets/css/style.css">
    <link rel="stylesheet" href="/rbmschedule/assets/css/airport-board.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .demo-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .demo-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .demo-header h1 {
            font-size: 3rem;
            color: var(--airport-accent);
            text-shadow: 0 0 30px var(--airport-glow);
            margin-bottom: 1rem;
        }
        
        .demo-header p {
            font-size: 1.25rem;
            color: var(--airport-text-dim);
        }
        
        .demo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .demo-panel {
            background: var(--airport-card-bg);
            border: 2px solid var(--airport-border);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .demo-panel h2 {
            color: var(--airport-accent);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(0, 212, 255, 0.1);
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .pulse-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--airport-running);
            box-shadow: 0 0 20px var(--airport-running);
            animation: pulse 2s ease-in-out infinite;
        }
        
        .counter-box {
            background: rgba(0, 212, 255, 0.05);
            border: 1px solid var(--airport-border);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .counter-value {
            font-size: 3rem;
            font-family: 'Courier New', monospace;
            color: var(--airport-accent);
            text-shadow: 0 0 20px var(--airport-glow);
            font-weight: bold;
        }
        
        .counter-label {
            color: var(--airport-text-dim);
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }
        
        .activity-log {
            background: #000;
            border: 1px solid var(--airport-border);
            border-radius: 8px;
            padding: 1rem;
            height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
        
        .log-line {
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            border-left: 3px solid var(--airport-accent);
            padding-left: 1rem;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .log-time {
            color: var(--airport-text-dim);
            margin-right: 0.5rem;
        }
        
        .log-success {
            color: #86efac;
            border-left-color: var(--airport-running);
        }
        
        .log-info {
            color: #93c5fd;
            border-left-color: var(--airport-finish);
        }
        
        .log-warning {
            color: #fde68a;
            border-left-color: var(--airport-pending);
        }
        
        .instructions {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(251, 191, 36, 0.05));
            border: 2px solid var(--airport-pending);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .instructions h3 {
            color: var(--airport-pending);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .instructions ol {
            margin-left: 1.5rem;
            color: var(--airport-text);
            line-height: 2;
        }
        
        .instructions li strong {
            color: var(--airport-accent);
        }
        
        @media (max-width: 768px) {
            .demo-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="airport-theme">
    <div class="demo-container">
        <div class="demo-header">
            <h1>🔄 Real-Time Sync Demo</h1>
            <p>Lihat perubahan data secara real-time tanpa refresh!</p>
        </div>
        
        <div class="instructions">
            <h3>📋 Cara Test:</h3>
            <ol>
                <li>Buka halaman ini di <strong>2 browser/tab berbeda</strong></li>
                <li>Buka juga <strong><a href="/rbmschedule/pages/dashboard.php" style="color: var(--airport-accent);">Dashboard</a></strong> di tab lain</li>
                <li>Tambah atau edit schedule di salah satu tab</li>
                <li>Lihat counter dan log di halaman ini <strong>update otomatis</strong> dalam 2 detik!</li>
                <li>Tidak perlu refresh - semua otomatis! ✨</li>
            </ol>
        </div>
        
        <div class="demo-grid">
            <div class="demo-panel">
                <h2><i class="fas fa-signal"></i> Connection Status</h2>
                <div class="status-indicator">
                    <div class="pulse-dot" id="statusDot"></div>
                    <div>
                        <div style="font-weight: bold; color: var(--airport-text);">
                            Status: <span id="connectionStatus">Connecting...</span>
                        </div>
                        <div style="color: var(--airport-text-dim); font-size: 0.9rem;">
                            Last check: <span id="lastCheckTime">-</span>
                        </div>
                    </div>
                </div>
                
                <div class="counter-box">
                    <div class="counter-value" id="totalSchedules">0</div>
                    <div class="counter-label">Total Schedules</div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="counter-box">
                        <div class="counter-value" style="font-size: 2rem; color: var(--airport-pending);" id="pendingCount">0</div>
                        <div class="counter-label">Not Started</div>
                    </div>
                    <div class="counter-box">
                        <div class="counter-value" style="font-size: 2rem; color: var(--airport-running);" id="runningCount">0</div>
                        <div class="counter-label">Running</div>
                    </div>
                    <div class="counter-box">
                        <div class="counter-value" style="font-size: 2rem; color: var(--airport-finish);" id="finishCount">0</div>
                        <div class="counter-label">Finish</div>
                    </div>
                </div>
            </div>
            
            <div class="demo-panel">
                <h2><i class="fas fa-chart-line"></i> Sync Statistics</h2>
                <div class="counter-box">
                    <div class="counter-value" id="syncChecks">0</div>
                    <div class="counter-label">Total Sync Checks</div>
                </div>
                <div class="counter-box">
                    <div class="counter-value" id="updatesDetected">0</div>
                    <div class="counter-label">Updates Detected</div>
                </div>
                <div class="counter-box">
                    <div class="counter-value">2s</div>
                    <div class="counter-label">Sync Interval</div>
                </div>
            </div>
        </div>
        
        <div class="demo-panel">
            <h2><i class="fas fa-list"></i> Activity Log</h2>
            <div class="activity-log" id="activityLog"></div>
        </div>
    </div>
    
    <script>
        let lastCheckTimestamp = Math.floor(Date.now() / 1000);
        let syncInterval = null;
        let isSyncing = false;
        let stats = {
            syncChecks: 0,
            updatesDetected: 0,
            totalSchedules: 0,
            pending: 0,
            running: 0,
            finish: 0
        };
        
        function addLog(message, type = 'info') {
            const log = document.getElementById('activityLog');
            const time = new Date().toLocaleTimeString();
            const line = document.createElement('div');
            line.className = `log-line log-${type}`;
            line.innerHTML = `<span class="log-time">[${time}]</span>${message}`;
            log.insertBefore(line, log.firstChild);
            
            // Keep only last 30 entries
            while (log.children.length > 30) {
                log.removeChild(log.lastChild);
            }
        }
        
        function updateUI() {
            document.getElementById('syncChecks').textContent = stats.syncChecks;
            document.getElementById('updatesDetected').textContent = stats.updatesDetected;
            document.getElementById('totalSchedules').textContent = stats.totalSchedules;
            document.getElementById('pendingCount').textContent = stats.pending;
            document.getElementById('runningCount').textContent = stats.running;
            document.getElementById('finishCount').textContent = stats.finish;
            document.getElementById('lastCheckTime').textContent = new Date().toLocaleTimeString();
        }
        
        function checkForUpdates() {
            if (isSyncing) return;
            
            isSyncing = true;
            stats.syncChecks++;
            
            fetch(`/rbmschedule/api/check_updates.php?last_check=${lastCheckTimestamp}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('connectionStatus').textContent = 'Connected ✓';
                    
                    if (data.success && data.has_updates) {
                        lastCheckTimestamp = data.timestamp;
                        stats.updatesDetected++;
                        addLog('🔔 NEW UPDATE DETECTED! Refreshing data...', 'success');
                        refreshScheduleData();
                    } else {
                        addLog('✓ No updates - data is current', 'info');
                    }
                    
                    updateUI();
                })
                .catch(error => {
                    document.getElementById('connectionStatus').textContent = 'Error ✗';
                    addLog('❌ Connection error: ' + error.message, 'warning');
                })
                .finally(() => {
                    isSyncing = false;
                });
        }
        
        function refreshScheduleData() {
            fetch('/rbmschedule/api/get_schedules.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        stats.totalSchedules = data.schedules.length;
                        stats.pending = data.schedules.filter(s => s.status === 'Not Started').length;
                        stats.running = data.schedules.filter(s => s.status === 'Running').length;
                        stats.finish = data.schedules.filter(s => s.status === 'Finish').length;
                        
                        updateUI();
                        addLog(`✅ Data refreshed! Total: ${stats.totalSchedules} (P:${stats.pending} R:${stats.running} F:${stats.finish})`, 'success');
                    }
                })
                .catch(error => {
                    addLog('❌ Error fetching data: ' + error.message, 'warning');
                });
        }
        
        function startSync() {
            addLog('🚀 Real-time sync started!', 'success');
            syncInterval = setInterval(checkForUpdates, 2000);
            refreshScheduleData(); // Initial load
        }
        
        function stopSync() {
            if (syncInterval) {
                clearInterval(syncInterval);
                addLog('⏸️ Sync paused', 'info');
            }
        }
        
        // Start on load
        document.addEventListener('DOMContentLoaded', function() {
            addLog('🎯 Demo initialized', 'info');
            startSync();
            
            // Pause/resume on visibility change
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    stopSync();
                } else {
                    lastCheckTimestamp = Math.floor(Date.now() / 1000);
                    startSync();
                    checkForUpdates();
                }
            });
        });
        
        window.addEventListener('beforeunload', stopSync);
    </script>
</body>
</html>