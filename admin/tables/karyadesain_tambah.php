<?php
include "../koneksi.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize inputs
    $judul = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
    $id_desainer = (int)($_POST['id_desainer'] ?? 0);
    $kategori = mysqli_real_escape_string($koneksi, trim($_POST['kategori'] ?? ''));
    $harga_awal = (float)($_POST['harga_awal'] ?? 0);

    if ($judul === '') $errors[] = "Judul Desain wajib diisi.";
    if ($id_desainer <= 0) $errors[] = "Nama Desainer wajib dipilih.";
    if ($kategori === '') $errors[] = "Kategori wajib dipilih.";
    if ($harga_awal <= 0) $errors[] = "Harga Awal harus lebih dari 0.";

    // handle gambar upload (optional)
    $gambar_name = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['gambar'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                $errors[] = "Tipe file tidak diperbolehkan. (jpg,jpeg,png,gif,webp)";
            } elseif ($f['size'] > 4 * 1024 * 1024) {
                $errors[] = "Ukuran file maksimal 4MB.";
            } else {
                $uploadsDir = __DIR__ . '/../uploads';
                if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                $gambar_name = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/','_', basename($f['name']));
                $target = $uploadsDir . DIRECTORY_SEPARATOR . $gambar_name;
                if (!move_uploaded_file($f['tmp_name'], $target)) {
                    $errors[] = "Gagal menyimpan file gambar.";
                    $gambar_name = '';
                }
            }
        } else {
            $errors[] = "Terjadi kesalahan upload gambar.";
        }
    }

    if (empty($errors)) {
        // set default status and tanggal_upload
        $status = 'pending';
        $tanggal_upload = date('Y-m-d H:i:s');

        $judul_q = mysqli_real_escape_string($koneksi, $judul);
        $kategori_q = mysqli_real_escape_string($koneksi, $kategori);
        $gambar_q = mysqli_real_escape_string($koneksi, $gambar_name);

        $sql = "INSERT INTO t_design (judul, id_designer, kategori, harga_awal, gambar, status, tanggal_upload)
                VALUES ('$judul_q', '$id_desainer', '$kategori_q', '$harga_awal', '$gambar_q', '$status', '$tanggal_upload')";

        if (mysqli_query($koneksi, $sql)) {
            echo "<script>
                    alert('Karya desain berhasil ditambahkan.');
                    window.location='karyadesain.php';
                  </script>";
            exit;
        } else {
            $errors[] = "Database error: " . mysqli_error($koneksi);
        }
    }
}

// load designers for select
$desainer_list = [];
$res = mysqli_query($koneksi, "SELECT id_user, nama FROM t_user ORDER BY nama ASC");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) $desainer_list[] = $r;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tambah Karya Desain</title>
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
    <!-- Sidebar (replaced to match dashboardadmin.php sidebar) -->
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
                  <button type="submit" class="btn btn-search pe-1"><i class="fa fa-search search-icon"></i></button>
                </div>
                <input type="text" placeholder="Search ..." class="form-control" />
              </div>
            </nav>
            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
              <!-- topbar items same as edit page -->
            </ul>
          </div>
        </nav>
      </div>

      <div class="container">
        <div class="page-inner">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Tambah Karya Desain</h3>
          </div>

          <div class="container mt-4">
            <div class="card card-round shadow-sm">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">Form Tambah</div>
              </div>

              <div class="card-body">
                <?php if (!empty($errors)): ?>
                  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                  <div class="row">
                    <!-- Left: preview placeholder -->
                    <div class="col-md-4 text-center">
                      <img src="../uploads/default.png" alt="Preview" class="img-fluid rounded mb-3" style="max-height:250px; object-fit:cover;">
                      <div class="form-text">Preview gambar setelah upload.</div>
                    </div>

                    <div class="col-md-8">
                      <div class="mb-3">
                        <label class="form-label fw-bold">Judul Desain</label>
                        <input type="text" name="judul" class="form-control" value="<?= isset($_POST['judul'])?htmlspecialchars($_POST['judul']):''; ?>" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Nama Desainer</label>
                        <select name="id_desainer" class="form-select" required>
                          <option value="">-- Pilih Desainer --</option>
                          <?php foreach ($desainer_list as $d): ?>
                            <option value="<?= $d['id_user'] ?>" <?= (isset($_POST['id_desainer']) && $_POST['id_desainer']==$d['id_user'])?'selected':''; ?>>
                              <?= htmlspecialchars($d['nama']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Kategori</label>
                        <select name="kategori" class="form-select" required>
                          <option value="">-- Pilih Kategori --</option>
                          <option value="ilustrasi" <?= (isset($_POST['kategori']) && $_POST['kategori']=='ilustrasi')?'selected':''; ?>>Ilustrasi</option>
                          <option value="tipografi" <?= (isset($_POST['kategori']) && $_POST['kategori']=='tipografi')?'selected':''; ?>>Tipografi</option>
                          <option value="mockup" <?= (isset($_POST['kategori']) && $_POST['kategori']=='mockup')?'selected':''; ?>>Mockup</option>
                          <option value="uiux" <?= (isset($_POST['kategori']) && $_POST['kategori']=='uiux')?'selected':''; ?>>UI/UX</option>
                          <option value="animasi" <?= (isset($_POST['kategori']) && $_POST['kategori']=='animasi')?'selected':''; ?>>Animasi</option>
                        </select>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Harga Awal</label>
                        <input type="number" name="harga_awal" class="form-control" step="0.01" value="<?= isset($_POST['harga_awal'])?htmlspecialchars($_POST['harga_awal']):''; ?>" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Upload Gambar</label>
                        <input type="file" name="gambar" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                        <div class="form-text">Opsional. Maks 4MB. jpg, png, gif, webp allowed.</div>
                      </div>

                      <div class="text-end">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="karyadesain.php" class="btn btn-secondary">Kembali</a>
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