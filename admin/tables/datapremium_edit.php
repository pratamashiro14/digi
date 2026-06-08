<?php
include '../koneksi.php';

session_start();
if(empty($_SESSION['admin'])){
  header('location:../logout.php');
} else {

$id = $_GET['id'];
$query = mysqli_query($koneksi, "
    SELECT p.*, u.nama AS nama_pengguna
    FROM t_premium p
    LEFT JOIN t_user u ON p.id_user = u.id_user
    WHERE p.id_premium = '$id'
");

$data = mysqli_fetch_assoc($query);

if (isset($_POST['simpan'])) {
    $tipe_premium = $_POST['tipe_premium'];
    $tanggal_aktif = $_POST['tanggal_aktif'];
    $tanggal_berakhir = $_POST['tanggal_berakhir'];
    $status = $_POST['status'];

    $update = mysqli_query($koneksi, "UPDATE t_premium SET 
        tipe_premium='$tipe_premium',
        tanggal_aktif='$tanggal_aktif',
        tanggal_berakhir='$tanggal_berakhir',
        status='$status'
        WHERE id_premium='$id'
    ");

    if ($update) {
        echo "<script>
                alert('Data premium berhasil diperbarui!');
                window.location.href='datapremium.php';
              </script>";
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Edit Data Premium</title>
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
              <li class="nav-item active">
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
              <h1 class="fw-bold">Edit Data Premium</h1>
            </div>

            <div class="col-md-8">
              <div class="card card-round shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="card-title">Form Edit Premium</h5>
                </div>

                <div class="card-body">
                  <form method="POST" action="">
                    <div class="mb-3">
                      <label class="form-label fw-bold">Nama Pengguna</label>
                      <input type="text" class="form-control" 
                             value="<?= htmlspecialchars($data['nama_pengguna']); ?>" disabled>
                    </div>

                    <div class="mb-3">
                      <label class="form-label fw-bold">Tipe Premium</label>
                      <select name="tipe_premium" class="form-select" required>
                        <option value="">-- Pilih Tipe Premium --</option>
                        <option value="buyer" <?= $data['tipe_premium'] == 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                        <option value="seller" <?= $data['tipe_premium'] == 'seller' ? 'selected' : ''; ?>>Seller</option>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label class="form-label fw-bold">Tanggal Aktif</label>
                      <input type="date" name="tanggal_aktif" class="form-control" 
                             value="<?= htmlspecialchars($data['tanggal_aktif']); ?>" required>
                    </div>

                    <div class="mb-3">
                      <label class="form-label fw-bold">Tanggal Berakhir</label>
                      <input type="date" name="tanggal_berakhir" class="form-control" 
                             value="<?= htmlspecialchars($data['tanggal_berakhir']); ?>" required>
                    </div>

                    <div class="mb-3">
                      <label class="form-label fw-bold">Status</label>
                      <select name="status" class="form-select" required>
                        <option value="">-- Pilih Status --</option>
                        <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                      </select>
                    </div>

                    <div class="d-flex gap-2">
                      <button type="submit" name="simpan" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan
                      </button>
                      <a href="datapremium.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                      </a>
                    </div>
                  </form>
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