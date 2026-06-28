<?php
/**
 * ============================================================
 *  PEMBAYARAN_HELPER.PHP — Aturan tenggat & anti-duplikat pending
 * ============================================================
 * Dipakai bersama oleh proses_bayar.php, bayar_ulang.php, dan riwayat.php
 * supaya perilaku status "Menunggu Bayar" konsisten:
 *
 *  1. LIMIT WAKTU: transaksi pending punya tenggat (PEMBAYARAN_LIMIT_MENIT).
 *     Tenggat disimpan di kolom t_transaksi.batas_pembayaran. Untuk baris
 *     lama yang batas_pembayaran-nya NULL, tenggat dihitung dari
 *     tanggal_transaksi + limit (lihat batas_pembayaran_sql()).
 *
 *  2. LAZY-EXPIRE (tanpa cron): pending yang lewat tenggat otomatis di-set
 *     'gagal' setiap kali halaman terkait dibuka. Karya jadi bebas dibeli lagi.
 *
 *  3. ANTI-DUPLIKAT: satu pembeli hanya boleh punya SATU pending aktif per
 *     karya. Klik beli/bayar kedua kali diarahkan menyelesaikan yang ada.
 */

/** Batas waktu (menit) pelanggan harus menyelesaikan pembayaran pending. */
const PEMBAYARAN_LIMIT_MENIT = 60;

/**
 * Ekspresi SQL tenggat bayar sebuah baris t_transaksi.
 * Pakai batas_pembayaran bila ada; jika NULL (data lama) hitung dari
 * tanggal_transaksi + limit supaya tetap tertib tanpa backfill.
 */
function batas_pembayaran_sql($alias = '') {
    $p = $alias === '' ? '' : $alias . '.';
    $limit = (int) PEMBAYARAN_LIMIT_MENIT;
    return "COALESCE({$p}batas_pembayaran, {$p}tanggal_transaksi + INTERVAL {$limit} MINUTE)";
}

/**
 * Tandai semua transaksi pending yang sudah lewat tenggat menjadi 'gagal'.
 * Aman dipanggil di awal halaman; murah dan idempotent.
 */
function expire_pending_kedaluwarsa($koneksi) {
    $batas = batas_pembayaran_sql();
    mysqli_query(
        $koneksi,
        "UPDATE t_transaksi
            SET status_pembayaran = 'gagal'
          WHERE status_pembayaran = 'pending'
            AND {$batas} < NOW()"
    );
}

/**
 * Cari transaksi pending milik $id_buyer untuk $id_design yang MASIH aktif
 * (belum lewat tenggat). Dipakai untuk mencegah pembuatan transaksi ganda.
 *
 * @return array|null baris t_transaksi (berisi id_transaksi, batas, dll) atau null.
 */
function pending_aktif_untuk_design($koneksi, $id_buyer, $id_design) {
    $id_buyer  = (int) $id_buyer;
    $id_design = (int) $id_design;
    $batas = batas_pembayaran_sql('t');

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT t.id_transaksi, t.id_midtrans_order, {$batas} AS batas_efektif
           FROM t_transaksi t
          WHERE t.id_buyer = ?
            AND t.id_design = ?
            AND t.status_pembayaran = 'pending'
            AND {$batas} >= NOW()
          ORDER BY t.id_transaksi DESC
          LIMIT 1"
    );
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'ii', $id_buyer, $id_design);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

/*
 * CATATAN: sisa waktu pembayaran SELALU dihitung di MySQL
 * (TIMESTAMPDIFF(SECOND, NOW(), batas_pembayaran_sql())) — lihat riwayat.php &
 * bayar_ulang.php — agar kebal selisih zona waktu antara PHP, MySQL, dan browser.
 * Jangan menghitung tenggat dengan strtotime()/time() di PHP.
 */
