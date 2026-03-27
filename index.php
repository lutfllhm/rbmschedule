<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect to dashboard if logged in, otherwise to login
if (isLoggedIn()) {
    header('Location: /rbmschedule/pages/dashboard.php');
} else {
    header('Location: /rbmschedule/pages/login.php');
}
exit();
?>