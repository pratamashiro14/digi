<?php 
include "koneksi.php";

session_start();
// Periksa apakah sesi admin kosong, jika ya, arahkan ke logout
if(empty($_SESSION['admin'])){
    header('location:logout.php');
    exit; // Wajib ada exit
}

// ==========================================================
// KODE PROFIL ADMIN YANG SEDANG LOGIN (DIJALANKAN HANYA JIKA SESSION ADA)
// ==========================================================
$id_admin = $_SESSION['admin'];
$query_admin = mysqli_query($koneksi, "SELECT * FROM t_admin WHERE id_admin = '$id_admin'"); 
$data_admin = mysqli_fetch_assoc($query_admin);

// Cek jika data admin tidak ditemukan
if (!$data_admin) {
    header('location:logout.php');
    exit;
}

// 1. MENGHITUNG TOTAL PENGGUNA (ASUMSI: SEMUA DI t_user)
$query_pengguna = mysqli_query($koneksi, "SELECT COUNT(id_user) AS total_pengguna FROM t_user");
$data_pengguna = mysqli_fetch_assoc($query_pengguna);
$total_pengguna = number_format($data_pengguna['total_pengguna']);

// 2. MENGHITUNG TOTAL PENJUALAN (ASUMSI: SUM total harga transaksi yang berhasil)
// Anda perlu kolom 'harga_final' dan 'status_pembayaran' di t_transaksi
$query_penjualan = mysqli_query($koneksi, "SELECT SUM(harga_final) AS total_penjualan, COUNT(id_transaksi) AS total_transaksi FROM t_transaksi WHERE status_pembayaran = 'berhasil'");
$data_penjualan = mysqli_fetch_assoc($query_penjualan);
$total_penjualan_sum = 'Rp ' . number_format($data_penjualan['total_penjualan'] ?? 0, 0, ',', '.'); // Total uang
$total_penjualan_count = number_format($data_penjualan['total_transaksi']); // Total jumlah item terjual

// 3. MENGHITUNG PELANGGAN (ASUMSI: PENGGUNA YANG PERNAH BERTRANSAKSI)
// Mengambil jumlah ID pembeli yang unik dari t_transaksi
$query_pelanggan = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_buyer) AS total_pelanggan FROM t_transaksi WHERE status_pembayaran = 'berhasil'");
$data_pelanggan = mysqli_fetch_assoc($query_pelanggan);
$total_pelanggan = number_format($data_pelanggan['total_pelanggan']);

// 4. MENGHITUNG PENGUNJUNG (Ini biasanya diambil dari tools seperti Google Analytics,
// namun kita bisa menggunakan total pengguna + 10% sebagai dummy jika tidak ada data traffic)
// Karena tidak ada tabel pengunjung, kita gunakan total pengguna + 100
$total_pengunjung = number_format($data_pengguna['total_pengguna'] + 100); 

// 5. MENGAMBIL DATA PENJUALAN TERAKHIR (TERBARU)
$query_penjualan_terakhir = mysqli_query($koneksi, "
    SELECT t.*, d.judul AS nama_karya 
    FROM t_transaksi t
    LEFT JOIN t_design d ON t.id_design = d.id_design
    WHERE t.status_pembayaran = 'berhasil'
    ORDER BY t.tanggal_transaksi DESC 
    LIMIT 7
");



?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Admin Dashboard</title>
    <meta
      content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
      name="viewport"
    />
    <link
      rel="icon"
      href="assets/img/kaiadmin/favicon.ico"
      type="image/x-icon"
    />

    <!-- Fonts and icons -->
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

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/denscreative-admin.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css" />
  </head>
  <body>
    <div class="wrapper">
      <!-- Sidebar -->
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <!-- Logo Header -->
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
          <!-- End Logo Header -->
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item active">
                <a href="beranda.php">
                  <i class="fas fa-home"></i>
                  <p>Beranda</p>
                </a>
              </li>
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
      <!-- End Sidebar -->

      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <!-- Logo Header -->
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
                        src="assets/img/fotoprofil/<?= htmlspecialchars($data_admin['foto'] ?: 'profile.jpg'); ?>"
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
                              src="assets/img/fotoprofil/<?= htmlspecialchars($data_admin['foto'] ?: 'profile.jpg'); ?>"
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
          <!-- End Navbar -->
        </div>

        <div class="container admin-dashboard">
          <div class="page-inner">
            <div class="dashboard-heading d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
              <div>
                <h3 class="fw-bold mb-2">Beranda Admin</h3>
                <p class="mb-0 text-muted">Pantau aktivitas pengguna, transaksi, dan pertumbuhan platform.</p>
              </div>
            </div>
            <div class="row dashboard-stats-row">
              <div class="col-sm-6 col-xl-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div
                          class="icon-big text-center icon-primary bubble-shadow-small"
                        >
                          <i class="fas fa-eye"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Pengunjung</p>
                          <h4 class="card-title"><?= $total_pengunjung; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div
                          class="icon-big text-center icon-info bubble-shadow-small"
                        >
                          <i class="fas fa-users"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Pengguna</p>
                          <h4 class="card-title"><?= $total_pengguna; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div
                          class="icon-big text-center icon-success bubble-shadow-small"
                        >
                          <i class="fas fa-shopping-cart"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Pembeli Aktif</p>
                          <h4 class="card-title"><?= $total_pelanggan; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-sm-6 col-xl-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div
                          class="icon-big text-center icon-secondary bubble-shadow-small"
                        >
                          <i class="far fa-check-circle"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Penjualan</p>
                          <h4 class="card-title"><?= $total_penjualan_count; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row dashboard-main-row">
              <div class="col-xl-8">
                <div class="card card-round dashboard-card dashboard-card-table">
                  <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                      <div class="card-title">Penjualan Terakhir</div>
                      <div class="card-tools">
                        <a href="tables/transaksi.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                      </div>
                    </div>
                  </div>
                  <div class="card-body p-0">
    <div class="table-responsive">
        <table class="table align-items-center mb-0">
            <thead class="thead-light">
                <tr>
                    <th scope="col">Nomor Pembayaran</th>
                    <th scope="col" class="text-end">Waktu dan Tanggal</th>
                    <th scope="col" class="text-end">Jumlah</th>
                    <th scope="col" class="text-end">Status</th>
                </tr>
            </thead>
            <tbody>
                
                <?php
                if (mysqli_num_rows($query_penjualan_terakhir) > 0) {
                    while ($data_jual = mysqli_fetch_assoc($query_penjualan_terakhir)) {
                        
                        // Format Tanggal
                        $timestamp = strtotime($data_jual['tanggal_transaksi']);
                        $waktu_format = date('H:i', $timestamp);
                        $tanggal_format = date('d M, Y', $timestamp);
                        
                        // Tentukan Label Status
                        $status_label = ucfirst($data_jual['status_pembayaran']);
                        $status_class = ($data_jual['status_pembayaran'] == 'berhasil') ? 'success' : 'warning';
                ?>
                
                <tr>
                    <th scope="row">
                        <button
                            class="btn btn-icon btn-round btn-success btn-sm me-2"
                        >
                            <i class="fa fa-check"></i>
                        </button>
                        Pembayaran: <?= htmlspecialchars($data_jual['nama_karya'] ?? 'ID #' . $data_jual['id_transaksi']); ?>
                    </th>
                    <td class="text-end"><?= $waktu_format; ?>, <?= $tanggal_format; ?></td>
                    <td class="text-end">Rp <?= number_format($data_jual['harga_final'], 0, ',', '.'); ?></td>
                    <td class="text-end">
                        <span class="badge badge-<?= $status_class; ?>"><?= $status_label; ?></span>
                    </td>
                </tr>
                
                <?php 
                    }
                } else {
                    echo "<tr><td colspan='4' class='text-center text-muted'>Tidak ada data penjualan yang berhasil.</td></tr>";
                }
                ?>
                
            </tbody>
        </table>
    </div>
</div>
                </div>
              </div>
              <div class="col-xl-4">
                <div class="card card-round dashboard-card dashboard-card-customers">
                  <div class="card-body">
                    <div class="card-head-row card-tools-still-right">
                      <div class="card-title">Pengguna Baru</div>
                      <div class="card-tools">
                        <div class="dropdown">
                        </div>
                      </div>
                    </div>
                    <div class="card-list py-4">
                      <div id="new-customers-list-container">
                        <div class="text-center text-muted p-4">Memuat data...</div>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
            </div><!-- end row penjualan + new customers -->
            <div class="row dashboard-chart-row">
              <div class="col-xl-12">
                <div class="card card-round dashboard-card dashboard-chart-card">
                  <div class="card-header">
                    <div class="card-head-row">
                      <div class="card-title">Statistik Pengguna</div>
                      <div class="card-tools">
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="chart-container" style="min-height: 375px">
                      <canvas id="statisticsChart"></canvas>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--   Core JS Files   -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Chart JS -->
    <script src="assets/js/plugin/chart.js/chart.min.js"></script>

    <!-- jQuery Sparkline -->
    <script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

    <!-- Chart Circle -->
    <script src="assets/js/plugin/chart-circle/circles.min.js"></script>

    <!-- Datatables -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- Bootstrap Notify -->
    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

    <!-- jQuery Vector Maps -->
    <script src="assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
    <script src="assets/js/plugin/jsvectormap/world.js"></script>

    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>

    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="assets/js/setting-demo.js"></script>
    <script src="assets/js/demo.js"></script>
    
    <script>
    
    function loadNewCustomers() {
        $.ajax({
            url: 'fetch_new_customers.php', 
            type: 'GET',
            success: function(data) {
                $('#new-customers-list-container').html(data);
            },
            error: function() {
                $('#new-customers-list-container').html('<div class="alert alert-danger text-center p-4">Gagal memuat data pelanggan.</div>');
            }
        });
    }

    $(document).ready(function() {
        loadNewCustomers(); 
        setInterval(loadNewCustomers, 10000); 
    });
    </script>
    
    <script>
    var statisticsChartInstance = null; 

    function loadAndDrawStats() {
        $.ajax({
            url: 'fetch_user_stats.php', 
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                // 1. Hapus instance chart lama
                if (statisticsChartInstance) {
                    statisticsChartInstance.destroy();
                }

                // 2. MENGHAPUS DAN MEMBUAT ULANG CANVAS BARU (SOLUSI UNTUK TOOLTIP BERMASALAH)
                var chartContainerDiv = document.querySelector('.card-body .chart-container');
                var oldCanvas = document.getElementById('statisticsChart');
                
                if (oldCanvas) {
                    chartContainerDiv.removeChild(oldCanvas);
                }

                var newCanvas = document.createElement('canvas');
                newCanvas.setAttribute('id', 'statisticsChart');
                newCanvas.style.minHeight = '375px';

                chartContainerDiv.appendChild(newCanvas);
                // AKHIR PERBAIKAN CANVAS

                var labels = response.labels;
                var data = response.data;

                var ctx = newCanvas.getContext('2d'); // Gunakan konteks dari canvas baru
                
                statisticsChartInstance = new Chart(ctx, { 
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Pengguna Baru',
                            borderColor: '#177dff',
                            pointBorderColor: '#FFF',
                            pointBackgroundColor: '#177dff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 4,
                            pointHoverBorderWidth: 1,
                            pointRadius: 4,
                            backgroundColor: 'transparent',
                            fill: true,
                            borderWidth: 2,
                            data: data
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: { display: false },
                        tooltips: {
                            mode: 'nearest', intersect: 0, position: 'nearest',
                            xPadding: 10, yPadding: 10, caretPadding: 10
                        },
                        layout: { padding: { left: 15, right: 15, top: 15, bottom: 15 } },
                        scales: {
                            yAxes: [{
                                ticks: { fontColor: 'rgba(0,0,0,0.5)', fontStyle: 'bold', beginAtZero: true }
                            }],
                            xAxes: [{
                                ticks: { fontColor: 'rgba(0,0,0,0.5)', fontStyle: 'bold' }
                            }]
                        }
                    }
                });
            },
            error: function() {
                console.error("Gagal memuat data statistik pengguna.");
            }
        });
    }

    $(document).ready(function() {
        loadAndDrawStats(); 
        setInterval(loadAndDrawStats, 60000); // Update setiap 1 menit
    });
    </script>

    <script>
      $("#lineChart").sparkline([102, 109, 120, 99, 110, 105, 115], {
        type: "line",
        height: "70",
        width: "100%",
        lineWidth: "2",
        lineColor: "#177dff",
        fillColor: "rgba(23, 125, 255, 0.14)",
      });

      $("#lineChart2").sparkline([99, 125, 122, 105, 110, 124, 115], {
        type: "line",
        height: "70",
        width: "100%",
        lineWidth: "2",
        lineColor: "#f3545d",
        fillColor: "rgba(243, 84, 93, .14)",
      });

      $("#lineChart3").sparkline([105, 103, 123, 100, 95, 105, 115], {
        type: "line",
        height: "70",
        width: "100%",
        lineWidth: "2",
        lineColor: "#ffa534",
        fillColor: "rgba(255, 165, 52, .14)",
      });
    </script>

<!-- SCRIPT UNTUK UPDATE NOTIFIKASI DROPDOWN -->
    <script>
    function updateNotificationDropdown() {
        $.ajax({
            url: 'check_notifs.php',
            type: 'GET',
            dataType: 'html', // Mengambil HTML dari file PHP
            success: function(html_content) {
                // Ganti seluruh isi dropdown dengan HTML yang diterima
                $('#notification-content-container').html(html_content);
                
                // Ambil count dari konten yang diterima (misalnya dari dropdown-title)
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
        // ... (Skrip AJAX lama di sini) ...

        // Aktifkan update notifikasi dropdown
        updateNotificationDropdown(); 
        setInterval(updateNotificationDropdown, 30000); // Update setiap 30 detik
    });
</script>
  </body>
</html>
