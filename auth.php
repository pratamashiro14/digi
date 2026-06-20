<?php
/**
 * ============================================================
 *  AUTH.PHP — Pusat pengelolaan SESSION & ROLE (DensCreative)
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

/**
 * True jika user tertentu (mana pun rolenya) berstatus Premium,
 * dicek dari kolom t_user.status_member = 'premium'.
 */
function user_is_premium($id) {
    $id = (int) $id;
    if (!$id) return false;

    global $koneksi;
    if (!isset($koneksi)) {
        include __DIR__ . '/admin/koneksi.php';
    }

    // Premium dianggap AKTIF bila salah satu sumber ini terpenuhi:
    //  1) t_user.status_member = 'premium'
    //  2) t_user.premium = 1 (flag)
    //  3) ada langganan aktif & belum kedaluwarsa di t_premium
    $sql = "SELECT 1
            FROM t_user u
            LEFT JOIN t_premium p
              ON p.id_user = u.id_user
             AND p.status = 'aktif'
             AND p.tanggal_berakhir >= CURDATE()
            WHERE u.id_user = '$id'
              AND (u.status_member = 'premium' OR u.premium = 1 OR p.id_premium IS NOT NULL)
            LIMIT 1";
    $res = mysqli_query($koneksi, $sql);
    return $res && mysqli_num_rows($res) > 0;
}

/** True jika yang sedang login adalah PEMBELI dengan status Premium. */
function is_premium_buyer() {
    return is_user_login() && user_is_premium(current_id());
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
            return 'index.php';
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
        redirect_with_alert('Halaman ini khusus pembeli. Anda sedang login sebagai Desainer.', 'index.php');
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

/** Halaman khusus DESAINER TERVERIFIKASI (KTP approved). */
function require_verified_designer() {
    require_designer();
    
    // Gunakan koneksi tanpa declare ulang jika sudah ada
    global $koneksi;
    if (!isset($koneksi)) {
        include __DIR__ . '/admin/koneksi.php';
    }
    
    $id = (int) current_id();
    $cek = mysqli_query($koneksi, "SELECT status_verifikasi FROM t_user WHERE id_user='$id'");
    $data = mysqli_fetch_assoc($cek);
    
    if (!$data || $data['status_verifikasi'] !== 'verified') {
        redirect_with_alert('Fitur ini hanya untuk desainer yang KTP-nya sudah diverifikasi oleh Admin. Silakan lengkapi profil Anda.', 'profil_desainer.php');
    }
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

// ------------------------------------------------------------
// 7. VERIFIKASI PASSWORD + AUTO-UPGRADE KE BCRYPT
// ------------------------------------------------------------
/**
 * Verifikasi password sekaligus migrasi format lama (plaintext/MD5) ke bcrypt
 * secara transparan saat user berhasil login. Setelah login pertama, baris
 * password di DB otomatis berubah jadi bcrypt sehingga jalur lama tidak terpakai lagi.
 *
 * @param string $input   Password dari form
 * @param string $hash_db Nilai kolom password di DB
 * @param string $table   't_user' atau 't_admin' (dibatasi whitelist)
 * @param string $id_col  Nama kolom id baris tsb
 * @param int    $id_val  Nilai id baris tsb
 * @return bool           true jika password cocok
 */
function verify_and_upgrade_password($input, $hash_db, $table, $id_col, $id_val) {
    // Tolak akun tanpa password (NULL/kosong): tidak boleh bisa login
    if ($input === '' || $hash_db === null || $hash_db === '') {
        return false;
    }

    // 1) Format modern: bcrypt / argon (password_hash)
    if (password_verify($input, $hash_db)) {
        if (password_needs_rehash($hash_db, PASSWORD_DEFAULT)) {
            _upgrade_password_hash($input, $table, $id_col, $id_val);
        }
        return true;
    }

    // 2) Format lama (HANYA untuk migrasi): MD5 32-heksa, atau plaintext
    $is_md5_match       = (strlen($hash_db) === 32 && ctype_xdigit($hash_db) && hash_equals($hash_db, md5($input)));
    $is_plaintext_match = hash_equals($hash_db, $input);

    if ($is_md5_match || $is_plaintext_match) {
        _upgrade_password_hash($input, $table, $id_col, $id_val); // konversi ke bcrypt
        return true;
    }

    return false;
}

/** Simpan ulang password sebagai bcrypt. Internal (dipakai fungsi di atas). */
function _upgrade_password_hash($plain, $table, $id_col, $id_val) {
    global $koneksi;
    if (!isset($koneksi)) {
        include __DIR__ . '/admin/koneksi.php';
    }
    // Whitelist tabel+kolom: cegah injeksi lewat nama tabel/kolom
    $allowed = ['t_user' => 'id_user', 't_admin' => 'id_admin'];
    if (!isset($allowed[$table]) || $allowed[$table] !== $id_col) {
        return;
    }
    $new_hash = password_hash($plain, PASSWORD_DEFAULT);
    if ($stmt = mysqli_prepare($koneksi, "UPDATE `$table` SET password = ? WHERE `$id_col` = ?")) {
        mysqli_stmt_bind_param($stmt, 'si', $new_hash, $id_val);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
