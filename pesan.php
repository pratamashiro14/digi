<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// 1. CEK LOGIN (User Biasa atau Desainer)
require_login();

$id_user = (int) current_id(); // ID User atau Desainer
$nama_user = current_name();
$is_user = is_user_login();
$is_designer = is_designer_login();
$id_lawan = isset($_GET['lawan']) ? intval($_GET['lawan']) : null;

$status_member = 'premium';
if ($is_user) {
    $q_member = mysqli_query($koneksi, "SELECT status_member FROM t_user WHERE id_user='$id_user' LIMIT 1");
    $data_member = $q_member ? mysqli_fetch_assoc($q_member) : null;
    $status_member = $data_member['status_member'] ?? 'free';
    if (is_premium_buyer()) {
        $status_member = 'premium';
    }
}

$batas_chat = 30;
$q_total_chat = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM t_chat WHERE id_pengirim='$id_user'");
$data_total_chat = $q_total_chat ? mysqli_fetch_assoc($q_total_chat) : ['total' => 0];
$total_chat_saya = (int) ($data_total_chat['total'] ?? 0);
$sisa_kuota = max(0, $batas_chat - $total_chat_saya);
$bisa_chat = !$is_user || $status_member !== 'free' || $sisa_kuota > 0;

// 2. LOGIKA KIRIM PESAN
if (isset($_POST['kirim_pesan'])) {
    $penerima = (int) ($_POST['id_penerima'] ?? 0);
    $isi = trim($_POST['isi_pesan'] ?? '');

    if (!empty($isi) && !empty($penerima)) {
        if (!$bisa_chat) {
            sweetalert_redirect('Kuota chat gratis Anda sudah habis. Upgrade Premium untuk mengirim pesan tanpa batas.', 'pesan.php?lawan=' . $penerima, 'warning', 'Kuota Chat Habis');
        }

        // Query Insert (prepared statement — cegah SQL injection)
        $stmt = mysqli_prepare($koneksi, "INSERT INTO t_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim)
                    VALUES (?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, 'iis', $id_user, $penerima, $isi);
        mysqli_stmt_execute($stmt);
        header("Location: pesan.php?lawan=$penerima"); // Refresh halaman
        exit();
    }
}

// 3. TANDAI SEBAGAI DIBACA (sebelum query apapun agar badge langsung update)
if ($id_lawan) {
    mysqli_query($koneksi, "UPDATE t_chat SET is_read=1 WHERE id_pengirim=$id_lawan AND id_penerima=" . intval($id_user) . " AND is_read=0");
}

// 4. LOGIKA MENGAMBIL DAFTAR KONTAK
$list_kontak = [];
$q_kontak = mysqli_query($koneksi, "
    SELECT DISTINCT u.id_user, u.nama, u.foto, u.foto_profil,
        (SELECT COUNT(*) FROM t_chat WHERE id_pengirim=u.id_user AND id_penerima='$id_user' AND is_read=0) as unread_count
    FROM t_user u
    JOIN t_chat c ON (u.id_user = c.id_pengirim OR u.id_user = c.id_penerima)
    WHERE (c.id_pengirim = '$id_user' OR c.id_penerima = '$id_user')
    AND u.id_user != '$id_user'
    ORDER BY unread_count DESC
");
while ($row = mysqli_fetch_assoc($q_kontak)) {
    $list_kontak[] = $row;
}

if ($id_lawan) {
    $ada_lawan_di_kontak = false;
    foreach ($list_kontak as $kontak) {
        if ((int) $kontak['id_user'] === $id_lawan) {
            $ada_lawan_di_kontak = true;
            break;
        }
    }

    if (!$ada_lawan_di_kontak) {
        $q_kontak_baru = mysqli_query($koneksi, "SELECT id_user, nama, foto, foto_profil, 0 AS unread_count
                                                 FROM t_user
                                                 WHERE id_user='$id_lawan'
                                                 LIMIT 1");
        if ($q_kontak_baru && mysqli_num_rows($q_kontak_baru) > 0) {
            array_unshift($list_kontak, mysqli_fetch_assoc($q_kontak_baru));
        }
    }
}

// 5. LOGIKA MENGAMBIL ISI CHAT
$chat_history = [];
$nama_lawan = "";

if ($id_lawan) {
    // Ambil nama lawan bicara
    $q_lawan = mysqli_query($koneksi, "SELECT nama FROM t_user WHERE id_user='$id_lawan'");
    $data_lawan = mysqli_fetch_assoc($q_lawan);
    if (!$data_lawan) {
        sweetalert_redirect('Pengguna tidak ditemukan.', 'pesan.php', 'error', 'Chat Tidak Tersedia');
    }
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
    <title>Pesan - DensCreative</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/account-ui.css?v=<?php echo filemtime(__DIR__ . '/css/account-ui.css'); ?>">

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
        .chat-quota-bar {
            padding: 9px 15px;
            border-bottom: 1px solid #dbe6ff;
            background: #eef4ff;
            color: #3f7dff;
            font-size: 13px;
            text-align: center;
            font-weight: 500;
        }
        .chat-quota-bar.is-warning {
            background: #fff7e6;
            border-bottom-color: #ffe1a6;
            color: #9a6700;
        }
        .chat-locked {
            padding: 16px;
            border-top: 1px solid #eee;
            background: #fff;
            color: #667085;
            text-align: center;
            font-size: 13px;
        }
        .chat-locked a {
            color: #1591DC;
            font-weight: 700;
            text-decoration: underline;
        }
        
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
<body class="animsition account-page">

    <?php
    $active_page = 'messages';
    include 'navbar.php';
    ?>

    <div class="container account-shell">
        <div class="row account-layout">
            
            <?php if (current_role() !== 'designer') { ?>
            <div class="col-md-3 col-lg-3 p-b-30">
                <aside class="account-sidebar">
                    <h4 class="account-sidebar-title">Menu Pembeli</h4>
                    <ul class="sidebar-menu">
                        <li><a href="profil.php"><i class="fa fa-user-circle"></i> Profil Saya</a></li>
                        <li><a href="riwayat.php"><i class="fa fa-shopping-bag"></i> Riwayat Pembelian</a></li>
                        <li><a href="premium.php"><i class="fa fa-star"></i> Fitur Unggulan</a></li>
                        <li><a href="pesan.php" class="active"><i class="fa fa-comments"></i> Pesan</a></li>
                    </ul>
                </aside>
            </div>
            <?php } ?>

            <div class="<?php echo current_role() === 'designer' ? 'col-12 designer-full-content' : 'col-md-9 col-lg-9 account-content'; ?>">
                <div class="account-page-header">
                    <div><h1>Pesan</h1><p>Kelola percakapan antara pembeli dan desainer.</p></div>
                </div>
                
                <div class="chat-container">
                    
                    <div class="chat-sidebar">
                        <div class="chat-sidebar-header">Percakapan</div>
                        <div class="contact-list">
                            <?php if (count($list_kontak) > 0) { 
                                foreach ($list_kontak as $kontak) {
                                    $active_class = ($id_lawan == $kontak['id_user']) ? 'active' : '';
                                    // Cek foto profil (kalau kosong pakai default)
                                    $foto_mentah = !empty($kontak['foto_profil']) ? $kontak['foto_profil'] : ($kontak['foto'] ?? '');
                                    $foto_k = !empty($foto_mentah) ? 'admin/uploads/'.$foto_mentah : 'images/icons/icon-header-01.png';
                            ?>
                                <a href="pesan.php?lawan=<?php echo $kontak['id_user']; ?>" class="contact-item <?php echo $active_class; ?>">
                                    <img src="<?php echo $foto_k; ?>" class="contact-avatar">
                                    <div class="contact-info" style="display:flex; align-items:center; justify-content:space-between;">
                                        <div>
                                            <span class="contact-name"><?php echo htmlspecialchars($kontak['nama']); ?></span>
                                            <span class="contact-preview">Klik untuk chat</span>
                                        </div>
                                        <?php if ($kontak['unread_count'] > 0) { ?>
                                            <span style="background:#e53935; color:#fff; font-size:11px; font-weight:700; min-width:20px; height:20px; border-radius:50px; display:inline-flex; align-items:center; justify-content:center; padding:0 5px; flex-shrink:0;">
                                                <?php echo $kontak['unread_count']; ?>
                                            </span>
                                        <?php } ?>
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
                                <i class="fa fa-user-circle"></i> <?php echo htmlspecialchars($nama_lawan); ?>
                            </div>

                            <?php if ($is_user && $status_member === 'free') { ?>
                                <div class="chat-quota-bar <?php echo $sisa_kuota <= 5 ? 'is-warning' : ''; ?>">
                                    <i class="fa fa-info-circle"></i>
                                    Kuota Chat Free: <b><?php echo $sisa_kuota; ?></b> / <?php echo $batas_chat; ?> Pesan.
                                    <?php if ($sisa_kuota <= 5 && $sisa_kuota > 0) { ?>
                                        <span>Hampir habis!</span>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <div class="chat-box" id="chatBox">
                                <?php foreach ($chat_history as $msg) { 
                                    // Tentukan CSS bubble (Punya sendiri atau Lawan)
                                    $bubble_class = ($msg['id_pengirim'] == $id_user) ? 'msg-me' : 'msg-you';
                                    $waktu = date('H:i', strtotime($msg['waktu_kirim']));
                                ?>
                                    <div class="msg <?php echo $bubble_class; ?>">
                                        <?php echo htmlspecialchars($msg['isi_pesan']); ?>
                                        <span class="msg-time"><?php echo $waktu; ?></span>
                                    </div>
                                <?php } ?>
                            </div>

                            <?php if ($bisa_chat) { ?>
                                <?php if (is_designer_login() && is_premium_designer()) {
                                    // FAST REPLY TOOLS (fitur premium desainer): balasan cepat sekali klik.
                                    $fast_replies = [
                                        '👋 Sapa'        => 'Halo kak, terima kasih sudah menghubungi saya 😊 Ada yang bisa saya bantu?',
                                        '💰 Harga'        => 'Untuk harga custom desain mulai dari Rp ____, tergantung tingkat kerumitan ya kak.',
                                        '⏱️ Estimasi'     => 'Estimasi pengerjaan sekitar ____ hari kerja setelah brief dan DP diterima.',
                                        '🔁 Revisi'       => 'Saya menyediakan ____ kali revisi gratis agar hasilnya sesuai keinginan kakak.',
                                        '📦 Format File'  => 'File final akan saya kirim dalam format ____ (mis. PNG, JPG, PSD, AI). Boleh request kak.',
                                        '💳 Pembayaran'   => 'Pembayaran bisa lewat tombol pembelian di platform ini ya kak, aman pakai sistem escrow.',
                                        '🙏 Terima kasih' => 'Terima kasih banyak kak! Senang bisa membantu. Ditunggu orderan berikutnya 🙏',
                                    ];
                                ?>
                                <div class="fast-reply-bar">
                                    <span class="fast-reply-label"><i class="fa fa-bolt"></i> Balasan Cepat</span>
                                    <?php foreach ($fast_replies as $label => $teks) { ?>
                                        <button type="button" class="fast-reply-chip"
                                                onclick="isiCepat(<?php echo htmlspecialchars(json_encode($teks), ENT_QUOTES); ?>)">
                                            <?php echo $label; ?>
                                        </button>
                                    <?php } ?>
                                </div>
                                <?php } ?>
                                <form action="" method="POST" class="chat-input">
                                    <input type="hidden" name="id_penerima" value="<?php echo $id_lawan; ?>">
                                    <input type="text" name="isi_pesan" id="chatInput" class="input-field" placeholder="Tulis pesan..." autocomplete="off" required>
                                    <button type="submit" name="kirim_pesan" class="btn-send" title="Kirim pesan" aria-label="Kirim pesan"><i class="fa fa-paper-plane"></i></button>
                                </form>
                            <?php } else { ?>
                                <div class="chat-locked">
                                    <b>Kuota Chat Gratis Habis.</b>
                                    <span>Upgrade ke <a href="premium.php">Premium</a> untuk mengirim pesan tanpa batas.</span>
                                </div>
                            <?php } ?>

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

    <style>
        .fast-reply-bar { display:flex; flex-wrap:wrap; gap:6px; align-items:center; padding:10px 14px 0; }
        .fast-reply-label { font-size:11px; font-weight:700; color:#7a7fe2; margin-right:4px; white-space:nowrap; }
        .fast-reply-chip { background:#eef1ff; color:#4338ca; border:1px solid #dfe3ff; border-radius:50px;
            font-size:12px; font-weight:600; padding:6px 12px; cursor:pointer; transition:0.2s; }
        .fast-reply-chip:hover { background:#7a7fe2; color:#fff; border-color:#7a7fe2; }
    </style>
    <script>
        // Biar chat langsung scroll ke paling bawah pas dibuka
        var chatBox = document.getElementById("chatBox");
        if(chatBox){
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // FAST REPLY: isi kolom input dengan teks template, lalu fokus untuk diedit/kirim.
        function isiCepat(teks) {
            var input = document.getElementById('chatInput');
            if (input) {
                input.value = teks;
                input.focus();
            }
        }
    </script>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>

</body>
</html>
