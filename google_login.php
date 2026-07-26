<?php
/**
 * Entry point tombol "Lanjutkan dengan Google" di login.php & register.php
 * (tab Desainer). Membangun state anti-CSRF lalu redirect ke consent Google.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/google_auth_helper.php';

if (!google_login_tersedia()) {
    redirect_with_alert('Login dengan Google belum dikonfigurasi di server ini.', 'login.php', 'error', 'Belum Tersedia');
}

$state = google_state_baru();
header('Location: ' . google_build_auth_url($state));
exit;
