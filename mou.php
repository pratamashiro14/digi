<?php
require_once __DIR__ . '/auth.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/mou_helper.php';

// 1. CEK LOGIN DESAINER
// BUKAN require_verified_designer(): MOU harus tetap bisa dibaca & ditandatangani
// walau KTP desainer belum di-ACC admin — persetujuan MOU justru salah satu syarat
// sebelum desainer bisa unggah karya (lihat require_mou_disetujui() di unggahan.php).
require_designer();

$id_desainer = (int) current_id();

// 2. Data profil untuk auto-fill blok PIHAK KEDUA
$stmt = mysqli_prepare($koneksi, "SELECT nama, email, no_telp, alamat FROM t_user WHERE id_user = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id_desainer);
mysqli_stmt_execute($stmt);
$profil = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];

$mou = mou_get($koneksi, $id_desainer);
$sudah_setuju = $mou !== null;
$csrf = mou_csrf_token();

if ($sudah_setuju) {
    $pk_nama     = $mou['nama_mitra'];
    $pk_instansi = $mou['instansi_mitra'];
    $pk_alamat   = $mou['alamat_mitra'];
    $pk_telp     = $mou['telp_mitra'];
    $tanggal_tampil = mou_tanggal_indo($mou['tanggal_setuju']);
} else {
    $pk_nama     = $profil['nama'] ?? '';
    $pk_instansi = '';
    $pk_alamat   = $profil['alamat'] ?? '';
    $pk_telp     = $profil['no_telp'] ?? '';
    $tanggal_tampil = mou_tanggal_indo(date('Y-m-d'));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>MOU - Perjanjian Kerja Sama</title>
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
    <link rel="stylesheet" type="text/css" href="css/mou.css?v=<?php echo filemtime(__DIR__ . '/css/mou.css'); ?>">
</head>
<body class="animsition account-page">

    <?php
    $active_page = 'designer-mou';
    include 'navbar.php';
    ?>

    <div class="container account-shell">
        <main class="designer-full-content">
            <div class="account-page-header">
                <div>
                    <h1>MOU &mdash; Perjanjian Kerja Sama</h1>
                    <p>Perjanjian Kerja Sama Kemitraan Desainer antara Anda dan DENSCREATIVE.</p>
                </div>
                <?php if ($sudah_setuju) { ?>
                <div class="mou-no-print">
                    <button type="button" class="btn-save" onclick="window.print()"><i class="fa fa-print m-r-5"></i> Cetak / Simpan PDF</button>
                </div>
                <?php } ?>
            </div>

            <?php if ($sudah_setuju) { ?>
                <div class="designer-alert is-success mou-status-banner mou-no-print">
                    <div>
                        <strong><i class="fa fa-check-circle m-r-5"></i>MOU sudah Anda setujui</strong>
                        <small>Disetujui pada <?php echo htmlspecialchars($tanggal_tampil); ?> &middot; Nomor <?php echo htmlspecialchars($mou['nomor']); ?></small>
                    </div>
                </div>
            <?php } else { ?>
                <div class="designer-alert is-warning mou-no-print">
                    <strong><i class="fa fa-info-circle m-r-5"></i> Belum Disetujui.</strong>
                    Baca seluruh isi perjanjian di bawah ini, lengkapi data yang diperlukan, lalu tanda tangani secara digital di bagian akhir dokumen. Anda perlu menyetujui MOU ini terlebih dahulu sebelum dapat mengunggah karya / membuat lelang.
                </div>
            <?php } ?>

            <div class="mou-print-area">
            <?php if (!$sudah_setuju) { ?>
            <form action="proses_mou.php" method="POST" id="formMou">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <?php } ?>

                <article class="mou-paper">
                    <div class="mou-kop">
                        <img src="images/icons/dens.png" alt="Logo DENSCREATIVE">
                        <div class="mou-kop-text">
                            <h2>DENSCREATIVE</h2>
                            <p>Program Proyek Integrasi II &mdash; Universitas Logistik dan Bisnis Internasional</p>
                            <p><?php echo htmlspecialchars(MOU_PIHAK1_ALAMAT); ?></p>
                            <p>Email: <?php echo htmlspecialchars(MOU_PIHAK1_EMAIL); ?></p>
                        </div>
                    </div>

                    <div class="mou-meta">
                        <span>Bandung, <?php echo htmlspecialchars($tanggal_tampil); ?></span>
                        <span>Nomor: <?php echo htmlspecialchars(MOU_NOMOR); ?></span>
                    </div>
                    <p>Perihal: <strong>Perjanjian Kerja Sama Mitra Desainer</strong></p>

                    <h1 class="mou-title">PERJANJIAN KERJA SAMA KEMITRAAN DESAINER</h1>

                    <p>Pada hari ini, tanggal <?php echo htmlspecialchars($tanggal_tampil); ?>, telah dibuat Perjanjian Kerja Sama antara:</p>

                    <!-- PIHAK PERTAMA -->
                    <dl class="mou-pihak">
                        <dt>Nama</dt><dd><?php echo htmlspecialchars(MOU_PIHAK1_NAMA); ?></dd>
                        <dt>Penanggung Jawab</dt><dd><?php echo htmlspecialchars(MOU_PIHAK1_PJ); ?></dd>
                        <dt>Instansi</dt><dd><?php echo htmlspecialchars(MOU_PIHAK1_INSTANSI); ?></dd>
                        <dt>Alamat</dt><dd><?php echo htmlspecialchars(MOU_PIHAK1_ALAMAT); ?></dd>
                        <dt>No. Telepon</dt><dd><?php echo htmlspecialchars(MOU_PIHAK1_TELP); ?></dd>
                    </dl>
                    <p>Dalam hal ini bertindak untuk dan atas nama <strong><?php echo htmlspecialchars(MOU_PIHAK1_NAMA); ?></strong>, selanjutnya disebut sebagai <strong>PIHAK PERTAMA</strong>.</p>

                    <!-- PIHAK KEDUA -->
                    <?php if ($sudah_setuju) { ?>
                        <dl class="mou-pihak">
                            <dt>Nama</dt><dd><?php echo htmlspecialchars($pk_nama); ?></dd>
                            <dt>Penanggung Jawab</dt><dd><?php echo htmlspecialchars($mou['pj_mitra']); ?></dd>
                            <dt>Instansi</dt><dd><?php echo htmlspecialchars($pk_instansi !== '' ? $pk_instansi : '-'); ?></dd>
                            <dt>Alamat</dt><dd><?php echo nl2br(htmlspecialchars($pk_alamat)); ?></dd>
                            <dt>No. Telepon</dt><dd><?php echo htmlspecialchars($pk_telp); ?></dd>
                        </dl>
                    <?php } else { ?>
                        <div class="mou-pihak-form">
                            <label for="fNamaMitra">Nama Lengkap</label>
                            <div>
                                <input type="text" name="nama_mitra" id="fNamaMitra" class="custom-input" value="<?php echo htmlspecialchars($pk_nama); ?>" maxlength="150" required>
                                <small>Sekaligus bertindak sebagai Penanggung Jawab.</small>
                            </div>

                            <label for="fInstansiMitra">Instansi</label>
                            <input type="text" name="instansi_mitra" id="fInstansiMitra" class="custom-input" placeholder='Isi "-" bila tidak ada' maxlength="150">

                            <label for="fAlamatMitra">Alamat</label>
                            <textarea name="alamat_mitra" id="fAlamatMitra" class="custom-input" maxlength="500" required><?php echo htmlspecialchars($pk_alamat); ?></textarea>

                            <label for="fTelpMitra">No. Telepon</label>
                            <input type="text" name="telp_mitra" id="fTelpMitra" class="custom-input" value="<?php echo htmlspecialchars($pk_telp); ?>" maxlength="30" required>
                        </div>
                    <?php } ?>
                    <p>Dalam hal ini bertindak untuk dan atas nama <strong><?php echo $pk_nama !== '' ? htmlspecialchars($pk_nama) : '(sesuai data di atas)'; ?></strong>, selanjutnya disebut sebagai <strong>PIHAK KEDUA</strong>.</p>

                    <p>PIHAK PERTAMA dan PIHAK KEDUA secara bersama-sama selanjutnya disebut sebagai <strong>PARA PIHAK</strong>, sepakat untuk mengadakan kerja sama dalam penggunaan platform marketplace karya desain digital DENSCREATIVE dengan ketentuan sebagai berikut:</p>

                    <!-- PASAL 1 -->
                    <div class="mou-pasal">
                        <h3>Pasal 1</h3>
                        <h4>Maksud dan Tujuan</h4>
                        <p>Kerja sama ini bertujuan memfasilitasi pemasaran, penjualan, dan pelelangan (<em>bidding</em>) karya desain digital milik PIHAK KEDUA melalui <em>website</em> DENSCREATIVE milik PIHAK PERTAMA, guna memperluas jangkauan pasar dan menjamin keamanan transaksi karya desain kreatif.</p>
                    </div>

                    <!-- PASAL 2 -->
                    <div class="mou-pasal">
                        <h3>Pasal 2</h3>
                        <h4>Ruang Lingkup Kerja Sama</h4>
                        <p>Ruang lingkup kerja sama ini meliputi:</p>
                        <ol>
                            <li>PIHAK PERTAMA menyediakan platform <em>website</em> DENSCREATIVE yang dapat digunakan oleh PIHAK KEDUA untuk mengunggah dan memamerkan karya.</li>
                            <li>PIHAK KEDUA menyerahkan data digital berupa file desain, deskripsi karya, dan menetapkan harga awal (untuk lelang) atau harga pasti (untuk penjualan langsung).</li>
                            <li>Transaksi pembayaran sepenuhnya dilakukan melalui gerbang pembayaran (<em>payment gateway</em>) yang terintegrasi di DENSCREATIVE, dan pendapatan bersih akan disalurkan ke saldo dompet PIHAK KEDUA.</li>
                            <li>PIHAK KEDUA dapat menggunakan layanan fitur premium berbayar agar karya desainnya tampil pada posisi prioritas pencarian.</li>
                        </ol>
                    </div>

                    <!-- PASAL 3 -->
                    <div class="mou-pasal">
                        <h3>Pasal 3</h3>
                        <h4>Hak dan Kewajiban</h4>
                        <p><strong>Hak dan Kewajiban PIHAK PERTAMA</strong></p>
                        <ol>
                            <li>Menyediakan infrastruktur sistem DENSCREATIVE yang aman untuk transaksi jual-beli dan lelang.</li>
                            <li>Melakukan promosi platform secara berkala untuk mendatangkan calon pembeli.</li>
                            <li>Berhak melakukan moderasi, menolak, atau menghapus konten karya PIHAK KEDUA apabila terindikasi plagiarisme, mengandung SARA, atau melanggar hukum.</li>
                            <li>Berkewajiban mencairkan dana penghasilan (<em>withdraw</em>) ke rekening PIHAK KEDUA sesuai permintaan, setelah dipotong biaya administrasi yang disepakati.</li>
                        </ol>
                        <p><strong>Hak dan Kewajiban PIHAK KEDUA</strong></p>
                        <ol>
                            <li>Berkewajiban menjamin bahwa seluruh karya desain yang diunggah adalah karya orisinil (asli) buatan sendiri serta bukan atas hasil plagiasi atau pelanggaran hak cipta pihak lain.</li>
                            <li>Bertanggung jawab penuh atas kualitas file desain yang dikirimkan kepada pembeli.</li>
                            <li>Berhak mendapatkan laporan riwayat penjualan dan saldo pendapatan secara transparan melalui <em>dashboard</em> sistem.</li>
                            <li>Bersedia dikenakan potongan biaya layanan (komisi admin) dari setiap transaksi berhasil, sesuai ketentuan yang berlaku di platform DENSCREATIVE.</li>
                        </ol>
                    </div>

                    <!-- PASAL 4 -->
                    <div class="mou-pasal">
                        <h3>Pasal 4</h3>
                        <h4>Masa Berlaku dan Pemutusan Kerja Sama</h4>
                        <p>Perjanjian kerja sama ini berlaku sejak tanggal ditandatanganinya surat ini sampai dengan adanya pemutusan kerja sama oleh salah satu pihak yang diajukan secara tertulis melalui surat elektronik resmi.</p>
                    </div>

                    <!-- PASAL 5 -->
                    <div class="mou-pasal">
                        <h3>Pasal 5</h3>
                        <h4>Penyelesaian</h4>
                        <p>Perjanjian ini dibuat dalam keadaan sadar tanpa adanya paksaan dari pihak mana pun.</p>
                        <p>Dengan ditandatanganinya dokumen ini, PARA PIHAK menyatakan telah membaca, memahami, dan menyetujui seluruh isi perjanjian kerja sama.</p>
                    </div>

                    <?php if (!$sudah_setuju) { ?>
                        <div class="mou-checkbox-row mou-no-print">
                            <input type="checkbox" name="setuju" id="chkSetuju" value="1" required>
                            <label for="chkSetuju" style="margin:0; font-weight:500;">Saya telah membaca, memahami, dan menyetujui seluruh isi Perjanjian Kerja Sama Kemitraan Desainer ini.</label>
                        </div>
                        <div class="row mou-no-print" style="margin:0 0 20px;">
                            <div class="col-md-6">
                                <label class="stext-102 cl3 p-b-5">Ketik nama lengkap Anda sebagai tanda tangan digital</label>
                                <input type="text" name="nama_ttd" id="inputTtd" class="custom-input mou-ttd-input" placeholder="Nama lengkap sesuai KTP" maxlength="150" required>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- BLOK TANDA TANGAN -->
                    <div class="mou-signoff">
                        <div class="mou-signblock">
                            <div class="label">Pihak Pertama</div>
                            <div class="signature-space">
                                <span class="signature-certified">VERIFIED</span>
                                <p class="signed-name"><?php echo htmlspecialchars(MOU_PIHAK1_PJ); ?></p>
                            </div>
                            <p class="signed-meta">Ditandatangani digital &middot; <?php echo htmlspecialchars($tanggal_tampil); ?></p>
                        </div>
                        <div class="mou-signblock">
                            <div class="label">Pihak Kedua</div>
                            <?php if ($sudah_setuju) { ?>
                                <div class="signature-space">
                                    <span class="signature-certified">VERIFIED</span>
                                    <p class="signed-name"><?php echo htmlspecialchars($mou['nama_ttd']); ?></p>
                                </div>
                                <p class="signed-meta">Ditandatangani digital &middot; <?php echo htmlspecialchars($tanggal_tampil); ?></p>
                            <?php } else { ?>
                                <div class="signature-space is-blank">
                                    <div class="signature-line"></div>
                                    <p style="font-size:12px; color:#9ca3af; margin: 0;">(DESAINER / MITRA)</p>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                </article>

            <?php if (!$sudah_setuju) { ?>
                <div class="mou-actions mou-no-print">
                    <button type="submit" class="btn-save" id="btnSubmitMou" disabled><i class="fa fa-pencil m-r-5"></i> Setuju &amp; Tanda Tangani</button>
                </div>
            </form>
            <?php } ?>
            </div><!-- /.mou-print-area -->

        </main>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
    <?php if (!$sudah_setuju) { ?>
    <script>
        (function () {
            var chk = document.getElementById('chkSetuju');
            var ttd = document.getElementById('inputTtd');
            var btn = document.getElementById('btnSubmitMou');
            function sync() {
                btn.disabled = !(chk.checked && ttd.value.trim().length > 0);
            }
            if (chk && ttd && btn) {
                chk.addEventListener('change', sync);
                ttd.addEventListener('input', sync);
                sync();
            }
        })();
    </script>
    <?php } ?>
</body>
</html>
