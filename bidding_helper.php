<?php
/**
 * ============================================================
 *  BIDDING_HELPER.PHP — Gerbang kelayakan ikut lelang (bid)
 * ============================================================
 * REVISI KEAMANAN: pelanggan TIDAK LAGI wajib verifikasi KTP. Foto KTP
 * pelanggan adalah data pribadi sensitif (UU PDP No. 27/2022) yang berisiko
 * bocor (tersimpan di admin/uploads/, bisa diakses langsung lewat URL) dan
 * tidak proporsional untuk pihak yang cuma MEMBAYAR.
 *
 * Jangkar identitas pengganti untuk pelanggan:
 *   1) email_terverifikasi (OTP saat registrasi — lihat otp_helper.php)
 *   2) no_telp_norm unik, 1 nomor HP = 1 akun (lihat identitas_helper.php)
 * Kontrol perilaku sebagai kompensasi (yang dulu SAMA SEKALI tidak ada):
 *   3) rate limit bid beruntun (cek_rate_limit_bid())
 *   4) strike wanprestasi -> suspend berjangka (lihat wanprestasi_helper.php)
 *
 * TRADE-OFF YANG DISENGAJA: daya cegah ban turun dari NIK (nyaris mustahil
 * diganti) ke nomor HP (bisa dibeli ulang murah). Sejak KTP/NIK desainer JUGA
 * dihapus (digantikan Google Login + persetujuan MOU — lihat
 * google_auth_helper.php & mou_helper.php), jangkar desainer pun sekarang
 * email, bukan lagi NIK. Trade-off ini disengaja: admin cross-check manual
 * saat pencairan dana (t_pencairan) jadi filter fraud kedua untuk desainer.
 *
 * Dipakai bersama oleh product.php (quick view), product-detail.php, dan
 * proses_bidding.php supaya tampilan & validasi server konsisten.
 */
require_once __DIR__ . '/identitas_helper.php';

/**
 * Status kelayakan bid seorang user.
 *
 * @return array{ok:bool, code:string, reason:?string}
 *   code: 'guest' | 'suspended' | 'banned' | 'email' | 'no_telp' | 'ok'
 */
function status_bid_user($koneksi, $id_user) {
    $id_user = (int) $id_user;
    if (!$id_user) {
        return ['ok' => false, 'code' => 'guest', 'reason' => 'Masuk sebagai pembeli untuk ikut lelang.'];
    }

    $stmt = mysqli_prepare($koneksi,
        "SELECT status, suspend_sampai, email, email_terverifikasi, no_telp_norm,
                (suspend_sampai IS NOT NULL AND suspend_sampai <= NOW()) AS suspend_habis
           FROM t_user WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    $u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$u) {
        return ['ok' => false, 'code' => 'guest', 'reason' => 'Akun tidak ditemukan.'];
    }

    // 1) SUSPEND (hasil strike wanprestasi atau tindakan admin) — dengan
    // lazy auto-unsuspend: begitu masa hukuman lewat, pulihkan di tempat
    // (bukan strtotime()/time() PHP, tapi kolom turunan dari SQL di atas,
    // supaya kebal selisih zona waktu — lihat catatan di pembayaran_helper.php).
    if (($u['status'] ?? '') === 'nonaktif') {
        if ((int) $u['suspend_habis'] === 1) {
            mysqli_query($koneksi, "UPDATE t_user SET status='aktif', suspend_sampai=NULL WHERE id_user=" . $id_user);
        } else {
            $sampai = $u['suspend_sampai'] ?? null;
            $pesan_sampai = $sampai ? ' sampai ' . date('d M Y H:i', strtotime($sampai)) : ' (permanen oleh admin)';
            return ['ok' => false, 'code' => 'suspended', 'reason' => 'Akun Anda dibekukan' . $pesan_sampai . ' karena tidak menyelesaikan pembayaran lelang yang Anda menangkan.'];
        }
    }

    // 2) BLOKIR IDENTITAS — email/telp (jangkar identitas semua role).
    if (identitas_diblokir($koneksi, 'email', $u['email'])) {
        return ['ok' => false, 'code' => 'banned', 'reason' => 'Akun Anda diblokir permanen karena pelanggaran dan tidak dapat mengikuti lelang.'];
    }
    if (!empty($u['no_telp_norm']) && identitas_diblokir($koneksi, 'telp', $u['no_telp_norm'])) {
        return ['ok' => false, 'code' => 'banned', 'reason' => 'Nomor HP akun Anda diblokir permanen karena pelanggaran dan tidak dapat mengikuti lelang.'];
    }

    // 3) EMAIL BELUM TERVERIFIKASI — pengganti gerbang KTP lama.
    if ((int) ($u['email_terverifikasi'] ?? 0) !== 1) {
        return ['ok' => false, 'code' => 'email', 'reason' => 'Verifikasi alamat email Anda dulu sebelum ikut lelang.'];
    }

    // 4) NOMOR HP WAJIB — jangkar identitas pengganti KTP (1 nomor = 1 akun).
    if (empty($u['no_telp_norm'])) {
        return ['ok' => false, 'code' => 'no_telp', 'reason' => 'Lengkapi nomor HP aktif di halaman Profil sebelum memasang penawaran.'];
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
    // status_bid <> 'ditolak' (BUKAN = 'aktif'): data lama menyimpan status_bid
    // sebagai string kosong, bukan 'aktif' — filter dengan = 'aktif' akan
    // menghilangkan seluruh riwayat bid lama. 'ditolak' hanya ditulis oleh
    // proses_wanprestasi() saat pemenang digugurkan (lihat wanprestasi_helper.php),
    // jadi menyaringnya di sini otomatis membuat penawar berikutnya naik
    // peringkat (promosi runner-up) tanpa kode tambahan di 6 pemanggil fungsi ini.
    $sql = "SELECT b.id_buyer
            FROM t_bidding b
            WHERE b.id_design = $id_design
              AND b.status_bid <> 'ditolak'
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

/**
 * ============================================================
 *  RATE LIMIT BID — maksimal N bid BERUNTUN per pembeli per karya
 * ============================================================
 * Mencegah satu pembeli menaikkan tawarannya sendiri berkali-kali tanpa
 * ada penawar lain (spam bid). Begitu pembeli LAIN memasang tawaran di
 * karya yang sama, hitungan otomatis reset ke nol — tidak perlu tabel,
 * kolom, atau cron tambahan: cukup lihat id_bid TERAKHIR milik orang lain
 * di karya itu sebagai "titik reset", lalu hitung bid pemohon sesudahnya.
 */
const BID_MAKS_BERUNTUN = 3;

/** Jumlah bid beruntun terakhir milik $id_buyer di $id_design tanpa diselingi bidder lain. */
function bid_beruntun_count($koneksi, $id_design, $id_buyer) {
    $id_design = (int) $id_design;
    $id_buyer  = (int) $id_buyer;

    $stmt = mysqli_prepare($koneksi,
        "SELECT COUNT(*) AS n
           FROM t_bidding
          WHERE id_design = ? AND id_buyer = ?
            AND id_bid > COALESCE(
                  (SELECT MAX(id_bid) FROM t_bidding WHERE id_design = ? AND id_buyer <> ?), 0)");
    mysqli_stmt_bind_param($stmt, 'iiii', $id_design, $id_buyer, $id_design, $id_buyer);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return (int) ($row['n'] ?? 0);
}

/**
 * @return array{ok:bool, terpakai:int, sisa:int, reason:?string}
 */
function cek_rate_limit_bid($koneksi, $id_design, $id_buyer) {
    $n = bid_beruntun_count($koneksi, $id_design, $id_buyer);
    if ($n >= BID_MAKS_BERUNTUN) {
        return [
            'ok' => false,
            'terpakai' => $n,
            'sisa' => 0,
            'reason' => 'Anda sudah memasang ' . BID_MAKS_BERUNTUN . ' penawaran berturut-turut di karya ini. Tunggu penawar lain sebelum menawar lagi.',
        ];
    }
    return ['ok' => true, 'terpakai' => $n, 'sisa' => BID_MAKS_BERUNTUN - $n, 'reason' => null];
}
