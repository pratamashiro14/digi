<?php
require_once __DIR__ . '/../admin_guard.php';
include "../koneksi.php";

// Tentukan base URL untuk gambar bukti
$base_bukti_url = '../../upload/bukti/'; 

// Pastikan ID diterima dan merupakan angka
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_transaksi = $_GET['id'];

    $stmt = $koneksi->prepare("
        SELECT t.*, d.judul AS nama_karya, u.nama AS nama_pembeli,
               d.harga_awal AS harga, d.deskripsi AS deskripsi_karya, u.email AS email_pembeli,
               t.id_midtrans_order, d.gambar AS gambar_karya
        FROM t_transaksi t
        LEFT JOIN t_design d ON t.id_design = d.id_design
        LEFT JOIN t_user u ON t.id_buyer = u.id_user
        WHERE t.id_transaksi = ?
    ");
    $stmt->bind_param("i", $id_transaksi);
    $stmt->execute();
    $result = $stmt->get_result();
    $detail = $result->fetch_assoc();
    $stmt->close();

    if ($detail) {
        
        // --- CONFIGURASI MIDTRANS ---
        require_once __DIR__ . '/../../config.php';
        $midtrans_server_key = MIDTRANS_SERVER_KEY;
        $midtrans_base_url = MIDTRANS_IS_PRODUCTION
            ? 'https://api.midtrans.com/v2/'
            : 'https://api.sandbox.midtrans.com/v2/';
        $transaction_id = isset($detail['id_midtrans_order']) ? $detail['id_midtrans_order'] : null;

        $midtrans_status = null;
        $midtrans_error = null;
        $payment_status_text = 'Belum Ada Data Midtrans';
        $payment_status_detail = 'Tidak ada ID Midtrans Order yang tersimpan.';
        $is_pending_or_failure = true;

        if (!empty($transaction_id)) {
            $ch = curl_init();
            $url = $midtrans_base_url . $transaction_id . '/status';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Authorization: Basic ' . base64_encode($midtrans_server_key . ':') ));
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                $midtrans_error = 'cURL Error: ' . curl_error($ch);
            } else {
                $midtrans_status = json_decode($response, true);
            }
            curl_close($ch);
            
            if ($midtrans_status) {
                $payment_status_text = $midtrans_status['transaction_status'] ?? 'Status Tidak Dikenal';
                $payment_status_detail = 'Metode: ' . ($midtrans_status['payment_type'] ?? 'N/A') . 
                                         ' | Waktu Settlement: ' . ($midtrans_status['settlement_time'] ?? 'Belum Lunas');
                
                if ($payment_status_text == 'settlement' || $payment_status_text == 'capture') {
                    $is_pending_or_failure = false;
                }
            } elseif ($midtrans_error) {
                $payment_status_text = 'Gagal Akses Midtrans API';
                $payment_status_detail = $midtrans_error;
            }
        }
        
        $bukti_file_name = isset($detail['bukti_pembayaran']) ? htmlspecialchars($detail['bukti_pembayaran']) : null;
        $full_bukti_url = !empty($bukti_file_name) ? $base_bukti_url . $bukti_file_name : null;
        
        $gambar_karya_url = '../../upload/design/' . htmlspecialchars($detail['gambar_karya']);
?>

<div class="row">
    <div class="col-lg-7">
        <div class="card card-round shadow-sm">
            <div class="card-body">
                <h4 class="mb-3 text-primary fw-bold">Detail Transaksi #<?= htmlspecialchars($detail['id_transaksi']) ?></h4>
                
                <p class="mb-2"><strong>Status Pembayaran Resmi:</strong></p>
                <p class="mb-3">
                    <span class="badge bg-<?= 
                        $payment_status_text == 'settlement' ? 'success' :
                        ($payment_status_text == 'pending' ? 'warning' : 'danger') ?> p-2">
                        <?= ucfirst($payment_status_text) ?>
                    </span>
                </p>
                <hr class="my-3">
                
                <div class="table-responsive">
                    <table class="table table-sm table-borderless">
                        <tbody>
                            <tr>
                                <td class="text-muted fw-bold" style="width: 40%;">Tanggal Transaksi</td>
                                <td>: <?= date('d M Y H:i:s', strtotime($detail['tanggal_transaksi'])) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">ID Midtrans Order</td>
                                <td>: <?= htmlspecialchars($transaction_id ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">Harga Final</td>
                                <td>: <strong>Rp <?= number_format($detail['harga_final'] ?? 0, 0, ',', '.') ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">Biaya Layanan</td>
                                <td>: Rp 13.000</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">Metode Pembayaran</td>
                                <td>: <?= ucfirst($detail['metode_pembayaran'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">Pembeli</td>
                                <td>: <?= htmlspecialchars($detail['nama_pembeli']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">Email</td>
                                <td>: <?= htmlspecialchars($detail['email_pembeli'] ?? '-') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr class="my-3">

                <?php if ($is_pending_or_failure && $full_bukti_url) { ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Status Midtrans masih <strong>PENDING</strong>. Cek bukti pembayaran manual di kolom kanan.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card card-round shadow-sm">
            <div class="card-body text-center">
                <h5 class="text-secondary mb-3 fw-bold">Karya: <?= htmlspecialchars($detail['nama_karya']) ?></h5>
                
                <img src="<?= $gambar_karya_url ?>" alt="Gambar Karya" class="img-fluid rounded mb-3" style="max-height: 250px; width: 100%; object-fit: cover;">
                
                <p class="small text-muted mb-2">Status Lokal: <strong><?= ucfirst($detail['status_pembayaran'] ?? '-') ?></strong></p>

                <?php if ($full_bukti_url) { ?>
                    <a href="<?= $full_bukti_url ?>" target="_blank" class="btn btn-primary btn-sm w-100 mt-2">
                        <i class="fas fa-eye me-2"></i> Lihat Bukti Pembayaran Manual
                    </a>
                <?php } else { ?>
                    <div class="alert alert-info p-3 small mt-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>Tidak ada bukti manual yang diunggah.
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card card-round shadow-sm">
            <div class="card-body">
                <p class="small text-muted border-top pt-3 mb-0">
                    <strong>Catatan:</strong> Status Midtrans resmi adalah <span class="badge bg-info"><?= ucfirst($payment_status_text) ?></span> | Detail: <?= htmlspecialchars($payment_status_detail) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
    } else {
        echo "<div class='alert alert-danger text-center'><i class='fas fa-exclamation-circle me-2'></i>Data transaksi tidak ditemukan.</div>";
    }
} else {
    echo "<div class='alert alert-danger text-center'><i class='fas fa-exclamation-circle me-2'></i>ID Transaksi tidak valid.</div>";
}
?>