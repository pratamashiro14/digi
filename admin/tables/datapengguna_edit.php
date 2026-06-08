<?php
include '../koneksi.php';

session_start();
if(empty($_SESSION['admin'])){
  header('location:../logout.php');
  exit;
} else { // <--- AWAL BLOK PHP KONTEN

    // === KODE WAJIB: AMBIL DATA ADMIN UNTUK PROFIL DINAMIS ===
    $id_admin = $_SESSION['admin'];
    $query_admin = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE id_admin='$id_admin'");
    $data_admin = mysqli_fetch_assoc($query_admin);
    
    // Periksa jika data admin tidak ditemukan
    if (!$data_admin) {
        header('location:../logout.php');
        exit;
    }
    // ========================================================

    $id = $_GET['id'];
    $query = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user='$id'");
    $data = mysqli_fetch_assoc($query);

    if (isset($_POST['simpan'])) {
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $no_telp = isset($_POST['no_telp']) ? $_POST['no_telp'] : '';
        $alamat = $_POST['alamat'];
        $role = strtolower($_POST['role']); // Convert to lowercase
        $premium = $_POST['premium'];

        $update = mysqli_query($koneksi, "UPDATE t_user SET 
            nama='$nama',
            email='$email',
            password='$password',
            no_telp='$no_telp',
            alamat='$alamat',
            role='$role',
            premium='$premium'
            WHERE id_user='$id'
        ");

        if ($update) {
            echo "<script>
                    alert('Data pengguna berhasil diperbarui!');
                    window.location.href='datapengguna.php';
                  </script>";
        } else {
            echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Edit Data Pengguna</title>
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
                        src="../assets/img/profile.jpg"
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
                              src="../assets/img/fotoprofil/<?= $data_admin['foto'] ?: 'profile.jpg'; ?>"
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
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="datapengguna_edit.php">Edit Data Pengguna</a>
                </li>
              </ul>
            </div>

       <div class="container mt-4">
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <h4 class="fw-bold mb-4 text-primary">Data Pengguna</h4>

      <form method="POST">
        <div class="row">
          <div class="col-md-8">
            <div class="mb-3">
              <label class="form-label fw-bold">Nama Pengguna</label>
              <input type="text" name="nama" class="form-control" 
                     value="<?= htmlspecialchars($data['nama']); ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Email</label>
              <input type="email" name="email" class="form-control" 
                     value="<?= htmlspecialchars($data['email']); ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Kata Sandi</label>
              <input type="password" name="password" class="form-control" 
                     value="<?= htmlspecialchars($data['password']); ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">No. Telepon</label>
              <input type="text" name="no_telp" class="form-control" 
                     value="<?= htmlspecialchars($data['no_telp']); ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Alamat</label>
              <input type="text" name="alamat" class="form-control" 
                     value="<?= htmlspecialchars($data['alamat']); ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Peran</label>
              <select name="role" class="form-select" required>
                <option value="designer" <?= $data['role']=='designer'?'selected':''; ?>>Designer</option>
                <option value="pelanggan" <?= $data['role']=='pelanggan'?'selected':''; ?>>Pelanggan</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Premium</label>
              <select name="premium" class="form-select" required>
                <option value="1" <?= $data['premium']==1?'selected':''; ?>>Ya</option>
                <option value="0" <?= $data['premium']==0?'selected':''; ?>>Tidak</option>
              </select>
            </div>

            <div class="text-end">
              <button type="submit" name="simpan" class="btn btn-primary">
                Simpan Perubahan
              </button>
            </div>
          </div>

          <div class="col-md-4 text-center">
            <img src="../uploads/<?= $data['foto'] ?: 'default.png'; ?>" 
                 alt="Profile" class="rounded-circle mb-3" 
                 style="width: 150px; height: 150px; object-fit: cover;">

            <p class="text-muted">Terakhir Online: <?= date('H:i, d F Y'); ?></p>

            <a href="#" class="btn btn-outline-secondary btn-sm">
              <i class="fas fa-edit"></i> Edit Profile
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

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

<?php 
} // <--- WAJIB: TAMBAH KURUNG KURAWAL TUTUP DI SINI
?>