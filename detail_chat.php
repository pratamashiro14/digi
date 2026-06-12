<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

// --- LOGIKA CEK LOGIN FLEKSIBEL (user atau desainer) ---
require_login();
$is_user     = is_user_login();
$is_designer = is_designer_login();

// Ambil ID Pelaku
$id_saya = current_id();

// 2. TANGKAP ID LAWAN BICARA
if (isset($_GET['tujuan'])) {
    $id_lawan = intval($_GET['tujuan']);
} else {
    echo "<script>window.history.back();</script>";
    exit();
}

// Cegah self-message
if ($id_lawan == $id_saya) {
    echo "<script>alert('Tidak bisa chat dengan diri sendiri.'); window.history.back();</script>";
    exit();
}

// Tandai pesan masuk dari lawan bicara sebagai sudah dibaca
mysqli_query($koneksi, "UPDATE t_chat SET is_read=1 WHERE id_pengirim=$id_lawan AND id_penerima=$id_saya AND is_read=0");

// Ambil Nama & Role Lawan Bicara
$q_lawan = mysqli_query($koneksi, "SELECT nama, role FROM t_user WHERE id_user='$id_lawan'");
$data_lawan = mysqli_fetch_assoc($q_lawan);
if (!$data_lawan) {
    echo "<script>alert('Pengguna tidak ditemukan.'); window.history.back();</script>";
    exit();
}
$nama_lawan = $data_lawan['nama'];
$role_lawan = $data_lawan['role'];

// 3. LOGIKA KUOTA CHAT (Khusus untuk User Biasa)
$status_member = 'free'; // Default
if ($is_user) {
    $q_me = mysqli_query($koneksi, "SELECT status_member FROM t_user WHERE id_user='$id_saya'");
    $me = mysqli_fetch_assoc($q_me);
    $status_member = $me['status_member'] ?? 'free';
}

// Hitung Chat yang Sudah Saya Kirim
$q_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM t_chat WHERE id_pengirim='$id_saya'");
$count_data = mysqli_fetch_assoc($q_count);
$total_chat_saya = $count_data['total'];

// Hitung Sisa Kuota
$batas_chat = 30;
$sisa_kuota = $batas_chat - $total_chat_saya;
$bisa_chat = true;

// Kalau Free DAN Kuota Habis, Kunci Chat
if ($status_member == 'free' && $sisa_kuota <= 0) {
    $bisa_chat = false;
    $sisa_kuota = 0; 
}

// 4. PROSES KIRIM PESAN
if (isset($_POST['kirim_pesan'])) {
    // Cek: Kalau Desainer selalu bisa, kalau User cek kuota/status
    if ($is_designer || ($bisa_chat == true || $status_member == 'premium')) {
        $isi_pesan = mysqli_real_escape_string($koneksi, $_POST['isi_pesan']);
        
        // Simpan ke DB
        $kirim = mysqli_query($koneksi, "INSERT INTO t_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim) VALUES ('$id_saya', '$id_lawan', '$isi_pesan', NOW())");
        
        if($kirim){
            header("Location: detail_chat.php?tujuan=$id_lawan"); 
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Chat dengan <?php echo $nama_lawan; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; display: flex; flex-direction: column; height: 100vh; margin:0; }
        
        /* HEADER BARU */
        .chat-header {
            background: #fff; color: #333; padding: 15px;
            display: flex; align-items: center;
            border-bottom: 1px solid #eee; z-index: 10;
        }
        .btn-back { color: #555; margin-right: 15px; font-size: 20px; text-decoration: none; transition: 0.3s; }
        .btn-back:hover { color: #4e8eff; }
        .user-avatar { width: 40px; height: 40px; background: #e9ecef; border-radius: 50%; margin-right: 12px; display:flex; align-items:center; justify-content:center; color:#6c757d; font-size: 16px;}
        .chat-title { font-weight: 600; font-size: 16px; color: #333; }
        .chat-status { font-size: 12px; color: #888; }

        /* INFO KUOTA */
        .quota-bar {
            background: #eef2ff; color: #4e8eff; padding: 8px 15px; font-size: 13px; text-align: center; border-bottom: 1px solid #dce4ff; font-weight: 500;
        }
        
        /* AREA CHAT */
        .chat-area {
            flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column;
            background-color: #f8f9fa; 
        }
        
        /* BUBBLE CHAT */
        .bubble {
            max-width: 75%; padding: 12px 15px; border-radius: 12px; margin-bottom: 12px; position: relative; font-size: 14px; line-height: 1.5; word-wrap: break-word; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .bubble-me {
            align-self: flex-end; 
            background-color: #4e8eff; 
            color: #fff;
            border-bottom-right-radius: 2px;
        }
        .bubble-you {
            align-self: flex-start; 
            background-color: #fff; 
            color: #333;
            border: 1px solid #eee;
            border-bottom-left-radius: 2px;
        }
        .chat-time { display: block; font-size: 11px; text-align: right; margin-top: 5px; opacity: 0.7; }
        .bubble-me .chat-time { color: #e0e0e0; }
        .bubble-you .chat-time { color: #999; }
        .fa-check { font-size: 10px; margin-left: 3px; }

        /* INPUT AREA */
        .input-area {
            background: #fff; padding: 15px; display: flex; align-items: center; border-top: 1px solid #eee;
        }
        .input-box {
            flex: 1; border: 1px solid #ddd; padding: 12px 15px; border-radius: 30px; outline: none; margin-right: 10px; background: #f8f9fa; transition: 0.3s;
        }
        .input-box:focus { border-color: #4e8eff; background: #fff; }
        .btn-send {
            background: #4e8eff; color: white; border: none; width: 48px; height: 48px; border-radius: 50%; cursor: pointer; display:flex; align-items:center; justify-content:center; font-size: 18px; transition: 0.3s; box-shadow: 0 2px 5px rgba(78, 142, 255, 0.3);
        }
        .btn-send:hover { background: #3b7ddd; transform: translateY(-2px); }
        
        /* TEMPLATE CHAT STARTER */
        .chat-starter {
            display: flex; flex-direction: column; align-items: center;
            padding: 30px 20px; text-align: center;
        }
        .chat-starter-icon { font-size: 48px; color: #ddd; margin-bottom: 12px; }
        .chat-starter-title { font-size: 15px; color: #444; margin-bottom: 4px; }
        .chat-starter-sub { font-size: 12.5px; color: #aaa; margin-bottom: 20px; }
        .template-grid {
            display: grid; grid-template-columns: repeat(2, 1fr);
            gap: 10px; width: 100%; max-width: 420px;
        }
        .template-btn {
            display: flex; align-items: center; gap: 8px;
            background: #fff; border: 1.5px solid #e5e7ff;
            border-radius: 10px; padding: 10px 12px;
            font-size: 13px; color: #444; cursor: pointer;
            text-align: left; transition: 0.2s; font-family: inherit;
        }
        .template-btn:hover { border-color: #4e8eff; color: #4e8eff; background: #f0f4ff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(78,142,255,.12); }
        .template-icon { font-size: 18px; flex-shrink: 0; }

        /* JIKA KUOTA HABIS */
        .locked-area {
            background: #fff; padding: 20px; text-align: center; color: #666; width: 100%; font-size: 14px; border-top: 1px solid #eee;
        }
        .btn-upgrade {
            display: inline-block; background: #f39c12; color: white; border: none; padding: 8px 15px; border-radius: 50px; font-weight: 600; text-decoration: none; margin-top: 10px; font-size: 13px; transition: 0.3s;
        }
        .btn-upgrade:hover { background: #e67e22; color: white; text-decoration: none; }
    </style>
</head>
<body>

    <div class="chat-header">
        <a href="javascript:history.back()" class="btn-back"><i class="fa fa-arrow-left"></i></a>
        
        <div class="user-avatar"><i class="fa fa-user"></i></div>
        <div>
            <div class="chat-title"><?php echo $nama_lawan; ?></div>
            <div class="chat-status">
                <?php
                $role_labels = ['designer' => 'Desainer', 'desainer' => 'Desainer', 'pelanggan' => 'Pembeli', 'buyer' => 'Pembeli'];
                echo $role_labels[$role_lawan] ?? ucfirst($role_lawan);
                ?>
            </div>
        </div>
    </div>

    <?php if($is_user && $status_member == 'free') { ?>
        <div class="quota-bar">
            <i class="fa fa-info-circle"></i> Kuota Chat Free: <b><?php echo $sisa_kuota; ?></b> / 30 Pesan. 
            <?php if($sisa_kuota < 5) echo "<span style='color:#d9534f; font-weight:bold;'>Hampir Habis!</span>"; ?>
        </div>
    <?php } ?>

    <div class="chat-area" id="chatBox">
        <?php
        $q_chat = mysqli_query($koneksi, "SELECT * FROM t_chat 
            WHERE (id_pengirim='$id_saya' AND id_penerima='$id_lawan') 
            OR (id_pengirim='$id_lawan' AND id_penerima='$id_saya') 
            ORDER BY waktu_kirim ASC");

        $jumlah_chat = mysqli_num_rows($q_chat);
        if($jumlah_chat > 0){
            while($c = mysqli_fetch_assoc($q_chat)) {
                $posisi = ($c['id_pengirim'] == $id_saya) ? 'bubble-me' : 'bubble-you';
                $jam = date('H:i', strtotime($c['waktu_kirim']));
        ?>
            <div class="bubble <?php echo $posisi; ?>">
                <?php echo nl2br(htmlspecialchars($c['isi_pesan'])); ?>
                <span class="chat-time"><?php echo $jam; ?> <?php if($posisi == 'bubble-me') echo '<i class="fa fa-check"></i>'; ?></span>
            </div>
        <?php
            }
        } else {
            // Kosong: tampilkan template hanya untuk pembeli
            if ($is_user) { ?>
                <div class="chat-starter">
                    <div class="chat-starter-icon"><i class="fa fa-comments-o"></i></div>
                    <p class="chat-starter-title">Mulai percakapan dengan <strong><?php echo htmlspecialchars($nama_lawan); ?></strong></p>
                    <p class="chat-starter-sub">Pilih template atau tulis pesanmu sendiri</p>
                    <div class="template-grid">
                        <button class="template-btn" onclick="pakai('Halo kak <?php echo htmlspecialchars($nama_lawan); ?>, saya tertarik dengan karya desain Anda 😊')">
                            <span class="template-icon">👋</span>
                            <span>Sapa desainer</span>
                        </button>
                        <button class="template-btn" onclick="pakai('Berapa harga untuk custom desain? Boleh saya tahu estimasinya?')">
                            <span class="template-icon">💰</span>
                            <span>Tanya harga</span>
                        </button>
                        <button class="template-btn" onclick="pakai('Apakah bisa request desain sesuai keinginan saya? Saya punya referensi yang bisa saya kirim.')">
                            <span class="template-icon">🎨</span>
                            <span>Request custom</span>
                        </button>
                        <button class="template-btn" onclick="pakai('Boleh minta contoh portofolio lainnya kak? Ingin melihat lebih banyak karya Anda.')">
                            <span class="template-icon">📁</span>
                            <span>Lihat portofolio</span>
                        </button>
                        <button class="template-btn" onclick="pakai('Berapa lama estimasi waktu pengerjaan desainnya kak?')">
                            <span class="template-icon">⏱️</span>
                            <span>Tanya waktu</span>
                        </button>
                        <button class="template-btn" onclick="pakai('Apakah file desain yang diberikan bisa diedit sendiri? Format apa yang tersedia?')">
                            <span class="template-icon">📦</span>
                            <span>Tanya format file</span>
                        </button>
                    </div>
                </div>
            <?php } else {
                echo "<div style='text-align:center; color:#999; margin-top:30px; font-size:14px;'><i class='fa fa-comments-o' style='font-size:40px; margin-bottom:10px; display:block; color:#ddd;'></i>Belum ada percakapan.</div>";
            }
        }
        ?>
    </div>

    <?php if ($is_designer || ($bisa_chat == true || $status_member == 'premium')) { ?>
        
        <form action="" method="POST" class="input-area">
            <input type="text" name="isi_pesan" class="input-box" placeholder="Tulis pesan..." required autocomplete="off">
            <button type="submit" name="kirim_pesan" class="btn-send">
                <i class="fa fa-paper-plane"></i>
            </button>
        </form>

    <?php } else { ?>

        <div class="locked-area">
            <i class="fa fa-lock" style="font-size: 24px; color: #f39c12; margin-bottom: 10px;"></i><br>
            <b>Kuota Chat Gratis Habis!</b><br>
            Kamu telah mencapai batas 30 pesan.<br>
            <a href="premium.php" class="btn-upgrade">UPGRADE KE PREMIUM</a>
        </div>

    <?php } ?>

    <script>
        var objDiv = document.getElementById("chatBox");
        objDiv.scrollTop = objDiv.scrollHeight;

        function pakai(teks) {
            var input = document.querySelector('input[name="isi_pesan"]');
            if (input) {
                input.value = teks;
                input.focus();
            }
        }
    </script>

</body>
</html>