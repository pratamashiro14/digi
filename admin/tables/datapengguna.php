<?php include "../koneksi.php";

session_start();

// Periksa apakah sesi admin kosong, jika ya, arahkan ke logout
if(empty($_SESSION['admin'])){
    header('location:../logout.php');
    exit;
}

require_once "../../bidding_helper.php";
require_once "../../wanprestasi_helper.php"; // sweep wanprestasi & tenggat
require_once "../../identitas_helper.php"; // blokir_identitas() generik (nik/telp/email)

// Halaman admin biasa — cukup throttled per sesi.
jalankan_pemeliharaan_lelang($koneksi);

// === LOGIKA VERIFIKASI KTP (DESAINER & PELANGGAN) ===
if (isset($_GET['approve_ktp'])) {
    $id_verif = mysqli_real_escape_string($koneksi, $_GET['approve_ktp']);
    mysqli_query($koneksi, "UPDATE t_user SET status_verifikasi = 'verified' WHERE id_user = '$id_verif'");
    header("Location: datapengguna.php");
    exit;
}
if (isset($_GET['reject_ktp'])) {
    $id_verif = mysqli_real_escape_string($koneksi, $_GET['reject_ktp']);
    mysqli_query($koneksi, "UPDATE t_user SET status_verifikasi = 'unverified', foto_ktp = NULL WHERE id_user = '$id_verif'");
    header("Location: datapengguna.php");
    exit;
}

// === SUSPEND / AKTIFKAN AKUN (sementara, level akun) ===
if (isset($_GET['suspend'])) {
    $id = (int) $_GET['suspend'];
    $stmt = mysqli_prepare($koneksi, "UPDATE t_user SET status='nonaktif' WHERE id_user=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    header("Location: datapengguna.php");
    exit;
}
if (isset($_GET['aktifkan'])) {
    $id = (int) $_GET['aktifkan'];
    $stmt = mysqli_prepare($koneksi, "UPDATE t_user SET status='aktif' WHERE id_user=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    header("Location: datapengguna.php");
    exit;
}

// === BAN / UNBAN IDENTITAS (permanen, lintas akun) ===
// Generalisasi dari ban_nik lama: sejak pelanggan tidak lagi menyimpan KTP,
// jangkar identitas mereka adalah nomor HP (no_telp_norm) & email, sedangkan
// desainer tetap punya NIK. $tipe menentukan kolom mana yang diblokir.
// t_blokir_nik LAMA tidak dipakai lagi di sini (datanya sudah dipindah ke
// t_blokir_identitas oleh auto-migration di admin/koneksi.php).
if (isset($_GET['ban_identitas'])) {
    $id = (int) $_GET['ban_identitas'];
    $tipe = $_GET['tipe'] ?? '';
    $alasan = trim($_GET['alasan'] ?? '');
    if ($alasan === '') $alasan = 'Pelanggaran (spam/penyalahgunaan lelang).';

    if (in_array($tipe, ['nik', 'telp', 'email'], true)) {
        $kolom = ['nik' => 'nik', 'telp' => 'no_telp_norm', 'email' => 'email'][$tipe];
        $q = mysqli_prepare($koneksi, "SELECT `$kolom` AS nilai FROM t_user WHERE id_user=?");
        mysqli_stmt_bind_param($q, 'i', $id);
        mysqli_stmt_execute($q);
        $nilai = trim(mysqli_fetch_assoc(mysqli_stmt_get_result($q))['nilai'] ?? '');
        if ($nilai !== '') {
            blokir_identitas($koneksi, $tipe, $nilai, $alasan, (int) $_SESSION['admin']);
            mysqli_query($koneksi, "UPDATE t_user SET status='nonaktif', suspend_sampai=NULL WHERE id_user='$id'");
        }
    }
    header("Location: datapengguna.php");
    exit;
}
if (isset($_GET['unban_identitas'])) {
    $id = (int) $_GET['unban_identitas'];
    $tipe = $_GET['tipe'] ?? '';

    if (in_array($tipe, ['nik', 'telp', 'email'], true)) {
        $kolom = ['nik' => 'nik', 'telp' => 'no_telp_norm', 'email' => 'email'][$tipe];
        $q = mysqli_prepare($koneksi, "SELECT `$kolom` AS nilai FROM t_user WHERE id_user=?");
        mysqli_stmt_bind_param($q, 'i', $id);
        mysqli_stmt_execute($q);
        $nilai = trim(mysqli_fetch_assoc(mysqli_stmt_get_result($q))['nilai'] ?? '');
        if ($nilai !== '') {
            buka_blokir_identitas($koneksi, $tipe, $nilai);
            mysqli_query($koneksi, "UPDATE t_user SET status='aktif' WHERE id_user='$id'");
        }
    }
    header("Location: datapengguna.php");
    exit;
}

// === MAAFKAN STRIKE WANPRESTASI ===
// Menghapus SEMUA pelanggaran aktif seorang user (dimaafkan=1, bukan
// DELETE — riwayat tetap ada untuk audit) sekaligus memulihkan akun bila
// sedang suspend berjangka akibat strike tersebut.
if (isset($_GET['maafkan_strike'])) {
    $id = (int) $_GET['maafkan_strike'];
    $stmt = mysqli_prepare($koneksi, "UPDATE t_wanprestasi SET dimaafkan=1 WHERE id_user=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $stmt = mysqli_prepare($koneksi, "UPDATE t_user SET status='aktif', suspend_sampai=NULL WHERE id_user=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: datapengguna.php");
    exit;
}

// === WAJIB ADA: AMBIL DATA ADMIN UNTUK PROFIL DINAMIS ===
$id_admin = $_SESSION['admin'];
$query_admin = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE id_admin = '$id_admin'"); 
$data_admin = mysqli_fetch_assoc($query_admin);

if (!$data_admin) {
    header('location:../logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tables - Kaiadmin Bootstrap 5 Admin Dashboard</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="../assets/img/kaiadmin/favicon.ico"
      type="image/x-icon"
    />

    <!-- Fonts and icons -->
    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular",
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["../assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="../assets/css/denscreative-admin.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="../assets/css/demo.css" />
  </head>
  <body>
    <div class="wrapper">
      <!-- Sidebar -->
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="../beranda.php" class="logo">
              <img
                src="../assets/img/dens.png"
                alt="navbar brand"
                class="navbar-brand"
                
              />
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
 </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item">
                <a href="../beranda.php"
                  aria-expanded="false"
                >
                  <i class="fas fa-home"></i>
                  <p>Beranda</p>
                </a>
              <li class="nav-item">
                <a href="karyadesain.php">
                  <i class="fas fa-palette"></i>
                  <p>Karya Desain</p>
                </a>
              </li>
               <li class="nav-item active">
                <a href="datapengguna.php">
                  <i class="fas fa-user"></i>
                  <p>Data Pengguna</p>
                </a>
              </li>
               <li class="nav-item">
                <a href="transaksi.php">
                  <i class="fas fa-money-bill-wave"></i>
                  <p>Transaksi</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="datapencairan.php">
                  <i class="fas fa-wallet"></i>
                  <p>Data Pencairan</p>
                </a>
              </li>

               <li class="nav-item">
                <a href="datapremium.php">
                  <i class="fas fa-crown"></i>
                  <p>Data Premium</p>
                </a>
              </li>
            <li class="nav-item">
                <a href="databidding.php">
                  <i class="fas fa-hand-holding-usd"></i>
                  <p>Data Bidding</p>
                </a>
              </li>
             <li class="nav-item">
                <a href="datachat.php">
                  <i class="fas fa-comments"></i>
                  <p>Moderisasi Chat</p>
                </a>
              </li>
              <li class="nav-item">
              <a href="dataadmin.php">
                <i class="fas fa-user-plus"></i>
                <p>Tambah Admin</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <p>Logout</p>
              </a>
            </li>
                <div class="collapse" id="submenu">
                  <ul class="nav nav-collapse">
                    <li>
                      <a data-bs-toggle="collapse" href="#subnav1">
                        <span class="sub-item">Level 1</span>
                        <span class="caret"></span>
                      </a>
                      <div class="collapse" id="subnav1">
                        <ul class="nav nav-collapse subnav">
                        </ul>
                      </div>
                    </li>
                    <li>
                      <a data-bs-toggle="collapse" href="#subnav2">
                        <span class="sub-item">Level 1</span>
                        <span class="caret"></span>
                      </a>
                      <div class="collapse" id="subnav2">
                        <ul class="nav nav-collapse subnav">
                          <li>
                            <a href="#">
                              <span class="sub-item">Level 2</span>
                            </a>
                          </li>
                        </ul>
                      </div>
                    </li>
                    <li>
                      <a href="#">
                        <span class="sub-item">Level 1</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->

      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <!-- Logo Header -->
            <div class="logo-header" data-background-color="dark">
              <a href="beranda.php" class="logo">
                <img
                  src="../assets/img/kaiadmin/logo_light.svg"
                  alt="navbar brand"
                  class="navbar-brand"
                  height="20"
                />
              </a>
              <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                  <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                  <i class="gg-menu-left"></i>
                </button>
              </div>
              <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
              </button>
            </div>
            <!-- End Logo Header -->
          </div>
          <!-- Navbar Header -->
                  <nav
            class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom"
          >
            <div class="container-fluid">
              <nav
                class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex"
              >
                <div class="input-group">
                  <div class="input-group-prepend">
                    <button type="submit" class="btn btn-search pe-1">
                      <i class="fa fa-search search-icon"></i>
                    </button>
                  </div>
                  <input
                    type="text"
                    placeholder="Search ..."
                    class="form-control"
                  />
                </div>
              </nav>

              <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li
                  class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none"
                >
                  <a
                    class="nav-link dropdown-toggle"
                    data-bs-toggle="dropdown"
                    href="#"
                    role="button"
                    aria-expanded="false"
                    aria-haspopup="true"
                  >
                    <i class="fa fa-search"></i>
                  </a>
                  <ul class="dropdown-menu dropdown-search animated fadeIn">
                    <form class="navbar-left navbar-form nav-search">
                      <div class="input-group">
                        <input
                          type="text"
                          placeholder="Search ..."
                          class="form-control"
                        />
                      </div>
                    </form>
                  </ul>
                </li>
                <li class="nav-item topbar-icon dropdown hidden-caret" id="notification-dropdown-item">
                  <a
                    class="nav-link dropdown-toggle"
                    href="#"
                    id="notifDropdown"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                  <i class="fa fa-bell"></i>
                    <span class="notification" id="notif-count-badge">0</span>
                  </a>
                  <ul
                    class="dropdown-menu notif-box animated fadeIn"
                    aria-labelledby="notifDropdown"
                    id="notification-content-container"
                  >
                    </ul>
                </li>
                      
                <li class="nav-item topbar-user dropdown hidden-caret">
                  <a
                    class="dropdown-toggle profile-pic"
                    data-bs-toggle="dropdown"
                    href="#"
                    aria-expanded="false"
                  >
                    <div class="avatar-sm">
                      <img
                        src="../assets/img/fotoprofil/<?= htmlspecialchars($data_admin['foto'] ?: 'profile.jpg'); ?>"
                        alt="..."
                        class="avatar-img rounded-circle"
                      />
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
                            <img
                              src="../assets/img/fotoprofil/<?= htmlspecialchars($data_admin['foto'] ?: 'profile.jpg'); ?>"
                              alt="image profile"
                              class="avatar-img rounded"
                            />
                          </div>
                          <div class="u-text">
                            <h4><?= htmlspecialchars($data_admin['nama_admin']); ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($data_admin['email']); ?></p>
                            <a
                              href="../profile.php"
                              class="btn btn-xs btn-secondary btn-sm"
                              >View Profile</a
                            >
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
          <!-- End Navbar -->
        </div>

        <div class="container">
          <div class="page-inner">
            <div class="page-header">
              <h3 class="fw-bold mb-3">Data Pengguna</h3>
              <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                  <a href="../beranda.php">
                    <i class="icon-home"></i>
                  </a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="datapengguna.php">Data Pengguna</a>
                </li>
              </ul>
            </div>
                    
<?php 
include '../koneksi.php'; 
?>

<div class="col-md-12">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Tabel Daftar Pengguna</div>
    </div>
    <div class="card-body">
      <a href="datapengguna_tambah.php" class="btn btn-primary mb-3">+ Tambah Data</a>

      <div class="table-responsive">
      <table class="table table-hover align-middle" style="min-width: 1000px;">
        <thead class="table-light">
          <tr>
            <th scope="col" style="width: 5%">No</th>
            <th scope="col">Nama Lengkap</th>
            <th scope="col">Email</th>
            <th scope="col">Peran</th>
            <th scope="col">Premium</th>
            <th scope="col">Status</th>
            <th scope="col">Verif KTP</th>
            <th scope="col">Moderasi</th>
            <th scope="col" style="width: 10%">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          $query = mysqli_query($koneksi, "SELECT * FROM t_user ORDER BY id_user DESC");

          if (mysqli_num_rows($query) == 0) {
              echo "<tr><td colspan='9' class='text-center text-muted'>Belum ada data pengguna</td></tr>";
          } else {
              while ($row = mysqli_fetch_assoc($query)) {
                  $is_designer = $row['role'] === 'designer';
                  $is_pelanggan = $row['role'] === 'pelanggan';

                  // Identitas yang relevan beda per role sejak pelanggan tidak lagi
                  // menyimpan KTP: desainer diban lewat NIK, pelanggan lewat telp/email.
                  $banned_nik   = $is_designer && !empty($row['nik']) && identitas_diblokir($koneksi, 'nik', $row['nik']);
                  $banned_telp  = !empty($row['no_telp_norm']) && identitas_diblokir($koneksi, 'telp', $row['no_telp_norm']);
                  $banned_email = identitas_diblokir($koneksi, 'email', $row['email']);
                  $tipe_diblokir = array_keys(array_filter([
                      'NIK' => $banned_nik, 'HP' => $banned_telp, 'Email' => $banned_email,
                  ]));
                  $is_banned = !empty($tipe_diblokir);

                  $jumlah_strike = $is_pelanggan ? hitung_strike($koneksi, $row['id_user']) : 0;
          ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
              <span class="badge bg-<?=
                ($row['role'] == 'designer' ? 'primary' :
                ($row['role'] == 'pelanggan' ? 'warning' : 'secondary')) ?>">
                <?= ucfirst($row['role']) ?>
              </span>
            </td>
            <td>
              <span class="badge bg-<?= $row['premium'] == 1 ? 'success' : 'secondary' ?>">
                <?= $row['premium'] == 1 ? 'Ya' : 'Tidak' ?>
              </span>
            </td>
            <td>
              <span class="badge bg-<?= $row['status'] == 'aktif' ? 'success' : 'warning' ?>">
                <?= ucfirst($row['status'] ?? 'aktif') ?>
              </span>
              <?php if (($row['status'] ?? '') === 'nonaktif' && !empty($row['suspend_sampai'])): ?>
                  <br><small class="text-muted" style="font-size:10px;">sampai <?= date('d M Y H:i', strtotime($row['suspend_sampai'])) ?></small>
              <?php endif; ?>
              <?php if ($is_banned): ?>
                <br><span class="badge bg-danger mt-1">Diblokir (<?= htmlspecialchars(implode(' + ', $tipe_diblokir)) ?>)</span>
              <?php endif; ?>
              <?php if ($jumlah_strike > 0): ?>
                  <br><span class="badge bg-dark mt-1"><?= $jumlah_strike ?> pelanggaran</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($is_designer) : ?>
                  <?php if ($row['status_verifikasi'] == 'pending') : ?>
                      <span class="badge bg-warning text-dark">Pending</span>
                      <br><small><strong>NIK:</strong> <?= htmlspecialchars($row['nik'] ?? '-') ?></small>
                      <?php if (!empty($row['foto_ktp'])): ?>
                          <br><small><button type="button" class="btn btn-link p-0 ktp-preview-btn" data-bs-toggle="modal" data-bs-target="#ktpPreviewModal" data-ktp-src="../uploads/<?= htmlspecialchars($row['foto_ktp']) ?>" data-ktp-name="<?= htmlspecialchars($row['nama']) ?>">Lihat KTP</button></small>
                      <?php endif; ?>
                      <br>
                      <a href="?approve_ktp=<?= $row['id_user'] ?>" class="btn btn-sm btn-success mt-1 py-0 px-1" style="font-size:11px;">Terima</a>
                      <a href="?reject_ktp=<?= $row['id_user'] ?>" class="btn btn-sm btn-danger mt-1 py-0 px-1" style="font-size:11px;">Tolak</a>
                  <?php elseif ($row['status_verifikasi'] == 'verified') : ?>
                      <span class="badge bg-success">Verified</span>
                      <br><small><strong>NIK:</strong> <?= htmlspecialchars($row['nik'] ?? '-') ?></small>
                      <?php if (!empty($row['foto_ktp'])): ?>
                          <br><small><button type="button" class="btn btn-link p-0 ktp-preview-btn" data-bs-toggle="modal" data-bs-target="#ktpPreviewModal" data-ktp-src="../uploads/<?= htmlspecialchars($row['foto_ktp']) ?>" data-ktp-name="<?= htmlspecialchars($row['nama']) ?>">Lihat KTP</button></small>
                      <?php endif; ?>
                  <?php else: ?>
                      <span class="badge bg-secondary">Unverified</span>
                      <?php if (!empty($row['nik'])): ?>
                          <br><small><strong>NIK:</strong> <?= htmlspecialchars($row['nik']) ?></small>
                      <?php endif; ?>
                  <?php endif; ?>
              <?php elseif ($is_pelanggan): ?>
                  <span class="text-muted" title="Pelanggan tidak lagi wajib verifikasi KTP — lihat email &amp; No. HP.">
                      <i class="fas fa-circle-info"></i> Tidak berlaku
                  </span>
                  <br><small>Email: <?= ((int) ($row['email_terverifikasi'] ?? 0) === 1) ? '<span class="text-success">terverifikasi</span>' : '<span class="text-danger">belum</span>' ?></small>
                  <br><small>HP: <?= !empty($row['no_telp_norm']) ? htmlspecialchars($row['no_telp_norm']) : '<span class="text-muted">belum diisi</span>' ?></small>
              <?php else: ?>
                  <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['role'] === 'admin'): ?>
                  <span class="text-muted">-</span>
              <?php else: ?>
                  <?php if (($row['status'] ?? 'aktif') === 'aktif'): ?>
                      <a href="?suspend=<?= $row['id_user'] ?>" class="btn btn-sm btn-warning py-0 px-1 mb-1" style="font-size:11px;"
                         onclick="return confirm('Suspend (nonaktifkan sementara) akun ini?')">Suspend</a>
                  <?php else: ?>
                      <a href="?aktifkan=<?= $row['id_user'] ?>" class="btn btn-sm btn-success py-0 px-1 mb-1" style="font-size:11px;">Aktifkan</a>
                  <?php endif; ?>
                  <br>

                  <?php if ($is_designer): ?>
                      <?php if (empty($row['nik'])): ?>
                          <small class="text-muted" style="font-size:10px;">Ban NIK: belum verifikasi</small>
                      <?php elseif ($banned_nik): ?>
                          <a href="?unban_identitas=<?= $row['id_user'] ?>&tipe=nik" class="btn btn-sm btn-secondary py-0 px-1 mt-1" style="font-size:11px;">Unban NIK</a>
                      <?php else: ?>
                          <a href="#" onclick="return banIdentitas(<?= $row['id_user'] ?>, 'nik')" class="btn btn-sm btn-danger py-0 px-1 mt-1" style="font-size:11px;">Ban NIK</a>
                      <?php endif; ?>
                  <?php else: ?>
                      <?php if ($banned_email): ?>
                          <a href="?unban_identitas=<?= $row['id_user'] ?>&tipe=email" class="btn btn-sm btn-secondary py-0 px-1 mt-1" style="font-size:11px;">Unban Email</a>
                      <?php else: ?>
                          <a href="#" onclick="return banIdentitas(<?= $row['id_user'] ?>, 'email')" class="btn btn-sm btn-danger py-0 px-1 mt-1" style="font-size:11px;">Ban Email</a>
                      <?php endif; ?>
                      <?php if (!empty($row['no_telp_norm'])): ?>
                          <?php if ($banned_telp): ?>
                              <a href="?unban_identitas=<?= $row['id_user'] ?>&tipe=telp" class="btn btn-sm btn-secondary py-0 px-1 mt-1" style="font-size:11px;">Unban HP</a>
                          <?php else: ?>
                              <a href="#" onclick="return banIdentitas(<?= $row['id_user'] ?>, 'telp')" class="btn btn-sm btn-danger py-0 px-1 mt-1" style="font-size:11px;">Ban HP</a>
                          <?php endif; ?>
                      <?php endif; ?>
                      <?php if ($jumlah_strike > 0): ?>
                          <br><a href="?maafkan_strike=<?= $row['id_user'] ?>" class="btn btn-sm btn-outline-dark py-0 px-1 mt-1" style="font-size:11px;"
                                 onclick="return confirm('Maafkan semua pelanggaran wanprestasi akun ini & pulihkan dari suspend?')">Maafkan Strike</a>
                      <?php endif; ?>
                  <?php endif; ?>
              <?php endif; ?>
            </td>
            <td>
              <a href="datapengguna_edit.php?id=<?= $row['id_user'] ?>" class="text-primary me-2">
                <i class="fas fa-pencil-alt"></i>
              </a>
              <a href="datapengguna_hapus.php?id=<?= $row['id_user'] ?>"
                 class="text-danger"
                 data-swal-confirm="Data pengguna ini akan dihapus permanen.">
                <i class="fas fa-trash-alt"></i>
              </a>
            </td>
          </tr>
          <?php
              }
          }
          ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="ktpPreviewModal" tabindex="-1" aria-labelledby="ktpPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content ktp-preview-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="ktpPreviewModalLabel">Preview KTP</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <img src="" alt="Preview KTP" id="ktpPreviewImage" class="ktp-preview-image">
      </div>
      <div class="modal-footer">
        <a href="#" target="_blank" rel="noopener" id="ktpPreviewOpen" class="btn btn-outline-primary">Buka Gambar</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
      </div>
    </div>
  </div>
</div>
    </div>
    <!--   Core JS Files   -->
    <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <!-- Kaiadmin JS -->
    <script src="../assets/js/kaiadmin.min.js"></script>
    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="../assets/js/setting-demo2.js"></script>
    <script>
      $("#displayNotif").on("click", function () {
        var placementFrom = $("#notify_placement_from option:selected").val();
        var placementAlign = $("#notify_placement_align option:selected").val();
        var state = $("#notify_state option:selected").val();
        var style = $("#notify_style option:selected").val();
        var content = {};

        content.message =
          'Turning standard Bootstrap alerts into "notify" like notifications';
        content.title = "Bootstrap notify";
        if (style == "withicon") {
          content.icon = "fa fa-bell";
        } else {
          content.icon = "none";
        }
        content.url = "beranda.php";
        content.target = "_blank";

        $.notify(content, {
          type: state,
          placement: {
            from: placementFrom,
            align: placementAlign,
          },
          time: 1000,
        });
      });
    </script>

    <script>
      var ktpPreviewModal = document.getElementById('ktpPreviewModal');
      if (ktpPreviewModal) {
        ktpPreviewModal.addEventListener('show.bs.modal', function (event) {
          var button = event.relatedTarget;
          var imageSrc = button ? button.getAttribute('data-ktp-src') : '';
          var userName = button ? button.getAttribute('data-ktp-name') : '';
          var image = document.getElementById('ktpPreviewImage');
          var openLink = document.getElementById('ktpPreviewOpen');
          var title = document.getElementById('ktpPreviewModalLabel');

          image.src = imageSrc;
          openLink.href = imageSrc;
          title.textContent = userName ? 'Preview KTP - ' + userName : 'Preview KTP';
        });

        ktpPreviewModal.addEventListener('hidden.bs.modal', function () {
          document.getElementById('ktpPreviewImage').src = '';
          document.getElementById('ktpPreviewOpen').href = '#';
        });
      }
    </script>

    <script>
    function updateNotificationDropdown() {
        $.ajax({
            // Path file PHP harus disesuaikan, karena karyadesain.php ada di subfolder,
            // file check_notifs.php berada satu folder di atasnya.
            url: '../check_notifs.php', 
            type: 'GET',
            dataType: 'html', // Mengambil HTML dari file PHP
            success: function(html_content) {
                // Ganti seluruh isi dropdown dengan HTML yang diterima
                $('#notification-content-container').html(html_content);
                
                // Ambil count dari konten yang diterima
                var countText = $('#notification-content-container').find('.dropdown-title').text();
                var match = countText.match(/(\d+)/); // Cari angka
                var count = match ? match[0] : 0;
                
                // Update badge (lingkaran merah)
                $('#notif-count-badge').text(count);
            },
            error: function() {
                $('#notification-content-container').html('<div class="p-3 text-center text-danger">Gagal memuat notifikasi.</div>');
            }
        });
    }

    // Panggil segera dan ulangi setiap 30 detik
    $(document).ready(function() {
        // Aktifkan update notifikasi dropdown
        updateNotificationDropdown(); 
        setInterval(updateNotificationDropdown, 30000); // Update setiap 30 detik
    });
    </script>
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script src="../../js/sweetalert-confirm.js"></script>
    <script>
      // Ban berbasis identitas (NIK utk desainer, Email/HP utk pelanggan):
      // minta alasan lalu arahkan ke handler.
      function banIdentitas(id, tipe) {
        var label = {nik: 'NIK', telp: 'nomor HP', email: 'email'}[tipe] || tipe;
        var alasan = prompt('BAN ' + label.toUpperCase() + ' permanen — pelaku tidak bisa ikut lelang walau buat akun baru.\n\nAlasan ban:', 'Spam/penyalahgunaan lelang');
        if (alasan === null) return false; // batal
        window.location.href = '?ban_identitas=' + id + '&tipe=' + encodeURIComponent(tipe) + '&alasan=' + encodeURIComponent(alasan);
        return false;
      }
    </script>
  </body>
</html>
