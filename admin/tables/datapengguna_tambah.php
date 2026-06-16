<?php
require_once __DIR__ . '/../admin_guard.php';
require_once __DIR__ . '/../../sweetalert.php';
include "../koneksi.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize inputs
    $nama      = trim($_POST['nama'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $no_telp   = trim($_POST['no_telp'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');
    $role      = trim($_POST['role'] ?? 'user');
    $status    = trim($_POST['status'] ?? 'aktif');
    $premium   = isset($_POST['premium']) ? 1 : 0;

    if ($nama === '') $errors[] = "Nama wajib diisi.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid.";
    if ($password === '' || strlen($password) < 6) $errors[] = "Password minimal 6 karakter.";
    if ($no_telp === '') $errors[] = "No. Telp wajib diisi.";

    // handle foto upload
    $foto_name = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['foto'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                $errors[] = "Tipe file foto tidak diperbolehkan. (jpg,jpeg,png,gif,webp)";
            } elseif ($f['size'] > 4 * 1024 * 1024) {
                $errors[] = "Ukuran foto maksimal 4MB.";
            } else {
                $uploadsDir = __DIR__ . '/../uploads';
                if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                $foto_name = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/','_', basename($f['name']));
                $target = $uploadsDir . DIRECTORY_SEPARATOR . $foto_name;
                if (!move_uploaded_file($f['tmp_name'], $target)) {
                    $errors[] = "Gagal menyimpan file foto.";
                    $foto_name = '';
                }
            }
        } else {
            $errors[] = "Terjadi kesalahan upload foto.";
        }
    }

    if (empty($errors)) {
        $p = (int)$premium;
        $pw_hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = mysqli_prepare($koneksi, "INSERT INTO t_user (nama, email, password, no_telp, alamat, role, status, premium, foto)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssssssis", $nama, $email, $pw_hash, $no_telp, $alamat, $role, $status, $p, $foto_name);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            sweetalert_redirect('Pengguna berhasil ditambahkan.', 'datapengguna.php', 'success', 'Berhasil!');
        } else {
            $errors[] = "Database error: " . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
    }

    if (!empty($errors)) {
        sweetalert_back(implode("\n", $errors), 'error', 'Data Belum Valid');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tambah Pengguna</title>
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
    <link rel="stylesheet" href="../assets/css/digidesain-admin.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
</head>
<body>
  <div class="wrapper">
    <!-- Sidebar (identical to karyadesain_tambah) -->
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
            <li class="nav-item active">
              <a href="datapengguna.php">
                <i class="fas fa-user"></i>
                <p>Data Pengguna</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="transaksi.php"><i class="fas fa-money-bill-wave"></i><p>Transaksi</p></a>
            </li>
              <li class="nav-item">
                <a href="datapencairan.php">
                  <i class="fas fa-wallet"></i>
                  <p>Data Pencairan</p>
                </a>
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
            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center"></ul>
          </div>
        </nav>
      </div>

      <div class="container">
        <div class="page-inner">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Tambah Pengguna</h3>
          </div>

          <div class="container mt-4">
            <div class="card card-round shadow-sm">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">Form Tambah Pengguna</div>
              </div>

              <div class="card-body">
                <?php if (!empty($errors)): ?>
                  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                  <div class="row">
                    <div class="col-md-4 text-center">
                      <img src="../uploads/default.png" alt="Preview" class="img-fluid rounded mb-3" style="max-height:250px; object-fit:cover;">
                      <div class="form-text">Preview foto setelah upload.</div>
                    </div>

                    <div class="col-md-8">
                      <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= isset($_POST['nama'])?htmlspecialchars($_POST['nama']):''; ?>" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="form-text">Minimal 6 karakter. Password akan disimpan dalam bentuk hash.</div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">No. Telp</label>
                        <input type="text" name="no_telp" class="form-control" value="<?= isset($_POST['no_telp'])?htmlspecialchars($_POST['no_telp']):''; ?>" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3"><?= isset($_POST['alamat'])?htmlspecialchars($_POST['alamat']):''; ?></textarea>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Peran</label>
                        <select name="role" class="form-select">
                          <option value="pelanggan" <?= (isset($_POST['role']) && $_POST['role']=='pelanggan')?'selected':''; ?>>Pelanggan</option>
                          <option value="designer" <?= (isset($_POST['role']) && $_POST['role']=='designer')?'selected':''; ?>>Designer</option>
                        </select>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                          <option value="aktif" <?= (isset($_POST['status']) && $_POST['status']=='aktif')?'selected':''; ?>>Aktif</option>
                          <option value="nonaktif" <?= (isset($_POST['status']) && $_POST['status']=='nonaktif')?'selected':''; ?>>Nonaktif</option>
                        </select>
                      </div>

                      <div class="mb-3 form-check">
                        <input type="checkbox" name="premium" id="premium" class="form-check-input" <?= isset($_POST['premium']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="premium">Premium</label>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Upload Foto</label>
                        <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                        <div class="form-text">Opsional. Maks 4MB. jpg, png, gif, webp allowed.</div>
                      </div>

                      <div class="text-end">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="datapengguna.php" class="btn btn-secondary">Kembali</a>
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
