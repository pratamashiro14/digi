<?php
include "../koneksi.php";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user           = (int)($_POST['id_user'] ?? 0);
    $tipe_premium      = trim($_POST['tipe_premium'] ?? '');
    $tanggal_aktif     = trim($_POST['tanggal_aktif'] ?? '');
    $tanggal_berakhir  = trim($_POST['tanggal_berakhir'] ?? '');
    $status            = trim($_POST['status'] ?? 'aktif');

    if ($id_user <= 0) $errors[] = "Pilih pengguna (ID User) yang akan diberi premium.";
    if ($tipe_premium === '') $errors[] = "Tipe premium wajib diisi.";
    if ($tanggal_aktif === '') $errors[] = "Tanggal aktif wajib diisi.";
    if ($tanggal_berakhir === '') $errors[] = "Tanggal berakhir wajib diisi.";

    if (empty($errors)) {
        $uid = mysqli_real_escape_string($koneksi, $id_user);
        $tp  = mysqli_real_escape_string($koneksi, $tipe_premium);
        $ta  = mysqli_real_escape_string($koneksi, $tanggal_aktif);
        $tb  = mysqli_real_escape_string($koneksi, $tanggal_berakhir);
        $st  = mysqli_real_escape_string($koneksi, $status);

        $sql = "INSERT INTO t_premium (id_user, tipe_premium, tanggal_aktif, tanggal_berakhir, status)
                VALUES ('$uid', '$tp', '$ta', '$tb', '$st')";

        if (mysqli_query($koneksi, $sql)) {
            header('Location: datapremium.php');
            exit;
        } else {
            $errors[] = "Database error: " . mysqli_error($koneksi);
        }
    }
}

// load users for select
$users = [];
$q = mysqli_query($koneksi, "SELECT id_user, nama FROM t_user ORDER BY nama ASC");
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) $users[] = $r;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tambah Premium</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../assets/img/kaiadmin/favicon.ico" type="image/x-icon" />
    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: ["Font Awesome 5 Solid","Font Awesome 5 Regular","Font Awesome 5 Brands","simple-line-icons"],
          urls: ["../assets/css/fonts.min.css"]
        },
        active: function(){ sessionStorage.fonts = true; }
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
    <!-- Sidebar (kept identical to other pages) -->
    <div class="sidebar" data-background-color="dark">
      <div class="sidebar-logo">
        <div class="logo-header" data-background-color="dark">
          <a href="../beranda.php" class="logo">
            <img src="../assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" />
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
            <li class="nav-item"><a href="../beranda.php"><i class="fas fa-home"></i><p>Beranda</p></a></li>
            <li class="nav-item"><a href="karyadesain.php"><i class="fas fa-palette"></i><p>Karya Desain</p></a></li>
            <li class="nav-item"><a href="datapengguna.php"><i class="fas fa-user"></i><p>Data Pengguna</p></a></li>
            <li class="nav-item"><a href="transaksi.php"><i class="fas fa-money-bill-wave"></i><p>Transaksi</p></a></li>
            <li class="nav-item active"><a href="datapremium.php"><i class="fas fa-crown"></i><p>Data Premium</p></a></li>
            <li class="nav-item"><a href="databidding.php"><i class="fas fa-hand-holding-usd"></i><p>Data Bidding</p></a></li>
            <li class="nav-item"><a href="datachat.php"><i class="fas fa-comments"></i><p>Moderisasi Chat</p></a></li>
            <li class="nav-item"><a href="dataadmin.php"><i class="fas fa-user-plus"></i><p>Tambah Admin</p></a></li>
            <li class="nav-item"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i><p>Logout</p></a></li>
          </ul>
        </div>
      </div>
    </div>
    <!-- End Sidebar -->

    <div class="main-panel">
      <div class="main-header">
        <div class="main-header-logo">
          <div class="logo-header" data-background-color="dark">
            <a href="../beranda.php" class="logo"><img src="../assets/img/kaiadmin/logo_light.svg" class="navbar-brand" height="20" /></a>
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
                <div class="input-group-prepend"><button class="btn btn-search pe-1"><i class="fa fa-search search-icon"></i></button></div>
                <input type="text" placeholder="Search ..." class="form-control" />
              </div>
            </nav>
            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center"></ul>
          </div>
        </nav>
      </div>

      <div class="container">
        <div class="page-inner">
          <div class="page-header"><h3 class="fw-bold mb-3">Tambah Premium</h3></div>

          <div class="container mt-4">
            <div class="card card-round shadow-sm">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">Form Tambah Premium</div>
              </div>
              <div class="card-body">
                <?php if (!empty($errors)): ?>
                  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                  <div class="row">
                    <div class="col-md-12">
                      <div class="mb-3">
                        <label class="form-label fw-bold">Pengguna</label>
                        <select name="id_user" class="form-select" required>
                          <option value="">-- Pilih Pengguna --</option>
                          <?php foreach ($users as $u): ?>
                            <option value="<?= htmlspecialchars($u['id_user']) ?>" <?= (isset($_POST['id_user']) && $_POST['id_user']==$u['id_user'])?'selected':''; ?>>
                              <?= htmlspecialchars($u['nama']) ?> (ID: <?= htmlspecialchars($u['id_user']) ?>)
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Tipe Premium</label>
                        <select name="tipe_premium" class="form-select" required>
                          <option value="">-- Pilih Tipe Premium --</option>
                          <option value="buyer" <?= (isset($_POST['tipe_premium']) && $_POST['tipe_premium']=='buyer')?'selected':''; ?>>Buyer</option>
                          <option value="designer" <?= (isset($_POST['tipe_premium']) && $_POST['tipe_premium']=='designer')?'selected':''; ?>>Designer</option>
                        </select>
                      </div>

                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <label class="form-label fw-bold">Tanggal Aktif</label>
                          <input type="date" name="tanggal_aktif" class="form-control" required value="<?= isset($_POST['tanggal_aktif'])?htmlspecialchars($_POST['tanggal_aktif']):''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                          <label class="form-label fw-bold">Tanggal Berakhir</label>
                          <input type="date" name="tanggal_berakhir" class="form-control" required value="<?= isset($_POST['tanggal_berakhir'])?htmlspecialchars($_POST['tanggal_berakhir']):''; ?>">
                        </div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                          <option value="aktif" <?= (isset($_POST['status']) && $_POST['status']=='aktif')?'selected':''; ?>>Aktif</option>
                          <option value="nonaktif" <?= (isset($_POST['status']) && $_POST['status']=='nonaktif')?'selected':''; ?>>Nonaktif</option>
                        </select>
                      </div>

                      <div class="text-end">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="datapremium.php" class="btn btn-secondary">Kembali</a>
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
  </div>

  <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
  <script src="../assets/js/kaiadmin.min.js"></script>
</body>
</html>
