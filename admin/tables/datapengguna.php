<?php include "../koneksi.php";

session_start();

// Periksa apakah sesi admin kosong, jika ya, arahkan ke logout
if(empty($_SESSION['admin'])){
    header('location:../logout.php');
    exit;
}

// === LOGIKA VERIFIKASI KTP DESAINER ===
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

      <table class="table table-hover">
        <thead class="table-light">
          <tr>
            <th scope="col" style="width: 5%">No</th>
            <th scope="col">Nama Lengkap</th>
            <th scope="col">Email</th>
            <th scope="col">Peran</th>
            <th scope="col">Premium</th>
            <th scope="col">Status</th>
            <th scope="col">Verif KTP (Desainer)</th>
            <th scope="col" style="width: 10%">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          $query = mysqli_query($koneksi, "SELECT * FROM t_user ORDER BY id_user DESC");
          
          if (mysqli_num_rows($query) == 0) {
              echo "<tr><td colspan='7' class='text-center text-muted'>Belum ada data pengguna</td></tr>";
          } else {
              while ($row = mysqli_fetch_assoc($query)) {
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
                <?= ucfirst($row['status']) ?>
              </span>
            </td>
            <td>
              <?php if ($row['role'] == 'designer') : ?>
                  <?php if ($row['status_verifikasi'] == 'pending') : ?>
                      <span class="badge bg-warning text-dark">Pending</span>
                      <br><small><strong>NIK:</strong> <?= htmlspecialchars($row['nik'] ?? '-') ?></small>
                      <?php if (!empty($row['foto_ktp'])): ?>
                          <br><small><a href="../uploads/<?= htmlspecialchars($row['foto_ktp']) ?>" target="_blank">Lihat KTP</a></small>
                      <?php endif; ?>
                      <br>
                      <a href="?approve_ktp=<?= $row['id_user'] ?>" class="btn btn-sm btn-success mt-1 py-0 px-1" style="font-size:11px;">Terima</a>
                      <a href="?reject_ktp=<?= $row['id_user'] ?>" class="btn btn-sm btn-danger mt-1 py-0 px-1" style="font-size:11px;">Tolak</a>
                  <?php elseif ($row['status_verifikasi'] == 'verified') : ?>
                      <span class="badge bg-success">Verified</span>
                      <br><small><strong>NIK:</strong> <?= htmlspecialchars($row['nik'] ?? '-') ?></small>
                      <?php if (!empty($row['foto_ktp'])): ?>
                          <br><small><a href="../uploads/<?= htmlspecialchars($row['foto_ktp']) ?>" target="_blank">Lihat KTP</a></small>
                      <?php endif; ?>
                  <?php else: ?>
                      <span class="badge bg-secondary">Unverified</span>
                      <?php if (!empty($row['nik'])): ?>
                          <br><small><strong>NIK:</strong> <?= htmlspecialchars($row['nik']) ?></small>
                      <?php endif; ?>
                  <?php endif; ?>
              <?php else: ?>
                  <span class="text-muted">-</span>
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
  </body>
</html>
