<?php
/**
 * ============================================================
 *  IDENTITAS_HELPER.PHP — Normalisasi & blokir identitas generik
 * ============================================================
 * Menggeneralisasi t_blokir_nik (khusus NIK) menjadi t_blokir_identitas
 * yang bisa memblokir NIK (desainer), nomor HP, atau email (pelanggan).
 *
 * Pelanggan tidak lagi diwajibkan KTP — jangkar identitas penggantinya
 * adalah nomor HP unik (1 nomor = 1 akun) + email terverifikasi. Ban
 * berbasis nomor HP tidak sekuat NIK (nomor baru bisa dibeli murah), tapi
 * jauh lebih kuat daripada email (gratis, tanpa biaya). Trade-off ini
 * disengaja — lihat catatan di bidding_helper.php.
 */

/**
 * Ubah berbagai format input nomor HP jadi bentuk baku 08xxxxxxxxxx.
 * Menerima: '0812...', '62812...', '+62812...', dengan spasi/strip/titik.
 */
function normalisasi_telp($raw) {
    $d = preg_replace('/[^0-9]/', '', (string) $raw);
    if ($d === '') return '';
    if (strpos($d, '62') === 0) {
        $d = '0' . substr($d, 2);
    } elseif ($d[0] !== '0') {
        $d = '0' . $d;
    }
    return $d;
}

/** True bila nomor (bentuk baku 08xxxxxxxxxx) punya panjang & awalan wajar. */
function telp_valid($norm) {
    return (bool) preg_match('/^08[1-9][0-9]{6,11}$/', (string) $norm);
}

/** True bila nomor sudah dipakai akun LAIN (aturan: 1 nomor = 1 akun). */
function telp_dipakai_user_lain($koneksi, $norm, $id_user_sekarang) {
    $norm = (string) $norm;
    if ($norm === '') return false;
    $id_user_sekarang = (int) $id_user_sekarang;

    $stmt = mysqli_prepare($koneksi,
        "SELECT 1 FROM t_user WHERE no_telp_norm = ? AND id_user <> ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'si', $norm, $id_user_sekarang);
    mysqli_stmt_execute($stmt);
    $ada = mysqli_fetch_row(mysqli_stmt_get_result($stmt)) !== null;
    mysqli_stmt_close($stmt);
    return $ada;
}

/** True jika nilai identitas ($tipe: 'nik'|'telp'|'email') ada di daftar blokir admin. */
function identitas_diblokir($koneksi, $tipe, $nilai) {
    $nilai = trim((string) $nilai);
    if ($nilai === '') return false;

    $stmt = mysqli_prepare($koneksi,
        "SELECT 1 FROM t_blokir_identitas WHERE tipe = ? AND nilai = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $tipe, $nilai);
    mysqli_stmt_execute($stmt);
    $ada = mysqli_fetch_row(mysqli_stmt_get_result($stmt)) !== null;
    mysqli_stmt_close($stmt);
    return $ada;
}

/** Blokir permanen sebuah identitas (dipakai admin/tables/datapengguna.php). */
function blokir_identitas($koneksi, $tipe, $nilai, $alasan, $id_admin) {
    $nilai = trim((string) $nilai);
    if ($nilai === '' || !in_array($tipe, ['nik', 'telp', 'email'], true)) return false;

    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO t_blokir_identitas (tipe, nilai, alasan, id_admin)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE alasan = VALUES(alasan), id_admin = VALUES(id_admin), tanggal_blokir = NOW()");
    mysqli_stmt_bind_param($stmt, 'sssi', $tipe, $nilai, $alasan, $id_admin);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/** Buka blokir sebuah identitas. */
function buka_blokir_identitas($koneksi, $tipe, $nilai) {
    $nilai = trim((string) $nilai);
    if ($nilai === '') return false;

    $stmt = mysqli_prepare($koneksi, "DELETE FROM t_blokir_identitas WHERE tipe = ? AND nilai = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $tipe, $nilai);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
