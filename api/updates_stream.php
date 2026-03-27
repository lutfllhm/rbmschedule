<?php
set_time_limit(0);
ignore_user_abort(true);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

$initialState = fetchUpdateState($conn);
$lastTimestamp = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? intval($_SERVER['HTTP_LAST_EVENT_ID']) : ($initialState['timestamp'] ?? time());
$lastCount = $initialState['total'];

$heartbeatInterval = 15;
$lastHeartbeat = 0;

while (!connection_aborted()) {
    $state = fetchUpdateState($conn);

    if ($state['timestamp'] > $lastTimestamp || $state['total'] !== $lastCount) {
        $payload = [
            'timestamp' => $state['timestamp'],
            'total_schedules' => $state['total']
        ];

        sendSseEvent('schedule-update', $payload, $state['timestamp']);
        $lastTimestamp = $state['timestamp'];
        $lastCount = $state['total'];
    } elseif ((time() - $lastHeartbeat) >= $heartbeatInterval) {
        sendSseEvent('heartbeat', ['ts' => time()]);
        $lastHeartbeat = time();
    }

    sleep(2);
}

closeDBConnection($conn);

function fetchUpdateState(mysqli $conn): array
{
    $latestSql = "SELECT MAX(GREATEST(
        UNIX_TIMESTAMP(created_at),
        COALESCE(UNIX_TIMESTAMP(updated_at), UNIX_TIMESTAMP(created_at))
    )) as latest_update FROM schedules";

    $result = $conn->query($latestSql);
    $row = $result ? $result->fetch_assoc() : null;
    $timestamp = isset($row['latest_update']) ? intval($row['latest_update']) : time();

    $countSql = "SELECT COUNT(*) as total FROM schedules";
    $countResult = $conn->query($countSql);
    $countRow = $countResult ? $countResult->fetch_assoc() : ['total' => 0];

    return [
        'timestamp' => $timestamp,
        'total' => intval($countRow['total'])
    ];
}

function sendSseEvent(string $event, array $data, ?int $id = null): void
{
    if ($id !== null) {
        echo "id: {$id}\n";
    }
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data) . "\n\n";
    @ob_flush();
    @flush();
}

