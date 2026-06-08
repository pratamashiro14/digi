<?php
include '../koneksi.php';

session_start();
if(empty($_SESSION['admin'])){
  header('location:../logout.php');
} else {

if (isset($_POST['simpan'])) {
    $id_buyer = $_POST['id_buyer'];
    $id_design = $_POST['id_design'];
    $nominal = $_POST['nominal'];
    $status = $_POST['status'];
    $tanggal_bid = date('Y-m-d');

    $insert = mysqli_query($koneksi, "INSERT INTO t_bidding (id_buyer, id_design, nominal, status, tanggal_bid) 
        VALUES ('$id_buyer', '$id_design', '$nominal', '$status', '$tanggal_bid')
    ");

    if ($insert) {
        echo "<script>
                alert('Data bidding berhasil ditambahkan!');
                window.location.href='databidding.php';
              </script>";
    } else {
        echo "<script>alert('Gagal menambahkan data: " . mysqli_error($koneksi) . "');</script>";
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tambah Data Bidding</title>
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
    <link rel="stylesheet" href="../assets/css/digidesain-admin.css" />

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
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item">
                <a href="../beranda.php" aria-expanded="false">
                  <i class="fas fa-home"></i>
                  <p>Beranda</p>
                </a>
              </li>
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
              <li class="nav-item">
                <a href="transaksi.php">
                  <i class="fas fa-money-bill-wave"></i>
                  <p>Transaksi</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="datapremium.php">
                  <i class="fas fa-crown"></i>
                  <p>Data Premium</p>
                </a>
              </li>
              <li class="nav-item active">
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
              <a href="../beranda.php" class="logo">
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
        </div>

        <div class="container">
          <div class="page-inner">
            <div class="page-header mb-4">
              <h1 class="fw-bold">Tambah Data Bidding</h1>
            </div>

            <div class="row">
              <div class="col-md-8">
                <div class="card card-round shadow-sm">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Form Tambah Bidding</h5>
                  </div>

                  <div class="card-body">
                    <form method="POST" action="">
                      <div class="mb-3">
                        <label class="form-label fw-bold">Nama Buyer</label>
                        <select name="id_buyer" class="form-select" required>
                          <option value="">-- Pilih Buyer --</option>
                          <?php
                          $query_buyer = mysqli_query($koneksi, "SELECT id_user, nama FROM t_user WHERE role='pelanggan' ORDER BY nama");
                          while ($buyer = mysqli_fetch_assoc($query_buyer)) {
                            echo "<option value='" . $buyer['id_user'] . "'>" . htmlspecialchars($buyer['nama']) . "</option>";
                          }
                          ?>
                        </select>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Judul Karya</label>
                        <select name="id_design" class="form-select" required>
                          <option value="">-- Pilih Karya Desain --</option>
                          <?php
                          $query_design = mysqli_query($koneksi, "SELECT id_design, judul FROM t_design ORDER BY judul");
                          while ($design = mysqli_fetch_assoc($query_design)) {
                            echo "<option value='" . $design['id_design'] . "'>" . htmlspecialchars($design['judul']) . "</option>";
                          }
                          ?>
                        </select>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Nominal Penawaran</label>
                        <input type="number" name="nominal" class="form-control" placeholder="Masukkan nominal penawaran" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select" required>
                          <option value="">-- Pilih Status --</option>
                          <option value="pending">Pending</option>
                          <option value="diterima">Diterima</option>
                          <option value="ditolak">Ditolak</option>
                        </select>
                      </div>

                      <div class="d-flex gap-2">
                        <button type="submit" name="simpan" class="btn btn-primary">
                          <i class="fas fa-save me-2"></i>Simpan
                        </button>
                        <a href="databidding.php" class="btn btn-secondary">
                          <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Custom template -->
        <div class="custom-template">
          <div class="title">Settings</div>
          <div class="custom-toggle">
            <i class="icon-settings"></i>
          </div>
        </div>
        <!-- End Custom template -->
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
  </body>
</html>
