<?php
include "../koneksi.php";

session_start();
if (empty($_SESSION['admin'])) {
    header('Location: ../logout.php');
    exit;
}

$id_admin = $_SESSION['admin'];
$query_admin = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE id_admin = '$id_admin'");
$data_admin = mysqli_fetch_assoc($query_admin);

if (!$data_admin) {
    header('Location: ../logout.php');
    exit;
}

$base_bukti_url = '../upload/bukti/';
$base_karya_url = '../uploads/';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID Transaksi tidak valid.");
}

$id_transaksi = (int) $_GET['id'];
$stmt = $koneksi->prepare("
    SELECT t.*, d.judul AS nama_karya, u.nama AS nama_pembeli,
           d.harga_awal AS harga, d.deskripsi AS deskripsi_karya, u.email AS email_pembeli,
           t.id_midtrans_order, d.gambar AS gambar_karya
    FROM t_transaksi t
    LEFT JOIN t_design d ON t.id_design = d.id_design
    LEFT JOIN t_user u ON t.id_buyer = u.id_user
    WHERE t.id_transaksi = ?
");
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$result = $stmt->get_result();
$detail = $result->fetch_assoc();
$stmt->close();

if (!$detail) {
    die("<div class='alert alert-danger'>Data transaksi dengan ID #{$id_transaksi} tidak ditemukan.</div>");
}

require_once __DIR__ . '/../../config.php';
$midtrans_server_key = MIDTRANS_SERVER_KEY;
$midtrans_base_url = MIDTRANS_IS_PRODUCTION
    ? 'https://api.midtrans.com/v2/'
    : 'https://api.sandbox.midtrans.com/v2/';
$transaction_id = $detail['id_midtrans_order'];

$payment_status_text = 'Belum Ada Data Midtrans';
$payment_status_detail = 'Tidak ada ID Midtrans Order yang tersimpan.';

if (!empty($transaction_id) && function_exists('curl_init')) {
    $ch = curl_init();
    $url = $midtrans_base_url . $transaction_id . '/status';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($midtrans_server_key . ':'),
    ]);
    $response = curl_exec($ch);

    if (!curl_errno($ch)) {
        $midtrans_status = json_decode($response, true);
        if ($midtrans_status) {
            $payment_status_text = $midtrans_status['transaction_status'] ?? 'Status Tidak Dikenal';
            $payment_status_detail = 'Metode: ' . ($midtrans_status['payment_type'] ?? 'N/A') .
                ' | Waktu Settlement: ' . ($midtrans_status['settlement_time'] ?? 'Belum Lunas');
        }
    } else {
        $payment_status_text = 'Gagal Akses Midtrans API';
        $payment_status_detail = curl_error($ch);
    }
    curl_close($ch);
}

$bukti_file_name = htmlspecialchars($detail['bukti_pembayaran'] ?? '');
$full_bukti_url = $bukti_file_name !== '' ? $base_bukti_url . $bukti_file_name : null;
$gambar_karya_url = $base_karya_url . ($detail['gambar_karya'] ?? '');
$status_lokal = strtolower($detail['status_pembayaran'] ?? '');
$status_badge = $status_lokal === 'berhasil' || $status_lokal === 'settlement'
    ? 'success'
    : ($status_lokal === 'pending' ? 'warning' : 'danger');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail Transaksi #<?= htmlspecialchars((string) $id_transaksi) ?></title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
          urls: ["../assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="../assets/css/digidesain-admin.css" />
    <style>
        .transaction-image {
            width: 100%;
            max-height: 280px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .info-label {
            color: #667085;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .info-value {
            color: #111827;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
            <div class="logo-header" data-background-color="dark">
                <a href="../beranda.php" class="logo">
                    <img src="../assets/img/dens.png" alt="DIGIDESAIN" class="navbar-brand" />
                </a>
                <div class="nav-toggle">
                    <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                    <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                </div>
                <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
            </div>
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
            <div class="sidebar-content">
                <ul class="nav nav-secondary">
                    <li class="nav-item"><a href="../beranda.php"><i class="fas fa-home"></i><p>Beranda</p></a></li>
                    <li class="nav-item"><a href="karyadesain.php"><i class="fas fa-palette"></i><p>Karya Desain</p></a></li>
                    <li class="nav-item"><a href="datapengguna.php"><i class="fas fa-user"></i><p>Data Pengguna</p></a></li>
                    <li class="nav-item active"><a href="transaksi.php"><i class="fas fa-money-bill-wave"></i><p>Transaksi</p></a></li>
                    <li class="nav-item"><a href="datapremium.php"><i class="fas fa-crown"></i><p>Data Premium</p></a></li>
                    <li class="nav-item"><a href="databidding.php"><i class="fas fa-hand-holding-usd"></i><p>Data Bidding</p></a></li>
                    <li class="nav-item"><a href="datachat.php"><i class="fas fa-comments"></i><p>Moderisasi Chat</p></a></li>
                    <li class="nav-item"><a href="dataadmin.php"><i class="fas fa-user-plus"></i><p>Tambah Admin</p></a></li>
                    <li class="nav-item"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><p>Logout</p></a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-panel">
        <div class="main-header">
            <div class="main-header-logo">
                <div class="logo-header" data-background-color="dark">
                    <a href="../beranda.php" class="logo">
                        <img src="../assets/img/dens.png" alt="DIGIDESAIN" class="navbar-brand" />
                    </a>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                        <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                    </div>
                    <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
                </div>
            </div>
            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                    <div class="navbar-form nav-search p-0 d-none d-lg-flex">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button type="button" class="btn btn-search pe-1"><i class="fa fa-search search-icon"></i></button>
                            </div>
                            <input type="text" placeholder="Cari transaksi..." class="form-control" />
                        </div>
                    </div>
                    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                        <li class="nav-item topbar-user dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                <div class="avatar-sm">
                                    <img src="../assets/img/fotoprofil/<?= htmlspecialchars($data_admin['foto'] ?: 'profile.jpg'); ?>" alt="Foto admin" class="avatar-img rounded-circle" />
                                </div>
                                <span class="profile-username">
                                    <span class="op-7">Hi,</span>
                                    <span class="fw-bold"><?= htmlspecialchars($data_admin['nama_admin']); ?></span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <div class="dropdown-user-scroll scrollbar-outer">
                                    <li>
                                        <div class="user-box">
                                            <div class="avatar-lg">
                                                <img src="../assets/img/fotoprofil/<?= htmlspecialchars($data_admin['foto'] ?: 'profile.jpg'); ?>" alt="Foto admin" class="avatar-img rounded" />
                                            </div>
                                            <div class="u-text">
                                                <h4><?= htmlspecialchars($data_admin['nama_admin']); ?></h4>
                                                <p class="text-muted"><?= htmlspecialchars($data_admin['email']); ?></p>
                                                <a href="../profile.php" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="../logout.php">Logout</a>
                                    </li>
                                </div>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Detail Transaksi</h3>
                    <ul class="breadcrumbs mb-3">
                        <li class="nav-home"><a href="../beranda.php"><i class="icon-home"></i></a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="transaksi.php">Transaksi</a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item">#<?= htmlspecialchars((string) $id_transaksi) ?></li>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title">Transaksi #<?= htmlspecialchars((string) $detail['id_transaksi']) ?></div>
                        <a href="transaksi.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fa fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="info-label">ID Transaksi</p>
                                        <p class="info-value">#<?= htmlspecialchars((string) $detail['id_transaksi']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="info-label">Waktu Transaksi</p>
                                        <p class="info-value"><?= date('d M Y H:i:s', strtotime($detail['tanggal_transaksi'])) ?></p>
                                    </div>
                                    <div class="col-md-12">
                                        <p class="info-label">Judul Desain</p>
                                        <p class="info-value"><?= htmlspecialchars($detail['nama_karya'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-12">
                                        <p class="info-label">Pembeli</p>
                                        <p class="info-value"><?= htmlspecialchars($detail['nama_pembeli'] ?? '-') ?> (<?= htmlspecialchars($detail['email_pembeli'] ?? '-') ?>)</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="info-label">Harga Final</p>
                                        <p class="info-value">Rp <?= number_format((float) $detail['harga_final'], 0, ',', '.') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="info-label">Metode Pembayaran</p>
                                        <p class="info-value"><?= htmlspecialchars(ucfirst($detail['metode_pembayaran'] ?? '-')) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="info-label">Status Lokal</p>
                                        <p class="info-value"><span class="badge bg-<?= $status_badge ?>"><?= htmlspecialchars(ucfirst($detail['status_pembayaran'] ?? '-')) ?></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="info-label">Status Midtrans</p>
                                        <p class="info-value"><?= htmlspecialchars(ucfirst($payment_status_text)) ?></p>
                                    </div>
                                </div>

                                <div class="alert alert-light border mb-0">
                                    <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($payment_status_detail) ?>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <img src="<?= htmlspecialchars($gambar_karya_url) ?>" alt="Gambar karya" class="transaction-image mb-3" onerror="this.src='../assets/img/digidesain.png'">
                                <?php if ($full_bukti_url) { ?>
                                    <p class="text-muted small mb-2">Bukti manual: <?= $bukti_file_name ?></p>
                                    <a href="<?= htmlspecialchars($full_bukti_url) ?>" target="_blank" class="btn btn-primary w-100">
                                        <i class="fas fa-file-image me-2"></i> Unduh Bukti Pembayaran
                                    </a>
                                <?php } else { ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Tidak ada bukti manual.
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/core/jquery-3.7.1.min.js"></script>
<script src="../assets/js/core/popper.min.js"></script>
<script src="../assets/js/core/bootstrap.min.js"></script>
<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="../assets/js/kaiadmin.min.js"></script>
</body>
</html>
