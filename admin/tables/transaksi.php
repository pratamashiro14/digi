<?php include "../koneksi.php";
require_once "../../keuangan_helper.php";

session_start();

// Periksa apakah sesi admin kosong, jika ya, arahkan ke logout
if(empty($_SESSION['admin'])){
    header('location:../logout.php');
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

// === PROSES PENARIKAN DANA PERUSAHAAN ===
// Pencatatan (ledger) penarikan saldo platform ke rekening perusahaan (terkunci).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tarik_perusahaan') {
    $info_cek = pendapatan_perusahaan($koneksi);
    $jumlah   = (int) preg_replace('/[^0-9]/', '', $_POST['jumlah'] ?? '0');
    $catatan  = trim($_POST['catatan'] ?? '');

    if ($jumlah < MIN_PENARIKAN_PERUSAHAAN) {
        $_SESSION['tarik_msg'] = ['danger', 'Nominal minimal ' . rupiah(MIN_PENARIKAN_PERUSAHAAN) . '.'];
    } elseif ($jumlah > $info_cek['tersedia']) {
        $_SESSION['tarik_msg'] = ['danger', 'Nominal melebihi saldo tersedia (' . rupiah($info_cek['tersedia']) . ').'];
    } else {
        $bank  = PERUSAHAAN_BANK;
        $rek   = PERUSAHAAN_REKENING;
        $nama  = PERUSAHAAN_NAMA;
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO t_penarikan_perusahaan (id_admin, jumlah, bank, no_rekening, nama_pemilik, catatan)
             VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'idssss', $id_admin, $jumlah, $bank, $rek, $nama, $catatan);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['tarik_msg'] = ['success', 'Penarikan ' . rupiah($jumlah) . ' berhasil dicatat ke rekening perusahaan.'];
        } else {
            $_SESSION['tarik_msg'] = ['danger', 'Gagal mencatat penarikan. Coba lagi.'];
        }
    }
    header('Location: transaksi.php');
    exit;
}

// === DATA SALDO & PENDAPATAN PERUSAHAAN ===
$pendapatan = pendapatan_perusahaan($koneksi);

// Riwayat penarikan perusahaan (siapa, berapa, kapan)
$riwayat_tarik = mysqli_query($koneksi,
    "SELECT pp.*, a.nama_admin
       FROM t_penarikan_perusahaan pp
       LEFT JOIN t_admin a ON pp.id_admin = a.id_admin
      ORDER BY pp.id_penarikan DESC");

// Flash message hasil penarikan
$tarik_msg = $_SESSION['tarik_msg'] ?? null;
unset($_SESSION['tarik_msg']);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tables - Transaksi Admin</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="../assets/img/kaiadmin/favicon.ico"
      type="image/x-icon"
    />

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

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="../assets/css/denscreative-admin.css" />

    <link rel="stylesheet" href="../assets/css/demo.css" />

<style>
      .logo-header .navbar-brand {
        height: 40px !important; 
        width: auto !important; 
      }
    </style>
    
  </head>
  <body>
    <div class="wrapper">
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
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
               <li class="nav-item">
                <a href="datapengguna.php">
                  <i class="fas fa-user"></i>
                  <p>Data Pengguna</p>
                </a>
              </li>
               <li class="nav-item active">
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
      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <div class="logo-header" data-background-color="dark">
              <a href="beranda.php" class="logo">
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
            </div>
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
                
                <li class="nav-item topbar-icon dropdown hidden-caret">
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
                    <span class="notification">4</span>
                  </a>
                  <ul
                    class="dropdown-menu notif-box animated fadeIn"
                    aria-labelledby="notifDropdown"
                  >
                    <li>
                      <div class="dropdown-title">
                        You have 1 new notification
                      </div>
                    </li>
                    <li>
                      <div class="notif-scroll scrollbar-outer">
                        <div class="notif-center">
                          <a href="#">
                            <div class="notif-icon notif-primary">
                              <i class="fa fa-user-plus"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block"> New user registered </span>
                              <span class="time">5 minutes ago</span>
                            </div>
                          </a>
                        </div>
                      </div>
                    </li>
                    <li>
                      <a class="see-all" href="javascript:void(0);"
                        >See all notifications<i class="fa fa-angle-right"></i>
                      </a>
                    </li>
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
                        <a class="dropdown-item" href="logout.php">Logout</a>
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
        <h1 class="fw-bold mb-3">Transaksi</h1>
        </div>

<?php if ($tarik_msg) { ?>
  <div class="alert alert-<?= $tarik_msg[0] ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($tarik_msg[1]) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php } ?>

<!-- ===== KARTU SALDO PERUSAHAAN ===== -->
<div class="row">
  <div class="col-sm-6 col-md-4">
    <div class="card card-stats card-round">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-icon">
            <div class="icon-big text-center icon-primary bubble-shadow-small">
              <i class="fas fa-coins"></i>
            </div>
          </div>
          <div class="col col-stats ms-3 ms-sm-0">
            <div class="numbers">
              <p class="card-category">Total Pendapatan Platform</p>
              <h4 class="card-title">Rp <?= number_format($pendapatan['kotor'], 0, ',', '.') ?></h4>
              <small class="text-muted">
                Fee transaksi Rp <?= number_format($pendapatan['fee_transaksi'], 0, ',', '.') ?>
                &middot; Premium Rp <?= number_format($pendapatan['premium'], 0, ',', '.') ?>
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-md-4">
    <div class="card card-stats card-round">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-icon">
            <div class="icon-big text-center icon-danger bubble-shadow-small">
              <i class="fas fa-money-bill-wave"></i>
            </div>
          </div>
          <div class="col col-stats ms-3 ms-sm-0">
            <div class="numbers">
              <p class="card-category">Sudah Ditarik</p>
              <h4 class="card-title">Rp <?= number_format($pendapatan['ditarik'], 0, ',', '.') ?></h4>
              <small class="text-muted">ke rekening perusahaan</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-12 col-md-4">
    <div class="card card-stats card-round">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-icon">
            <div class="icon-big text-center icon-success bubble-shadow-small">
              <i class="fas fa-wallet"></i>
            </div>
          </div>
          <div class="col col-stats ms-3 ms-sm-0">
            <div class="numbers">
              <p class="card-category">Saldo Tersedia</p>
              <h4 class="card-title">Rp <?= number_format($pendapatan['tersedia'], 0, ',', '.') ?></h4>
              <button type="button" class="btn btn-success btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#modalTarik">
                <i class="fas fa-paper-plane"></i> Tarik Dana
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="col-md-12">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Data Transaksi</div>
    </div>

      <table class="table table-hover">
        <thead class="table-light">
          <tr>
            <th>No</th>
            <th>Karya</th>
            <th>Pembeli</th>
            <th>Harga Final</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th>Aksi</th> 
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          $query = mysqli_query($koneksi, "
            SELECT t.*, d.judul AS nama_karya, u.nama AS nama_pembeli
            FROM t_transaksi t
            LEFT JOIN t_design d ON t.id_design = d.id_design
            LEFT JOIN t_user u ON t.id_buyer = u.id_user
            ORDER BY t.id_transaksi DESC
          ");

          if (mysqli_num_rows($query) == 0) {
            echo "<tr><td colspan='8' class='text-center text-muted'>Belum ada data transaksi</td></tr>"; 
          }
          
          while ($row = mysqli_fetch_assoc($query)) {
          ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= htmlspecialchars($row['nama_karya']) ?></td>
              <td><?= htmlspecialchars($row['nama_pembeli']) ?></td>
              <td>Rp <?= number_format($row['harga_final'], 0, ',', '.') ?></td>
              <td><?= ucfirst($row['metode_pembayaran']) ?></td>
              <td>
                <span class="badge bg-<?= 
                  $row['status_pembayaran']=='berhasil' ? 'success' :
                  ($row['status_pembayaran']=='pending' ? 'warning' : 'danger') ?>">
                  <?= ucfirst($row['status_pembayaran']) ?>
                </span>
              </td>
              <td><?= $row['tanggal_transaksi'] ?></td>
<td>
                <a href="transaksi_detail.php?id=<?= $row['id_transaksi'] ?>" 
                    class="text-info"
                    style="text-decoration: underline;">
                    Lihat Detail
                </a>
              </td>
              </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
    </div>
</div>
<!-- ===== MODAL TARIK DANA PERUSAHAAN ===== -->
<div class="modal fade" id="modalTarik" tabindex="-1" aria-labelledby="modalTarikLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTarikLabel"><i class="fas fa-wallet me-2"></i>Tarik Dana Perusahaan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <!-- Rincian pendapatan per periode -->
        <h6 class="fw-bold mb-2">Rincian Pendapatan</h6>
        <div class="table-responsive mb-3">
          <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Periode</th>
                <th class="text-center">Transaksi</th>
                <th class="text-end">Fee Transaksi</th>
                <th class="text-end">Premium</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $periode_label = ['harian' => 'Hari Ini', 'mingguan' => '7 Hari Terakhir', 'bulanan' => 'Bulan Ini'];
              foreach ($periode_label as $key => $label) {
                  $r = $pendapatan['rincian'][$key];
              ?>
              <tr>
                <td><?= $label ?></td>
                <td class="text-center"><?= (int) $r['jml_transaksi'] ?></td>
                <td class="text-end">Rp <?= number_format($r['fee'], 0, ',', '.') ?></td>
                <td class="text-end">Rp <?= number_format($r['premium'], 0, ',', '.') ?></td>
                <td class="text-end fw-bold">Rp <?= number_format($r['total'], 0, ',', '.') ?></td>
              </tr>
              <?php } ?>
              <tr class="table-light">
                <td class="fw-bold">Total Keseluruhan</td>
                <td class="text-center fw-bold"><?= (int) $pendapatan['jml_transaksi'] ?></td>
                <td class="text-end fw-bold">Rp <?= number_format($pendapatan['fee_transaksi'], 0, ',', '.') ?></td>
                <td class="text-end fw-bold">Rp <?= number_format($pendapatan['premium'], 0, ',', '.') ?></td>
                <td class="text-end fw-bold text-primary">Rp <?= number_format($pendapatan['kotor'], 0, ',', '.') ?></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Rekening tujuan (terkunci) -->
        <h6 class="fw-bold mb-2">Rekening Tujuan (Default)</h6>
        <div class="border rounded p-3 mb-3 bg-light">
          <div class="d-flex justify-content-between">
            <span class="text-muted">Bank</span>
            <strong><?= htmlspecialchars(PERUSAHAAN_BANK) ?></strong>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">No. Rekening</span>
            <strong><?= htmlspecialchars(PERUSAHAAN_REKENING) ?></strong>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Atas Nama</span>
            <strong><?= htmlspecialchars(PERUSAHAAN_NAMA) ?></strong>
          </div>
          <small class="text-muted d-block mt-2"><i class="fas fa-lock me-1"></i>Rekening terkunci — penarikan hanya ke rekening resmi perusahaan.</small>
        </div>

        <!-- Form penarikan -->
        <form method="POST" id="formTarik">
          <input type="hidden" name="aksi" value="tarik_perusahaan">
          <div class="mb-3">
            <label class="form-label fw-bold">Saldo Tersedia</label>
            <div class="h4 text-success mb-0">Rp <?= number_format($pendapatan['tersedia'], 0, ',', '.') ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label">Nominal Penarikan (Rp)</label>
            <input type="number" name="jumlah" id="inputTarik" class="form-control"
                   min="<?= (int) MIN_PENARIKAN_PERUSAHAAN ?>" max="<?= (int) $pendapatan['tersedia'] ?>"
                   placeholder="Min. <?= number_format(MIN_PENARIKAN_PERUSAHAAN, 0, ',', '.') ?>"
                   <?= $pendapatan['tersedia'] < MIN_PENARIKAN_PERUSAHAAN ? 'disabled' : 'required' ?>>
            <small class="text-muted">Minimal <?= rupiah(MIN_PENARIKAN_PERUSAHAAN) ?> &middot; maksimal <?= rupiah($pendapatan['tersedia']) ?></small>
          </div>
          <div class="mb-3">
            <label class="form-label">Catatan (opsional)</label>
            <input type="text" name="catatan" class="form-control" maxlength="200" placeholder="mis. setor kas operasional Juni">
          </div>
          <?php if ($pendapatan['tersedia'] < MIN_PENARIKAN_PERUSAHAAN) { ?>
            <button type="button" class="btn btn-secondary w-100" disabled>Saldo belum mencukupi (min. <?= rupiah(MIN_PENARIKAN_PERUSAHAAN) ?>)</button>
          <?php } else { ?>
            <button type="submit" class="btn btn-success w-100"
                    onclick="return confirm('Catat penarikan dana ke rekening perusahaan?')">
              <i class="fas fa-paper-plane me-1"></i> Tarik & Catat
            </button>
          <?php } ?>
        </form>

        <!-- Riwayat penarikan -->
        <hr class="my-4">
        <h6 class="fw-bold mb-2">Riwayat Penarikan</h6>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Tanggal</th>
                <th class="text-end">Nominal</th>
                <th>Rekening</th>
                <th>Oleh Admin</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$riwayat_tarik || mysqli_num_rows($riwayat_tarik) == 0) { ?>
                <tr><td colspan="5" class="text-center text-muted">Belum ada penarikan</td></tr>
              <?php } else { while ($t = mysqli_fetch_assoc($riwayat_tarik)) { ?>
                <tr>
                  <td><small><?= date('d M Y H:i', strtotime($t['tanggal_penarikan'])) ?></small></td>
                  <td class="text-end fw-bold text-danger">Rp <?= number_format($t['jumlah'], 0, ',', '.') ?></td>
                  <td><small><?= htmlspecialchars($t['bank']) ?><br><?= htmlspecialchars($t['no_rekening']) ?></small></td>
                  <td><small><?= htmlspecialchars($t['nama_admin'] ?? '-') ?></small></td>
                  <td><small><?= $t['catatan'] ? htmlspecialchars($t['catatan']) : '<span class="text-muted">-</span>' ?></small></td>
                </tr>
              <?php } } ?>
            </tbody>
          </table>
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
    
  </body>
</html>
