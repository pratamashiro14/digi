<?php
include "../koneksi.php";

session_start();
if (empty($_SESSION['admin'])) {
    header('location:../logout.php');
    exit;
}

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

    <!-- Logo Branding Style -->
    <style>
      .logo-header .navbar-brand {
        height: 40px !important;
        width: auto !important;
      }
    </style>
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
             <li class="nav-item active">
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
                <li class="nav-item topbar-icon dropdown hidden-caret">
                  <a
                    class="nav-link dropdown-toggle"
                    href="#"
                    id="messageDropdown"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                  </a>
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

 <?php
$query = mysqli_query($koneksi, "
    SELECT
        latest.*,
        pelanggan.id_user AS id_pelanggan,
        pelanggan.nama AS nama_pelanggan,
        pelanggan.email AS email_pelanggan,
        admin_user.nama AS nama_admin_chat,
        admin_user.id_user AS id_admin_chat,
        COALESCE(unread.jumlah_unread, 0) AS jumlah_unread,
        COALESCE(total.jumlah_pesan, 0) AS jumlah_pesan
    FROM (
        SELECT c.*
        FROM t_chat c
        INNER JOIN t_user pengirim ON pengirim.id_user = c.id_pengirim
        INNER JOIN t_user penerima ON penerima.id_user = c.id_penerima
        INNER JOIN (
            SELECT
                CASE
                    WHEN pengirim2.role = 'admin' THEN c2.id_penerima
                    ELSE c2.id_pengirim
                END AS id_pelanggan,
                MAX(c2.waktu_kirim) AS waktu_terakhir
            FROM t_chat c2
            INNER JOIN t_user pengirim2 ON pengirim2.id_user = c2.id_pengirim
            INNER JOIN t_user penerima2 ON penerima2.id_user = c2.id_penerima
            WHERE (pengirim2.role = 'admin' AND penerima2.role = 'pelanggan')
               OR (pengirim2.role = 'pelanggan' AND penerima2.role = 'admin')
            GROUP BY id_pelanggan
        ) last_chat
          ON last_chat.waktu_terakhir = c.waktu_kirim
         AND last_chat.id_pelanggan = CASE
             WHEN pengirim.role = 'admin' THEN c.id_penerima
             ELSE c.id_pengirim
         END
        WHERE (pengirim.role = 'admin' AND penerima.role = 'pelanggan')
           OR (pengirim.role = 'pelanggan' AND penerima.role = 'admin')
    ) latest
    INNER JOIN t_user pengirim_latest ON pengirim_latest.id_user = latest.id_pengirim
    INNER JOIN t_user penerima_latest ON penerima_latest.id_user = latest.id_penerima
    INNER JOIN t_user pelanggan
      ON pelanggan.id_user = CASE
          WHEN pengirim_latest.role = 'admin' THEN latest.id_penerima
          ELSE latest.id_pengirim
      END
    INNER JOIN t_user admin_user
      ON admin_user.id_user = CASE
          WHEN pengirim_latest.role = 'admin' THEN latest.id_pengirim
          ELSE latest.id_penerima
      END
    LEFT JOIN (
        SELECT c3.id_pengirim AS id_pelanggan, COUNT(*) AS jumlah_unread
        FROM t_chat c3
        INNER JOIN t_user pengirim3 ON pengirim3.id_user = c3.id_pengirim
        INNER JOIN t_user penerima3 ON penerima3.id_user = c3.id_penerima
        WHERE pengirim3.role = 'pelanggan'
          AND penerima3.role = 'admin'
          AND c3.is_read = 0
        GROUP BY c3.id_pengirim
    ) unread ON unread.id_pelanggan = pelanggan.id_user
    LEFT JOIN (
        SELECT
            CASE
                WHEN pengirim4.role = 'admin' THEN c4.id_penerima
                ELSE c4.id_pengirim
            END AS id_pelanggan,
            COUNT(*) AS jumlah_pesan
        FROM t_chat c4
        INNER JOIN t_user pengirim4 ON pengirim4.id_user = c4.id_pengirim
        INNER JOIN t_user penerima4 ON penerima4.id_user = c4.id_penerima
        WHERE (pengirim4.role = 'admin' AND penerima4.role = 'pelanggan')
           OR (pengirim4.role = 'pelanggan' AND penerima4.role = 'admin')
        GROUP BY id_pelanggan
    ) total ON total.id_pelanggan = pelanggan.id_user
    ORDER BY latest.waktu_kirim DESC, latest.id_chat DESC
");
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h1 class="fw-bold mb-3">Chat Admin</h1>
        </div>
        
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Tabel Data Percakapan</div>
                </div>

                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Pelanggan</th>
                                <th>Admin Tujuan</th>
                                <th>Pesan Terakhir</th>
                                <th>Total Pesan</th>
                                <th>Belum Dibaca</th>
                                <th>Waktu Terakhir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            // Cek apakah query berhasil dan ada data
                            if (!$query || mysqli_num_rows($query) == 0) {
                                echo "<tr><td colspan='8' class='text-center text-muted py-4'>Belum ada pelanggan yang chat dengan admin</td></tr>";
                            } else {
                                while ($data = mysqli_fetch_assoc($query)) {
                                    $unread = (int) ($data['jumlah_unread'] ?? 0);
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($data['nama_pelanggan'] ?? 'ID: ' . $data['id_pelanggan']); ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($data['email_pelanggan'] ?? ''); ?></small>
                                </td>
                                <td><?= htmlspecialchars($data['nama_admin_chat'] ?? 'Admin'); ?></td>
                                <td>
                                    <div class="text-muted small mb-1">
                                        <?= ((int) $data['id_pengirim'] === (int) $data['id_pelanggan']) ? 'Pelanggan' : 'Admin'; ?>
                                    </div>
                                    <?= htmlspecialchars(substr($data['isi_pesan'], 0, 95)); ?><?= strlen($data['isi_pesan']) > 95 ? '...' : ''; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= (int) ($data['jumlah_pesan'] ?? 0); ?></span></td>
                                <td>
                                    <?php if ($unread > 0) { ?>
                                        <span class="badge bg-danger"><?= $unread; ?> baru</span>
                                    <?php } else { ?>
                                        <span class="badge bg-success">Sudah dibaca</span>
                                    <?php } ?>
                                </td>
                                <td><?= date('d M Y, H:i', strtotime($data['waktu_kirim'])); ?></td>
                                <td>
                                    <a href="datachat_edit.php?id=<?= $data['id_chat']; ?>" class="btn btn-sm btn-primary" title="Lihat Percakapan">
                                        <i class="fas fa-comments me-1"></i> Lihat Chat
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
</div>

      <!-- End Custom template -->
    </div>
    <!--   Core JS Files   -->
    <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <!-- Kaiadmin JS -->
    <script src="../assets/js/kaiadmin.min.js"></script>
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script src="../../js/sweetalert-confirm.js"></script>
  </body>
</html>
