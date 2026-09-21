<?php
/**
 * Mobile API - Dashboard
 *
 * GET ?status=active|all|Running|Not Started|Finish&q=&page=&per_page=
 * -> { success, stats, schedules, meta }
 *
 * Mengikuti logika pages/dashboard.php: statistik agregat, filter status,
 * pencarian, pagination, dan pembatasan akses operator (tidak boleh melihat
 * filter 'all' maupun 'Finish').
 *
 * @package RBM\Schedule\Mobile
 */

require_once __DIR__ . '/_bootstrap.php';

[$user, $conn] = requireMobileUser();

$isOperator = ($user['role'] === 'operator');

$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'active';
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

$perPage = max(5, min($perPage, 100));
$page = max(1, $page);

// Operator tidak boleh melihat schedule yang sudah Finish (sama seperti web).
if ($isOperator && in_array($filterStatus, ['Finish', 'all'], true)) {
    $filterStatus = 'active';
}

// Statistik agregat per status.
$stats = [
    'total' => 0,
    'not_started' => 0,
    'running' => 0,
    'finish' => 0,
];
$statsResult = $conn->query("SELECT status, COUNT(*) as total FROM schedules GROUP BY status");
if ($statsResult) {
    while ($row = $statsResult->fetch_assoc()) {
        $count = intval($row['total']);
        switch ($row['status']) {
            case 'Not Started':
                $stats['not_started'] += $count;
                break;
            case 'Running':
                $stats['running'] += $count;
                break;
            case 'Finish':
                $stats['finish'] += $count;
                break;
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
    $params[] = $filterStatus;
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

$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM schedules{$whereSql}");
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = intval($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$querySql = "SELECT * FROM schedules{$whereSql}
    ORDER BY FIELD(status, 'Running', 'Not Started', 'Finish'), updated_at DESC, created_at DESC
    LIMIT ? OFFSET ?";

$stmt = $conn->prepare($querySql);
$stmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = mobileScheduleRow($row);
}
$stmt->close();

closeDBConnection($conn);

mobileJson([
    'success' => true,
    'stats' => $stats,
    'schedules' => $schedules,
    'meta' => [
        'total' => $totalRows,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
    ],
]);
