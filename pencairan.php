<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/designer_layout.php';
include 'admin/koneksi.php';
require_once __DIR__ . '/keuangan_helper.php';

// 1. CEK LOGIN DESAINER
require_designer();

$id_desainer = (int) current_id();

$view = $_GET['view'] ?? 'withdraw';
if (!in_array($view, ['withdraw', 'history'], true)) {
    $view = 'withdraw';
}

// 2. Ringkasan saldo
$saldo = designer_saldo($koneksi, $id_desainer);

// 3. Riwayat pencairan
$riwayat = [];
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM t_pencairan WHERE id_designer = ? ORDER BY id_pencairan DESC"
);
mysqli_stmt_bind_param($stmt, 'i', $id_desainer);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) {
    $riwayat[] = $r;
}

// Label & warna badge status pencairan
function pencairan_badge($status) {
    $map = [
        'pending'  => ['Menunggu', '#b58105', '#fff4d6'],
        'diproses' => ['Diproses', '#1565c0', '#e3f0ff'],
        'selesai'  => ['Selesai',  '#1b7d3f', '#dff5e6'],
        'ditolak'  => ['Ditolak',  '#c62828', '#fde4e4'],
    ];
    $c = $map[$status] ?? ['-', '#555', '#eee'];
    return '<span style="display:inline-block;padding:4px 12px;border-radius:50px;font-size:12px;font-weight:700;color:' . $c[1] . ';background:' . $c[2] . ';">' . $c[0] . '</span>';
}

$bisa_tarik = $saldo['tersedia'] >= MIN_PENARIKAN;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pencairan Dana</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="images/icons/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/account-ui.css">

    <style>
        body { background-color: #fff; font-family: 'Poppins', sans-serif; }

        .balance-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 26px; }
        .balance-card { border: 1px solid #e0e0e0; border-radius: 10px; padding: 22px; }
        .balance-card .lbl { font-size: 13px; color: #888; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
        .balance-card .val { font-size: 24px; font-weight: 800; color: #222; }
        .balance-card.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .balance-card.primary .lbl { color: #e7e2ff; }
        .balance-card.primary .val { color: #fff; }

        .panel { border: 1px solid #e0e0e0; border-radius: 10px; padding: 28px; }
        .panel h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #333; }

        .withdraw-cta { display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;
            border:1px solid #e6e6f0; border-radius:12px; padding:22px 26px; margin-bottom:30px;
            background:linear-gradient(135deg,#f7f8ff,#fdfbff); }
        .withdraw-cta .desc small { color:#888; }
        .btn-cair { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; border-radius: 10px; padding: 13px 26px; font-weight: 600; box-shadow:0 6px 18px rgba(118,75,162,.28); }
        .btn-cair:disabled { opacity: .55; cursor: not-allowed; box-shadow:none; }

        .table-cair { width: 100%; }
        .table-cair th { font-size: 13px; text-transform: uppercase; color: #555; font-weight: 800; padding: 12px 10px; border-bottom: 2px solid #eee; }
        .table-cair td { font-size: 14px; color: #555; padding: 14px 10px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        .note-info { font-size: 12px; color: #999; margin-top: 6px; }
        @media (max-width: 768px){ .balance-grid { grid-template-columns: 1fr; } }

        /* ===== MODAL PENCAIRAN ===== */
        .cair-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px);
            z-index: 99990; display: none; align-items: center; justify-content: center; padding: 20px; }
        .cair-overlay.show { display: flex; animation: cairFade .3s ease-out forwards; }
        @keyframes cairFade { from { opacity: 0; backdrop-filter: blur(0px); } to { opacity: 1; backdrop-filter: blur(8px); } }
        .cair-modal { background: #fff; width: 100%; max-width: 520px; border-radius: 20px; overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: cairPop .4s cubic-bezier(0.16, 1, 0.3, 1) forwards; transform: scale(0.95); opacity: 0; max-height: 94vh; display: flex; flex-direction: column; }
        .cair-overlay.show .cair-modal { transform: scale(1); opacity: 1; }
        @keyframes cairPop { from { transform: translateY(30px) scale(.95); opacity:0; } to { transform: translateY(0) scale(1); opacity:1; } }
        
        .cair-modal-head {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #fff;
            padding: 16px 22px 14px;
            position: relative;
            text-align: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .cair-modal-head::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 60%);
            animation: pulse-glow 4s infinite alternate;
        }
        @keyframes pulse-glow { 0% { transform: scale(1); } 100% { transform: scale(1.1); } }
        .cair-modal-head .illus { width: 54px; height: 54px; object-fit: cover; border-radius: 50%; border: 3px solid rgba(255,255,255,0.3); box-shadow: 0 6px 16px rgba(0,0,0,0.2); margin-bottom: 7px; position: relative; z-index: 2; transition: transform 0.3s ease; }
        .cair-modal-head .illus:hover { transform: scale(1.05) rotate(5deg); }
        .cair-modal-head h3 { margin:0; font-size:17px; font-weight:800; position: relative; z-index: 2; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .cair-modal-head p { margin:3px 0 0; font-size:12px; color:#e0e7ff; position: relative; z-index: 2; font-weight: 500; }
        .cair-modal-head .x { position:absolute; top:14px; right:14px; color:#fff; background:rgba(255,255,255,.15); border:none;
            width:32px; height:32px; border-radius:50%; font-size:18px; cursor:pointer; line-height:1; z-index: 10; transition: all 0.2s ease; display:flex; align-items:center; justify-content:center; backdrop-filter: blur(4px); }
        .cair-modal-head .x:hover { background:rgba(255,255,255,.3); transform: rotate(90deg); }

        .cair-modal-body { padding: 20px 24px; background: #fff; overflow-y: auto; }
        .cair-modal-body label { font-size:12px; font-weight:700; color:#334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .cair-modal-body .form-group { margin-bottom: 14px; }
        .cair-modal-body .form-control { border-radius:12px; padding: 12px 16px; border: 2px solid #e2e8f0; height: auto; font-size: 14px; font-weight: 500; color: #1e293b; transition: all 0.3s ease; }
        .cair-modal-body .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); outline: none; }
        
        .fee-box { background: linear-gradient(to right, #f8fafc, #f1f5f9); border: 1.5px dashed #cbd5e1; border-radius: 11px; padding: 10px 14px; margin-top: 6px; font-size: 10.5px; }
        .fee-row { display:flex; justify-content:space-between; padding:3px 0; color:#475569; font-weight: 500; }
        .fee-row.total { border-top:1.5px dashed #cbd5e1; margin-top:6px; padding-top:8px; font-weight:800; color:#0f172a; font-size:13px; }

        .btn-submit-cair { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff; border: none; border-radius: 12px; padding: 14px; font-size: 15px; font-weight: 700; width: 100%; margin-top: 16px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4); display: flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-submit-cair:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(99, 102, 241, 0.5); }
        .btn-submit-cair:active { transform: translateY(0); }

        /* Samakan tinggi select2 dengan input bootstrap .form-control */
        .cair-modal-body .select2-container .select2-selection--single { height: 48px; border:2px solid #e2e8f0; border-radius:12px; display: flex; align-items: center; transition: all 0.3s ease; }
        .cair-modal-body .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 44px; padding-left:16px; color:#1e293b; font-weight: 500; font-size: 14px; }
        .cair-modal-body .select2-container--default .select2-selection--single .select2-selection__arrow { height: 44px; right: 8px; }
        .cair-modal-body .select2-container--default .select2-selection--single .select2-selection__placeholder { color:#94a3b8; }
        .select2-dropdown { border: 2px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); padding: 8px; z-index: 100001; }
        /* Dropdown di-append ke body; pastikan tampil di atas overlay modal (z-index 99990) */
        .select2-container--open { z-index: 100001; }
        .select2-results__option { border-radius: 8px; padding: 10px 16px; margin-bottom: 2px; font-size: 14px; font-weight: 500; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #6366f1; color: white; }
    </style>
</head>
<body class="animsition account-page">

    <?php
    $active_page = 'designer-finance';
    include 'navbar.php';
    ?>

    <div class="container account-shell">
        <div class="designer-layout-grid">
            <?php render_designer_section_sidebar('finance', $view); ?>

            <main class="designer-section-content">
                <div class="account-page-header">
                    <div>
                        <h1><?php echo $view === 'history' ? 'Riwayat Pencairan' : 'Pencairan Dana'; ?></h1>
                        <p>Dana penjualan ditahan platform dan bisa dicairkan kapan saja ke rekening Anda.</p>
                    </div>
                </div>

                <!-- Kartu saldo -->
                <div class="balance-grid">
                    <div class="balance-card primary">
                        <div class="lbl">Saldo Tersedia</div>
                        <div class="val"><?php echo rupiah($saldo['tersedia']); ?></div>
                    </div>
                    <div class="balance-card">
                        <div class="lbl">Total Penjualan (Berhasil)</div>
                        <div class="val"><?php echo rupiah($saldo['kotor']); ?></div>
                    </div>
                    <div class="balance-card">
                        <div class="lbl">Sedang/Sudah Dicairkan</div>
                        <div class="val"><?php echo rupiah($saldo['dicairkan']); ?></div>
                    </div>
                </div>

                <?php if ($view !== 'history') { ?>
                <!-- CTA buka modal pencairan -->
                <div class="withdraw-cta">
                    <div class="desc">
                        <div style="font-weight:700; color:#333; font-size:16px;">Mau cairkan saldo?</div>
                        <small>Tanpa potongan biaya admin &middot; dana diterima penuh &middot; minimal <?php echo rupiah(MIN_PENARIKAN); ?>.</small>
                    </div>
                    <?php if ($bisa_tarik) { ?>
                        <button type="button" class="btn-cair" onclick="bukaModalCair()"><i class="fa fa-paper-plane"></i> Ajukan Pencairan</button>
                    <?php } else { ?>
                        <button type="button" class="btn-cair" disabled>Saldo belum mencukupi (min. <?php echo rupiah(MIN_PENARIKAN); ?>)</button>
                    <?php } ?>
                </div>
                <?php } ?>

                <!-- Riwayat pencairan -->
                <div class="panel">
                    <h3>Riwayat Pencairan</h3>
                    <div class="table-responsive-account">
                        <table class="table-cair">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tujuan</th>
                                    <th>Nominal Diterima</th>
                                    <th>Status</th>
                                    <th>Catatan Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($riwayat) === 0) { ?>
                                    <tr><td colspan="5" style="text-align:center; padding:30px; color:#999;">Belum ada pengajuan pencairan.</td></tr>
                                <?php } else {
                                    foreach ($riwayat as $r) {
                                        // Dana diterima: untuk data lama yang sempat terpotong fee, tetap pakai jumlah_diterima.
                                        $diterima = ($r['jumlah_diterima'] > 0) ? $r['jumlah_diterima'] : $r['jumlah']; ?>
                                    <tr>
                                        <td><?php echo date('d M Y H:i', strtotime($r['tanggal_request'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($r['bank']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($r['no_rekening']); ?> &middot; <?php echo htmlspecialchars($r['nama_pemilik_rek']); ?></small>
                                        </td>
                                        <td style="font-weight:700; color:#1b7d3f;"><?php echo rupiah($diterima); ?></td>
                                        <td><?php echo pencairan_badge($r['status']); ?></td>
                                        <td><?php echo $r['catatan_admin'] ? htmlspecialchars($r['catatan_admin']) : '<span style="color:#bbb;">-</span>'; ?></td>
                                    </tr>
                                <?php } } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <?php if ($view !== 'history' && $bisa_tarik) { ?>
    <!-- ===== MODAL AJUKAN PENCAIRAN ===== -->
    <div class="cair-overlay" id="cairOverlay">
        <div class="cair-modal">
            <div class="cair-modal-head">
                <img src="images/wallet_illustration.png" alt="Wallet Illustration" class="illus">
                <h3>Ajukan Pencairan</h3>
                <p>Saldo tersedia: <?php echo rupiah($saldo['tersedia']); ?></p>
                <button type="button" class="x" onclick="tutupModalCair()" aria-label="Tutup"><i class="zmdi zmdi-close"></i></button>
            </div>
            <div class="cair-modal-body">
                <form action="proses_pencairan.php" method="POST" id="formCair">
                    <div class="form-row">
                        <div class="col-12 col-md-6 form-group">
                            <label>Bank / E-Wallet</label>
                            <select name="bank" id="selectBank" class="form-control">
                                <option value="" disabled selected>Pilih bank / e-wallet</option>
                                <optgroup label="Bank BUMN & Nasional">
                                    <option value="Bank BCA">Bank Central Asia (BCA)</option>
                                    <option value="Bank Mandiri">Bank Mandiri</option>
                                    <option value="Bank BRI">Bank Rakyat Indonesia (BRI)</option>
                                    <option value="Bank BNI">Bank Negara Indonesia (BNI)</option>
                                    <option value="Bank BTN">Bank Tabungan Negara (BTN)</option>
                                    <option value="Bank BSI">Bank Syariah Indonesia (BSI)</option>
                                    <option value="Bank CIMB Niaga">CIMB Niaga</option>
                                    <option value="Bank Danamon">Bank Danamon</option>
                                    <option value="Bank Permata">Bank Permata</option>
                                    <option value="Bank OCBC NISP">OCBC NISP</option>
                                    <option value="Bank Panin">Bank Panin</option>
                                    <option value="Bank Maybank">Maybank Indonesia</option>
                                    <option value="Bank Mega">Bank Mega</option>
                                    <option value="Bank BTPN">Bank BTPN</option>
                                    <option value="Bank Sinarmas">Bank Sinarmas</option>
                                    <option value="Bank Muamalat">Bank Muamalat</option>
                                    <option value="Bank KB Bukopin">KB Bukopin</option>
                                    <option value="Bank DBS Indonesia">DBS Indonesia</option>
                                    <option value="Bank UOB Indonesia">UOB Indonesia</option>
                                    <option value="Bank HSBC Indonesia">HSBC Indonesia</option>
                                    <option value="Bank Commonwealth">Commonwealth</option>
                                    <option value="Citibank">Citibank Indonesia</option>
                                    <option value="Standard Chartered">Standard Chartered</option>
                                </optgroup>
                                <optgroup label="Bank Pembangunan Daerah (BPD)">
                                    <option value="Bank BJB">Bank BJB (Jabar Banten)</option>
                                    <option value="Bank DKI">Bank DKI</option>
                                    <option value="Bank Jatim">Bank Jatim</option>
                                    <option value="Bank Jateng">Bank Jateng</option>
                                    <option value="Bank Jogja (BPD DIY)">BPD DIY</option>
                                    <option value="Bank Sumut">Bank Sumut</option>
                                    <option value="Bank Nagari (Sumbar)">Bank Nagari</option>
                                    <option value="Bank Kalbar">Bank Kalbar</option>
                                    <option value="Bank Kaltimtara">Bank Kaltimtara</option>
                                    <option value="Bank Sulselbar">Bank Sulselbar</option>
                                    <option value="Bank Bali (BPD Bali)">BPD Bali</option>
                                    <option value="Bank Riau Kepri">Bank Riau Kepri</option>
                                    <option value="Bank Sumselbabel">Bank Sumselbabel</option>
                                </optgroup>
                                <optgroup label="Bank Digital">
                                    <option value="Bank Jago">Bank Jago</option>
                                    <option value="blu by BCA Digital">blu by BCA Digital</option>
                                    <option value="Allo Bank">Allo Bank</option>
                                    <option value="SeaBank">SeaBank</option>
                                    <option value="Bank Neo Commerce">Bank Neo Commerce (Neobank)</option>
                                    <option value="Jenius (BTPN)">Jenius</option>
                                    <option value="Bank Raya">Bank Raya</option>
                                    <option value="LINE Bank">LINE Bank</option>
                                    <option value="Superbank">Superbank</option>
                                    <option value="Krom Bank">Krom Bank</option>
                                    <option value="Bank Aladin Syariah">Bank Aladin Syariah</option>
                                    <option value="digibank by DBS">digibank by DBS</option>
                                    <option value="TMRW by UOB">TMRW by UOB</option>
                                </optgroup>
                                <optgroup label="E-Wallet">
                                    <option value="GoPay">GoPay</option>
                                    <option value="OVO">OVO</option>
                                    <option value="DANA">DANA</option>
                                    <option value="ShopeePay">ShopeePay</option>
                                    <option value="LinkAja">LinkAja</option>
                                    <option value="AstraPay">AstraPay</option>
                                    <option value="DOKU Wallet">DOKU</option>
                                    <option value="iSaku">iSaku</option>
                                    <option value="Sakuku">Sakuku</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 form-group">
                            <label>Nomor Rekening / E-Wallet</label>
                            <input type="text" name="no_rekening" class="form-control" placeholder="Contoh: 1234567890" required>
                        </div>
                        <div class="col-12 form-group">
                            <label>Nama Pemilik Rekening</label>
                            <input type="text" name="nama_pemilik_rek" class="form-control" value="<?php echo htmlspecialchars(current_name()); ?>" required>
                        </div>
                        <div class="col-12 form-group">
                            <label>Nominal Penarikan (Rp)</label>
                            <input type="number" name="jumlah" id="inputNominal" class="form-control" min="<?php echo (int) MIN_PENARIKAN; ?>" max="<?php echo (int) $saldo['tersedia']; ?>" placeholder="Min. <?php echo number_format(MIN_PENARIKAN, 0, ',', '.'); ?>" required oninput="hitungFee()">
                            <div class="note-info">Maksimal <?php echo rupiah($saldo['tersedia']); ?></div>
                        </div>
                    </div>

                    <div class="fee-box">
                        <div class="fee-row total"><span>Dana diterima</span><span id="feeDiterima">Rp 0</span></div>
                        <div class="fee-row" style="padding-top:6px;"><span style="font-size:11px; color:#64748b;">Tanpa potongan biaya admin — diterima penuh.</span></div>
                    </div>

                    <button type="submit" class="btn-submit-cair mt-3"><i class="fa fa-paper-plane"></i> Kirim Pengajuan</button>
                </form>
            </div>
        </div>
    </div>
    <?php } ?>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/select2/select2.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Inisialisasi select2 untuk pilihan bank/e-wallet (di dalam modal).
        $(function () {
            if ($('#selectBank').length) {
                $('#selectBank').select2({
                    placeholder: 'Pilih bank / e-wallet',
                    width: '100%'
                });
            }
        });

        var FEE_PERSEN = <?php echo (float) FEE_PERSEN; ?>;
        var SALDO_TERSEDIA = <?php echo (int) $saldo['tersedia']; ?>;

        function rupiahJS(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(n))); }

        function bukaModalCair() {
            var o = document.getElementById('cairOverlay');
            if (o) { o.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }
        function tutupModalCair() {
            var o = document.getElementById('cairOverlay');
            if (o) { o.classList.remove('show'); document.body.style.overflow = ''; }
        }
        function hitungFee() {
            // Tanpa potongan biaya admin: dana diterima = nominal yang ditarik.
            var n = parseInt(document.getElementById('inputNominal').value || '0', 10);
            if (isNaN(n) || n < 0) n = 0;
            document.getElementById('feeDiterima').textContent = rupiahJS(n);
        }

        // Tutup modal saat klik area gelap / tombol Esc
        document.addEventListener('click', function(e){
            var o = document.getElementById('cairOverlay');
            if (o && e.target === o) tutupModalCair();
        });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') tutupModalCair(); });

        // Validasi nominal sebelum submit
        var formCair = document.getElementById('formCair');
        if (formCair) {
            formCair.addEventListener('submit', function(e){
                var bank = (document.getElementById('selectBank').value || '').trim();
                var n = parseInt(document.getElementById('inputNominal').value || '0', 10);
                if (bank === '') {
                    e.preventDefault();
                    alert('Silakan pilih bank / e-wallet tujuan terlebih dahulu.');
                    $('#selectBank').select2('open');
                } else if (isNaN(n) || n < <?php echo (int) MIN_PENARIKAN; ?>) {
                    e.preventDefault();
                    alert('Nominal minimal <?php echo rupiah(MIN_PENARIKAN); ?>.');
                } else if (n > SALDO_TERSEDIA) {
                    e.preventDefault();
                    alert('Nominal melebihi saldo tersedia.');
                }
            });
        }
    </script>

</body>
</html>
