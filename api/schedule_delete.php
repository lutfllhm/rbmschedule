<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /rbmschedule/pages/manage.php');
    exit();
}

$schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;

if ($schedule_id <= 0) {
    header('Location: /rbmschedule/pages/manage.php?error=invalid');
    exit();
}

$conn = getDBConnection();

$stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
$stmt->bind_param("i", $schedule_id);

if ($stmt->execute()) {
    $stmt->close();
    closeDBConnection($conn);
    header('Location: /rbmschedule/pages/manage.php?success=deleted');
    exit();
}

$stmt->close();
closeDBConnection($conn);
header('Location: /rbmschedule/pages/manage.php?error=failed');
exit();
?>