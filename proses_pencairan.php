<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/keuangan_helper.php';

// Hanya desainer yang boleh mengajukan pencairan
require_designer();

$id_desainer = (int) current_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sweetalert_redirect('Akses tidak sah.', 'pencairan.php', 'error', 'Ditolak');
    exit;
}

$bank        = trim($_POST['bank'] ?? '');
$no_rekening = trim($_POST['no_rekening'] ?? '');
$nama_rek    = trim($_POST['nama_pemilik_rek'] ?? '');
$jumlah      = (int) preg_replace('/[^0-9]/', '', $_POST['jumlah'] ?? '');

// Saldo terkini (otoritas tetap di server, bukan dari nilai form)
$saldo = designer_saldo($koneksi, $id_desainer);

if ($bank === '' || $no_rekening === '' || $nama_rek === '') {
    sweetalert_back('Lengkapi data bank, nomor rekening, dan nama pemilik.', 'error', 'Data Kurang!');
} elseif ($jumlah < MIN_PENARIKAN) {
    sweetalert_back('Nominal minimal pencairan adalah ' . rupiah(MIN_PENARIKAN) . '.', 'error', 'Nominal Terlalu Kecil!');
} elseif ($jumlah > $saldo['tersedia']) {
    sweetalert_back('Nominal melebihi saldo tersedia (' . rupiah($saldo['tersedia']) . ').', 'error', 'Saldo Tidak Cukup!');
} else {
    // Biaya admin dipotong per penarikan; desainer menerima (jumlah - fee).
    $rincian  = fee_pencairan($jumlah);
    $fee      = $rincian['fee'];
    $diterima = $rincian['diterima'];

    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO t_pencairan (id_designer, jumlah, fee, jumlah_diterima, bank, no_rekening, nama_pemilik_rek, status, tanggal_request)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
    );
    mysqli_stmt_bind_param($stmt, 'idddsss', $id_desainer, $jumlah, $fee, $diterima, $bank, $no_rekening, $nama_rek);

    if (mysqli_stmt_execute($stmt)) {
        sweetalert_redirect('Pengajuan pencairan ' . rupiah($jumlah) . ' berhasil dikirim (diterima penuh tanpa potongan). Tunggu diproses Admin.', 'pencairan.php?view=history', 'success', 'Pengajuan Terkirim!');
    } else {
        sweetalert_back('Gagal mengajukan pencairan: ' . mysqli_error($koneksi), 'error', 'Gagal!');
    }
}
?>
