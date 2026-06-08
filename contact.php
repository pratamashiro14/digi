<?php 
session_start(); 
include 'admin/koneksi.php'; 

// Cek Login
$is_designer_logged_in = isset($_SESSION['status_designer']) && $_SESSION['status_designer'] == "login";
$nama_desainer = isset($_SESSION['nama_desainer']) ? $_SESSION['nama_desainer'] : 'Desainer';

$is_user_logged_in = isset($_SESSION['status']) && $_SESSION['status'] == "login";
$nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User';

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
						<a href="shoping-cart.php" class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3 p-lr-15 trans-04 m-b-10">Check Out</a>>
								1 x $17.00
							</span>
						</div>
					</li>
				</ul>
				
				<div class="w-full">
					<div class="header-cart-total w-full p-tb-40">
						Total: $75.00
					</div>

					<div class="header-cart-buttons flex-w w-full">
						<a href="shoping-cart.html" class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3 p-lr-15 trans-04 m-r-8 m-b-10">
							View Cart
						</a>

						<a href="shoping-cart.html" class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3 p-lr-15 trans-04 m-b-10">
							Check Out
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>


	<!-- Title page -->
	<section class="bg-img1 txt-center p-lr-15 p-tb-92" style="background-color: #f5f5f5;">
		<div class="container">
			<div style="text-align: center; margin-bottom: 40px;">
				<h1 style="font-size: 4rem; font-weight: 700; margin: 30px 0; color: #222;">Hubungi Kami</h1>
				
				<!-- Team Photos -->
				<div style="display: flex; justify-content: center; gap: 30px; align-items: center; flex-wrap: wrap; margin: 30px 0;">
					<div style="text-align: center;">
						<img src="images/hubunginkami-02.png" alt="Team Member 1" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #e74c3c; display: block;">
						<p style="margin-top: 15px; font-weight: 600; color: #333;">Tim Kami</p>
					</div>
					<div style="text-align: center;">
						<img src="images/hubunginkami-01.png" alt="Team Member 2" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #e74c3c; display: block;">
						<p style="margin-top: 15px; font-weight: 600; color: #333;">Tim Kami</p>
					</div>
				</div>
			</div>
		</div>
	</section>	


	<!-- Content page -->
	<section class="bg0 p-t-104 p-b-116">
		<div class="container">
			<div class="flex-w flex-tr">
				<div class="size-210 bor10 p-lr-70 p-t-55 p-b-70 p-lr-15-lg w-full-md" style="background: #f9f9f9; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
					<form>
						<h4 class="mtext-105 cl2 txt-center p-b-30" style="font-weight: 700; color: #333;">
							Kirimkan Pesan
						</h4>

						<div class="bor8 m-b-20 how-pos4-parent">
							<input class="stext-111 cl2 plh3 size-116 p-l-62 p-r-30" type="text" name="email" placeholder="Email Anda" style="border: 1px solid #ddd; border-radius: 8px; padding: 12px 15px;">
							<img class="how-pos4 pointer-none" src="images/icons/icon-email.png" alt="ICON">
						</div>

						<div class="bor8 m-b-30">
							<textarea class="stext-111 cl2 plh3 size-120 p-lr-28 p-tb-25" name="msg" placeholder="Ada yang bisa dibantu?" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; min-height: 120px;"></textarea>
						</div>

						<button class="flex-c-m stext-101 cl0 size-121 bg3 bor1 hov-btn3 p-lr-15 trans-04 pointer" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 8px; padding: 12px 30px; cursor: pointer; font-weight: 600;">
							KIRIM
						</button>
					</form>
				</div>

				<div class="size-210 bor10 flex-w flex-col-m p-lr-93 p-tb-30 p-lr-15-lg w-full-md" style="background: #f9f9f9; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
					<h4 class="mtext-105 cl2 p-b-30 w-full" style="font-weight: 700; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 20px;">
						Informasi Kontak
					</h4>

					<div class="flex-w w-full p-b-42">
						<span class="fs-18 cl5 txt-center size-211" style="color: #667eea; font-size: 24px;">
							<i class="lnr lnr-map-marker"></i>
						</span>

						<div class="size-212 p-t-2">
							<span class="mtext-110 cl2" style="font-weight: 600; color: #333;">
								Alamat
							</span>

							<p class="stext-115 cl6 size-213 p-t-18" style="color: #666; line-height: 1.6;">
								Jl. Sariasih No.54, Sarijadi, Kec. Sukasari, Kota Bandung, Jawa Barat 40151
							</p>
						</div>
					</div>

					<div class="flex-w w-full p-b-42">
						<span class="fs-18 cl5 txt-center size-211" style="color: #667eea; font-size: 24px;">
							<i class="lnr lnr-phone-handset"></i>
						</span>

						<div class="size-212 p-t-2">
							<span class="mtext-110 cl2" style="font-weight: 600; color: #333;">
								No. Telp
							</span>

							<p class="stext-115 cl1 size-213 p-t-18" style="color: #667eea; font-weight: 600;">
								+62 881010229410
							</p>
						</div>
					</div>

					<div class="flex-w w-full">
						<span class="fs-18 cl5 txt-center size-211" style="color: #667eea; font-size: 24px;">
							<i class="lnr lnr-envelope"></i>
						</span>

						<div class="size-212 p-t-2">
							<span class="mtext-110 cl2" style="font-weight: 600; color: #333;">
								Email
							</span>

							<p class="stext-115 cl1 size-213 p-t-18" style="color: #667eea; font-weight: 600;">
								Digidesain@gmail.com
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>	
	
	
	<!-- Map -->
	<div class="map">
		<div class="size-303" id="google_map" data-map-x="-6.8743050039142375" data-map-y="107.57572125303169" data-pin="images/icons/pin.png?v=<?php echo time(); ?>" data-scrollwhell="0" data-draggable="1" data-zoom="11"></div>
	</div>



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
	<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAKFWBqlKAGCeS1rMVoaNlwyayu0e0YRes"></script>
	<script src="js/map-custom.js"></script>
<!--===============================================================================================-->
	<script src="js/main.js"></script>

</body>
</html>