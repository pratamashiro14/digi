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

/**
 * Ekspresi SQL boolean: apakah pembeli (pada kolom $buyer_col) berstatus PREMIUM aktif.
 * Premium = t_user.status_member='premium' ATAU t_user.premium=1 ATAU
 *           ada langganan t_premium 'aktif' yang belum kedaluwarsa.
 * Dipakai untuk PRIORITAS BIDDING: saat nominal seri, premium menang.
 */
function premium_buyer_flag_sql($buyer_col) {
    return "(
        COALESCE((SELECT (uu.status_member='premium' OR uu.premium=1)
                  FROM t_user uu WHERE uu.id_user = $buyer_col), 0)
        OR EXISTS (SELECT 1 FROM t_premium pp
                   WHERE pp.id_user = $buyer_col
                     AND pp.status = 'aktif'
                     AND pp.tanggal_berakhir >= CURDATE())
    )";
}

/**
 * ID pembeli yang sedang MEMIMPIN lelang sebuah karya.
 *
 * Urutan kemenangan (PRIORITAS BIDDING untuk premium):
 *   1) nominal tawaran tertinggi
 *   2) jika seri  -> pembeli PREMIUM didahulukan (boleh menyamai & memimpin)
 *   3) jika masih seri -> yang menawar lebih dulu
 *
 * @return int id_buyer pemimpin, atau 0 bila belum ada bid.
 */
function bid_leader_id($koneksi, $id_design) {
    $id_design = (int) $id_design;
    if (!$id_design) return 0;
    $prem = premium_buyer_flag_sql('b.id_buyer');
    $sql = "SELECT b.id_buyer
            FROM t_bidding b
            WHERE b.id_design = $id_design
            ORDER BY b.harga_tawaran DESC, ($prem) DESC, b.tanggal_bid ASC, b.id_bid ASC
            LIMIT 1";
    $r = mysqli_query($koneksi, $sql);
    if (!$r) return 0;
    $row = mysqli_fetch_assoc($r);
    return $row ? (int) $row['id_buyer'] : 0;
}

/** True bila $id_buyer adalah pemimpin/pemenang lelang $id_design saat ini. */
function is_bid_leader($koneksi, $id_design, $id_buyer) {
    return bid_leader_id($koneksi, $id_design) === (int) $id_buyer;
}
