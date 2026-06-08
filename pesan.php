<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// 1. CEK LOGIN (User Biasa atau Desainer)
require_login();

$id_user = current_id(); // ID User atau Desainer
$nama_user = current_name();

// 2. LOGIKA KIRIM PESAN
if (isset($_POST['kirim_pesan'])) {
    $penerima = $_POST['id_penerima'];
    $isi = mysqli_real_escape_string($koneksi, $_POST['isi_pesan']);

    if (!empty($isi) && !empty($penerima)) {
        // Query Insert sesuai nama kolom di screenshot kamu
        $q_kirim = "INSERT INTO t_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim) 
                    VALUES ('$id_user', '$penerima', '$isi', NOW())";
        mysqli_query($koneksi, $q_kirim);
        header("Location: pesan.php?lawan=$penerima"); // Refresh halaman
        exit();
    }
}

// 3. LOGIKA MENGAMBIL DAFTAR KONTAK (Orang yang pernah chat)
// Kita cari ID unik dari kolom pengirim/penerima yang berhubungan dengan user ini
$list_kontak = [];
$q_kontak = mysqli_query($koneksi, "
    SELECT DISTINCT u.id_user, u.nama, u.foto_profil 
    FROM t_user u 
    JOIN t_chat c ON (u.id_user = c.id_pengirim OR u.id_user = c.id_penerima)
    WHERE (c.id_pengirim = '$id_user' OR c.id_penerima = '$id_user') 
    AND u.id_user != '$id_user'
");
while ($row = mysqli_fetch_assoc($q_kontak)) {
    $list_kontak[] = $row;
}

// 4. LOGIKA MENGAMBIL ISI CHAT (Jika ada lawan bicara yang dipilih)
$id_lawan = isset($_GET['lawan']) ? $_GET['lawan'] : null;
$chat_history = [];
$nama_lawan = "";

if ($id_lawan) {
    // Ambil nama lawan bicara
    $q_lawan = mysqli_query($koneksi, "SELECT nama FROM t_user WHERE id_user='$id_lawan'");
    $data_lawan = mysqli_fetch_assoc($q_lawan);
    $nama_lawan = $data_lawan['nama'];

    // Ambil history chat (Urutkan dari yang terlama ke terbaru)
    $q_chat = mysqli_query($koneksi, "
        SELECT * FROM t_chat 
        WHERE (id_pengirim = '$id_user' AND id_penerima = '$id_lawan') 
           OR (id_pengirim = '$id_lawan' AND id_penerima = '$id_user') 
        ORDER BY waktu_kirim ASC
    ");
    while ($chat = mysqli_fetch_assoc($q_chat)) {
        $chat_history[] = $chat;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pesan Desainer</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">

    <style>
        body { background-color: #fff; font-family: 'Poppins', sans-serif; }

        /* SIDEBAR */
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { margin-bottom: 12px; }
        .sidebar-menu a { display: flex; align-items: center; font-size: 16px; color: #555; text-decoration: none; font-weight: 500; padding: 10px 15px; border-radius: 8px; transition: 0.2s; }
        .sidebar-menu a i { width: 30px; font-size: 18px; margin-right: 5px; color: #888; text-align: center; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: #f5f5f5; color: #333; font-weight: 700; }
        @media (min-width: 768px) { .border-right-custom { border-right: 1px solid #eee; } }

        /* LAYOUT CHAT */
        .chat-container { display: flex; border: 1px solid #e0e0e0; border-radius: 8px; height: 550px; overflow: hidden; background: #fff; }
        
        /* KIRI: DAFTAR KONTAK */
        .chat-sidebar { width: 30%; border-right: 1px solid #e0e0e0; background: #f8f9fa; display: flex; flex-direction: column; }
        .chat-sidebar-header { padding: 15px; font-weight: 700; border-bottom: 1px solid #ddd; background: #fff; color: #333; }
        .contact-list { flex-grow: 1; overflow-y: auto; }
        .contact-item { padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; cursor: pointer; transition: 0.2s; text-decoration: none; color: #333; }
        .contact-item:hover, .contact-item.active { background: #fff; border-left: 4px solid #4e8eff; }
        .contact-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; margin-right: 12px; background: #ddd; }
        .contact-info { flex-grow: 1; }
        .contact-name { font-size: 14px; font-weight: 600; display: block; }
        .contact-preview { font-size: 12px; color: #888; }

        /* KANAN: AREA CHAT */
        .chat-main { width: 70%; display: flex; flex-direction: column; background: #fff; }
        .chat-header { padding: 15px; border-bottom: 1px solid #eee; font-weight: 700; background: #fff; display: flex; align-items: center; }
        .chat-header i { margin-right: 10px; color: #4e8eff; font-size: 20px; }
        
        .chat-box { flex-grow: 1; padding: 20px; overflow-y: auto; background-color: #f5f6fa; display: flex; flex-direction: column; gap: 10px; }
        
        /* BUBBLE CHAT */
        .msg { max-width: 75%; padding: 10px 15px; border-radius: 15px; font-size: 14px; line-height: 1.5; position: relative; word-wrap: break-word; }
        .msg-me { align-self: flex-end; background: #4e8eff; color: #fff; border-bottom-right-radius: 2px; }
        .msg-you { align-self: flex-start; background: #fff; color: #333; border: 1px solid #ddd; border-bottom-left-radius: 2px; }
        .msg-time { display: block; font-size: 10px; margin-top: 5px; opacity: 0.8; text-align: right; }

        /* INPUT CHAT */
        .chat-input { padding: 15px; border-top: 1px solid #eee; display: flex; gap: 10px; background: #fff; }
        .input-field { flex-grow: 1; border: 1px solid #ddd; border-radius: 25px; padding: 10px 20px; outline: none; transition: 0.3s; }
        .input-field:focus { border-color: #4e8eff; }
        .btn-send { width: 45px; height: 45px; background: #4e8eff; color: #fff; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: 0.3s; }
        .btn-send:hover { background: #3b75dd; transform: scale(1.05); }

        .empty-chat { display: flex; align-items: center; justify-content: center; height: 100%; color: #999; flex-direction: column; }
        .empty-chat i { font-size: 60px; margin-bottom: 15px; color: #ddd; }
    </style>
</head>
<body class="animsition">

    <header class="header-v4">
        <div class="container-menu-desktop">
            <div class="top-bar">
                <div class="content-topbar flex-sb-m h-full container">
                    <div class="left-top-bar">Pilih Desain Sesuai Standarmu!</div>
                    <div class="right-top-bar flex-w h-full">
                        <a href="#" class="flex-c-m trans-04 p-lr-25">Bantuan</a>
                        <a href="profil_desainer.php" class="flex-c-m trans-04 p-lr-25">Halo, <?php echo $nama_desainer; ?></a>
                        <a href="logout.php" class="flex-c-m trans-04 p-lr-25">Logout</a>
                    </div>
                </div>
            </div>
            <div class="wrap-menu-desktop how-shadow1">
                <nav class="limiter-menu-desktop container">
                    <a href="index.php" class="logo"><img src="images/icons/logo-01.png" alt="IMG-LOGO"></a>
                    <div class="menu-desktop">
                        <ul class="main-menu">
                            <li><a href="index.php">Beranda</a></li>
                            <li><a href="product.php">Pasar Desain</a></li>
                            <li><a href="shoping-cart.php">Pembelian</a></li>
                        </ul>
                    </div>    
                </nav>
            </div>    
        </div>
    </header>

    <div class="container p-t-50 p-b-80">
        <div class="row">
            
            <div class="col-md-3 col-lg-3 p-b-30 border-right-custom">
                <h4 class="mtext-105 cl2 p-b-30" style="font-size: 18px;">Menu Desainer</h4>
                <ul class="sidebar-menu">
                    <li><a href="profil_desainer.php"><i class="fa fa-user-circle"></i> Profil Desainer</a></li>
                    <li><a href="unggahan.php"><i class="fa fa-cloud-upload"></i> Unggahan</a></li>
                    <li><a href="penjualan.php"><i class="fa fa-shopping-basket"></i> Penjualan</a></li> 
                    <li><a href="pesan.php" class="active"><i class="fa fa-comments"></i> Pesan</a></li>
                </ul>
            </div>

            <div class="col-md-9 col-lg-9 p-l-40 p-l-15-lg">
                <p class="mtext-105 cl2 p-b-20">Pesan Masuk</p>
                
                <div class="chat-container">
                    
                    <div class="chat-sidebar">
                        <div class="chat-sidebar-header">Percakapan</div>
                        <div class="contact-list">
                            <?php if (count($list_kontak) > 0) { 
                                foreach ($list_kontak as $kontak) {
                                    $active_class = ($id_lawan == $kontak['id_user']) ? 'active' : '';
                                    // Cek foto profil (kalau kosong pakai default)
                                    $foto_k = !empty($kontak['foto_profil']) ? 'admin/uploads/'.$kontak['foto_profil'] : 'images/icons/icon-header-01.png';
                            ?>
                                <a href="pesan.php?lawan=<?php echo $kontak['id_user']; ?>" class="contact-item <?php echo $active_class; ?>">
                                    <img src="<?php echo $foto_k; ?>" class="contact-avatar">
                                    <div class="contact-info">
                                        <span class="contact-name"><?php echo $kontak['nama']; ?></span>
                                        <span class="contact-preview">Klik untuk chat</span>
                                    </div>
                                </a>
                            <?php } 
                            } else { ?>
                                <div style="padding:20px; text-align:center; color:#999; font-size:13px;">Belum ada riwayat chat.</div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="chat-main">
                        <?php if ($id_lawan) { ?>
                            
                            <div class="chat-header">
                                <i class="fa fa-user-circle"></i> <?php echo $nama_lawan; ?>
                            </div>

                            <div class="chat-box" id="chatBox">
                                <?php foreach ($chat_history as $msg) { 
                                    // Tentukan CSS bubble (Punya sendiri atau Lawan)
                                    $bubble_class = ($msg['id_pengirim'] == $id_desainer) ? 'msg-me' : 'msg-you';
                                    $waktu = date('H:i', strtotime($msg['waktu_kirim']));
                                ?>
                                    <div class="msg <?php echo $bubble_class; ?>">
                                        <?php echo htmlspecialchars($msg['isi_pesan']); ?>
                                        <span class="msg-time"><?php echo $waktu; ?></span>
                                    </div>
                                <?php } ?>
                            </div>

                            <form action="" method="POST" class="chat-input">
                                <input type="hidden" name="id_penerima" value="<?php echo $id_lawan; ?>">
                                <input type="text" name="isi_pesan" class="input-field" placeholder="Tulis pesan..." autocomplete="off" required>
                                <button type="submit" name="kirim_pesan" class="btn-send"><i class="fa fa-paper-plane"></i></button>
                            </form>

                        <?php } else { ?>
                            <div class="empty-chat">
                                <i class="fa fa-comments-o"></i>
                                <p>Pilih percakapan untuk memulai chat</p>
                            </div>
                        <?php } ?>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script>
        // Biar chat langsung scroll ke paling bawah pas dibuka
        var chatBox = document.getElementById("chatBox");
        if(chatBox){
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>

</body>
</html>