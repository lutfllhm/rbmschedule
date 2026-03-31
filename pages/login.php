<?php
$pageTitle = 'Login';
$hideNav = true;
require_once __DIR__ . '/../includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . getPath('pages/dashboard.php'));
    exit();
}

$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<div class="login-container">
    <div class="login-layout">
        <!-- Left side: Login form -->
        <div class="login-panel">
            <div class="login-header">
                <div class="login-icon">
                    <img src="<?php echo asset('img/iw.png'); ?>" alt="RBM Logo">
                </div>
                <h1>Selamat datang di RBM Schedule</h1>
                <p>Portal internal untuk mengelola dan memantau jadwal produksi label.</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                $errorMessages = [
                    'invalid' => 'Username atau password salah!',
                    'required' => 'Silakan isi username dan password!',
                    'csrf' => 'Token keamanan tidak valid. Silakan refresh halaman dan coba lagi.',
                    'method' => 'Metode request tidak valid.',
                    'database' => 'Terjadi kesalahan pada sistem. Silakan hubungi administrator.'
                ];
                echo $errorMessages[$error] ?? 'Terjadi kesalahan. Silakan coba lagi.';
                ?>
            </div>
            <?php endif; ?>

            <form action="<?php echo getPath('api/login_process.php'); ?>" method="POST" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" class="form-control" autocomplete="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
                </div>

                <div class="login-remember-row">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                    <span class="login-small-text">Lupa password? Silakan hubungi admin RBM.</span>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block login-submit-btn" aria-label="Masuk ke RBM Schedule">
                    <i class="fas fa-sign-in-alt"></i> Masuk ke RBM Schedule
                </button>
            </form>
        </div>

        <!-- Right side: Preview / hero -->
        <div class="login-preview">
            <div class="login-preview-header">
                <h2>Papan jadwal produksi Anda</h2>
                <p>Pantau prioritas SPK, progres mesin, dan status pekerjaan dalam satu tampilan.</p>
            </div>

            <div class="login-preview-board">
                <div class="login-preview-row login-preview-row--header">
                    <span>SPK</span>
                    <span>Item</span>
                    <span>Customer</span>
                    <span>Status</span>
                </div>
                <div class="login-preview-row">
                    <span>SPK-2401</span>
                    <span>Label Botol PP</span>
                    <span>PT Sumber Makmur</span>
                    <span class="badge badge-running">Running</span>
                </div>
                <div class="login-preview-row">
                    <span>SPK-2402</span>
                    <span>Shrink Sleeve 500 ml</span>
                    <span>Indo Beverage</span>
                    <span class="badge badge-pending">Not Started</span>
                </div>
                <div class="login-preview-row">
                    <span>SPK-2398</span>
                    <span>Label Karton Export</span>
                    <span>Global Pack</span>
                    <span class="badge badge-finish">Finish</span>
                </div>
            </div>

            <div class="login-preview-meta">
                <div class="login-preview-meta-item">
                    <span class="meta-label">Realtime update</span>
                    <span class="meta-value"><i class="fas fa-signal"></i> Sinkron otomatis tiap 2 detik</span>
                </div>
                <div class="login-preview-meta-item">
                    <span class="meta-label">Mode display</span>
                    <span class="meta-value"><i class="fas fa-tv"></i> Display 32&quot; &amp; View-Only Board</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>