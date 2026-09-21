<?php
/**
 * Mobile API Bootstrap
 *
 * Token-based authentication layer for the Flutter mobile app.
 * Sengaja terpisah dari includes/auth.php agar aplikasi web tidak terpengaruh:
 * tidak memakai session cookie, tidak memakai CSRF token, dan tidak ada
 * timeout 30 menit seperti di web.
 *
 * @package RBM\Schedule\Mobile
 */

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../config/autoload.php';
require_once __DIR__ . '/../../config/database.php';

/** Masa berlaku token: 30 hari sejak terakhir dipakai. */
define('MOBILE_TOKEN_TTL', 30 * 24 * 60 * 60);

/**
 * Kirim response JSON lalu hentikan eksekusi.
 */
function mobileJson(array $payload, int $httpCode = 200): void
{
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Kirim response gagal.
 */
function mobileFail(string $message, int $httpCode = 400): void
{
    mobileJson(['success' => false, 'message' => $message], $httpCode);
}

/**
 * Pastikan tabel token tersedia (dibuat otomatis saat pertama dipakai).
 */
function ensureMobileTokenTable(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS mobile_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            device_name VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_mobile_tokens_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/**
 * Ambil token bearer dari request.
 *
 * Beberapa konfigurasi Apache/proxy membuang header Authorization sebelum
 * sampai ke PHP, jadi header X-Auth-Token disediakan sebagai cadangan.
 */
function mobileBearerToken(): ?string
{
    $header = null;

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        foreach (apache_request_headers() as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $header = $value;
                break;
            }
        }
    }

    if ($header && stripos($header, 'Bearer ') === 0) {
        $token = trim(substr($header, 7));
        if ($token !== '') {
            return $token;
        }
    }

    // Cadangan: header kustom yang tidak pernah disaring proxy.
    $fallback = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    $fallback = trim($fallback);

    return $fallback !== '' ? $fallback : null;
}

/**
 * Terbitkan token baru untuk seorang user.
 *
 * @return string Token mentah (hanya dikembalikan sekali, yang disimpan adalah hash-nya).
 */
function issueMobileToken(mysqli $conn, int $userId, ?string $deviceName): string
{
    ensureMobileTokenTable($conn);

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $device = $deviceName !== null && $deviceName !== '' ? mb_substr($deviceName, 0, 100) : null;

    $stmt = $conn->prepare("
        INSERT INTO mobile_tokens (user_id, token_hash, device_name, last_used_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iss", $userId, $hash, $device);
    $stmt->execute();
    $stmt->close();

    return $token;
}

/**
 * Cari user yang memiliki token ini, sekaligus perbarui last_used_at.
 *
 * @return array|null Data user, atau null jika token tidak valid/kedaluwarsa.
 */
function resolveMobileUser(mysqli $conn, string $token): ?array
{
    ensureMobileTokenTable($conn);

    $hash = hash('sha256', $token);

    $stmt = $conn->prepare("
        SELECT t.id AS token_id, t.last_used_at, u.id, u.username, u.role
        FROM mobile_tokens t
        INNER JOIN users u ON u.id = t.user_id
        WHERE t.token_hash = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    // Token kedaluwarsa jika terlalu lama tidak dipakai.
    if ($row['last_used_at'] !== null) {
        $lastUsed = strtotime($row['last_used_at']);
        if ($lastUsed !== false && (time() - $lastUsed) > MOBILE_TOKEN_TTL) {
            $del = $conn->prepare("DELETE FROM mobile_tokens WHERE id = ?");
            $del->bind_param("i", $row['token_id']);
            $del->execute();
            $del->close();
            return null;
        }
    }

    $touch = $conn->prepare("UPDATE mobile_tokens SET last_used_at = NOW() WHERE id = ?");
    $touch->bind_param("i", $row['token_id']);
    $touch->execute();
    $touch->close();

    return [
        'id' => (int) $row['id'],
        'username' => $row['username'],
        'role' => $row['role'],
        'token_id' => (int) $row['token_id'],
    ];
}

/**
 * Cabut token yang sedang dipakai (logout).
 */
function revokeMobileToken(mysqli $conn, int $tokenId): void
{
    $stmt = $conn->prepare("DELETE FROM mobile_tokens WHERE id = ?");
    $stmt->bind_param("i", $tokenId);
    $stmt->execute();
    $stmt->close();
}

/**
 * Wajibkan token valid. Mengembalikan [user, conn].
 *
 * includes/audit.php memanggil getCurrentUser() dari session, jadi kita isi
 * $_SESSION agar logging audit tetap mencatat pelaku yang benar.
 */
function requireMobileUser(): array
{
    $token = mobileBearerToken();
    if ($token === null) {
        mobileFail('Token tidak ditemukan. Silakan login kembali.', 401);
    }

    $conn = getDBConnection();
    if (!$conn) {
        mobileFail('Koneksi database gagal.', 500);
    }

    $user = resolveMobileUser($conn, $token);
    if ($user === null) {
        closeDBConnection($conn);
        mobileFail('Sesi berakhir. Silakan login kembali.', 401);
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    return [$user, $conn];
}

/**
 * Wajibkan role admin.
 */
function requireMobileAdmin(array $user, mysqli $conn): void
{
    if (($user['role'] ?? '') !== 'admin') {
        closeDBConnection($conn);
        mobileFail('Akses ditolak. Hanya admin yang dapat melakukan aksi ini.', 403);
    }
}

/**
 * Ambil body JSON dari request, fallback ke $_POST untuk form-encoded.
 */
function mobileInput(): array
{
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return $_POST;
}

/**
 * Normalisasi satu baris schedule agar tipe datanya konsisten untuk Flutter.
 */
function mobileScheduleRow(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'spk' => $row['spk'],
        'nama_barang' => $row['nama_barang'],
        'qty_order' => (int) $row['qty_order'],
        'customer' => $row['customer'],
        'op_cetak' => $row['op_cetak'],
        'tanggal_mulai_cetak' => $row['tanggal_mulai_cetak'],
        'op_slitting' => $row['op_slitting'],
        'tanggal_mulai_slitting' => $row['tanggal_mulai_slitting'],
        'status' => $row['status'],
        'catatan' => $row['catatan'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];
}
