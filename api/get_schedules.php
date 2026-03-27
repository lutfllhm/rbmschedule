<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
// Display mode doesn't require login
// require_once __DIR__ . '/../includes/auth.php';
// requireLogin();

$conn = getDBConnection();

$includeFinish = isset($_GET['include_finish']) && $_GET['include_finish'] === '1';
$status = isset($_GET['status']) ? $_GET['status'] : ($includeFinish ? 'all' : 'active');
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 50;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$lastUpdate = isset($_GET['last_update']) ? $_GET['last_update'] : null;

$page = max(1, $page);
// Meningkatkan batasan maksimal untuk menampilkan semua schedule di display_32.php
$perPage = max(5, min($perPage, 10000));

$dateFromValid = DateTime::createFromFormat('Y-m-d', $dateFrom);
$dateToValid = DateTime::createFromFormat('Y-m-d', $dateTo);
if ($dateFrom && !$dateFromValid) {
    $dateFrom = '';
}
if ($dateTo && !$dateToValid) {
    $dateTo = '';
}

$whereClauses = [];
$types = '';
$params = [];

if (!$includeFinish && $status === 'all') {
    $status = 'active';
}

if ($status === 'active') {
    $whereClauses[] = "status <> 'Finish'";
} elseif (in_array($status, ['Not Started', 'Running', 'Finish'], true)) {
    $whereClauses[] = "status = ?";
    $types .= "s";
    $params[] = $status;
}

if ($searchTerm !== '') {
    $whereClauses[] = "(spk LIKE ? OR nama_barang LIKE ? OR customer LIKE ?)";
    $like = '%' . $searchTerm . '%';
    $types .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($dateFrom !== '') {
    $whereClauses[] = "DATE(created_at) >= ?";
    $types .= "s";
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $whereClauses[] = "DATE(created_at) <= ?";
    $types .= "s";
    $params[] = $dateTo;
}

$whereSql = $whereClauses ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Count total rows for pagination metadata
$countSql = "SELECT COUNT(*) as total FROM schedules{$whereSql}";
$countStmt = $conn->prepare($countSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = intval($countResult->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$totalPages = max(1, ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$querySql = "SELECT * FROM schedules{$whereSql}
    ORDER BY FIELD(status, 'Running', 'Not Started', 'Finish'), updated_at DESC, created_at DESC
    LIMIT ? OFFSET ?";
$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$perPage, $offset]);

$stmt = $conn->prepare($querySql);
$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
$latestUpdate = null;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
        $rowUpdate = max(
            strtotime($row['created_at']),
            strtotime($row['updated_at'] ?? $row['created_at'])
        );
        if ($latestUpdate === null || $rowUpdate > $latestUpdate) {
            $latestUpdate = $rowUpdate;
        }
    }
}
$stmt->close();

closeDBConnection($conn);

$hasUpdates = true;
if ($lastUpdate !== null) {
    $hasUpdates = ($latestUpdate > floatval($lastUpdate));
}

echo json_encode([
    'success' => true,
    'schedules' => $schedules,
    'timestamp' => $latestUpdate ?? time(),
    'has_updates' => $hasUpdates,
    'meta' => [
        'total' => $totalRows,
        'page' => $page,
        'per_page' => $perPage
    ]
]);
exit();
?>