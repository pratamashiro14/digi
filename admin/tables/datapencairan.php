<?php
include "../koneksi.php";
require_once "../../keuangan_helper.php";

session_start();

// Guard admin
if (empty($_SESSION['admin'])) {
    header('location:../logout.php');
    exit;
}

// === AKSI PROSES PENCAIRAN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'], $_POST['id'])) {
    $id   = (int) $_POST['id'];
    $aksi = $_POST['aksi'];

    if ($aksi === 'diproses') {
        $stmt = mysqli_prepare($koneksi, "UPDATE t_pencairan SET status='diproses' WHERE id_pencairan=? AND status='pending'");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
    } elseif ($aksi === 'selesai') {
        $stmt = mysqli_prepare($koneksi, "UPDATE t_pencairan SET status='selesai', tanggal_proses=NOW() WHERE id_pencairan=? AND status IN ('pending','diproses')");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
    } elseif ($aksi === 'tolak') {
        $catatan = trim($_POST['catatan'] ?? '');
        if ($catatan === '') $catatan = 'Ditolak oleh admin.';
        $stmt = mysqli_prepare($koneksi, "UPDATE t_pencairan SET status='ditolak', catatan_admin=?, tanggal_proses=NOW() WHERE id_pencairan=? AND status IN ('pending','diproses')");
        mysqli_stmt_bind_param($stmt, 'si', $catatan, $id);
        mysqli_stmt_execute($stmt);
    }
    header("Location: datapencairan.php");
    exit;
}

// Data admin untuk profil
$id_admin = $_SESSION['admin'];
$query_admin = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE id_admin = '$id_admin'");
$data_admin = mysqli_fetch_assoc($query_admin);
if (!$data_admin) {
    header('location:../logout.php');
    exit;
}

// Daftar pencairan + nama desainer
$query = mysqli_query($koneksi, "SELECT p.*, u.nama AS nama_desainer, u.email AS email_desainer
                                 FROM t_pencairan p
                                 JOIN t_user u ON p.id_designer = u.id_user
                                 ORDER BY p.id_pencairan DESC");

function badge_status($status) {
    $map = [
        'pending'  => ['Menunggu', 'warning text-dark'],
        'diproses' => ['Diproses', 'info text-dark'],
        'selesai'  => ['Selesai',  'success'],
        'ditolak'  => ['Ditolak',  'danger'],
    ];
    $c = $map[$status] ?? ['-', 'secondary'];
    return '<span class="badge bg-' . $c[1] . '">' . $c[0] . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Data Pencairan - DigiDesain Admin</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

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
      <!-- Sidebar -->
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <div class="logo-header" data-background-color="dark">
            <a href="../beranda.php" class="logo">
              <img src="../assets/img/dens.png" alt="navbar brand" class="navbar-brand" />
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
                <a href="../beranda.php"><i class="fas fa-home"></i><p>Beranda</p></a>
              </li>
              <li class="nav-item">
                <a href="karyadesain.php"><i class="fas fa-palette"></i><p>Karya Desain</p></a>
              </li>
              <li class="nav-item">
                <a href="datapengguna.php"><i class="fas fa-user"></i><p>Data Pengguna</p></a>
              </li>
              <li class="nav-item">
                <a href="transaksi.php"><i class="fas fa-money-bill-wave"></i><p>Transaksi</p></a>
              </li>
              <li class="nav-item active">
                <a href="datapencairan.php"><i class="fas fa-wallet"></i><p>Data Pencairan</p></a>
              </li>
              <li class="nav-item">
                <a href="datapremium.php"><i class="fas fa-crown"></i><p>Data Premium</p></a>
              </li>
              <li class="nav-item">
                <a href="databidding.php"><i class="fas fa-hand-holding-usd"></i><p>Data Bidding</p></a>
              </li>
              <li class="nav-item">
                <a href="datachat.php"><i class="fas fa-comments"></i><p>Moderisasi Chat</p></a>
              </li>
              <li class="nav-item">
                <a href="dataadmin.php"><i class="fas fa-user-plus"></i><p>Tambah Admin</p></a>
              </li>
              <li class="nav-item">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i><p>Logout</p></a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->

      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <div class="logo-header" data-background-color="dark">
              <a href="beranda.php" class="logo">
                <img src="../assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" />
              </a>
              <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
              </div>
              <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
            </div>
          </div>
          <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
            <div class="container-fluid">
              <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <button type="submit" class="btn btn-search pe-1">
                      <i class="fa fa-search search-icon"></i>
                    </button>
                  </div>
                  <input type="text" placeholder="Search ..." class="form-control" />
                </div>
              </nav>

              <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none">
                  <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false" aria-haspopup="true">
                    <i class="fa fa-search"></i>
                  </a>
                  <ul class="dropdown-menu dropdown-search animated fadeIn">
                    <form class="navbar-left navbar-form nav-search">
                      <div class="input-group">
                        <input type="text" placeholder="Search ..." class="form-control" />
                      </div>
                    </form>
                  </ul>
                </li>
                <li class="nav-item topbar-icon dropdown hidden-caret" id="notification-dropdown-item">
                  <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-bell"></i>
                    <span class="notification" id="notif-count-badge">0</span>
                  </a>
                  <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown" id="notification-content-container"></ul>
                </li>
                <li class="nav-item topbar-user dropdown hidden-caret">
                  <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                    <div class="avatar-sm">
                      <img src="../assets/img/fotoprofil/<?= htmlspecialchars($data_admin['foto'] ?: 'profile.jpg'); ?>" alt="..." class="avatar-img rounded-circle" />
                    </div>
                    <span class="profile-username">
                      <span class="op-7">Hi,</span>
                      <span class="fw-bold"><?= htmlspecialchars($data_admin['nama_admin']); ?></span>
                    </span>
                  </a>
                  <ul class="dropdown-menu dropdown-user animated fadeIn">
                    <li>
                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item" href="../logout.php">Logout</a>
                    </li>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>
        </div>

        <div class="container">
          <div class="page-inner">
            <div class="page-header">
              <h3 class="fw-bold mb-3">Data Pencairan Dana</h3>
              <ul class="breadcrumbs mb-3">
                <li class="nav-home"><a href="../beranda.php"><i class="icon-home"></i></a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="datapencairan.php">Data Pencairan</a></li>
              </ul>
            </div>

            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Permintaan Pencairan Desainer</div>
                  <p class="text-muted mt-1 mb-0" style="font-size:13px;">Transfer manual ke rekening desainer, lalu tandai <b>Selesai</b>. Tolak jika data salah/mencurigakan.</p>
                </div>
                <div class="card-body">
                  <table class="table table-hover align-middle">
                    <thead class="table-light">
                      <tr>
                        <th style="width:5%">No</th>
                        <th>Desainer</th>
                        <th>Tujuan Transfer</th>
                        <th>Nominal</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th style="width:22%">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $no = 1;
                      if (!$query || mysqli_num_rows($query) == 0) {
                          echo "<tr><td colspan='7' class='text-center text-muted'>Belum ada permintaan pencairan</td></tr>";
                      } else {
                          while ($row = mysqli_fetch_assoc($query)) {
                      ?>
                      <tr>
                        <td><?= $no++ ?></td>
                        <td>
                          <strong><?= htmlspecialchars($row['nama_desainer']) ?></strong><br>
                          <small class="text-muted"><?= htmlspecialchars($row['email_desainer']) ?></small>
                        </td>
                        <td>
                          <strong><?= htmlspecialchars($row['bank']) ?></strong><br>
                          <small><?= htmlspecialchars($row['no_rekening']) ?><br>a.n. <?= htmlspecialchars($row['nama_pemilik_rek']) ?></small>
                        </td>
                        <td><strong><?= rupiah($row['jumlah']) ?></strong></td>
                        <td><small><?= date('d M Y H:i', strtotime($row['tanggal_request'])) ?></small></td>
                        <td>
                          <?= badge_status($row['status']) ?>
                          <?php if ($row['catatan_admin']): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($row['catatan_admin']) ?></small>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($row['status'] === 'pending' || $row['status'] === 'diproses'): ?>
                            <?php if ($row['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline">
                              <input type="hidden" name="id" value="<?= $row['id_pencairan'] ?>">
                              <input type="hidden" name="aksi" value="diproses">
                              <button class="btn btn-sm btn-info py-0 px-2 mb-1" style="font-size:12px;">Proses</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" class="d-inline">
                              <input type="hidden" name="id" value="<?= $row['id_pencairan'] ?>">
                              <input type="hidden" name="aksi" value="selesai">
                              <button class="btn btn-sm btn-success py-0 px-2 mb-1" style="font-size:12px;"
                                onclick="return confirm('Tandai pencairan ini SELESAI? Pastikan dana sudah ditransfer.')">Selesai</button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return tolakPencairan(this)">
                              <input type="hidden" name="id" value="<?= $row['id_pencairan'] ?>">
                              <input type="hidden" name="aksi" value="tolak">
                              <input type="hidden" name="catatan" value="">
                              <button class="btn btn-sm btn-danger py-0 px-2 mb-1" style="font-size:12px;">Tolak</button>
                            </form>
                          <?php else: ?>
                            <span class="text-muted" style="font-size:12px;">Selesai diproses</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php } } ?>
                    </tbody>
                  </table>
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
      function tolakPencairan(form) {
        var alasan = prompt('Alasan penolakan (opsional):', '');
        if (alasan === null) return false; // batal
        form.catatan.value = alasan;
        return true;
      }
    </script>
    <script>
      function updateNotificationDropdown() {
        $.ajax({
          url: '../check_notifs.php',
          type: 'GET',
          dataType: 'html',
          success: function(htmlContent) {
            $('#notification-content-container').html(htmlContent);
            var countText = $('#notification-content-container').find('.dropdown-title').text();
            var match = countText.match(/(\d+)/);
            $('#notif-count-badge').text(match ? match[0] : 0);
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
