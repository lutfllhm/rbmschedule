<?php
/**
 * Mobile API - Logout
 *
 * POST (Bearer token) -> { success }
 * Token yang dipakai dicabut, perangkat lain tidak terpengaruh.
 *
 * @package RBM\Schedule\Mobile
 */

require_once __DIR__ . '/_bootstrap.php';

[$user, $conn] = requireMobileUser();

revokeMobileToken($conn, $user['token_id']);
closeDBConnection($conn);

mobileJson(['success' => true, 'message' => 'Berhasil logout.']);
