<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';

$sudah_login = is_login();
$id_saya     = $sudah_login ? (int) current_id() : 0;

/**
 * Cari satu akun admin sebagai penerima keluhan.
 * Prioritaskan admin yang berstatus aktif; jika tidak ada, ambil admin mana pun.
 */
function cari_admin_penerima($koneksi, $id_saya) {
    $q = mysqli_query($koneksi, "
        SELECT id_user FROM t_user
        WHERE role='admin' AND (status='aktif' OR status IS NULL) AND id_user <> '$id_saya'
        ORDER BY id_user ASC LIMIT 1
    ");
    if (!$q || mysqli_num_rows($q) === 0) {
        $q = mysqli_query($koneksi, "
            SELECT id_user FROM t_user
            WHERE role='admin' AND id_user <> '$id_saya'
            ORDER BY id_user ASC LIMIT 1
        ");
    }
    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        return (int) $row['id_user'];
    }
    return 0;
}

$id_admin_tujuan = cari_admin_penerima($koneksi, $id_saya);

// ====== PROSES KIRIM KELUHAN ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_keluhan'])) {
    if (!$sudah_login) {
        sweetalert_redirect('Silakan login terlebih dahulu untuk mengirim keluhan ke admin.', 'login.php', 'info', 'Login Diperlukan');
    }
    if ($id_admin_tujuan <= 0) {
        sweetalert_redirect('Belum ada akun admin yang tersedia untuk menerima keluhan saat ini.', 'contact.php', 'warning', 'Admin Tidak Tersedia');
    }

    $kategori = trim($_POST['kategori'] ?? '');
    $subjek   = trim($_POST['subjek'] ?? '');
    $isi      = trim($_POST['pesan'] ?? '');

    if ($isi === '') {
        sweetalert_redirect('Pesan keluhan tidak boleh kosong.', 'contact.php', 'warning', 'Lengkapi Keluhan');
    }

    // Susun isi pesan keluhan yang rapi untuk dibaca admin di Moderisasi Chat.
    $kategori_label = $kategori !== '' ? $kategori : 'Umum';
    $isi_chat  = "📩 KELUHAN — " . $kategori_label . "\n";
    if ($subjek !== '') {
        $isi_chat .= "Subjek: " . $subjek . "\n";
    }
    $isi_chat .= "\n" . $isi;

    $stmt = mysqli_prepare($koneksi, "INSERT INTO t_chat (id_pengirim, id_penerima, isi_pesan, waktu_kirim)
                                      VALUES (?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, 'iis', $id_saya, $id_admin_tujuan, $isi_chat);
    if (mysqli_stmt_execute($stmt)) {
        sweetalert_redirect('Keluhan Anda sudah terkirim ke admin. Anda bisa melanjutkan percakapan di halaman Pesan.', 'pesan.php?lawan=' . $id_admin_tujuan, 'success', 'Keluhan Terkirim');
    } else {
        sweetalert_redirect('Maaf, keluhan gagal dikirim. Silakan coba lagi.', 'contact.php', 'error', 'Gagal Mengirim');
    }
    exit;
}

// Data user untuk prefill form
$nama_saya  = $sudah_login ? current_name()  : '';
$email_saya = $sudah_login ? current_email() : '';

// Hitung Keranjang
$jumlah_item_keranjang = 0;
if(isset($_SESSION['keranjang'])) {
    foreach($_SESSION['keranjang'] as $jml) {
        $jumlah_item_keranjang += $jml;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Contact</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="images/icons/favicon.png"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/linearicons-v1.0.0/icon-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/perfect-scrollbar/perfect-scrollbar.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="css/util.css">
	<link rel="stylesheet" type="text/css" href="css/main.css">
<!--===============================================================================================-->
</head>
<body class="animsition">
	
	<?php 
	$active_page = 'contact'; 
	include 'navbar.php';
	?>

	<!-- Cart -->

	<!-- Cart -->
	<div class="wrap-header-cart js-panel-cart">
		<div class="s-full js-hide-cart"></div>
		<div class="header-cart flex-col-l p-l-65 p-r-25">
			<div class="header-cart-title flex-w flex-sb-m p-b-8">
				<span class="mtext-103 cl2">Keranjang</span>
				<div class="fs-35 lh-10 cl2 p-lr-5 pointer hov-cl1 trans-04 js-hide-cart"><i class="zmdi zmdi-close"></i></div>
			</div>
			<div class="header-cart-content flex-w js-pscroll">
				<ul class="header-cart-wrapitem w-full">
                    <?php 
                    $total_harga_sidebar = 0;
                    if(isset($_SESSION['keranjang']) && !empty($_SESSION['keranjang'])) {
                        foreach($_SESSION['keranjang'] as $id_produk => $qty) {
                            $sql_cart = "SELECT * FROM t_design WHERE id_design = '$id_produk'";
                            $res_cart = mysqli_query($koneksi, $sql_cart);
                            $d_cart = mysqli_fetch_assoc($res_cart);
                            if($d_cart) {
                                $subtotal = $d_cart['harga_awal'] * $qty;
                                $total_harga_sidebar += $subtotal;
                    ?>
					<li class="header-cart-item flex-w flex-t m-b-12">
						<div class="header-cart-item-img"><img src="admin/uploads/<?php echo $d_cart['gambar']; ?>" alt="IMG" style="height:100%; object-fit:cover;"></div>
						<div class="header-cart-item-txt p-t-8">
							<a href="#" class="header-cart-item-name m-b-18 hov-cl1 trans-04"><?php echo $d_cart['judul']; ?></a>
							<span class="header-cart-item-info"><?php echo $qty; ?> x Rp <?php echo number_format($d_cart['harga_awal'],0,',','.'); ?></span>
						</div>
					</li>
                    <?php } } } else { echo "<li class='header-cart-item m-b-12'><div class='header-cart-item-txt p-t-8'>Keranjang masih kosong.</div></li>"; } ?>
				</ul>
				<div class="w-full">
					<div class="header-cart-total w-full p-tb-40">Total: Rp <?php echo number_format($total_harga_sidebar,0,',','.'); ?></div>
					<div class="header-cart-buttons flex-w w-full">
						<a href="shoping-cart.php" class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3 p-lr-15 trans-04 m-b-10">Check Out</a>
					</div>
				</div>
			</div>
		</div>
	</div>


	<!-- Title page -->
	<section class="bg-img1 txt-center p-lr-15 p-tb-92" style="background-color: #f5f5f5;">
		<div class="container">
			<div style="text-align: center; margin-bottom: 40px;">
				<h1 style="font-size: 4rem; font-weight: 700; margin: 30px 0 10px; color: #222;">Hubungi Kami</h1>
				<p style="font-size: 1.1rem; color: #666; max-width: 640px; margin: 0 auto 10px; line-height: 1.7;">
					Punya kendala atau keluhan saat menggunakan DensCreative? Sampaikan langsung kepada admin
					melalui form di bawah, atau hubungi kami lewat kontak yang tersedia.
				</p>

				<!-- Team Photos -->
				<div style="display: flex; justify-content: center; gap: 30px; align-items: center; flex-wrap: wrap; margin: 30px 0;">
					<div style="text-align: center;">
						<img src="images/hubunginkami-01.png" alt="Team Member" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #e74c3c; display: block;">
						<p style="margin-top: 15px; margin-bottom: 2px; font-weight: 700; color: #222; font-size: 1.05rem;">Denden</p>
						<p style="margin: 0; font-weight: 500; color: #777; font-size: 0.9rem;">Admin 1</p>
					</div>
				</div>
			</div>
		</div>
	</section>	


	<!-- Content page -->
	<section class="bg0 p-t-104 p-b-116">
		<div class="container">
			<div class="flex-w flex-tr" style="display:flex; flex-wrap:wrap; gap:30px; align-items:stretch; justify-content:center;">
				<div class="bor10 p-lr-70 p-t-55 p-b-70 p-lr-15-lg" style="background: #f9f9f9; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex:1 1 360px; max-width:560px; width:auto;">
					<form method="post" action="contact.php">
						<h4 class="txt-center" style="font-weight: 700; color: #333; font-size: 1.5rem; margin-bottom: 10px;">
							Sampaikan Keluhan
						</h4>
						<p class="txt-center" style="color:#777; line-height:1.6; margin-bottom: 30px;">
							Ada masalah pembayaran, karya, akun, atau lainnya? Tulis keluhan Anda di bawah ini.
							Pesan akan langsung diteruskan ke admin dan dapat Anda lanjutkan di halaman Pesan.
						</p>

						<?php if (!$sudah_login) { ?>
							<div style="background:#fff7e6; border:1px solid #ffe0a3; color:#8a6d3b; border-radius:8px; padding:14px 16px; line-height:1.5; margin-bottom: 25px;">
								<i class="fa fa-info-circle m-r-6"></i>
								Anda perlu <a href="login.php" style="color:#c97b00; font-weight:700;">login</a> terlebih dahulu untuk mengirim keluhan ke admin.
							</div>
						<?php } ?>

						<?php if ($sudah_login) { ?>
						<div style="margin-bottom: 20px;">
							<label style="display:block; font-weight:600; color:#333; margin-bottom:8px; font-size:0.95rem;">Nama</label>
							<input type="text" value="<?php echo htmlspecialchars($nama_saya); ?>" readonly style="width:100%; border: 1px solid #e2e2e2; border-radius: 8px; padding: 12px 15px; background:#f1f1f1; color:#555; font-size:0.95rem;">
						</div>
						<?php } ?>

						<div style="margin-bottom: 20px;">
							<label style="display:block; font-weight:600; color:#333; margin-bottom:8px; font-size:0.95rem;">Kategori</label>
							<select name="kategori" style="width:100%; border: 1px solid #ddd; border-radius: 8px; padding: 12px 15px; background:#fff; color:#333; font-size:0.95rem;" <?php echo $sudah_login ? '' : 'disabled'; ?>>
								<option value="Umum">Keluhan Umum</option>
								<option value="Pembayaran">Masalah Pembayaran</option>
								<option value="Karya / Pesanan">Masalah Karya / Pesanan</option>
								<option value="Akun">Masalah Akun</option>
								<option value="Bidding / Lelang">Masalah Bidding / Lelang</option>
								<option value="Lainnya">Lainnya</option>
							</select>
						</div>

						<div style="margin-bottom: 20px;">
							<label style="display:block; font-weight:600; color:#333; margin-bottom:8px; font-size:0.95rem;">Subjek <span style="color:#999; font-weight:400;">(opsional)</span></label>
							<input type="text" name="subjek" placeholder="Ringkasan singkat keluhan Anda" style="width:100%; border: 1px solid #ddd; border-radius: 8px; padding: 12px 15px; font-size:0.95rem;" <?php echo $sudah_login ? '' : 'disabled'; ?>>
						</div>

						<div style="margin-bottom: 30px;">
							<label style="display:block; font-weight:600; color:#333; margin-bottom:8px; font-size:0.95rem;">Pesan</label>
							<textarea name="pesan" placeholder="Tuliskan keluhan Anda secara jelas..." style="width:100%; border: 1px solid #ddd; border-radius: 8px; padding: 12px 15px; min-height: 130px; font-size:0.95rem; resize:vertical;" <?php echo $sudah_login ? 'required' : 'disabled'; ?>></textarea>
						</div>

						<button type="submit" name="kirim_keluhan" style="width:100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; border: none; border-radius: 8px; padding: 14px 30px; cursor: pointer; font-weight: 600; font-size:1rem; letter-spacing:0.5px;" <?php echo $sudah_login ? '' : 'disabled'; ?>>
							KIRIM KELUHAN
						</button>
					</form>
				</div>

				<div class="bor10 p-lr-15-lg" style="background: #f9f9f9; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex:1 1 360px; max-width:560px; width:auto; padding:55px 50px 70px;">
					<h4 class="mtext-105 cl2" style="font-weight: 700; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 18px; margin-bottom: 30px;">
						Informasi Kontak
					</h4>

					<div style="display:flex; flex-direction:column; gap:26px;">
						<div style="display:flex; align-items:flex-start; gap:16px;">
							<span style="flex:0 0 46px; width:46px; height:46px; border-radius:50%; background:#eef0ff; color:#667eea; display:flex; align-items:center; justify-content:center; font-size:20px;">
								<i class="lnr lnr-map-marker"></i>
							</span>
							<div style="flex:1; min-width:0;">
								<span style="display:block; font-weight:600; color:#333; margin-bottom:6px;">Alamat</span>
								<p style="color:#666; line-height:1.6; margin:0;">
									Jl. Sariasih No.54, Sarijadi, Kec. Sukasari, Kota Bandung, Jawa Barat 40151
								</p>
							</div>
						</div>

						<div style="display:flex; align-items:flex-start; gap:16px;">
							<span style="flex:0 0 46px; width:46px; height:46px; border-radius:50%; background:#eef0ff; color:#667eea; display:flex; align-items:center; justify-content:center; font-size:20px;">
								<i class="lnr lnr-phone-handset"></i>
							</span>
							<div style="flex:1; min-width:0;">
								<span style="display:block; font-weight:600; color:#333; margin-bottom:6px;">No. Telp</span>
								<p style="color:#667eea; font-weight:600; margin:0;">
									+62 881010229410
								</p>
							</div>
						</div>

						<div style="display:flex; align-items:flex-start; gap:16px;">
							<span style="flex:0 0 46px; width:46px; height:46px; border-radius:50%; background:#eef0ff; color:#667eea; display:flex; align-items:center; justify-content:center; font-size:20px;">
								<i class="lnr lnr-envelope"></i>
							</span>
							<div style="flex:1; min-width:0;">
								<span style="display:block; font-weight:600; color:#333; margin-bottom:6px;">Email</span>
								<p style="color:#667eea; font-weight:600; margin:0; word-break:break-word;">
									DensCreative@gmail.com
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>


	<!-- Footer -->
	<footer class="bg3 p-t-75 p-b-32">
		<div class="container">
			<div class="row">
				<div class="col-sm-6 col-lg-3 p-b-50">
					<h4 class="stext-301 cl0 p-b-30">
						Kategori
					</h4>

					<ul>
						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Ilustrasi
							</a>
						</li>

						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Tipografi
							</a>
						</li>

						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Mockup
							</a>
						</li>

						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Ui & Ux
							</a>
						</li>
					</ul>
				</div>

				<div class="col-sm-6 col-lg-3 p-b-50">
					<h4 class="stext-301 cl0 p-b-30">
						Bantuan
					</h4>

					<ul>
						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Fitur Unggulan
							</a>
						</li>

						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Pesanan 
							</a>
						</li>

						<li class="p-b-10">
							<a href="#" class="stext-107 cl7 hov-cl1 trans-04">
								Bantuan
							</a>
						</li>
					</ul>
				</div>

				<div class="col-sm-6 col-lg-3 p-b-50">
					<h4 class="stext-301 cl0 p-b-30">
						Alamat
					</h4>

					<p class="stext-107 cl7 size-201">
						Ada Pertanyaan? Hubungi kami di Jl. Sariasih No.54, Sarijadi, Kec. Sukasari, Kota Bandung, Jawa Barat 40151, (+62) 881010229410

					<div class="p-t-27">
						<a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16">
							<i class="fa fa-instagram"></i>
						</a>

						<a href="#" class="fs-18 cl7 hov-cl1 trans-04 m-r-16">
   							 <i class="fab fa-tiktok"></i>
						</a>
					</div>
				</div>
	
		
			</div>
		</div>
	</footer>


	<!-- Back to top -->
	<div class="btn-back-to-top" id="myBtn">
		<span class="symbol-btn-back-to-top">
			<i class="zmdi zmdi-chevron-up"></i>
		</span>
	</div>

<!--===============================================================================================-->	
	<script src="vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/animsition/js/animsition.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/bootstrap/js/popper.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/select2/select2.min.js"></script>
	<script>
		$(".js-select2").each(function(){
			$(this).select2({
				minimumResultsForSearch: 20,
				dropdownParent: $(this).next('.dropDownSelect2')
			});
		})
	</script>
<!--===============================================================================================-->
	<script src="vendor/MagnificPopup/jquery.magnific-popup.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
	<script>
		$('.js-pscroll').each(function(){
			$(this).css('position','relative');
			$(this).css('overflow','hidden');
			var ps = new PerfectScrollbar(this, {
				wheelSpeed: 1,
				scrollingThreshold: 1000,
				wheelPropagation: false,
			});

			$(window).on('resize', function(){
				ps.update();
			})
		});
	</script>
<!--===============================================================================================-->
	<script src="js/main.js"></script>

</body>
</html>
