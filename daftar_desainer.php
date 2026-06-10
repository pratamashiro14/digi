<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Menjadi Desainer - DIGIDESAIN</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root { --brand: #4e60ff; --brand-dark: #3b4dcc; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef1ff 0%, #f8f9ff 100%);
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; margin: 0; padding: 20px;
        }
        .auth-card {
            width: 100%; max-width: 480px; background: #fff;
            border-radius: 18px; padding: 36px 32px;
            box-shadow: 0 12px 40px rgba(78, 96, 255, 0.12);
        }
        .brand-title { text-align: center; font-weight: 700; color: var(--brand); margin-bottom: 4px; letter-spacing: .5px; }
        .brand-sub { text-align: center; color: #888; font-size: 13px; margin-bottom: 26px; }

        .form-label { font-weight: 600; font-size: 13.5px; color: #333; margin-bottom: 6px; }
        .form-control {
            border-radius: 10px; padding: 11px 14px; border: 1px solid #d8d8e8; font-size: 14px;
        }
        .form-control:focus { box-shadow: none; border-color: var(--brand); }

        .btn-brand {
            background: var(--brand); color: #fff; font-weight: 600; font-size: 16px;
            border-radius: 50px; padding: 11px; width: 100%; border: none; transition: .25s; margin-top: 6px;
        }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }

        .hint { font-size: 12.5px; color: #999; text-align: center; margin-top: 8px; }
        .back-home { position: absolute; top: 22px; left: 24px; color:#888; text-decoration:none; font-size:14px; }
        .back-home:hover { color: var(--brand); }
        .badge-role { font-size: 11px; background:#eef1ff; color:var(--brand); padding:3px 9px; border-radius:50px; display:inline-block; }
        
        .designer-perks {
            background: #f8f9ff;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #555;
            border: 1px solid #eef1ff;
        }
        .designer-perks ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        .designer-perks li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <a href="login.php" class="back-home">&larr; Kembali ke Login</a>

    <div class="auth-card">
        <h3 class="brand-title">DIGIDESAIN</h3>
        <p class="brand-sub">Bergabung Sebagai Desainer</p>

        <div class="designer-perks">
            <strong>Keuntungan & Syarat:</strong>
            <ul>
                <li>Jual karya desain Anda ke ribuan pembeli</li>
                <li>Ikuti proyek lelang (bidding)</li>
                <li><strong>Wajib melakukan verifikasi KTP</strong> (diunggah setelah login) agar dapat mulai berjualan.</li>
            </ul>
        </div>

        <form action="proses_daftar_desainer.php" method="POST">
            <div class="text-center mb-3"><span class="badge-role">Pendaftaran Desainer</span></div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap Sesuai KTP</label>
                <input type="text" class="form-control" name="nama" placeholder="Contoh: Budi Santoso" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Alamat Email</label>
                <input type="email" class="form-control" name="email" placeholder="email@contoh.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Kata Sandi</label>
                <input type="password" class="form-control" name="password" placeholder="Buat Kata Sandi" required>
            </div>
            <button type="submit" class="btn-brand">Daftar Sekarang</button>
            <p class="hint mt-3">Sudah punya akun? <a href="login.php" style="color:var(--brand); text-decoration:none; font-weight:600;">Masuk di sini</a></p>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
