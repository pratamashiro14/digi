<?php 
require_once __DIR__ . '/../sweetalert.php';
include "koneksi.php";
session_start();

// Periksa apakah sesi admin kosong, jika ya, arahkan ke logout
if(empty($_SESSION['admin'])){
    header('location:logout.php');
    exit;
}

// ==========================================================
// KODE PROFIL ADMIN YANG SEDANG LOGIN
// ==========================================================
$id_admin = $_SESSION['admin'];
$query_admin = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE id_admin = '$id_admin'"); 
$data_admin = mysqli_fetch_assoc($query_admin);

if (!$data_admin) {
    header('location:logout.php');
    exit;
}

// Ambil data-data admin
$nama_admin = $data_admin['nama_admin'];
$email_admin = $data_admin['email'];
// Asumsi ada kolom 'foto' di t_admin. Jika null, gunakan 'profile.jpg'
$foto_admin = $data_admin['foto'] ?? 'profile.jpg'; 
$message = '';
$error = '';

// Tentukan mode tampilan: 'view' (default) atau 'edit'
$mode = $_GET['mode'] ?? 'view';

// === HANDLE PROSES UPDATE DAN UPLOAD FOTO (HANYA JIKA MODE ADALAH EDIT) ===
if ($mode === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    
    $new_nama = trim($_POST['nama_admin']);
    $new_email = trim($_POST['email']);
    $target_dir = "assets/img/fotoprofil/";
    $new_foto_name = $foto_admin;
    $update_fields = [];
    $update_types = '';
    $update_vals = [];

    // Cek perubahan Nama & Email
    if ($new_nama !== $nama_admin) {
        $update_fields[] = "nama_admin = ?";
        $update_types .= 's';
        $update_vals[] = $new_nama;
    }

    if ($new_email !== $email_admin) {
        $check_email = mysqli_prepare($koneksi, "SELECT id_admin FROM t_admin WHERE email = ? AND id_admin != ?");
        mysqli_stmt_bind_param($check_email, "si", $new_email, $id_admin);
        mysqli_stmt_execute($check_email);
        $check_email_result = mysqli_stmt_get_result($check_email);
        if (mysqli_num_rows($check_email_result) > 0) {
            $error = 'Email baru sudah digunakan oleh admin lain!';
        } else {
            $update_fields[] = "email = ?";
            $update_types .= 's';
            $update_vals[] = $new_email;
        }
        mysqli_stmt_close($check_email);
    }

    // Cek upload foto
    if (isset($_FILES['foto_baru']) && $_FILES['foto_baru']['error'] === UPLOAD_ERR_OK && empty($error)) {
        $file_tmp = $_FILES['foto_baru']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['foto_baru']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (!in_array($file_ext, $allowed_ext)) {
            $error = "Hanya file JPG, JPEG, dan PNG yang diperbolehkan.";
        } else {
            // Hapus foto lama (kecuali foto default)
            if ($foto_admin !== 'profile.jpg' && file_exists($target_dir . $foto_admin)) {
                @unlink($target_dir . $foto_admin);
            }
            
            $new_foto_name = "admin_" . $id_admin . "_" . time() . "." . $file_ext;
            $upload_path = $target_dir . $new_foto_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $update_fields[] = "foto = ?";
                $update_types .= 's';
                $update_vals[] = $new_foto_name;
            } else {
                $error = "Gagal mengunggah file foto.";
            }
        }
    }

    // Eksekusi Update
    if (empty($error) && !empty($update_fields)) {
        $update_query = "UPDATE t_admin SET " . implode(', ', $update_fields) . " WHERE id_admin = ?";
        $update_types .= 'i';
        $update_vals[] = $id_admin;

        $stmt = mysqli_prepare($koneksi, $update_query);
        mysqli_stmt_bind_param($stmt, $update_types, ...$update_vals);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            sweetalert_redirect('Profil admin berhasil diperbarui.', 'profile.php', 'success', 'Berhasil!');
        } else {
            $error = "Gagal menyimpan perubahan: " . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
    } elseif (empty($error)) {
         sweetalert_redirect('Tidak ada perubahan yang disimpan.', 'profile.php', 'info', 'Informasi');
    }

    if (!empty($error)) {
        sweetalert_back($error, 'error', 'Gagal!');
    }
    
    // Ambil ulang data (jika ada error)
    if (!empty($error) || !empty($message)) {
        $query_admin = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE id_admin = '$id_admin'"); 
        $data_admin = mysqli_fetch_assoc($query_admin);
        $nama_admin = $data_admin['nama_admin'];
        $email_admin = $data_admin['email'];
        $foto_admin = $data_admin['foto'] ?? 'profile.jpg';
    }
}

// Cek status dari redirect (jika ada)
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $message = "Profil berhasil diperbarui!";
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Profile | <?= htmlspecialchars($nama_admin); ?></title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/kaiadmin/favicon.ico"
      type="image/x-icon"
    />

    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
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
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/digidesain-admin.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <style>
        .profile-pic-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ccc;
            margin-bottom: 15px;
        }
    </style>
  </head>
  <body>
    <div class="wrapper">
      
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <div class="logo-header" data-background-color="dark">
            <a href="beranda.php" class="logo">
              <img
                src="assets/img/dens.png"
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
                <a href="beranda.php"
                  data-bs-toggle="collapse"
                  href="#dashboard"
                  class="collapsed"
                  aria-expanded="false"
                >
                  <i class="fas fa-home"></i>
                  <p>Beranda</p>
                </a>
              <li class="nav-item">
                <a href="tables/karyadesain.php">
                  <i class="fas fa-palette"></i>
                  <p>Karya Desain</p>
                </a>
              </li>
               <li class="nav-item">
                <a href="tables/datapengguna.php">
                  <i class="fas fa-user"></i>
                  <p>Data Pengguna</p>
                </a>
              </li>
               <li class="nav-item">
                <a href="tables/transaksi.php">
                  <i class="fas fa-money-bill-wave"></i>
                  <p>Transaksi</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="tables/datapencairan.php">
                  <i class="fas fa-wallet"></i>
                  <p>Data Pencairan</p>
                </a>
              </li>

               <li class="nav-item">
                <a href="tables/datapremium.php">
                  <i class="fas fa-crown"></i>
                  <p>Data Premium</p>
                </a>
              </li>
            <li class="nav-item">
                <a href="tables/databidding.php">
                  <i class="fas fa-hand-holding-usd"></i>
                  <p>Data Bidding</p>
                </a>
              </li>
             <li class="nav-item">
                <a href="tables/datachat.php">
                  <i class="fas fa-comments"></i>
                  <p>Moderisasi Chat</p>
                </a>
              </li>
            <li class="nav-item">
              <a href="tables/dataadmin.php">
                <i class="fas fa-user-plus"></i>
                <p>Tambah Admin</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="logout.php">
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
              <a href="index.php" class="logo">
                <img
                  src="assets/img/kaiadmin/logo_light.svg"
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
                        src="assets/img/fotoprofil/<?= htmlspecialchars($foto_admin); ?>"
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
                              src="assets/img/fotoprofil/<?= htmlspecialchars($foto_admin); ?>"
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
            <div
              class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4"
            >
              <div>
                <h3 class="fw-bold mb-3"><?= $mode === 'edit' ? 'Edit Profil' : 'Profil Admin'; ?></h3>
                <ul class="breadcrumbs mb-3">
                    <li class="nav-home"><a href="beranda.php"><i class="icon-home"></i></a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="profile.php">Profil</a></li>
                    <?php if ($mode === 'edit'): ?>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item">Edit</li>
                    <?php endif; ?>
                </ul>
              </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="row">
                <?php if ($mode === 'view'): ?>
                    <div class="col-md-4">
                        <div class="card card-profile">
                            <div class="card-header" style="background-image: url('assets/img/blogpost.jpg')">
                                <div class="profile-picture">
                                    <div class="avatar avatar-xl">
                                        <img src="assets/img/fotoprofil/<?= htmlspecialchars($foto_admin); ?>" alt="..." class="avatar-img rounded-circle">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="user-profile text-center">
                                    <div class="name"><?= htmlspecialchars($nama_admin); ?></div>
                                    <div class="job">Admin Utama</div>
                                    <div class="desc"><?= htmlspecialchars($email_admin); ?></div>
                                    <div class="view-profile">
                                        <a href="profile.php?mode=edit" class="btn btn-secondary btn-round">Edit Profil</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card card-round">
                            <div class="card-header">
                                <div class="card-title">Detail Informasi</div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tbody>
                                            <tr>
                                                <th>ID Admin</th>
                                                <td><?= htmlspecialchars($id_admin); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Nama Lengkap</th>
                                                <td><?= htmlspecialchars($nama_admin); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Email</th>
                                                <td><?= htmlspecialchars($email_admin); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td><span class="badge bg-success">Aktif</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($mode === 'edit'): ?>
                    <div class="col-md-10 mx-auto">
                        <div class="card card-round">
                            <div class="card-header"><div class="card-title">Perbarui Informasi Profil</div></div>
                            <div class="card-body">
                                
                                <form method="post" enctype="multipart/form-data" action="profile.php?mode=edit">
                                    
                                    <div class="mb-3 text-center">
                                        <label class="form-label d-block fw-bold">Foto Profil Saat Ini</label>
                                        <img src="assets/img/fotoprofil/<?= htmlspecialchars($foto_admin); ?>" 
                                             alt="Foto Profil" 
                                             class="profile-pic-preview">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="nama_admin" class="form-label fw-bold">Nama Lengkap</label>
                                        <input type="text" name="nama_admin" id="nama_admin" class="form-control" 
                                               value="<?= htmlspecialchars($nama_admin); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-bold">Email</label>
                                        <input type="email" name="email" id="email" class="form-control" 
                                               value="<?= htmlspecialchars($email_admin); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="foto_baru" class="form-label fw-bold">Ganti Foto Profil</label>
                                        <input type="file" name="foto_baru" id="foto_baru" class="form-control" accept="image/jpeg, image/png">
                                        <div class="form-text">Maksimum ukuran file: 2MB. Format: JPG atau PNG.</div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <a href="profile.php" class="btn btn-default me-2">Batal</a>
                                        <button type="submit" name="update_profile" class="btn btn-primary">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
          </div>
        </div>
        </div>
      </div>
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
    
    <script>
    function updateNotificationDropdown() {
        $.ajax({
            url: 'check_notifs.php',
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
        setInterval(updateNotificationDropdown, 30000); // Update setiap 30 detik
    });
    </script>
    
    </body>
</html>
