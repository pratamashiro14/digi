<?php
include '../koneksi.php';

session_start();
if(empty($_SESSION['admin'])){
  header('location:../logout.php');
  exit;
} else { // <--- AWAL BLOK PHP KONTEN

$id_admin = $_SESSION['admin'];
$query_admin = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE id_admin = '$id_admin'"); 
$data_admin = mysqli_fetch_assoc($query_admin);

if (!$data_admin) {
    header('location:../logout.php');
    exit;
}
// ========================================================

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = mysqli_query($koneksi, "
    SELECT b.*, d.judul, d.gambar, u.nama AS nama_penawaran, b.status_bid AS status_bid_db, b.id_bid
    FROM t_bidding b
    LEFT JOIN t_design d ON b.id_design = d.id_design
    LEFT JOIN t_user u ON b.id_buyer = u.id_user
    WHERE b.id_bid= '$id'
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Data bidding tidak ditemukan!'); window.location.href='databidding.php';</script>";
    exit;
}

if (isset($_POST['simpan'])) {
    $status_bid = isset($_POST['status_bid']) ? $_POST['status_bid'] : '';

    // update only the status field
    $stmt = $koneksi->prepare("UPDATE t_bidding SET status_bid = ? WHERE id_bid = ?");
    if ($stmt) {
        // PERBAIKAN TYPO: Mengikat $status_bid
        $stmt->bind_param("si", $status_bid, $id); 
        if ($stmt->execute()) {
            echo "<script>
                    alert('Data bidding berhasil diperbarui!');
                    window.location.href='databidding.php';
                  </script>";
            exit;
        } else {
            echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Prepare failed: " . mysqli_error($koneksi) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Edit Data Bidding</title>
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
    <link rel="stylesheet" href="../assets/css/digidesain-admin.css" />

    <link rel="stylesheet" href="../assets/css/demo.css" />
  </head>
  <body>
    <div class="wrapper">
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <div class="logo-header" data-background-color="dark">
             <a href="../beranda.php" class="logo">
              <img
                src="../assets/img/digidesain.png"
                alt="navbar brand"
                class="navbar-brand"
                style="height: 150px !important; width: auto !important;"
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
      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <div class="logo-header" data-background-color="dark">
              <a href="../beranda.php" class="logo">
                <img
                  src="../assets/img/digidesain.png"
                  alt="navbar brand"
                  class="navbar-brand"
                  style="height: 40px !important; width: auto !important;"
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
                              href="profile.php"
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
          </div>

        <div class="container">
          <div class="page-inner">
            <div class="page-header mb-4">
              <h1 class="fw-bold">Edit Data Bidding</h1>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="card card-round shadow-sm">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Form Edit Bidding</h5>
                  </div>

                  <div class="card-body">
                    <form method="POST" action="">
                      <div class="row">
                        <div class="col-md-4">
                          <div class="mb-3">
                            <label class="form-label fw-bold">Gambar Karya</label>
                            <div class="card">
                              <img src="../assets/img/karya/<?= htmlspecialchars($data['gambar']); ?>" 
                                   class="card-img-top" alt="Gambar Karya" style="height: 200px; object-fit: cover;">
                            </div>
                          </div>
                        </div>

                        <div class="col-md-8">
                          <div class="mb-3">
                            <label class="form-label fw-bold">Judul Karya</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($data['judul']); ?>" disabled>
                          </div>

                          <div class="mb-3">
                            <label class="form-label fw-bold">Nama Penawar</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($data['nama_penawaran']); ?>" disabled>
                          </div>

                          <div class="row">
                            <div class="col-md-6 mb-3">
                              <label class="form-label fw-bold"> Status</label>
                              <select name="status_bid" class="form-select" required>
                                <option value="">-- Pilih status --</option>
                                <option value="aktif" <?= $data['status_bid_db'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="diterima" <?= $data['status_bid_db'] == 'diterima' ? 'selected' : ''; ?>>Diterima</option>
                                <option value="ditolak" <?= $data['status_bid_db'] == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                <option value="selesai" <?= $data['status_bid_db'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                              </select>
                            </div>

                            <div class="col-md-6 mb-3">
                              <label class="form-label fw-bold">Nominal Penawaran</label>
                              <input type="text" class="form-control" 
                                     value="Rp <?= number_format($data['harga_tawaran'], 0, ',', '.'); ?>" disabled>
                            </div>
                          </div>

                          <div class="d-flex gap-2 mt-4">
                            <button type="submit" name="simpan" class="btn btn-primary">
                              <i class="fas fa-save me-2"></i>Simpan
                            </button>
                            <a href="databidding.php" class="btn btn-secondary">
                              <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                          </div>
                        </div>
                      </div>
                    </form>
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
    <script src="../assets/js/setting-demo2.js"></script>
    
    <script>
    function updateNotificationDropdown() {
        $.ajax({
            url: '../check_notifs.php', // Path file PHP
            type: 'GET',
            dataType: 'html', 
            success: function(html_content) {
                $('#notification-content-container').html(html_content);
                
                var countText = $('#notification-content-container').find('.dropdown-title').text();
                var match = countText.match(/(\d+)/); 
                var count = match ? match[0] : 0;
                
                $('#notif-count-badge').text(count);
            },
            error: function() {
                $('#notification-content-container').html('<div class="p-3 text-center text-danger">Gagal memuat notifikasi.</div>');
            }
        });
    }

    $(document).ready(function() {
        updateNotificationDropdown(); 
        setInterval(updateNotificationDropdown, 30000); 
    });
    </script>
  </body>
</html>

<?php 
} // <--- KURUNG KURAWAL TUTUP BLOK ELSE
?>
