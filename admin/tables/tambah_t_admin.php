<?php
<?php
include "../koneksi.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize inputs
    $nama_admin = mysqli_real_escape_string($koneksi, trim($_POST['nama_admin'] ?? ''));
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($nama_admin === '') $errors[] = "Nama Admin wajib diisi.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid.";
    if ($password === '' || strlen($password) < 6) $errors[] = "Password minimal 6 karakter.";

    if (empty($errors)) {
        // hash password
        $pw_hash = password_hash($password, PASSWORD_BCRYPT);

        // insert into t_admin
        $sql = "INSERT INTO t_admin (nama_admin, email, password, created_at)
                VALUES ('$nama_admin', '$email', '$pw_hash', NOW())";

        if (mysqli_query($koneksi, $sql)) {
            echo "<script>
                    alert('Admin berhasil ditambahkan.');
                    window.location='dataadmin.php';
                  </script>";
            exit;
        } else {
            $errors[] = "Database error: " . mysqli_error($koneksi);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tambah Admin</title>
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
        active: function () { sessionStorage.fonts = true; },
      });
    </script>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
</head>
<body>
  <div class="wrapper">
    <!-- Sidebar (identical to karyadesain_tambah.php) -->
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
            <li class="nav-item">
              <a href="../beranda.php">
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
    <!-- End Sidebar -->

    <div class="main-panel">
      <div class="main-header">
        <div class="main-header-logo">
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
              <!-- topbar items same as karyadesain_tambah.php -->
            </ul>
          </div>
        </nav>
      </div>

      <div class="container">
        <div class="page-inner">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Tambah Admin</h3>
          </div>

          <div class="container mt-4">
            <div class="card card-round shadow-sm">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">Form Tambah Admin</div>
              </div>

              <div class="card-body">
                <?php if (!empty($errors)): ?>
                  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                  <div class="row">
                    <!-- Left: small illustration / placeholder to keep structure identical -->
                    <div class="col-md-4 text-center">
                      <img src="../uploads/default.png" alt="Preview" class="img-fluid rounded mb-3" style="max-height:250px; object-fit:cover;">
                      <div class="form-text">Optional image or avatar (not required).</div>
                    </div>

                    <div class="col-md-8">
                      <div class="mb-3">
                        <label class="form-label fw-bold">Nama Admin</label>
                        <input type="text" name="nama_admin" class="form-control" value="<?= isset($_POST['nama_admin'])?htmlspecialchars($_POST['nama_admin']):''; ?>" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="form-text">Minimal 6 karakter. Password akan disimpan sebagai hash.</div>
                      </div>

                      <div class="text-end">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="dataadmin.php" class="btn btn-secondary">Kembali</a>
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
```// filepath: c:\xampp\htdocs\admin\tables\tambah_t_admin.php
<?php
include "../koneksi.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize inputs
    $nama_admin = mysqli_real_escape_string($koneksi, trim($_POST['nama_admin'] ?? ''));
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($nama_admin === '') $errors[] = "Nama Admin wajib diisi.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid.";
    if ($password === '' || strlen($password) < 6) $errors[] = "Password minimal 6 karakter.";

    if (empty($errors)) {
        // hash password
        $pw_hash = password_hash($password, PASSWORD_BCRYPT);

        // insert into t_admin
        $sql = "INSERT INTO t_admin (nama_admin, email, password, created_at)
                VALUES ('$nama_admin', '$email', '$pw_hash', NOW())";

        if (mysqli_query($koneksi, $sql)) {
            echo "<script>
                    alert('Admin berhasil ditambahkan.');
                    window.location='dataadmin.php';
                  </script>";
            exit;
        } else {
            $errors[] = "Database error: " . mysqli_error($koneksi);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tambah Admin</title>
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
        active: function () { sessionStorage.fonts = true; },
      });
    </script>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
</head>
<body>
  <div class="wrapper">
    <!-- Sidebar (identical to karyadesain_tambah.php) -->
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
            <li class="nav-item">
              <a href="../beranda.php">
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
    <!-- End Sidebar -->

    <div class="main-panel">
      <div class="main-header">
        <div class="main-header-logo">
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
              <!-- topbar items same as karyadesain_tambah.php -->
            </ul>
          </div>
        </nav>
      </div>

      <div class="container">
        <div class="page-inner">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Tambah Admin</h3>
          </div>

          <div class="container mt-4">
            <div class="card card-round shadow-sm">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">Form Tambah Admin</div>
              </div>

              <div class="card-body">
                <?php if (!empty($errors)): ?>
                  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                  <div class="row">
                    <!-- Left: small illustration / placeholder to keep structure identical -->
                    <div class="col-md-4 text-center">
                      <img src="../uploads/default.png" alt="Preview" class="img-fluid rounded mb-3" style="max-height:250px; object-fit:cover;">
                      <div class="form-text">Optional image or avatar (not required).</div>
                    </div>

                    <div class="col-md-8">
                      <div class="mb-3">
                        <label class="form-label fw-bold">Nama Admin</label>
                        <input type="text" name="nama_admin" class="form-control" value="<?= isset($_POST['nama_admin'])?htmlspecialchars($_POST['nama_admin']):''; ?>" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="form-text">Minimal 6 karakter. Password akan disimpan sebagai hash.</div>
                      </div>

                      <div class="text-end">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="dataadmin.php" class="btn btn-secondary">Kembali</a>
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