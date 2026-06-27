<?php
/**
 * ============================================================
 *  KEUANGAN_HELPER.PHP — Perhitungan saldo & pencairan desainer
 * ============================================================
 * Model escrow (seperti Shopee):
 *  - Uang hasil penjualan ditahan platform.
 *  - Desainer mengajukan pencairan, admin transfer manual & menandai selesai.
 *
 * Pencairan TANPA potongan biaya admin (platform sudah untung dari fee per
 * transaksi pembelian). Desainer menerima penuh nominal yang ditarik:
 *  - Saldo tersedia = total penjualan 'berhasil' - total yang sudah/sedang dicairkan.
 *  - Saat menarik nominal X: desainer menerima X penuh.
 */

if (!defined('FEE_PERSEN')) {
    define('FEE_PERSEN', 0);         // Tanpa biaya admin per penarikan (%)
}
if (!defined('MIN_PENARIKAN')) {
    define('MIN_PENARIKAN', 50000);  // Minimum nominal sekali tarik (Rp)
}

/**
 * Hitung ringkasan saldo seorang desainer.
 *
 * @return array{kotor:float, dicairkan:float, tersedia:float}
 */
function designer_saldo($koneksi, $id_designer) {
    $id_designer = (int) $id_designer;

    // 1) Total penjualan BERHASIL atas karya milik desainer ini.
    $kotor = 0.0;
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT COALESCE(SUM(t.harga_final), 0) AS kotor
           FROM t_transaksi t
           JOIN t_design d ON t.id_design = d.id_design
          WHERE d.id_designer = ?
            AND t.status_pembayaran = 'berhasil'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_designer);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $kotor = (float) ($row['kotor'] ?? 0);

    // 2) Total yang sudah/sedang dicairkan (nominal tarik mengunci saldo;
    //    yang 'ditolak' tidak mengurangi saldo).
    $dicairkan = 0.0;
    $stmt2 = mysqli_prepare(
        $koneksi,
        "SELECT COALESCE(SUM(jumlah), 0) AS total
           FROM t_pencairan
          WHERE id_designer = ?
            AND status IN ('pending','diproses','selesai')"
    );
    mysqli_stmt_bind_param($stmt2, 'i', $id_designer);
    mysqli_stmt_execute($stmt2);
    $row2 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
    $dicairkan = (float) ($row2['total'] ?? 0);

    $tersedia = $kotor - $dicairkan;
    if ($tersedia < 0) $tersedia = 0.0;

    return [
        'kotor'     => $kotor,
        'dicairkan' => $dicairkan,
        'tersedia'  => $tersedia,
    ];
}

/**
 * Hitung biaya admin & dana diterima untuk sebuah nominal penarikan.
 *
 * @return array{fee:float, diterima:float}
 */
function fee_pencairan($jumlah) {
    $jumlah = (float) $jumlah;
    $fee = round($jumlah * (FEE_PERSEN / 100), 2);
    $diterima = $jumlah - $fee;
    if ($diterima < 0) $diterima = 0.0;
    return ['fee' => $fee, 'diterima' => $diterima];
}

/** Format angka ke Rupiah. */
function rupiah($n) {
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}

/** Tampilkan FEE_PERSEN tanpa angka 0 di belakang (2.5 -> "2,5"). */
function fee_label() {
    return rtrim(rtrim(number_format(FEE_PERSEN, 1, ',', '.'), '0'), ',');
}
