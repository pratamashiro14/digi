<?php
/**
 * ============================================================
 *  KEUANGAN_HELPER.PHP — Perhitungan saldo & pencairan desainer
 * ============================================================
 * Model escrow (seperti Shopee):
 *  - Uang hasil penjualan ditahan platform.
 *  - Desainer mengajukan pencairan, admin transfer manual & menandai selesai.
 *
 * Saldo tersedia = (pendapatan kotor - fee platform) - total yang sudah/sedang dicairkan.
 *  - Pendapatan kotor : SUM(harga_final) transaksi berstatus 'berhasil' atas karya desainer.
 *  - Fee platform     : FEE_PERSEN% dari pendapatan kotor.
 *  - Total dicairkan  : pencairan berstatus pending + diproses + selesai
 *                       (yang 'ditolak' tidak mengurangi saldo).
 */

if (!defined('FEE_PERSEN')) {
    define('FEE_PERSEN', 2.5);       // Potongan platform per penjualan (%)
}
if (!defined('MIN_PENARIKAN')) {
    define('MIN_PENARIKAN', 50000);  // Minimum nominal sekali tarik (Rp)
}

/**
 * Hitung ringkasan keuangan seorang desainer.
 *
 * @return array{kotor:float, fee:float, bersih:float, dicairkan:float, tersedia:float}
 */
function designer_saldo($koneksi, $id_designer) {
    $id_designer = (int) $id_designer;

    // 1) Pendapatan kotor = transaksi BERHASIL atas karya milik desainer ini.
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

    // 2) Total yang sudah/sedang dicairkan (kunci saldo).
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

    $fee      = round($kotor * (FEE_PERSEN / 100), 2);
    $bersih   = $kotor - $fee;
    $tersedia = $bersih - $dicairkan;
    if ($tersedia < 0) $tersedia = 0.0;

    return [
        'kotor'     => $kotor,
        'fee'       => $fee,
        'bersih'    => $bersih,
        'dicairkan' => $dicairkan,
        'tersedia'  => $tersedia,
    ];
}

/** Format angka ke Rupiah. */
function rupiah($n) {
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}
