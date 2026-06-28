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
// Biaya admin platform per transaksi penjualan karya (Rp).
// Model: pembeli bayar harga produk UTUH; biaya ini DIPOTONG dari hasil desainer
// (mis. karya laku Rp 500.000 -> desainer terima Rp 490.000, Rp 10.000 untuk platform).
if (!defined('BIAYA_ADMIN_TRANSAKSI')) {
    define('BIAYA_ADMIN_TRANSAKSI', 10000);
}
// Rekening tujuan default penarikan dana PERUSAHAAN (dummy, terkunci di UI admin).
if (!defined('PERUSAHAAN_BANK'))     define('PERUSAHAAN_BANK', 'Bank BCA');
if (!defined('PERUSAHAAN_REKENING')) define('PERUSAHAAN_REKENING', '1234567890');
if (!defined('PERUSAHAAN_NAMA'))     define('PERUSAHAAN_NAMA', 'PT. Den Creative Sejahtera');
if (!defined('MIN_PENARIKAN_PERUSAHAAN')) define('MIN_PENARIKAN_PERUSAHAAN', 50000);

/**
 * Hitung ringkasan saldo seorang desainer.
 *
 * @return array{kotor:float, dicairkan:float, tersedia:float}
 */
function designer_saldo($koneksi, $id_designer) {
    $id_designer = (int) $id_designer;

    // 1) Total penjualan BERHASIL atas karya milik desainer ini + jumlah transaksinya.
    //    Biaya admin platform (BIAYA_ADMIN_TRANSAKSI) dipotong sekali per transaksi.
    $kotor = 0.0;
    $jml_transaksi = 0;
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT COALESCE(SUM(t.harga_final), 0) AS kotor, COUNT(*) AS jml
           FROM t_transaksi t
           JOIN t_design d ON t.id_design = d.id_design
          WHERE d.id_designer = ?
            AND t.status_pembayaran = 'berhasil'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_designer);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $kotor = (float) ($row['kotor'] ?? 0);
    $jml_transaksi = (int) ($row['jml'] ?? 0);

    // Potongan biaya admin = jumlah transaksi berhasil x biaya admin per transaksi.
    $potongan_admin = $jml_transaksi * BIAYA_ADMIN_TRANSAKSI;
    $bersih = $kotor - $potongan_admin;       // hak desainer setelah potong admin
    if ($bersih < 0) $bersih = 0.0;

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

    $tersedia = $bersih - $dicairkan;
    if ($tersedia < 0) $tersedia = 0.0;

    return [
        'kotor'          => $kotor,            // total penjualan kotor (sebelum potong admin)
        'jml_transaksi'  => $jml_transaksi,
        'potongan_admin' => $potongan_admin,   // total biaya admin yang sudah dipotong
        'bersih'         => $bersih,           // hak desainer setelah potong admin
        'dicairkan'      => $dicairkan,
        'tersedia'       => $tersedia,
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

/**
 * Ringkasan pendapatan PERUSAHAAN (platform).
 * Sumber pemasukan:
 *   1. Biaya admin transaksi  = BIAYA_ADMIN_TRANSAKSI x jumlah transaksi 'berhasil'
 *      (bidding ikut, karena lewat alur t_transaksi yang sama).
 *   2. Langganan premium       = SUM(harga) langganan berstatus 'aktif'.
 * Saldo tersedia = total pemasukan - total yang sudah ditarik (t_penarikan_perusahaan).
 *
 * @return array
 */
function pendapatan_perusahaan($koneksi) {
    // Helper: hitung pemasukan untuk sebuah filter periode (string kondisi SQL).
    $hitung = function ($kondisi_trx, $kondisi_prem) use ($koneksi) {
        // Fee transaksi
        $rt = mysqli_query($koneksi,
            "SELECT COUNT(*) AS jml
               FROM t_transaksi
              WHERE status_pembayaran = 'berhasil' $kondisi_trx");
        $jml = $rt ? (int) (mysqli_fetch_assoc($rt)['jml'] ?? 0) : 0;
        $fee = $jml * BIAYA_ADMIN_TRANSAKSI;

        // Premium
        $rp = mysqli_query($koneksi,
            "SELECT COALESCE(SUM(harga), 0) AS total
               FROM t_premium
              WHERE status = 'aktif' $kondisi_prem");
        $prem = $rp ? (float) (mysqli_fetch_assoc($rp)['total'] ?? 0) : 0.0;

        return [
            'jml_transaksi' => $jml,
            'fee'           => (float) $fee,
            'premium'       => $prem,
            'total'         => (float) $fee + $prem,
        ];
    };

    $semua    = $hitung('', '');
    $harian   = $hitung(" AND DATE(tanggal_transaksi) = CURDATE()",
                        " AND DATE(tanggal_aktif) = CURDATE()");
    $mingguan = $hitung(" AND tanggal_transaksi >= (CURDATE() - INTERVAL 6 DAY)",
                        " AND tanggal_aktif >= (CURDATE() - INTERVAL 6 DAY)");
    $bulanan  = $hitung(" AND YEAR(tanggal_transaksi) = YEAR(CURDATE()) AND MONTH(tanggal_transaksi) = MONTH(CURDATE())",
                        " AND YEAR(tanggal_aktif) = YEAR(CURDATE()) AND MONTH(tanggal_aktif) = MONTH(CURDATE())");

    // Total sudah ditarik perusahaan
    $rd = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah), 0) AS total FROM t_penarikan_perusahaan");
    $ditarik = $rd ? (float) (mysqli_fetch_assoc($rd)['total'] ?? 0) : 0.0;

    $kotor    = $semua['total'];
    $tersedia = $kotor - $ditarik;
    if ($tersedia < 0) $tersedia = 0.0;

    return [
        'fee_transaksi' => $semua['fee'],
        'premium'       => $semua['premium'],
        'jml_transaksi' => $semua['jml_transaksi'],
        'kotor'         => $kotor,
        'ditarik'       => $ditarik,
        'tersedia'      => $tersedia,
        'rincian'       => [
            'harian'   => $harian,
            'mingguan' => $mingguan,
            'bulanan'  => $bulanan,
        ],
    ];
}

/**
 * Kirim notifikasi ke desainer saat karyanya TERJUAL (status -> 'berhasil').
 * Memberi tahu nominal laku, potongan biaya admin, & saldo bersih yang diterima.
 * Panggil HANYA saat transisi pertama ke 'berhasil' agar tidak ganda.
 */
function kirim_notif_penjualan($koneksi, $id_transaksi) {
    require_once __DIR__ . '/notifikasi_helper.php';
    $id_transaksi = (int) $id_transaksi;
    if (!$id_transaksi) return false;

    $sql = "SELECT d.id_designer, d.judul, t.harga_final
              FROM t_transaksi t
              JOIN t_design d ON t.id_design = d.id_design
             WHERE t.id_transaksi = $id_transaksi
             LIMIT 1";
    $res = mysqli_query($koneksi, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if (!$row || empty($row['id_designer'])) return false;

    $harga    = (float) $row['harga_final'];
    $potongan = (float) BIAYA_ADMIN_TRANSAKSI;
    $diterima = $harga - $potongan;
    if ($diterima < 0) $diterima = 0.0;

    $pesan = 'Selamat! Karyamu "' . $row['judul'] . '" terjual seharga ' . rupiah($harga)
           . '. Setelah dipotong biaya admin ' . rupiah($potongan)
           . ', saldomu bertambah ' . rupiah($diterima)
           . '. Cek di menu Pencairan Dana.';

    return kirim_notifikasi($koneksi, (int) $row['id_designer'], $pesan, 'pencairan.php', 'penjualan');
}
