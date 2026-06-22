<?php
require_once __DIR__ . '/../../sweetalert.php';
include "../koneksi.php";

session_start();
if(empty($_SESSION['admin'])){
  header('location:../logout.php');
  exit;
} else { // <--- AWAL BLOK PHP KONTEN

    // === KODE WAJIB: AMBIL DATA ADMIN UNTUK PROFIL DINAMIS ===
    $id_admin = $_SESSION['admin'];
    $query_admin = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE id_admin = '$id_admin'"); 
    $data_admin = mysqli_fetch_assoc($query_admin);

    if (!$data_admin) {
        header('location:../logout.php');
        exit;
    }
    // ========================================================

    // Lanjutkan dengan kode spesifik file ini
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    $query = mysqli_query($koneksi, "
        SELECT d.*, u.nama AS nama_desainer
        FROM t_design d
        LEFT JOIN t_user u ON d.id_designer = u.id_user
        WHERE d.id_design = '$id'
    ");

    $data = mysqli_fetch_assoc($query);

    // Proses form update status dan simpan perubahan
    if (isset($_POST['status']) || isset($_POST['simpan'])) {
        $status_update = isset($_POST['status']) ? $_POST['status'] : $data['status'];
        $judul = isset($_POST['judul']) ? $_POST['judul'] : $data['judul'];
        $kategori = isset($_POST['kategori']) ? $_POST['kategori'] : $data['kategori'];
        $harga_awal = isset($_POST['harga_awal']) ? $_POST['harga_awal'] : $data['harga_awal'];

        // Query update
        $stmt = mysqli_prepare($koneksi, "
            UPDATE t_design
            SET judul = ?, kategori = ?, harga_awal = ?, status = ?
            WHERE id_design = ?
        ");
        mysqli_stmt_bind_param($stmt, "ssdsi", $judul, $kategori, $harga_awal, $status_update, $id);
        $update = mysqli_stmt_execute($stmt);

        if ($update) {
            sweetalert_redirect('Perubahan karya berhasil disimpan.', 'karyadesain.php', 'success', 'Berhasil!');
        } else {
            sweetalert_back('Gagal menyimpan perubahan: ' . mysqli_error($koneksi), 'error', 'Gagal!');
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Edit Karya Desain</title>
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
              </li>
              <li class="nav-item active">
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
                  src="../assets/img/dens.png"
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
                              src="../assets/img/profile.jpg<?= $data_admin['foto'] ?: 'profile.jpg'; ?>"
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

        <div class="container">
          <div class="page-inner">
            <div class="page-header">
              <h3 class="fw-bold mb-3">Karya Desain</h3>
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
                  <a href="karyadesain.php">Karya Desain</a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="karyadesain_edit.php">Edit Karya Desain</a>
                </li>
              </ul>
            </div>

    <div class="container mt-4">
  <div class="card card-round shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div class="card-title">Edit Karya Desain</div>
    </div>

    <div class="card-body">
      <form method="POST">
        <div class="row">
          <div class="col-md-4 text-center">
            <img src="../uploads/<?= $data['gambar'] ?: 'default.png'; ?>" 
                 alt="Karya" class="img-fluid rounded mb-3" 
                 style="max-height: 250px; object-fit: cover;">
            
            <div class="d-flex justify-content-center gap-2">
              <button type="submit" name="status" value="approved" class="btn btn-success">Disetujui</button>
              <button type="submit" name="status" value="rejected" class="btn btn-danger">Tolak</button>
            </div>
          </div>

          <div class="col-md-8">
            <div class="mb-3">
              <label class="form-label fw-bold">Judul Desain</label>
              <input type="text" name="judul" class="form-control" 
                     value="<?= htmlspecialchars($data['judul']); ?>" required>
            </div>

         <div class="mb-3">
            <label class="form-label fw-bold">Nama Desainer</label>
            <input type="text" class="form-control" 
             value="<?= htmlspecialchars($data['nama_desainer']); ?>" readonly>
        </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Jenis / Kategori</label>
              <select name="kategori" class="form-select" required>
                <option value="ilustrasi" <?= $data['kategori']=='ilustrasi' ? 'selected' : ''; ?>>Ilustrasi</option>
                <option value="tipografi" <?= $data['kategori']=='tipografi' ? 'selected' : ''; ?>>Tipografi</option>
                <option value="mockup" <?= $data['kategori']=='mockup' ? 'selected' : ''; ?>>Mockup</option>
                <option value="uiux" <?= in_array(strtolower($data['kategori']), ['uiux', 'ui-ux', 'ui/ux', 'ui & ux'], true) ? 'selected' : ''; ?>>UI/UX</option>
                <option value="animasi" <?= $data['kategori']=='animasi' ? 'selected' : ''; ?>>Animasi</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Harga Awal</label>
              <input type="number" name="harga_awal" class="form-control" 
                     value="<?= htmlspecialchars($data['harga_awal']); ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Status</label>
              <input type="text" class="form-control" 
                     value="<?= ucfirst($data['status']); ?>" readonly>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Tanggal Upload</label>
              <input type="text" class="form-control" 
                     value="<?= $data['tanggal_upload']; ?>" readonly>
            </div>

            <div class="text-end">
              <button type="submit" name="simpan" class="btn btn-primary">
                Simpan Perubahan
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="custom-template">
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
            url: '../check_notifs.php', // Perhatikan pathnya
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
