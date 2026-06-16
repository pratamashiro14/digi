<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/designer_layout.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/keuangan_helper.php';

// 1. CEK LOGIN DESAINER
require_designer();

$id_desainer = (int) current_id();

$view = $_GET['view'] ?? 'withdraw';
if (!in_array($view, ['withdraw', 'history'], true)) {
    $view = 'withdraw';
}

// 2. Ringkasan saldo
$saldo = designer_saldo($koneksi, $id_desainer);

// 3. Riwayat pencairan
$riwayat = [];
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM t_pencairan WHERE id_designer = ? ORDER BY id_pencairan DESC"
);
mysqli_stmt_bind_param($stmt, 'i', $id_desainer);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) {
    $riwayat[] = $r;
}

// Label & warna badge status pencairan
function pencairan_badge($status) {
    $map = [
        'pending'  => ['Menunggu', '#b58105', '#fff4d6'],
        'diproses' => ['Diproses', '#1565c0', '#e3f0ff'],
        'selesai'  => ['Selesai',  '#1b7d3f', '#dff5e6'],
        'ditolak'  => ['Ditolak',  '#c62828', '#fde4e4'],
    ];
    $c = $map[$status] ?? ['-', '#555', '#eee'];
    return '<span style="display:inline-block;padding:4px 12px;border-radius:50px;font-size:12px;font-weight:700;color:' . $c[1] . ';background:' . $c[2] . ';">' . $c[0] . '</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pencairan Dana</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/account-ui.css">

    <style>
        body { background-color: #fff; font-family: 'Poppins', sans-serif; }

        .balance-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 30px; }
        .balance-card { border: 1px solid #e0e0e0; border-radius: 10px; padding: 22px; }
        .balance-card .lbl { font-size: 13px; color: #888; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
        .balance-card .val { font-size: 24px; font-weight: 800; color: #222; }
        .balance-card.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .balance-card.primary .lbl { color: #e7e2ff; }
        .balance-card.primary .val { color: #fff; }

        .panel { border: 1px solid #e0e0e0; border-radius: 10px; padding: 28px; }
        .panel h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #333; }

        .form-control { border-radius: 6px; }
        .btn-cair { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; border-radius: 8px; padding: 12px; font-weight: 600; width: 100%; }
        .btn-cair:disabled { opacity: .55; cursor: not-allowed; }

        .table-cair { width: 100%; }
        .table-cair th { font-size: 13px; text-transform: uppercase; color: #555; font-weight: 800; padding: 12px 10px; border-bottom: 2px solid #eee; }
        .table-cair td { font-size: 14px; color: #555; padding: 14px 10px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        .note-info { font-size: 12px; color: #999; margin-top: 6px; }
        @media (max-width: 768px){ .balance-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="animsition account-page">

    <?php
    $active_page = 'designer-finance';
    include 'navbar.php';
    ?>

    <div class="container account-shell">
        <div class="designer-layout-grid">
            <?php render_designer_section_sidebar('finance', $view); ?>

            <main class="designer-section-content">
                <div class="account-page-header">
                    <div>
                        <h1><?php echo $view === 'history' ? 'Riwayat Pencairan' : 'Pencairan Dana'; ?></h1>
                        <p>Dana penjualan ditahan platform dan bisa dicairkan kapan saja ke rekening Anda.</p>
                    </div>
                </div>

                <!-- Kartu saldo -->
                <div class="balance-grid">
                    <div class="balance-card primary">
                        <div class="lbl">Saldo Tersedia</div>
                        <div class="val"><?php echo rupiah($saldo['tersedia']); ?></div>
                    </div>
                    <div class="balance-card">
                        <div class="lbl">Total Penjualan (Berhasil)</div>
                        <div class="val"><?php echo rupiah($saldo['kotor']); ?></div>
                        <div class="note-info">Potongan platform <?php echo rtrim(rtrim(number_format(FEE_PERSEN, 1), '0'), '.'); ?>% = <?php echo rupiah($saldo['fee']); ?></div>
                    </div>
                    <div class="balance-card">
                        <div class="lbl">Sedang/Sudah Dicairkan</div>
                        <div class="val"><?php echo rupiah($saldo['dicairkan']); ?></div>
                    </div>
                </div>

                <?php if ($view !== 'history') { ?>
                <!-- Form ajukan pencairan -->
                <div class="panel" style="margin-bottom: 30px;">
                    <h3>Ajukan Pencairan</h3>
                    <form action="proses_pencairan.php" method="POST" id="formCair">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Bank / E-Wallet</label>
                                <input type="text" name="bank" class="form-control" placeholder="Contoh: BCA / DANA / GoPay" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Nomor Rekening / Nomor E-Wallet</label>
                                <input type="text" name="no_rekening" class="form-control" placeholder="Contoh: 1234567890" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Nama Pemilik Rekening</label>
                                <input type="text" name="nama_pemilik_rek" class="form-control" value="<?php echo htmlspecialchars(current_name()); ?>" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Nominal Pencairan (Rp)</label>
                                <input type="number" name="jumlah" class="form-control" min="<?php echo (int) MIN_PENARIKAN; ?>" max="<?php echo (int) $saldo['tersedia']; ?>" placeholder="Min. <?php echo number_format(MIN_PENARIKAN, 0, ',', '.'); ?>" required <?php echo $saldo['tersedia'] < MIN_PENARIKAN ? 'disabled' : ''; ?>>
                                <div class="note-info">Maksimal <?php echo rupiah($saldo['tersedia']); ?> &middot; Minimal <?php echo rupiah(MIN_PENARIKAN); ?></div>
                            </div>
                        </div>
                        <div style="max-width: 280px; margin-top: 10px;">
                            <?php if ($saldo['tersedia'] < MIN_PENARIKAN) { ?>
                                <button type="button" class="btn-cair" disabled>Saldo belum mencukupi</button>
                                <div class="note-info">Saldo minimal untuk menarik adalah <?php echo rupiah(MIN_PENARIKAN); ?>.</div>
                            <?php } else { ?>
                                <button type="submit" class="btn-cair"><i class="fa fa-paper-plane"></i> Ajukan Pencairan</button>
                            <?php } ?>
                        </div>
                    </form>
                </div>
                <?php } ?>

                <!-- Riwayat pencairan -->
                <div class="panel">
                    <h3>Riwayat Pencairan</h3>
                    <div class="table-responsive-account">
                        <table class="table-cair">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tujuan</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                    <th>Catatan Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($riwayat) === 0) { ?>
                                    <tr><td colspan="5" style="text-align:center; padding:30px; color:#999;">Belum ada pengajuan pencairan.</td></tr>
                                <?php } else {
                                    foreach ($riwayat as $r) { ?>
                                    <tr>
                                        <td><?php echo date('d M Y H:i', strtotime($r['tanggal_request'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($r['bank']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($r['no_rekening']); ?> &middot; <?php echo htmlspecialchars($r['nama_pemilik_rek']); ?></small>
                                        </td>
                                        <td><?php echo rupiah($r['jumlah']); ?></td>
                                        <td><?php echo pencairan_badge($r['status']); ?></td>
                                        <td><?php echo $r['catatan_admin'] ? htmlspecialchars($r['catatan_admin']) : '<span style="color:#bbb;">-</span>'; ?></td>
                                    </tr>
                                <?php } } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>

</body>
</html>
