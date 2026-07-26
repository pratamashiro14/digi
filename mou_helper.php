<?php
/**
 * ============================================================
 *  MOU_HELPER.PHP — Perjanjian Kerja Sama Kemitraan Desainer
 * ============================================================
 * Menyimpan & memvalidasi persetujuan MOU digital antara DENSCREATIVE
 * (PIHAK PERTAMA) dan desainer/mitra (PIHAK KEDUA).
 *
 * Sengaja dibuat sebagai file berdiri sendiri (bukan menambah ke
 * auth.php / admin/koneksi.php) supaya tidak bentrok dengan pekerjaan
 * lain yang berjalan paralel di file-file inti tersebut.
 */

// ------------------------------------------------------------
// 1. IDENTITAS DOKUMEN & PIHAK PERTAMA (sesuai draf MOU resmi)
// ------------------------------------------------------------
if (!defined('MOU_NOMOR'))            define('MOU_NOMOR', '001/DENSCREATIVE/PI-2/I/2026');
if (!defined('MOU_VERSI'))            define('MOU_VERSI', 1);
if (!defined('MOU_PIHAK1_NAMA'))      define('MOU_PIHAK1_NAMA', 'DENSCREATIVE');
if (!defined('MOU_PIHAK1_PJ'))        define('MOU_PIHAK1_PJ', 'Den Den Mauludi Apriansyah');
if (!defined('MOU_PIHAK1_INSTANSI'))  define('MOU_PIHAK1_INSTANSI', 'Universitas Logistik dan Bisnis Internasional');
if (!defined('MOU_PIHAK1_ALAMAT'))    define('MOU_PIHAK1_ALAMAT', 'Jl. Sari Asih No.54 Sarijadi Bandung 40151');
if (!defined('MOU_PIHAK1_TELP'))      define('MOU_PIHAK1_TELP', '081385228606');
if (!defined('MOU_PIHAK1_EMAIL'))     define('MOU_PIHAK1_EMAIL', 'denscreative.official@gmail.com');

// ------------------------------------------------------------
// 2. AUTO-MIGRATION TABEL t_mou
// ------------------------------------------------------------
/**
 * Buat tabel t_mou bila belum ada. Idempoten (CREATE TABLE IF NOT EXISTS),
 * tapi tetap di-guard dengan flag statis supaya query ini hanya dikirim
 * sekali per request meski dipanggil dari beberapa fungsi.
 */
function mou_ensure_table($koneksi) {
    static $sudah = false;
    if ($sudah) return;
    $sudah = true;

    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS t_mou (
        id_mou INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        nomor VARCHAR(60) NOT NULL,
        versi INT NOT NULL DEFAULT 1,
        nama_mitra VARCHAR(150) NOT NULL,
        pj_mitra VARCHAR(150) NOT NULL,
        instansi_mitra VARCHAR(150) DEFAULT NULL,
        alamat_mitra TEXT,
        telp_mitra VARCHAR(100) DEFAULT NULL,
        email_mitra VARCHAR(100) DEFAULT NULL,
        nama_ttd VARCHAR(150) NOT NULL,
        status ENUM('disetujui') NOT NULL DEFAULT 'disetujui',
        tanggal_setuju DATETIME NOT NULL,
        ip_setuju VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        UNIQUE KEY uniq_mou_user (id_user)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ------------------------------------------------------------
// 3. BACA STATUS PERSETUJUAN
// ------------------------------------------------------------
/** Ambil baris t_mou milik seorang user, atau null bila belum pernah TTD. */
function mou_get($koneksi, $id_user) {
    mou_ensure_table($koneksi);
    $id_user = (int) $id_user;
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM t_mou WHERE id_user = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    return $row ?: null;
}

/** True bila desainer sudah menandatangani MOU. */
function mou_sudah_disetujui($koneksi, $id_user) {
    return mou_get($koneksi, $id_user) !== null;
}

/** Halaman/aksi yang WAJIB menunggu MOU disetujui memanggil ini di awal. */
function require_mou_disetujui() {
    global $koneksi;
    if (!isset($koneksi)) {
        include __DIR__ . '/admin/koneksi.php';
    }
    $id = (int) current_id();
    if (!mou_sudah_disetujui($koneksi, $id)) {
        redirect_with_alert(
            'Anda harus membaca dan menyetujui Perjanjian Kerja Sama Mitra Desainer (MOU) terlebih dahulu sebelum dapat mengunggah karya.',
            'mou.php',
            'warning',
            'Persetujuan MOU Diperlukan'
        );
    }
}

// ------------------------------------------------------------
// 4. SIMPAN PERSETUJUAN
// ------------------------------------------------------------
/**
 * Simpan persetujuan MOU. Dipanggil dari proses_mou.php setelah semua
 * validasi (CSRF, checkbox, nama TTD) lolos. Mengembalikan true/false.
 */
function mou_simpan($koneksi, $id_user, $data) {
    mou_ensure_table($koneksi);
    $id_user = (int) $id_user;

    $stmt = mysqli_prepare($koneksi, "INSERT INTO t_mou
        (id_user, nomor, versi, nama_mitra, pj_mitra, instansi_mitra, alamat_mitra, telp_mitra, email_mitra, nama_ttd, status, tanggal_setuju, ip_setuju, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'disetujui', NOW(), ?, ?)");
    mysqli_stmt_bind_param(
        $stmt,
        'isisssssssss',
        $id_user,
        $data['nomor'],
        $data['versi'],
        $data['nama_mitra'],
        $data['pj_mitra'],
        $data['instansi_mitra'],
        $data['alamat_mitra'],
        $data['telp_mitra'],
        $data['email_mitra'],
        $data['nama_ttd'],
        $data['ip_setuju'],
        $data['user_agent']
    );

    // PHP 8.1+ membuat mysqli melempar mysqli_sql_exception saat query gagal
    // (bukan lagi return false apa adanya). id_user di t_mou punya UNIQUE KEY,
    // jadi klik ganda / submit berbarengan bisa memicu error 1062 (duplicate
    // entry) tepat di sini walau proses_mou.php sudah mengecek lebih dulu
    // (celah TOCTOU). Tangkap khusus kasus itu supaya jadi 'gagal biasa'
    // (return false) alih-alih fatal error / halaman putih; error lain
    // (mis. DB down) tetap dilempar apa adanya seperti semula.
    try {
        $ok = mysqli_stmt_execute($stmt);
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            return false;
        }
        throw $e;
    }

    // MOU disetujui = satu-satunya syarat kemitraan sejak KTP dihapus (Google
    // Login menggantikan KYC identitas). status_verifikasi dijadikan cache
    // fakta ini di t_user, dibaca badge "Terverifikasi" (toko_desainer.php,
    // desainer_favorit.php) & panel admin tanpa perlu JOIN ke t_mou.
    // Sumber kebenaran TETAP tabel t_mou (lihat mou_sudah_disetujui());
    // kegagalan UPDATE ini tidak menggagalkan persetujuan MOU itu sendiri.
    if ($ok) {
        $upd = mysqli_prepare($koneksi, "UPDATE t_user SET status_verifikasi = 'verified' WHERE id_user = ?");
        mysqli_stmt_bind_param($upd, 'i', $id_user);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }

    return $ok;
}

// ------------------------------------------------------------
// 5. CSRF TOKEN SEDERHANA (khusus fitur MOU)
// ------------------------------------------------------------
// Project ini belum punya helper CSRF bersama. Dibuat lokal di sini
// (bukan di auth.php) supaya tidak bersinggungan dengan file inti yang
// mungkin sedang dikerjakan di tab lain.
function mou_csrf_token() {
    if (empty($_SESSION['mou_csrf'])) {
        $_SESSION['mou_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['mou_csrf'];
}

function mou_csrf_valid($token) {
    if (empty($_SESSION['mou_csrf']) || !is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['mou_csrf'], $token);
}

// ------------------------------------------------------------
// 6. FORMAT TANGGAL INDONESIA
// ------------------------------------------------------------
/** "2026-07-26 10:00:00" -> "26 Juli 2026". Server tidak selalu punya locale id_ID aktif. */
function mou_tanggal_indo($tgl) {
    if (!$tgl) return '';
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($tgl);
    if (!$ts) return $tgl;
    return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}
