<?php
/**
 * Mobile API - Login
 *
 * POST { username, password, device_name? }
 * -> { success, token, user: { id, username, role } }
 *
 * @package RBM\Schedule\Mobile
 */

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobileFail('Metode request tidak valid.', 405);
}

$input = mobileInput();
$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';
$deviceName = isset($input['device_name']) ? trim($input['device_name']) : null;

if ($username === '' || $password === '') {
    mobileFail('Silakan isi username dan password!', 422);
}

try {
    $conn = getDBConnection();
    if (!$conn) {
        mobileFail('Koneksi database gagal.', 500);
    }

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->num_rows === 1 ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user || !password_verify($password, $user['password'])) {
        closeDBConnection($conn);
        mobileFail('Username atau password salah!', 401);
    }

    $token = issueMobileToken($conn, (int) $user['id'], $deviceName);
    closeDBConnection($conn);

    mobileJson([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ],
    ]);
} catch (Exception $e) {
    error_log('Mobile login error: ' . $e->getMessage());
    mobileFail('Terjadi kesalahan pada sistem. Silakan hubungi administrator.', 500);
}
