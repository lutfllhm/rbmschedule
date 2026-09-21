<?php
/**
 * Mobile API - Profil user saat ini
 *
 * GET (Bearer token) -> { success, user }
 * Dipakai aplikasi saat start untuk memastikan token tersimpan masih berlaku.
 *
 * @package RBM\Schedule\Mobile
 */

require_once __DIR__ . '/_bootstrap.php';

[$user, $conn] = requireMobileUser();
closeDBConnection($conn);

mobileJson([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ],
]);
