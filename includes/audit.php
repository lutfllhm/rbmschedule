<?php
require_once __DIR__ . '/auth.php';

/**
 * Store schedule activity for auditing purposes.
 *
 * @param mysqli $conn
 * @param int|null $scheduleId
 * @param string $action
 * @param string $description
 * @param array|null $changes
 */
function logScheduleActivity(mysqli $conn, ?int $scheduleId, string $action, string $description, ?array $changes = null): void
{
    $user = getCurrentUser();
    $userId = $user['id'] ?? null;
    $username = $user['username'] ?? 'system';
    $role = $user['role'] ?? 'system';
    $changesJson = $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null;

    $stmt = $conn->prepare("
        INSERT INTO schedule_logs (schedule_id, user_id, username, role, action, description, changes_json)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iisssss",
        $scheduleId,
        $userId,
        $username,
        $role,
        $action,
        $description,
        $changesJson
    );

    $stmt->execute();
    $stmt->close();
}

