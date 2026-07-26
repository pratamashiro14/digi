<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin/koneksi.php';
require_once __DIR__ . '/otp_helper.php';

$email          = $_POST['email'] ?? '';
$password_input = $_POST['password'] ?? '';

if (empty(trim($email)) || empty(trim($password_input))) {
    sweetalert_back('Alamat email dan kata sandi wajib diisi!', 'error', 'Login Gagal!');
    exit;
}

// 1. Cari akun berdasarkan email (prepared statement — cegah SQL injection)
$stmt = mysqli_prepare($koneksi, "SELECT * FROM t_user WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$query     = mysqli_stmt_get_result($stmt);
$cek_email = $query ? mysqli_num_rows($query) : 0;

if ($cek_email > 0) {
    $data        = mysqli_fetch_assoc($query);
    $password_db = $data['password'];

    // 2. Verifikasi password + migrasi otomatis format lama (md5/plaintext) ke bcrypt
    $cocok = verify_and_upgrade_password($password_input, $password_db, 't_user', 'id_user', (int) $data['id_user']);

    if ($cocok) {
        // 2b. Akun SUSPEND (hasil strike wanprestasi atau tindakan admin).
        // Lazy auto-unsuspend: kalau masa hukuman sudah lewat, pulihkan di
        // sini juga (bukan cuma di status_bid_user()) supaya user tidak
        // perlu buka halaman lain dulu baru bisa login lagi.
        if (($data['status'] ?? '') === 'nonaktif') {
            $sampai = $data['suspend_sampai'] ?? null;
            if ($sampai !== null && strtotime($sampai) <= time()) {
                mysqli_query($koneksi, "UPDATE t_user SET status='aktif', suspend_sampai=NULL WHERE id_user=" . (int) $data['id_user']);
                $data['status'] = 'aktif';
            } else {
                $pesan_sampai = $sampai !== null ? ' sampai ' . date('d M Y H:i', strtotime($sampai)) : ' (permanen)';
                sweetalert_back('Akun Anda dibekukan' . $pesan_sampai . ' karena tidak menyelesaikan pembayaran lelang yang Anda menangkan. Hubungi admin bila ini keliru.', 'error', 'Akun Dibekukan');
            }
        }

        // 2c. Email belum diverifikasi (OTP) -> selesaikan dulu sebelum masuk.
        if ((int) ($data['email_terverifikasi'] ?? 0) !== 1) {
            $_SESSION['otp_email'] = $data['email'];
            $_SESSION['otp_nama']  = $data['nama'];
            $hasil = otp_buat_dan_kirim($koneksi, $data['email'], $data['nama'], 'registrasi');
            if ($hasil['ok'] && $hasil['kode_dev'] !== null) {
                $_SESSION['otp_kode_dev'] = $hasil['kode_dev'];
            }
            redirect_with_alert('Akun Anda belum diverifikasi. Kami telah mengirim kode verifikasi ke email Anda.', 'verifikasi_email.php', 'info', 'Verifikasi Diperlukan');
        }

        // 3. OTOMATIS arahkan sesuai ROLE di database
        $role = strtolower(trim($data['role'] ?? ''));

        if ($role === 'designer' || $role === 'desainer') {
            login_as_designer($data['id_user'], $data['nama'], $data['email']);
            sweetalert_redirect_auto('Login berhasil sebagai Desainer! Selamat datang, ' . $data['nama'] . '.', 'index.php', 'success', 'Login Berhasil!');
        } else {
            login_as_user($data['id_user'], $data['nama'], $data['email']);
            sweetalert_redirect_auto('Login berhasil sebagai Pembeli! Selamat datang, ' . $data['nama'] . '.', 'product.php', 'success', 'Login Berhasil!');
        }
    } else {
        // Password salah
        sweetalert_back('Password yang kamu masukkan salah. Klik ikon mata untuk mengecek ketikan, dan pastikan Caps Lock tidak aktif.', 'error', 'Password Salah!');
    }
} else {
    // Email tidak ditemukan
    sweetalert_back('Email tidak terdaftar. Silakan daftar terlebih dahulu.', 'error', 'Login Gagal!');
}
?>
