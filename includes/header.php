<?php
/**
 * Header Template
 * 
 * Includes common header elements for all pages
 * 
 * @package RBM\Schedule
 */

// Load autoloader first
require_once __DIR__ . '/../config/autoload.php';

// Load authentication and CSRF protection
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// Get current user and CSRF token
$currentUser = getCurrentUser();
$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>RBM Schedule</title>
    <link rel="icon" type="image/png" href="/rbmschedule/assets/img/iw.png">
    <link rel="stylesheet" href="/rbmschedule/assets/css/style.css">
    <link rel="stylesheet" href="/rbmschedule/assets/css/airport-board.css">
    <link rel="stylesheet" href="/rbmschedule/assets/css/modern-ui.css">
    <!-- Font Awesome with multiple CDN fallbacks -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Fallback Font Awesome CDN (without SRI to avoid mismatched hash blocking) -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.5.1/css/all.css" crossorigin="anonymous">
    <!-- Fallback CSS jika Font Awesome tidak ter-load -->
    <link rel="stylesheet" href="/rbmschedule/assets/css/fontawesome-fallback.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        // Check if Font Awesome loaded, if not use fallback
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const testIcon = document.createElement('i');
                testIcon.className = 'fas fa-check';
                testIcon.style.position = 'absolute';
                testIcon.style.visibility = 'hidden';
                document.body.appendChild(testIcon);
                
                const computedStyle = window.getComputedStyle(testIcon, ':before');
                const fontFamily = computedStyle.getPropertyValue('font-family');
                
                if (!fontFamily || !fontFamily.includes('Font Awesome')) {
                    console.warn('Font Awesome tidak ter-load, menggunakan fallback');
                    document.body.classList.add('fa-fallback');
                }
                
                document.body.removeChild(testIcon);
            }, 1000);
        });
    </script>
    <script>
        window.csrfToken = <?php echo json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
</head>
<body class="airport-theme">
    <?php if (isLoggedIn() && !isset($hideNav)): ?>
    <nav class="navbar" id="mainNavbar">
        <div class="nav-container">
            <div class="nav-brand">
                <button class="nav-toggle-btn" id="navToggleBtn" onclick="toggleNavbar()" title="Toggle Navbar">
                    <i class="fas fa-chevron-up" id="navToggleIcon"></i>
                </button>
                <div class="nav-logo">
                    <img src="/rbmschedule/assets/img/iw.png" alt="RBM Logo">
                </div>
                <span>RBM Schedule</span>
            </div>
            <div class="nav-menu">
                <a href="/rbmschedule/pages/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <?php if (isAdmin()): ?>
                <a href="/rbmschedule/pages/manage.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i> Manage
                </a>
                <?php endif; ?>
                <a href="/rbmschedule/pages/report.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Report
                </a>
                <a href="/rbmschedule/pages/display_32.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'display_32.php' ? 'active' : ''; ?>" target="_blank" title="Display 32&quot; - Live Board">
                    <i class="fas fa-tv"></i> Display 32&quot;
                </a>
                <div class="nav-user">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <span class="user-role">(<?php echo ucfirst($currentUser['role']); ?>)</span>
                    <a href="/rbmschedule/api/logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <main class="main-content">