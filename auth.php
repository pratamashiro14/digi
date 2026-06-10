<?php
/**
 * ============================================================
 *  AUTH.PHP — Pusat pengelolaan SESSION & ROLE (DIGIDESAIN)
 * ============================================================
 * Tujuan: menghilangkan bug "logout mulu" / role kacau.
 *
 * Cara pakai di SETIAP halaman .php (paling atas, baris pertama):
 *     <?php require_once __DIR__ . '/auth.php'; ?>
 *
 * Lalu untuk halaman yang butuh login:
 *     require_user();       // khusus user/pembeli
 *     require_designer();   // khusus desainer
 *     require_login();      // user ATAU desainer
 *
 * Untuk ambil data yang sedang login:
 *     current_id();    current_name();    current_role();
 *
 * Backward-compatible: tetap membaca kunci session lama
 * ($_SESSION['status'], $_SESSION['status_designer'], dll)
 * sehingga halaman yang belum dimigrasi tetap berjalan.
 */

// ------------------------------------------------------------
// 1. MULAI SESSION SEKALI SAJA, dengan masa hidup yang panjang
//    (memperbaiki "logout sendiri" karena timeout default ~24 menit)
// ------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $lifetime = 60 * 60 * 8; // 8 jam
    // Set sebelum session_start() supaya berlaku
    @ini_set('session.gc_maxlifetime', $lifetime);
    @ini_set('session.cookie_lifetime', $lifetime);
    $params = session_get_cookie_params();
    @session_set_cookie_params(
        $lifetime,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        false,   // secure (true jika sudah pakai HTTPS)
        true     // httponly
    );
    session_start();
}

require_once __DIR__ . '/sweetalert.php';

// ------------------------------------------------------------
// 2. PENGECEKAN STATUS LOGIN per ROLE
// ------------------------------------------------------------
function is_user_login() {
    if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
        return false;
    }

    $role = strtolower($_SESSION['role'] ?? 'pelanggan');
    return in_array($role, ['user', 'pelanggan', 'buyer', 'pembeli'], true);
}
function is_designer_login() {
    if (!isset($_SESSION['status_designer']) || $_SESSION['status_designer'] !== 'login') {
        return false;
    }

    $role = strtolower($_SESSION['role'] ?? 'designer');
    return in_array($role, ['designer', 'desainer'], true);
}
function is_admin_login() {
    return !empty($_SESSION['admin']);
}
/** True jika login sebagai user ATAU desainer (bukan tamu). */
function is_login() {
    return is_user_login() || is_designer_login();
}

// ------------------------------------------------------------
// 3. DATA USER YANG SEDANG LOGIN (seragam, apa pun rolenya)
// ------------------------------------------------------------
function current_role() {
    if (is_admin_login())    return 'admin';
    if (is_designer_login()) return 'designer';
    if (is_user_login())     return 'pelanggan';
    return 'guest';
}
/** ID akun (user & desainer sama-sama tersimpan di 'id_user'). */
function current_id() {
    if (is_admin_login()) {
        return $_SESSION['admin'];
    }
    return $_SESSION['id_user'] ?? null;
}
/** Nama tampilan sesuai role. */
function current_name() {
    if (is_designer_login()) {
        return $_SESSION['nama_desainer'] ?? ($_SESSION['nama'] ?? 'Desainer');
    }
    return $_SESSION['nama'] ?? 'User';
}
function current_email() {
    if (is_designer_login()) {
        return $_SESSION['email_designer'] ?? ($_SESSION['email'] ?? '');
    }
    return $_SESSION['email'] ?? '';
}

function current_role_label() {
    $labels = [
        'admin' => 'Admin',
        'designer' => 'Desainer',
        'pelanggan' => 'Pembeli',
        'guest' => 'Tamu',
    ];

    return $labels[current_role()] ?? 'Tamu';
}

function current_role_home() {
    switch (current_role()) {
        case 'admin':
            return 'admin/beranda.php';
        case 'designer':
            return 'profil_desainer.php';
        case 'pelanggan':
            return 'product.php';
        default:
            return 'index.php';
    }
}

// ------------------------------------------------------------
// 4. HELPER REDIRECT + PESAN (pesan jujur, bukan "sesi habis")
// ------------------------------------------------------------
function redirect_with_alert($msg, $url, $icon = 'info', $title = 'Informasi') {
    sweetalert_redirect($msg, $url, $icon, $title);
}

// ------------------------------------------------------------
// 5. PENJAGA HALAMAN (GUARD) — role-aware
// ------------------------------------------------------------

/** Halaman butuh login (user atau desainer). */
function require_login($redirect = 'login.php') {
    if (!is_login()) {
        redirect_with_alert('Silakan login terlebih dahulu.', $redirect);
    }
}

/** Halaman khusus USER/pembeli. Desainer diarahkan ke area-nya, bukan "logout". */
function require_user($redirect = 'login.php') {
    if (is_user_login()) return;
    if (is_designer_login()) {
        redirect_with_alert('Halaman ini khusus pembeli. Anda sedang login sebagai Desainer.', 'profil_desainer.php');
    }
    redirect_with_alert('Silakan login sebagai user terlebih dahulu.', $redirect);
}

/** Halaman khusus DESAINER. User diarahkan ke area-nya, bukan "logout". */
function require_designer($redirect = 'login.php') {
    if (is_designer_login()) return;
    if (is_user_login()) {
        redirect_with_alert('Halaman ini khusus Desainer. Anda sedang login sebagai pembeli.', 'profil.php');
    }
    redirect_with_alert('Silakan login sebagai Desainer terlebih dahulu.', $redirect);
}

// ------------------------------------------------------------
// 6. LOGIN / LOGOUT TERPUSAT (dipakai file proses_login*)
// ------------------------------------------------------------

/** Set session untuk USER biasa secara seragam. */
function login_as_user($id, $nama, $email) {
    session_regenerate_id(true); // cegah session fixation, sekaligus sesi bersih
    unset(
        $_SESSION['admin'],
        $_SESSION['status_designer'],
        $_SESSION['nama_desainer'],
        $_SESSION['nama_designer'],
        $_SESSION['email_designer']
    );
    $_SESSION['id_user'] = $id;
    $_SESSION['nama']    = $nama;
    $_SESSION['email']   = $email;
    $_SESSION['status']  = 'login';
    $_SESSION['role']    = 'pelanggan';
}

/** Set session untuk DESAINER secara seragam. */
function login_as_designer($id, $nama, $email) {
    session_regenerate_id(true);
    unset(
        $_SESSION['admin'],
        $_SESSION['status']
    );
    $_SESSION['id_user']         = $id;
    $_SESSION['nama']            = $nama;          // disimpan juga di 'nama' biar konsisten
    $_SESSION['nama_desainer']   = $nama;
    $_SESSION['email']           = $email;
    $_SESSION['email_designer']  = $email;
    $_SESSION['status_designer'] = 'login';
    $_SESSION['role']            = 'designer';
}

/** Set session untuk ADMIN dan bersihkan role publik. */
function login_as_admin($id) {
    session_regenerate_id(true);
    unset(
        $_SESSION['id_user'],
        $_SESSION['nama'],
        $_SESSION['email'],
        $_SESSION['status'],
        $_SESSION['status_designer'],
        $_SESSION['nama_desainer'],
        $_SESSION['nama_designer'],
        $_SESSION['email_designer']
    );
    $_SESSION['admin'] = $id;
    $_SESSION['role'] = 'admin';
}

/** Logout total (user/desainer). Admin punya logout sendiri di /admin. */
function do_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
