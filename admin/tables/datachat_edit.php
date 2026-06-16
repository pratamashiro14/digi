<?php
require_once __DIR__ . '/../../sweetalert.php';
include '../koneksi.php';

session_start();
if(empty($_SESSION['admin'])){
  header('location:../logout.php');
} else {

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle delete chat BEFORE any output
if (isset($_GET['delete'])) {
    $id_chat = isset($_GET['delete']) ? (int)$_GET['delete'] : 0;
    
    if ($id_chat > 0) {
        $stmt = $koneksi->prepare("DELETE FROM t_chat WHERE id_chat = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id_chat);
            if ($stmt->execute()) {
                $stmt->close();
                sweetalert_redirect('Pesan berhasil dihapus.', "datachat_edit.php?id=$id", 'success', 'Berhasil Dihapus!');
            } else {
                sweetalert_back('Gagal menghapus pesan: ' . mysqli_error($koneksi), 'error', 'Gagal!');
            }
            $stmt->close();
        } else {
            sweetalert_back('Gagal menyiapkan penghapusan: ' . mysqli_error($koneksi), 'error', 'Gagal!');
        }
    }
}

$query = mysqli_query($koneksi, "
    SELECT c.*, u1.nama AS nama_pengirim, u2.nama AS nama_penerima
    FROM t_chat c
    LEFT JOIN t_user u1 ON c.id_pengirim = u1.id_user
    LEFT JOIN t_user u2 ON c.id_penerima = u2.id_user
    WHERE c.id_chat = '$id'
");

$data = mysqli_fetch_assoc($query);

// Check if data exists
if (!$data) {
    sweetalert_redirect('Chat tidak ditemukan.', 'datachat.php', 'error', 'Gagal!');
}

// Get full conversation
$conversation = mysqli_query($koneksi, "
    SELECT c.*, u1.nama AS nama_pengirim, u2.nama AS nama_penerima
    FROM t_chat c
    LEFT JOIN t_user u1 ON c.id_pengirim = u1.id_user
    LEFT JOIN t_user u2 ON c.id_penerima = u2.id_user
    WHERE (c.id_pengirim = '{$data['id_pengirim']}' AND c.id_penerima = '{$data['id_penerima']}')
       OR (c.id_pengirim = '{$data['id_penerima']}' AND c.id_penerima = '{$data['id_pengirim']}')
    ORDER BY c.waktu_kirim ASC
");

if (isset($_POST['simpan'])) {
    $isi_pesan = isset($_POST['isi_pesan']) ? $_POST['isi_pesan'] : '';

    $stmt = mysqli_prepare($koneksi, "UPDATE t_chat SET isi_pesan = ? WHERE id_chat = ?");
    mysqli_stmt_bind_param($stmt, "si", $isi_pesan, $id);
    $update = mysqli_stmt_execute($stmt);

    if ($update) {
        sweetalert_redirect('Data chat berhasil diperbarui.', 'datachat.php', 'success', 'Berhasil!');
    } else {
        sweetalert_back('Gagal memperbarui data: ' . mysqli_error($koneksi), 'error', 'Gagal!');
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Moderasi Chat</title>
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
    <link rel="stylesheet" href="../assets/css/digidesain-admin.css" />

    <link rel="stylesheet" href="../assets/css/demo.css" />
    
    <style>
      .chat-container {
        height: 400px;
        overflow-y: auto;
        background-color: #f5f5f5;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
      }
      
      .message-bubble {
        display: flex;
        margin-bottom: 15px;
        position: relative;
      }
      
      .message-bubble.sent {
        justify-content: flex-end;
      }
      
      .message-bubble.received {
        justify-content: flex-start;
      }
      
      .bubble-content {
        max-width: 70%;
        padding: 12px 16px;
        border-radius: 12px;
        word-wrap: break-word;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        position: relative;
      }
      
      .message-bubble.sent .bubble-content {
        background-color: #3f51b5;
        color: white;
      }
      
      .message-bubble.received .bubble-content {
        background-color: white;
        color: #333;
        border: 1px solid #e0e0e0;
      }
      
      .message-time {
        font-size: 12px;
        color: #999;
        margin-top: 5px;
      }
      
      .delete-btn {
        position: absolute;
        top: -10px;
        right: -10px;
        background-color: #dc3545;
        border: none;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 12px;
      }
      
      .message-bubble:hover .delete-btn {
        opacity: 1;
      }
      
      .delete-btn:hover {
        background-color: #c82333;
      }
    </style>
  </head>
  <body>
    <div class="wrapper">
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <div class="logo-header" data-background-color="dark">
            <a href="../beranda.php" class="logo">
              <img
                src="../assets/img/kaiadmin/logo_light.svg"
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
                  src="../assets/img/kaiadmin/logo_light.svg"
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
        </div>

        <div class="container">
          <div class="page-inner">
            <div class="page-header mb-4">
              <h1 class="fw-bold">Moderasi Chat</h1>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Pesan berhasil dihapus!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <div class="row mb-4">
              <div class="col-md-12">
                <div class="card card-round shadow-sm">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Detail Chat</h5>
                  </div>

                  <div class="card-body">
                    <form>
                      <div class="row">
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label fw-bold">Pengguna A (Pengirim Awal)</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($data['nama_pengirim']); ?>" disabled>
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label fw-bold">Pengguna B (Penerima Awal)</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($data['nama_penerima']); ?>" disabled>
                          </div>
                        </div>
                      </div>
                      <div class="mb-3">
                        <label class="form-label fw-bold">Waktu Pesan Dipilih</label>
                        <input type="text" class="form-control" 
                               value="<?= htmlspecialchars($data['waktu_kirim']); ?>" disabled>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="card card-round shadow-sm">
                  <div class="card-header">
                    <h5 class="card-title">Percakapan Lengkap (Kronologis)</h5>
                  </div>

                  <div class="card-body">
                    <div class="chat-container">
                      <?php
                      if (mysqli_num_rows($conversation) > 0) {
                        // Ambil ID Pengirim Awal untuk menentukan sisi bubble
                        $id_pengirim_awal = $data['id_pengirim'];

                        while ($chat = mysqli_fetch_assoc($conversation)) {
                          // Tentukan apakah pesan ini dikirim oleh Pengirim Awal ($id_pengirim_awal) atau bukan
                          $is_sent = ($chat['id_pengirim'] == $id_pengirim_awal) ? 'sent' : 'received';
                          
                          // Tentukan warna teks untuk nama (Putih jika di bubble biru 'sent', abu-abu jika di bubble putih 'received')
                          $nama_color = ($is_sent === 'sent') ? 'white' : '#555';

                          echo "
                            <div class='message-bubble $is_sent'>
                              <div class='bubble-content'>
                                <div class='fw-bold mb-1' style='font-size: 13px; color: $nama_color;'>
                                    " . htmlspecialchars($chat['nama_pengirim']) . "
                                </div>
                                <div>" . htmlspecialchars($chat['isi_pesan']) . "</div>
                                <div class='message-time'>" . date('d M H:i', strtotime($chat['waktu_kirim'])) . "</div>
                              </div>
                              <a href='datachat_edit.php?id=$id&delete=" . $chat['id_chat'] . "' 
                                 class='delete-btn' data-swal-confirm='Pesan ini akan dihapus permanen.'>
                                <i class='fas fa-trash'></i>
                              </a>
                            </div>
                          ";
                        }
                      } else {
                        echo "<div class='alert alert-info'>Tidak ada pesan dalam percakapan ini</div>";
                      }
                      ?>
                    </div>

                    <div class="input-group mt-3">
                      <input type="text" class="form-control" placeholder="Admin tidak dapat mengirim pesan" disabled>
                      <button class="btn btn-primary" type="button" disabled>
                        <i class="fas fa-paper-plane"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="custom-template">
          <div class="title">Settings</div>
          <div class="custom-toggle">
            <i class="icon-settings"></i>
          </div>
        </div>
        </div>
    </div>

    <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>

    <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../assets/js/kaiadmin.min.js"></script>
    <script src="../assets/js/setting-demo2.js"></script>
    <script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script src="../../js/sweetalert-confirm.js"></script>
  </body>
</html>
