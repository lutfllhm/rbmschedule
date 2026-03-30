<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect to dashboard if logged in, otherwise to login
if (isLoggedIn()) {
    header('Location: pages/dashboard.php');
} else {
    header('Location: pages/login.php');
}
exit();
?>