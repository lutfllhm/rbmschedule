 <?php
// Public Display 32" View - Tidak perlu login, realtime update, maksimal 6 schedule
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

// Hitung statistik semua schedule untuk running text
$statusCounts = [
    'Not Started' => 0,
    'Running' => 0,
    'Processing' => 0,
    'Finish' => 0
];
$totalSchedules = 0;
$statsSql = "SELECT status, COUNT(*) as total FROM schedules GROUP BY status";
$statsResult = $conn->query($statsSql);
$processingCount = 0;
if ($statsResult && $statsResult->num_rows > 0) {
    while ($row = $statsResult->fetch_assoc()) {
        $status = $row['status'] ?? '';
        $count = (int)($row['total'] ?? 0);
        $totalSchedules += $count;
        if ($status === 'Running') {
            $statusCounts['Running'] = $count;
            $statusCounts['Processing'] = $count;
        } elseif (isset($statusCounts[$status])) {
            $statusCounts[$status] = $count;
        }
    }
}
$totalSchedules = max($totalSchedules, array_sum($statusCounts));
$tickerItems = [
    "Total Schedule: {$totalSchedules}",
    "Not Started: {$statusCounts['Not Started']}",
    "Processing: {$statusCounts['Processing']}",
    "Finish: {$statusCounts['Finish']}"
];

// Ambil semua schedule aktif (Not Started & Running) untuk display board
// Menggunakan filter yang sama dengan dashboard: status <> 'Finish'
// Tidak ada limit agar semua schedule ditampilkan seperti di dashboard
$sql = "SELECT * FROM schedules 
    WHERE status <> 'Finish'
    ORDER BY 
        FIELD(status, 'Running', 'Not Started'),
        COALESCE(updated_at, created_at) DESC,
        created_at DESC";
$result = $conn->query($sql);
$schedules = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (isset($row['status']) && $row['status'] === 'Running') {
            $row['status'] = 'Processing';
        }
        $schedules[] = $row;
    }
}

// Get server-side latest update timestamp (seconds since epoch) to initialize polling baseline
$latestSql = "SELECT MAX(GREATEST(
    UNIX_TIMESTAMP(created_at),
    COALESCE(UNIX_TIMESTAMP(updated_at), UNIX_TIMESTAMP(created_at))
)) as latest_update FROM schedules";
$latestResult = $conn->query($latestSql);
$latestRow = $latestResult ? $latestResult->fetch_assoc() : null;
$serverLatestUpdate = intval($latestRow['latest_update'] ?? time());

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display 32" - RBM Production</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0e27;
            --panel: #141b3d;
            --panel-2: #1a2351;
            --text: #cfe8ff;
            --text-dim: #7c8db5;
            --accent: #00d4ff;
            --running: #10b981;
            --pending: #f59e0b;
            --finish: #3b82f6;
            --border: #1a3a5c;
            --glow: rgba(0, 212, 255, 0.6);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            overflow: hidden; /* display dinding - tanpa scroll */
        }
        .container {
            height: 100vh;
            display: grid;
            grid-template-rows: 17vh 1fr auto auto; /* header, konten, ticker, footer */
        }
        /* Header */
        .header {
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            padding: 1.5rem 2.5rem 1.6rem;
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.10), rgba(0, 212, 255, 0.05));
            border-bottom: 3px solid var(--accent);
            box-shadow: 0 6px 24px rgba(0, 212, 255, 0.25);
        }
        .brand { display: flex; align-items: center; gap: 1rem; }
        .brand .logo { 
            height: 60px;
            width: auto;
        }
        
        .brand .logo img,
        .brand .logo svg {
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
        
        .brand .title {
            display: flex; flex-direction: column; gap: 0.2rem;
        }
        .brand .title h1 {
            margin: 0; font-size: 2rem; font-weight: 900; letter-spacing: 2px; color: var(--accent);
            text-shadow: 0 0 18px rgba(0, 212, 255, 0.55);
        }
        .ticker {
            overflow: hidden;
            position: relative;
            background: rgba(0, 212, 255, 0.08);
            border-top: 1px solid rgba(0, 212, 255, 0.35);
            border-bottom: 1px solid rgba(0, 212, 255, 0.35);
            box-shadow: inset 0 0 12px rgba(0, 212, 255, 0.15);
        }
        /* Footer */
        .footer {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.10), rgba(0, 212, 255, 0.05));
            border-top: 2px solid rgba(0, 212, 255, 0.35);
            padding: 0.75rem 2.5rem;
            text-align: center;
            color: var(--text-dim);
            font-size: 0.85rem;
            box-shadow: 0 -4px 16px rgba(0, 212, 255, 0.15);
        }
        .footer p {
            margin: 0;
            letter-spacing: 0.5px;
        }
        .ticker-track {
            display: flex;
            gap: 2rem;
            white-space: nowrap;
            padding: 0.4rem 2rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--text);
            text-transform: uppercase;
            will-change: transform;
        }
        .ticker-item {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .ticker-item::before {
            content: '•';
            color: var(--accent);
        }
        .meta { 
            position: absolute;
            top: 1.1rem;
            right: 2.5rem;
            display: flex; 
            flex-direction: column;
            align-items: flex-end; 
            gap: 0.5rem; 
            padding-bottom: 0.8rem;
        }
        .clock { 
            font-size: 1.2rem; 
            font-weight: 800; 
            color: var(--text); 
            text-shadow: 0 0 16px rgba(0,212,255,0.45); 
            letter-spacing: 2px;
        }
        .meta-date {
            font-size: 0.9rem;
            color: var(--text-dim);
            text-align: right;
            letter-spacing: 0.07em;
            margin-bottom: 0.6rem;
        }
        .live {
            display: inline-flex; align-items: center; gap: 0.6rem; padding: 0.5rem 1rem;
            border: 2px solid var(--accent); border-radius: 999px; background: rgba(0, 212, 255, 0.08);
        }
        .live .dot { width: 12px; height: 12px; border-radius: 40%; background: #00ff66; box-shadow: 0 0 12px #00ff66; animation: blink 1s infinite; }
        .live .txt { font-weight: 700; color: var(--accent); }
        @keyframes blink { 0%,100% {opacity:1} 50% {opacity:.4} }

        /* Board */
        .board {
            height: 100%;
            padding: 1.8rem 1.6rem 1.6rem;
            display: flex; flex-direction: column; gap: 1rem;
            overflow: hidden; /* penting: tidak scroll */
        }
        /* Container untuk scroll vertikal */
        .rows { 
            flex: 1; 
            overflow: hidden; 
            position: relative;
            padding-top: 1.5rem; /* Spacing yang cukup dari header */
            mask-image: linear-gradient(to bottom, transparent 0%, black 10%, black 90%, transparent 100%);
            -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 10%, black 90%, transparent 100%);
        }
        /* Pastikan wrapper tidak terpotong */
        .rows-wrapper {
            min-height: 100%;
        }
        /* Wrapper untuk konten yang di-scroll */
        .rows-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            will-change: transform;
            transition: opacity 0.3s ease;
        }
        /* Animasi hanya aktif jika konten lebih tinggi dari container */
        .rows-wrapper.animate {
            animation: scrollUp 80s linear infinite !important;
        }
        /* Pause animation saat hover (optional) */
        .rows:hover .rows-wrapper.animate {
            animation-play-state: paused;
        }
        /* Animasi scroll dari bawah ke atas */
        @keyframes scrollUp {
            0% {
                transform: translateY(0);
            }
            100% {
                transform: translateY(-50%);
            }
        }
        .row {
            display: grid; grid-template-columns: 1.1fr 2fr 0.9fr 1.3fr 1.3fr 1.2fr; gap: 1.2rem; align-items: center;
            background: linear-gradient(135deg, rgba(20,27,61,0.92), rgba(26,35,81,0.90));
            border: 2px solid var(--border); border-radius: 12px; padding: 0.9rem 1.2rem;
            transition: border-color .3s ease, transform .3s ease, box-shadow .3s ease;
        }
        .row:hover { border-color: var(--accent); box-shadow: 0 0 26px rgba(0, 212, 255, 0.28); transform: translateY(-2px); }
        .cell { display: flex; flex-direction: column; gap: 0.35rem; min-width: 0; }
        .label { font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
        .value { font-size: 1.15rem; color: var(--text); font-weight: 700; white-space: normal; overflow: visible; text-overflow: clip; }
        .value.spk { font-size: 1.6rem; color: var(--accent); text-shadow: 0 0 12px var(--glow); }
        .value.small { 
            font-size: 0.95rem; 
            color: var(--text-dim); 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 0.4rem; 
        }
        .value.small i { 
            display: inline-block; 
            width: 16px; 
            text-align: center; 
            color: var(--accent); 
            flex-shrink: 0;
        }
        .value.small .fa-building {
            color: var(--text-dim);
        }
        .value.small .fa-print,
        .value.small .fa-cut {
            color: var(--accent);
        }
        .value.note { 
            color: #fcd34d; 
            display: flex; 
            align-items: center; 
            gap: 0.4rem; 
            line-height: 1.2; 
        }
        .value.note i { 
            display: inline-block; 
            width: 16px; 
            text-align: center; 
            color: #fcd34d; 
            flex-shrink: 0;
        }
        .badge i {
            display: inline-block;
            margin-right: 0.25rem;
            vertical-align: middle;
        }
        .date { font-family: 'Orbitron', monospace; font-size: 0.9rem; color: var(--text); margin-top: 0.25rem; }
        .time { font-family: 'Orbitron', monospace; font-size: 0.85rem; color: var(--accent); margin-top: 0.15rem; }

        .badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border: 2px solid; border-radius: 999px; font-weight: 900; text-transform: uppercase; font-size: 0.95rem; letter-spacing: 1px; }
        .badge.pending,
        .badge.not-started { color: var(--pending); border-color: var(--pending); background: rgba(245, 158, 11, 0.12); }
        .badge.processing { color: var(--running); border-color: var(--running); background: rgba(16, 185, 129, 0.12); }
        .badge.finish  { color: var(--finish);  border-color: var(--finish);  background: rgba(59, 130, 246, 0.12); }

        .empty { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-dim); gap: 1rem; }
        .empty i { font-size: 3.5rem; opacity: .5; }
        .empty svg.empty-icon { width: 4rem; height: 4rem; opacity: 0.8; stroke: currentColor; stroke-width: 1.5px; }
        .empty svg.empty-icon rect,
        .empty svg.empty-icon line { stroke: currentColor; }
        .empty p { font-size: 1.2rem; }

        /* Animasi masuk/update */
        .row.new { animation: slideIn .5s ease; }
        .row.updated { animation: glow .8s ease; }
        @keyframes slideIn { from {opacity:0; transform: translateY(-16px);} to {opacity:1; transform: translateY(0);} }
        @keyframes glow { 0%{box-shadow:0 0 0 rgba(0,0,0,0)} 50%{box-shadow:0 0 36px rgba(0,212,255,.6)} 100%{box-shadow:0 0 0 rgba(0,0,0,0)} }
        @keyframes displayNeonPulse {
            0%, 100% {
                filter: brightness(0) saturate(100%) invert(82%) sepia(44%) saturate(1284%) hue-rotate(152deg) brightness(107%) contrast(101%) drop-shadow(0 0 12px rgba(0, 212, 255, 0.7));
            }
            50% {
                filter: brightness(0) saturate(100%) invert(82%) sepia(44%) saturate(1284%) hue-rotate(152deg) brightness(120%) contrast(105%) drop-shadow(0 0 24px rgba(0, 212, 255, 1));
            }
        }

        /* Responsif untuk resolusi kecil */
        @media (max-width: 1600px) {
            .value { font-size: 1.05rem; }
            .value.spk { font-size: 1.4rem; }
            .row { grid-template-columns: 1fr 1.7fr 0.8fr 1.1fr 1.1fr 1.1fr; }
        }
        @media (max-width: 1280px) {
            .value { font-size: 1rem; }
            .value.spk { font-size: 1.25rem; }
            .date { font-size: 0.8rem; }
            .time { font-size: 0.75rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="brand">
                <div class="logo" aria-label="RBM Logo">
                    <svg viewBox="0 0 220 80" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="RBM">
                        <defs>
                            <linearGradient id="rbmStroke" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#00d4ff" />
                                <stop offset="100%" stop-color="#7ee7ff" />
                            </linearGradient>
                        </defs>
                        <rect x="4" y="4" width="212" height="72" rx="10" ry="10" fill="none" stroke="url(#rbmStroke)" stroke-width="3" />
                        <text x="110" y="52" text-anchor="middle" font-family="Orbitron, Arial, sans-serif" font-size="38" font-weight="900" fill="#cfe8ff">RBM</text>
                    </svg>
                </div>
                <div class="title">
                    <h1> RBM PRODUCTION SCHEDULE </h1>
                </div>
            </div>
            <div class="meta">
                <div class="live">
                    <div class="dot"></div>
                    <div class="txt">LIVE UPDATE</div>
                </div>
                <div class="clock" id="headerClock">--:--:--</div>
                <div class="meta-date" id="headerDate">-</div>
            </div>
        </header>
        <main class="board">
            <div class="rows" id="rows">
                <?php if (empty($schedules)): ?>
                    <div class="empty" id="emptyState">
                        <svg class="empty-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" style="fill: none;">
                            <rect x="14" y="10" width="36" height="44" rx="4" ry="4" stroke-width="2" />
                            <rect x="22" y="4" width="20" height="8" rx="2" ry="2" stroke-width="2" />
                            <line x1="22" y1="24" x2="42" y2="24" stroke-width="2" stroke-linecap="round" />
                            <line x1="22" y1="32" x2="42" y2="32" stroke-width="2" stroke-linecap="round" />
                            <line x1="22" y1="40" x2="42" y2="40" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        <p>No Schedules Available</p>
                    </div>
                <?php else: ?>
                    <div class="rows-wrapper">
                        <?php foreach ($schedules as $schedule): ?>
                            <div class="row" data-schedule-id="<?php echo $schedule['id']; ?>" data-status="<?php echo str_replace([' ', 'Running'], ['-', 'Processing'], strtolower($schedule['status'])); ?>">
                                <div class="cell">
                                    <div class="label">SPK Number</div>
                                    <div class="value spk"><?php echo htmlspecialchars($schedule['spk']); ?></div>
                                </div>
                                <div class="cell">
                                    <div class="label">Item & Customer</div>
                                    <div class="value"><?php echo htmlspecialchars($schedule['nama_barang'] ?? ''); ?></div>
                                    <div class="value small"><i class="fas fa-building"></i> <?php echo htmlspecialchars($schedule['customer'] ?? ''); ?></div>
                                </div>
                                <div class="cell">
                                    <div class="label">Quantity</div>
                                    <div class="value"><?php echo number_format((int)($schedule['qty_order'] ?? 0)); ?></div>
                                    <div class="value small">ROLL</div>
                                </div>
                                <div class="cell">
                                    <div class="label">Cetak</div>
                                    <div class="value small"><i class="fas fa-print"></i> <?php echo $schedule['op_cetak'] ? htmlspecialchars($schedule['op_cetak']) : '-'; ?></div>
                                    <?php if ($schedule['tanggal_mulai_cetak']): ?>
                                        <div class="date"><?php echo date('d/m/Y', strtotime($schedule['tanggal_mulai_cetak'])); ?></div>
                                        <div class="time"><?php echo date('H:i:s', strtotime($schedule['tanggal_mulai_cetak'])); ?></div>
                                    <?php else: ?>
                                        <div class="date">-</div>
                                        <div class="time">-</div>
                                    <?php endif; ?>
                                </div>
                                <div class="cell">
                                    <div class="label">Slitting</div>
                                    <div class="value small"><i class="fas fa-cut"></i> <?php echo $schedule['op_slitting'] ? htmlspecialchars($schedule['op_slitting']) : '-'; ?></div>
                                    <?php if ($schedule['tanggal_mulai_slitting']): ?>
                                        <div class="date"><?php echo date('d/m/Y', strtotime($schedule['tanggal_mulai_slitting'])); ?></div>
                                        <div class="time"><?php echo date('H:i:s', strtotime($schedule['tanggal_mulai_slitting'])); ?></div>
                                    <?php else: ?>
                                        <div class="date">-</div>
                                        <div class="time">-</div>
                                    <?php endif; ?>
                                </div>
                                <div class="cell">
                                    <div class="label">Status</div>
                                    <span class="badge <?php echo str_replace([' ', 'Running'], ['-', 'Processing'], strtolower($schedule['status'])); ?>">
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
                                    <?php if (!empty($schedule['catatan'])): ?>
                                        <div class="value small note"><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($schedule['catatan']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        <div class="ticker">
            <div class="ticker-track" id="tickerTrack">
                <?php foreach ($tickerItems as $item): ?>
                    <span class="ticker-item"><?php echo htmlspecialchars($item); ?></span>
                <?php endforeach; ?>
                <?php foreach ($tickerItems as $item): ?>
                    <span class="ticker-item"><?php echo htmlspecialchars($item); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> RBM Schedule Management System. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Clock & Date
        function updateClock() {
            const now = new Date();
            document.getElementById('headerClock').textContent = now.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
            document.getElementById('headerDate').textContent = now.toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
        }
        setInterval(updateClock, 1000); updateClock();

        // Realtime logic
        // Initialize polling baseline using server time to avoid missed updates due to client clock skew
        let lastCheckTimestamp = <?php echo (int)$serverLatestUpdate; ?>;
        let syncing = false;
        let intervalId = null;
        let lastCount = <?php echo (int)$totalSchedules; ?>;
        const rowsEl = document.getElementById('rows');
        const previous = new Map();
        const tickerTrack = document.getElementById('tickerTrack');
        const tickerContainer = tickerTrack ? tickerTrack.parentElement : null;
        const tickerFallbackItems = [
            'Total Schedule: 0',
            'Not Started: 0',
            'Processing: 0',
            'Finish: 0'
        ];
        let tickerBaseItems = [];
        let tickerOffset = 0;
        let tickerLastTs = null;
        let tickerRaf = null;
        // px per detik untuk kecepatan jalan ticker (nilai tetap)
        const tickerSpeed = 18;
        // Konfigurasi animasi scroll vertikal (rows running text)
        // Semakin besar PER_ROW_SECONDS => animasi lebih lambat
        // Make vertical scrolling much slower and more readable
        const ROW_SCROLL_MIN_DURATION = 60; // detik minimum
        const ROW_SCROLL_MAX_DURATION = 900; // detik maksimum
        const PER_ROW_SECONDS = 900; // detik per baris (scale) - sedikit lebih pelan

        function buildTickerStats(list) {
            const stats = { total: 0, notStarted: 0, processing: 0, finish: 0 };
            if (!Array.isArray(list)) return stats;
            list.forEach(item => {
                if (!item) return;
                stats.total += 1;
                let status = (item.status || '').toLowerCase();
                if (status === 'running') status = 'processing';
                if (status === 'not started') stats.notStarted += 1;
                else if (status === 'processing') stats.processing += 1;
                else if (status === 'finish') stats.finish += 1;
            });
            return stats;
        }

        function statsToTickerItems(stats) {
            return [
                `Total Schedule: ${stats.total}`,
                `Not Started: ${stats.notStarted}`,
                `Processing: ${stats.processing}`,
                `Finish: ${stats.finish}`
            ];
        }

        function createTickerSpan(text) {
            const span = document.createElement('span');
            span.className = 'ticker-item';
            span.textContent = text;
            return span;
        }

        function ensureTickerFill() {
            if (!tickerTrack || !tickerBaseItems.length) return;
            const minWidth = (tickerContainer ? tickerContainer.offsetWidth : 0) * 2;
            while (tickerTrack.scrollWidth < minWidth) {
                tickerBaseItems.forEach(text => tickerTrack.appendChild(createTickerSpan(text)));
            }
        }

        function stopTickerAnimation() {
            if (tickerRaf) {
                cancelAnimationFrame(tickerRaf);
                tickerRaf = null;
            }
            tickerLastTs = null;
        }

        function stepTicker(timestamp) {
            if (!tickerTrack || !tickerBaseItems.length) return;
            if (tickerLastTs === null) tickerLastTs = timestamp;
            const delta = timestamp - tickerLastTs;
            tickerLastTs = timestamp;
            const distance = (tickerSpeed * delta) / 1000;
            tickerOffset -= distance;
            recycleTickerItems();
            tickerTrack.style.transform = `translateX(${tickerOffset}px)`;
            tickerRaf = requestAnimationFrame(stepTicker);
        }

        function recycleTickerItems() {
            if (!tickerTrack.firstElementChild) return;
            const first = tickerTrack.firstElementChild;
            const firstWidth = first.getBoundingClientRect().width;
            const gap = parseFloat(getComputedStyle(tickerTrack).columnGap || getComputedStyle(tickerTrack).gap || 0);
            const threshold = firstWidth + gap;
            if (-tickerOffset >= threshold) {
                tickerOffset += threshold;
                tickerTrack.appendChild(first);
            }
        }

        function startTickerAnimation() {
            if (!tickerTrack) return;
            stopTickerAnimation();
            tickerOffset = 0;
            tickerTrack.style.transform = 'translateX(0px)';
            tickerRaf = requestAnimationFrame(stepTicker);
        }

        

        function updateTicker(items) {
            if (!tickerTrack) return;
            tickerBaseItems = (items && items.length) ? items.slice() : tickerFallbackItems.slice();
            tickerTrack.innerHTML = '';
            // Tambahkan minimal dua set data agar tidak ada celah
            for (let i = 0; i < 2; i++) {
                tickerBaseItems.forEach(text => tickerTrack.appendChild(createTickerSpan(text)));
            }
            ensureTickerFill();
            tickerOffset = 0;
            tickerTrack.style.transform = 'translateX(0px)';
            startTickerAnimation();
        }

        const initialTickerItems = <?php echo json_encode($tickerItems, JSON_UNESCAPED_UNICODE); ?>;
        updateTicker(initialTickerItems);

        // Init previous map with PHP data
        <?php foreach ($schedules as $s): ?>
        previous.set(<?php echo (int)$s['id']; ?>, {
            id: <?php echo (int)$s['id']; ?>,
            status: '<?php echo strtolower($s['status']); ?>',
            spk: '<?php echo htmlspecialchars($s['spk'], ENT_QUOTES); ?>',
            nama_barang: '<?php echo htmlspecialchars($s['nama_barang'] ?? '', ENT_QUOTES); ?>',
            customer: '<?php echo htmlspecialchars($s['customer'] ?? '', ENT_QUOTES); ?>',
            catatan: '<?php echo htmlspecialchars($s['catatan'] ?? '', ENT_QUOTES); ?>',
            qty_order: '<?php echo (int)($s['qty_order'] ?? 0); ?>',
            op_cetak: '<?php echo htmlspecialchars($s['op_cetak'] ?? '', ENT_QUOTES); ?>',
            op_slitting: '<?php echo htmlspecialchars($s['op_slitting'] ?? '', ENT_QUOTES); ?>',
            tanggal_mulai_cetak: '<?php echo $s['tanggal_mulai_cetak'] ?? ''; ?>',
            tanggal_mulai_slitting: '<?php echo $s['tanggal_mulai_slitting'] ?? ''; ?>'
        });
        <?php endforeach; ?>

        function startSync() { if (intervalId) return; intervalId = setInterval(checkUpdates, 2000); checkUpdates(); }
        function stopSync() { if (!intervalId) return; clearInterval(intervalId); intervalId = null; }

        function checkUpdates() {
            if (syncing) return; syncing = true;
            let url = '/rbmschedule/api/check_updates.php?last_check=' + lastCheckTimestamp;
            if (lastCount !== null) {
                url += '&last_count=' + encodeURIComponent(lastCount);
            }
            fetch(url)
                .then(r => r.json())
                .then(d => {
                    if (d && typeof d.total_schedules !== 'undefined') {
                        lastCount = d.total_schedules;
                    }
                    if (d.success && d.has_updates) {
                        lastCheckTimestamp = d.timestamp; 
                        refreshBoard();
                    }
                })
                .catch(err => console.error('check_updates error', err))
                .finally(() => { syncing = false; });
        }

        function refreshBoard() {
            // Ambil semua schedule tanpa limit, seperti di dashboard
            // Gunakan status=active untuk mendapatkan semua Not Started & Running (sama seperti dashboard default)
            // Ambil semua halaman untuk memastikan semua schedule ditampilkan
            const fetchAllSchedules = async () => {
                let allSchedules = [];
                let page = 1;
                let hasMore = true;
                
                while (hasMore) {
                    try {
                        const response = await fetch(`/rbmschedule/api/get_schedules.php?status=active&per_page=1000&page=${page}`);
                        const data = await response.json();
                        
                        if (!data.success || !data.schedules || data.schedules.length === 0) {
                            hasMore = false;
                            break;
                        }
                        
                        allSchedules = allSchedules.concat(data.schedules);
                        
                        // Cek apakah masih ada halaman berikutnya
                        const totalRows = data.meta ? data.meta.total : 0;
                        const perPage = data.meta ? data.meta.per_page : 1000;
                        const totalPages = totalRows > 0 ? Math.ceil(totalRows / perPage) : 1;
                        
                        // Jika sudah mencapai halaman terakhir atau tidak ada data lagi, stop
                        if (page >= totalPages || data.schedules.length === 0 || data.schedules.length < perPage) {
                            hasMore = false;
                        } else {
                            page++;
                        }
                    } catch (err) {
                        console.error('Error fetching schedules:', err);
                        hasMore = false;
                    }
                }
                
                // Filter hanya Not Started & Processing (sama seperti dashboard dengan status=active)
                // Tidak ada limit agar semua schedule ditampilkan seperti di dashboard
                const active = allSchedules
                    .filter(s => {
                        let status = (s.status || '').trim();
                        if (status === 'Running') status = 'Processing';
                        return status === 'Not Started' || status === 'Processing';
                    })
                    .sort((a,b) => {
                        function normalizeStatus(st) { return st === 'Running' ? 'Processing' : st; }
                        const order = { 'Processing': 1, 'Not Started': 2 };
                        const aStatus = normalizeStatus((a.status || '').trim());
                        const bStatus = normalizeStatus((b.status || '').trim());
                        const ao = order[aStatus] || 9; 
                        const bo = order[bStatus] || 9;
                        if (ao !== bo) return ao - bo;
                        const at = new Date(a.updated_at || a.created_at || 0).getTime();
                        const bt = new Date(b.updated_at || b.created_at || 0).getTime();
                        return bt - at; // terbaru di atas
                    });
                
                // Tidak ada .slice() agar semua schedule ditampilkan
                renderRows(active);
                const stats = buildTickerStats(allSchedules);
                const statsItems = statsToTickerItems(stats);
                updateTicker(statsItems.length ? statsItems : tickerFallbackItems);
            };
            
            fetchAllSchedules();
        }

        // Fungsi untuk cek dan toggle animasi berdasarkan tinggi konten
        function checkAndToggleAnimation() {
            const wrapper = rowsEl.querySelector('.rows-wrapper');
            if (!wrapper) return;
            
            // Tunggu sebentar agar DOM selesai render dan layout stabil
            setTimeout(() => {
                const containerHeight = rowsEl.offsetHeight;
                const rows = wrapper.querySelectorAll('.row');
                
                if (rows.length === 0) {
                    wrapper.classList.remove('animate');
                    wrapper.style.transform = 'translateY(0)';
                    wrapper.style.animationDuration = '';
                    wrapper.style.animation = '';
                    return;
                }
                
                // Deteksi apakah sudah ada duplikasi (jumlah genap dan lebih dari 0)
                const isDuplicated = rows.length > 0 && rows.length % 2 === 0;
                
                // Ambil setengah pertama jika ada duplikasi, atau semua jika belum
                const actualRows = isDuplicated ? 
                    Array.from(rows).slice(0, rows.length / 2) : 
                    Array.from(rows);
                
                // Hitung tinggi konten (hanya dari actualRows, tanpa duplikasi)
                let contentHeight = 0;
                const gap = parseFloat(getComputedStyle(wrapper).gap) || 14.4; // 0.9rem = 14.4px
                
                actualRows.forEach((row, index) => {
                    const rowHeight = row.offsetHeight || row.getBoundingClientRect().height;
                    contentHeight += rowHeight;
                    if (index < actualRows.length - 1) {
                        contentHeight += gap;
                    }
                });
                
                // Jika konten lebih tinggi dari container, aktifkan animasi dengan duplikasi
                // Atau jika ada lebih dari 1 schedule, aktifkan animasi untuk efek running text
                if (contentHeight > containerHeight || actualRows.length > 1) {
                    // Jika belum ada duplikasi, buat duplikasi untuk seamless loop
                    if (!isDuplicated) {
                        const fragment = document.createDocumentFragment();
                        actualRows.forEach(row => {
                            fragment.appendChild(row.cloneNode(true));
                        });
                        wrapper.appendChild(fragment);
                    }
                    
                    // Hitung durasi animasi berdasarkan jumlah schedule
                    // Gunakan konfigurasi constants di atas; durasi lebih lama => animasi lebih pelan
                    const scheduleCount = actualRows.length;
                    const baseDuration = Math.max(ROW_SCROLL_MIN_DURATION, Math.min(ROW_SCROLL_MAX_DURATION, scheduleCount * PER_ROW_SECONDS));
                    
                    // Hapus semua style yang bisa mengganggu animasi
                    wrapper.style.transform = '';
                    wrapper.style.animation = '';
                    wrapper.style.animationDuration = baseDuration + 's';
                    
                    // Aktifkan animasi dengan force reflow untuk memastikan animasi berjalan
                    wrapper.classList.remove('animate');
                    void wrapper.offsetWidth; // Force reflow
                    wrapper.classList.add('animate');
                } else {
                    // Jika semua konten terlihat, hapus duplikasi dan hentikan animasi
                    if (isDuplicated) {
                        // Simpan actualRows dulu sebelum menghapus
                        const rowsToKeep = actualRows.map(row => row.cloneNode(true));
                        wrapper.innerHTML = '';
                        rowsToKeep.forEach(row => {
                            wrapper.appendChild(row);
                        });
                    }
                    wrapper.classList.remove('animate');
                    wrapper.style.transform = 'translateY(0)';
                    wrapper.style.animationDuration = '';
                    wrapper.style.animation = '';
                }
            }, 400);
        }

        function renderRows(list) {
            // Hapus empty state jika ada
            const empty = document.getElementById('emptyState');
            if (empty) empty.remove();

            // Cari atau buat wrapper untuk scroll
            let wrapper = rowsEl.querySelector('.rows-wrapper');
            if (!wrapper) {
                wrapper = document.createElement('div');
                wrapper.className = 'rows-wrapper';
                rowsEl.appendChild(wrapper);
            }

            if (list.length === 0) {
                wrapper.innerHTML = '';
                wrapper.classList.remove('animate');
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty';
                emptyDiv.id = 'emptyState';
                emptyDiv.innerHTML = '<svg class="empty-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" style="fill: none;"><rect x="14" y="10" width="36" height="44" rx="4" ry="4" stroke-width="2" /><rect x="22" y="4" width="20" height="8" rx="2" ry="2" stroke-width="2" /><line x1="22" y1="24" x2="42" y2="24" stroke-width="2" stroke-linecap="round" /><line x1="22" y1="32" x2="42" y2="32" stroke-width="2" stroke-linecap="round" /><line x1="22" y1="40" x2="42" y2="40" stroke-width="2" stroke-linecap="round" /></svg><p>No Schedules Available</p>';
                rowsEl.appendChild(emptyDiv);
                return;
            }

            // Render semua schedule
            const frag = document.createDocumentFragment();
            list.forEach(s => {
                const node = createRowNode(s);
                frag.appendChild(node);
            });

            // Fade out sedikit saat update, lalu fade in
            wrapper.style.opacity = '0.7';
            setTimeout(() => {
                wrapper.innerHTML = '';
                wrapper.appendChild(frag);
                wrapper.style.opacity = '1';
                
                // Cek apakah perlu animasi scroll setelah render selesai
                // Gunakan setTimeout tambahan untuk memastikan layout sudah stabil
                setTimeout(() => {
                    checkAndToggleAnimation();
                }, 100);
            }, 150);

            // Update snapshot previous
            previous.clear();
            list.forEach(s => {
                previous.set(parseInt(s.id), {
                    id: parseInt(s.id),
                    status: (s.status||'').toLowerCase(),
                    spk: s.spk||'',
                    nama_barang: s.nama_barang||'',
                    customer: s.customer||'',
                    catatan: s.catatan||'',
                    qty_order: s.qty_order||'',
                    op_cetak: s.op_cetak||'',
                    op_slitting: s.op_slitting||'',
                    tanggal_mulai_cetak: s.tanggal_mulai_cetak||'',
                    tanggal_mulai_slitting: s.tanggal_mulai_slitting||''
                });
            });
        }

        function createRowNode(s) {
            const row = document.createElement('div');
            row.className = 'row';
            row.setAttribute('data-schedule-id', s.id);
            function normalizeStatus(st) { if (!st) return ''; if (st === 'Running') return 'Processing'; return st; }
            const normStatus = normalizeStatus(s.status || '');
            row.setAttribute('data-status', String(normStatus).toLowerCase().replace(/\s+/g, '-'));
            // pass normalized status to render function
            const sNorm = Object.assign({}, s, { status: normStatus });
            row.innerHTML = rowInnerHtml(sNorm);
            return row;
        }
        function updateRowNode(row, s) {
            function normalizeStatus(st) { if (!st) return ''; if (st === 'Running') return 'Processing'; return st; }
            const normStatus = normalizeStatus(s.status || '');
            row.setAttribute('data-status', String(normStatus).toLowerCase().replace(/\s+/g, '-'));
            const sNorm = Object.assign({}, s, { status: normStatus });
            row.innerHTML = rowInnerHtml(sNorm);
        }
        function esc(t){ const d=document.createElement('div'); d.textContent = t==null?'' : String(t); return d.innerHTML; }
        function fmtDate(dt){ if(!dt) return '-'; const d=new Date(dt); return d.toLocaleDateString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric'}); }
        function fmtTime(dt){ if(!dt) return '-'; const d=new Date(dt); return d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'}); }
        function rowInnerHtml(s){
            let status = (s.status||'');
            if (status === 'Running') status = 'Processing';
            const badgeClass = String(status).toLowerCase().replace(/\s+/g, '-');
            const icon = status==='Processing' ? '<i class="fas fa-spinner fa-spin"></i> ' : (status==='Finish' ? '<i class="fas fa-check-circle"></i> ' : '<i class="fas fa-clock"></i> ');
            return `
                <div class="cell">
                    <div class="label">SPK Number</div>
                    <div class="value spk">${esc(s.spk)}</div>
                </div>
                <div class="cell">
                    <div class="label">Item & Customer</div>
                    <div class="value">${esc(s.nama_barang||'')}</div>
                    <div class="value small"><i class="fas fa-building"></i> ${esc(s.customer||'')}</div>
                </div>
                <div class="cell">
                    <div class="label">Quantity</div>
                    <div class="value">${Number(s.qty_order||0).toLocaleString()}</div>
                    <div class="value small">ROLL</div>
                </div>
                <div class="cell">
                    <div class="label">Cetak</div>
                    <div class="value small"><i class="fas fa-print"></i> ${esc(s.op_cetak||'-')}</div>
                    <div class="date">${fmtDate(s.tanggal_mulai_cetak)}</div>
                    <div class="time">${fmtTime(s.tanggal_mulai_cetak)}</div>
                </div>
                <div class="cell">
                    <div class="label">Slitting</div>
                    <div class="value small"><i class="fas fa-cut"></i> ${esc(s.op_slitting||'-')}</div>
                    <div class="date">${fmtDate(s.tanggal_mulai_slitting)}</div>
                    <div class="time">${fmtTime(s.tanggal_mulai_slitting)}</div>
                </div>
                <div class="cell">
                    <div class="label">Status</div>
                    <span class="badge ${badgeClass}">${icon}${esc(status)}</span>
                    ${s.catatan ? `<div class="value small note"><i class="fas fa-sticky-note"></i> ${esc(s.catatan)}</div>` : ''}
                </div>
            `;
        }

        // Visibility optimization: pause ketika tab tidak aktif
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) { 
                stopSync(); 
                stopTickerAnimation();
            }
            else { 
                lastCheckTimestamp = Math.floor(Date.now()/1000); 
                startSync(); 
                checkUpdates();
                startTickerAnimation();
            }
        });

        // Start
        document.addEventListener('DOMContentLoaded', () => { 
            startSync(); 
            startTickerAnimation();
            // Cek animasi setelah DOM ready dengan delay lebih lama
            setTimeout(() => {
                checkAndToggleAnimation();
            }, 500);
        });
        
        // Cek animasi setelah window fully loaded
        window.addEventListener('load', () => {
            setTimeout(() => {
                checkAndToggleAnimation();
            }, 800);
        });
        
        // Cek animasi saat window resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                checkAndToggleAnimation();
            }, 300);
        });

        // Tambahkan animasi slideOut (untuk remove)
        (function(){ const st=document.createElement('style'); st.textContent=`@keyframes slideOut{0%{opacity:1;transform:translateX(0)}100%{opacity:0;transform:translateX(-80px)}}`; document.head.appendChild(st); })();
    </script>
</body>
</html>

