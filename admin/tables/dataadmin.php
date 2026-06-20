<?php
require_once __DIR__ . '/../../sweetalert.php';
include "../koneksi.php";
session_start();

if(empty($_SESSION['admin'])){
  header('location:../logout.php');
  exit; 
} else { // Mulai blok utama untuk seluruh konten

// === KODE WAJIB: AMBIL DATA ADMIN UNTUK PROFIL DINAMIS ===
$id_admin = $_SESSION['admin']; 
$query_admin = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE id_admin = '$id_admin'"); 
$data_admin = mysqli_fetch_assoc($query_admin);

if (!$data_admin) {
    header('location:../logout.php');
    exit;
}
// =========================================================

$errors = [];
$success = '';

// handle add admin form (uses mysqli_query as requested)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $nama_admin = trim($_POST['nama_admin'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($nama_admin === '') $errors[] = 'Nama Admin wajib diisi.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if ($password === '' || strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    if (empty($errors)) {
        // Cek email duplikat (prepared statement)
        $check_stmt = mysqli_prepare($koneksi, "SELECT id_admin FROM t_admin WHERE email = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = 'Email sudah terdaftar. Gunakan email lain.';
            mysqli_stmt_close($check_stmt);
        } else {
            mysqli_stmt_close($check_stmt);
            $pw_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($koneksi, "INSERT INTO t_admin (nama_admin, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $nama_admin, $email, $pw_hash);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                sweetalert_redirect('Admin baru berhasil ditambahkan.', 'dataadmin.php', 'success', 'Berhasil!');
            } else {
                $errors[] = "Gagal menambahkan admin: " . mysqli_error($koneksi);
                mysqli_stmt_close($stmt);
            }
        }
    }

    if (!empty($errors)) {
        sweetalert_back(implode("\n", $errors), 'error', 'Data Belum Valid');
    }
}

// Fetch all admins for the table display
$query_admins = mysqli_query($koneksi, "SELECT id_admin, nama_admin, email FROM t_admin ORDER BY id_admin ASC");
$admins = mysqli_fetch_all($query_admins, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Data Admin - Kaiadmin</title>
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
              <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
              <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
            </div>
            <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
          </div>
          </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item">
                <a href="../beranda.php" class="collapsed" aria-expanded="false">
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
              <li class="nav-item active">
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
          <div class="main-header-content">
            <div class="page-top-header">
              <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                  <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <button type="submit" class="btn btn-search pe-1"><i class="fa fa-search search-icon"></i></button>
                      </div>
                      <input type="text" placeholder="Search ..." class="form-control" />
                    </div>
                  </nav>

                  <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                    
                    <li class="nav-item dropdown hidden-caret">
                      <a
                        class="nav-link"
                        data-bs-toggle="dropdown"
                        href="#"
                        aria-expanded="false"
                      >
                        <i class="fa fa-bell"></i>
                        <span class="notification" id="notif-count-badge">0</span> 
                      </a>
                      <ul class="dropdown-menu notif-box animated fadeIn" id="notification-content-container">
                        <div class="p-3 text-center text-muted">Memuat notifikasi...</div>
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
            </div>
          </div>
          </div>

        <div class="container">
          <div class="page-inner">
            <div class="page-header">
              <h3 class="fw-bold mb-3">Data Admin</h3>
            </div>

            <div class="container mt-3">
              <div class="card card-round shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <div class="card-title">Tambah Admin</div>
                </div>

                <div class="card-body">
                  <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
                  <?php endif; ?>
                  
                  <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
                  <?php endif; ?>

                  <form method="post" class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label fw-bold">Nama Admin</label>
                      <input type="text" name="nama_admin" class="form-control" required value="<?= isset($_POST['nama_admin'])?htmlspecialchars($_POST['nama_admin']):''; ?>">
                    </div>

                    <div class="col-md-6">
                      <label class="form-label fw-bold">Email</label>
                      <input type="email" name="email" class="form-control" required value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>">
                    </div>

                    <div class="col-md-6">
                      <label class="form-label fw-bold">Password</label>
                      <input type="password" name="password" class="form-control" required>
                      <div class="form-text">Minimal 6 karakter. Password sekarang disimpan dalam teks biasa.</div>
                    </div>

                    <div class="col-12 text-end">
                      <button type="submit" name="add_admin" class="btn btn-primary">Tambah Admin</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div class="container mt-4 mb-5">
              <div class="card card-round shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <div class="card-title">Daftar Admin</div>
                </div>

                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Nama Admin</th>
                          <th>Email</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($admins)): ?>
                          <tr><td colspan="3" class="text-center">Belum ada data admin.</td></tr>
                        <?php else: ?>
                          <?php foreach ($admins as $a): ?>
                            <tr>
                              <td><?= htmlspecialchars($a['id_admin']) ?></td>
                              <td><?= htmlspecialchars($a['nama_admin']) ?></td>
                              <td><?= htmlspecialchars($a['email']) ?></td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
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
    
    <script>
        function updateNotificationDropdown() {
            $.ajax({
                url: '../check_notifs.php', // Path file PHP
                type: 'GET',
                dataType: 'html', 
                success: function(html_content) {
                    $('#notification-content-container').html(html_content);
                    
                    var countText = $('#notification-content-container').find('.dropdown-title').text();
                    var match = countText.match(/(\d+)/); // Cari angka
                    var count = match ? match[1] : 0; 
                    
                    $('#notif-count-badge').text(count);
                },
                error: function() {
                    $('#notification-content-container').html('<div class="p-3 text-center text-danger">Gagal memuat notifikasi.</div>');
                }
            });
        }

        $(document).ready(function() {
            updateNotificationDropdown(); 
            setInterval(updateNotificationDropdown, 30000); // Update setiap 30 detik
        });
    </script>
  </body>
</html>
<?php } // Kurung kurawal penutup untuk blok 'else' utama ?>
