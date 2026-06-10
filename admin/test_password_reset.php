<?php
/**
 * File ini untuk TESTING SAJA - bisa dihapus setelah production
 * 
 * Fungsi:
 * - Lihat semua token password reset yang aktif
 * - Copy-paste link untuk testing tanpa perlu email real
 * - Useful untuk development di localhost
 * 
 * Akses: http://localhost/xampp/digi/admin/test_password_reset.php
 */

session_start();

// Cek apakah user sudah login admin
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'koneksi.php';

// Ambil semua token yang masih berlaku
$query = mysqli_query($koneksi, "SELECT email, token, expiry FROM t_password_reset WHERE expiry > NOW() ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testing - Password Reset Tokens</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .alert-warning {
            border-radius: 10px;
        }
        .token-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        .token-email {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .reset-link {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
            color: #555;
        }
        .btn-copy {
            font-size: 12px;
            padding: 5px 10px;
        }
        .token-expires {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Testing - Password Reset Tokens</h2>
        <p class="text-muted">Halaman ini hanya untuk testing development. Hapus setelah production.</p>

        <div class="alert alert-warning">
            <strong>⚠️ Perhatian:</strong> Halaman ini hanya bisa diakses oleh admin yang sudah login.
            Gunakan untuk testing fitur lupa password tanpa harus kirim email real.
        </div>

        <h5 class="mt-4">Token Password Reset Aktif (belum expired)</h5>

        <?php if (mysqli_num_rows($query) > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($query)) { 
                $reset_url = "http://localhost/xampp/digi/admin/reset_password.php?token=" . $row['token'];
            ?>
                <div class="token-card">
                    <div class="token-email">📧 <?php echo htmlspecialchars($row['email']); ?></div>
                    
                    <div class="reset-link" id="link-<?php echo substr($row['token'], 0, 8); ?>">
                        <?php echo htmlspecialchars($reset_url); ?>
                    </div>
                    
                    <button class="btn btn-sm btn-outline-primary btn-copy" 
                            onclick="copyToClipboard('link-<?php echo substr($row['token'], 0, 8); ?>')">
                        📋 Copy Link
                    </button>
                    
                    <button class="btn btn-sm btn-primary" 
                            onclick="window.open('<?php echo htmlspecialchars($reset_url); ?>', '_blank')">
                        🔗 Buka di Tab Baru
                    </button>
                    
                    <div class="token-expires">
                        ⏰ Expired: <?php echo date('d M Y H:i:s', strtotime($row['expiry'])); ?>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="alert alert-info">
                Belum ada token password reset yang aktif. <br>
                Kunjungi halaman <a href="forgot_password.php">Lupa Kata Sandi</a> untuk membuat token.
            </div>
        <?php } ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <h5>Petunjuk Testing:</h5>
            <ol>
                <li>Klik tombol <strong>"Copy Link"</strong> untuk copy link reset ke clipboard</li>
                <li>Atau klik <strong>"Buka di Tab Baru"</strong> untuk langsung membuka halaman reset</li>
                <li>Ikuti proses reset password seperti normal</li>
                <li>Setelah selesai, token otomatis dihapus dari list ini</li>
            </ol>
        </div>

        <div style="margin-top: 30px;">
            <a href="beranda.php" class="btn btn-secondary">← Kembali ke Dashboard</a>
        </div>
    </div>

    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.innerText;
            
            navigator.clipboard.writeText(text).then(() => {
                swal('Berhasil!', 'Link berhasil disalin ke clipboard.', 'success');
            }).catch(() => {
                // Fallback untuk browser lama
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                swal('Berhasil!', 'Link berhasil disalin ke clipboard.', 'success');
            });
        }
    </script>
</body>
</html>
