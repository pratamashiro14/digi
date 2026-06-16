<?php
/**
 * ============================================================
 *  BIDDING_HELPER.PHP — Gerbang kelayakan ikut lelang (bid)
 * ============================================================
 * Aturan: hanya pembeli yang AKTIF, KTP-nya VERIFIED, dan NIK-nya
 * TIDAK diblokir yang boleh memasang penawaran. Dipakai bersama oleh
 * product.php (quick view), product-detail.php, dan proses_bidding.php
 * supaya tampilan & validasi server konsisten.
 */

/** True jika NIK ada di daftar blokir (ban berbasis NIK). */
function nik_diblokir($koneksi, $nik) {
    $nik = trim((string) $nik);
    if ($nik === '') return false;
    $stmt = mysqli_prepare($koneksi, "SELECT 1 FROM t_blokir_nik WHERE nik = ? LIMIT 1");
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 's', $nik);
    mysqli_stmt_execute($stmt);
    $ada = mysqli_fetch_row(mysqli_stmt_get_result($stmt)) !== null;
    mysqli_stmt_close($stmt);
    return $ada;
}

/**
 * Status kelayakan bid seorang user.
 *
 * @return array{ok:bool, code:string, reason:?string}
 *   code: 'guest' | 'suspended' | 'banned' | 'pending' | 'unverified' | 'ok'
 */
function status_bid_user($koneksi, $id_user) {
    $id_user = (int) $id_user;
    if (!$id_user) {
        return ['ok' => false, 'code' => 'guest', 'reason' => 'Masuk sebagai pembeli untuk ikut lelang.'];
    }

    $stmt = mysqli_prepare($koneksi, "SELECT status, status_verifikasi, nik FROM t_user WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    $u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$u) {
        return ['ok' => false, 'code' => 'guest', 'reason' => 'Akun tidak ditemukan.'];
    }
    if (($u['status'] ?? '') === 'nonaktif') {
        return ['ok' => false, 'code' => 'suspended', 'reason' => 'Akun Anda sedang disuspend oleh admin. Hubungi admin untuk informasi lebih lanjut.'];
    }
    if (!empty($u['nik']) && nik_diblokir($koneksi, $u['nik'])) {
        return ['ok' => false, 'code' => 'banned', 'reason' => 'Akun (NIK) Anda diblokir permanen karena pelanggaran dan tidak dapat mengikuti lelang.'];
    }
    if (($u['status_verifikasi'] ?? '') === 'pending') {
        return ['ok' => false, 'code' => 'pending', 'reason' => 'Verifikasi KTP Anda sedang dicek admin. Mohon tunggu.'];
    }
    if (($u['status_verifikasi'] ?? '') !== 'verified') {
        return ['ok' => false, 'code' => 'unverified', 'reason' => 'Anda harus verifikasi KTP terlebih dahulu untuk ikut lelang.'];
    }

    return ['ok' => true, 'code' => 'ok', 'reason' => null];
}
