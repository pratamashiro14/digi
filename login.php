<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk / Daftar - DIGIDESAIN</title>

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
            width: 100%; max-width: 440px; background: #fff;
            border-radius: 18px; padding: 36px 32px;
            box-shadow: 0 12px 40px rgba(78, 96, 255, 0.12);
        }
        .brand-title { text-align: center; font-weight: 700; color: var(--brand); margin-bottom: 4px; letter-spacing: .5px; }
        .brand-sub { text-align: center; color: #888; font-size: 13px; margin-bottom: 26px; }

        /* Tab */
        .nav-pills { background: #f1f3ff; border-radius: 50px; padding: 5px; margin-bottom: 26px; gap: 4px; }
        .nav-pills .nav-link {
            border-radius: 50px; font-size: 13.5px; font-weight: 600;
            color: #555; padding: 9px 6px; text-align: center;
        }
        .nav-pills .nav-link.active { background: var(--brand); color: #fff; box-shadow: 0 4px 10px rgba(78,96,255,.3); }

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
        .admin-link { display:block; text-align:center; margin-top:22px; font-size:13px; color:#888; text-decoration:none; }
        .admin-link:hover { color: var(--brand); }
        .badge-role { font-size: 11px; background:#eef1ff; color:var(--brand); padding:3px 9px; border-radius:50px; }
        .back-home { position: absolute; top: 22px; left: 24px; color:#888; text-decoration:none; font-size:14px; }
        .back-home:hover { color: var(--brand); }
        .role-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:8px; margin-bottom:22px; }
        .role-option {
            border:1px solid #e5e7ff; background:#fff; border-radius:10px; padding:14px 8px;
            color:#333; text-decoration:none; text-align:center; cursor:pointer;
            display:flex; flex-direction:column; align-items:center; gap:6px;
        }
        .role-option:hover { border-color:var(--brand); color:var(--brand); box-shadow:0 8px 22px rgba(78,96,255,.12); }
        .role-option:hover i { color:var(--brand); }
        .role-option i { font-size:26px; color:#aab0d5; transition:.2s; }
        .role-option strong { display:block; font-size:12.5px; color:#333; font-weight:600; }
        @media (max-width: 520px) { .role-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <a href="index.php" class="back-home">&larr; Beranda</a>

    <div class="auth-card">
        <h3 class="brand-title">DIGIDESAIN</h3>
        <p class="brand-sub">Marketplace Desain Grafis</p>

        <div class="role-grid">
            <button class="role-option" type="button" onclick="showAuthTab('#tab-pembeli')">
                <i class="fa-solid fa-bag-shopping"></i>
                <strong>Pembeli</strong>
            </button>
            <button class="role-option" type="button" onclick="showAuthTab('#tab-desainer')">
                <i class="fa-solid fa-paint-brush"></i>
                <strong>Desainer</strong>
            </button>
            <a class="role-option" href="admin/">
                <i class="fa-solid fa-shield-halved"></i>
                <strong>Admin</strong>
            </a>
        </div>

        <!-- TAB HEADER -->
        <ul class="nav nav-pills nav-justified" id="authTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-masuk" type="button">Masuk</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-pembeli" type="button">Daftar Pembeli</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-desainer" type="button">Daftar Desainer</button>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ===== MASUK (otomatis sesuai role) ===== -->
            <div class="tab-pane fade show active" id="tab-masuk">
                <form action="proses_login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Alamat Email</label>
                        <input type="email" class="form-control" name="email" placeholder="email@contoh.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi</label>
                        <input type="password" class="form-control" name="password" placeholder="Kata Sandi" required>
                    </div>
                    <button type="submit" class="btn-brand">Masuk</button>
                    <p class="hint">Sistem akan mengarahkan otomatis sesuai role akunmu (Pembeli / Desainer).</p>
                </form>
            </div>

            <!-- ===== DAFTAR PEMBELI ===== -->
            <div class="tab-pane fade" id="tab-pembeli">
                <form action="proses_daftar.php" method="POST">
                    <div class="text-center mb-3"><span class="badge-role">Akun Pembeli</span></div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama" placeholder="Nama Lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Email</label>
                        <input type="email" class="form-control" name="email" placeholder="email@contoh.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi</label>
                        <input type="password" class="form-control" name="password" placeholder="Buat Kata Sandi" required>
                    </div>
                    <button type="submit" class="btn-brand">Daftar sebagai Pembeli</button>
                </form>
            </div>

            <!-- ===== DAFTAR DESAINER ===== -->
            <div class="tab-pane fade" id="tab-desainer">
                <form action="proses_daftar_desainer.php" method="POST">
                    <div class="text-center mb-3"><span class="badge-role">Akun Desainer</span></div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama" placeholder="Nama Desainer" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Email</label>
                        <input type="email" class="form-control" name="email" placeholder="email@contoh.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi</label>
                        <input type="password" class="form-control" name="password" placeholder="Buat Kata Sandi" required>
                    </div>
                    <button type="submit" class="btn-brand">Daftar sebagai Desainer</button>
                </form>
            </div>

        </div>

        <a href="admin/" class="admin-link">Masuk sebagai Admin &rarr;</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAuthTab(target) {
            const trigger = document.querySelector('[data-bs-target="' + target + '"]');
            if (trigger) {
                bootstrap.Tab.getOrCreateInstance(trigger).show();
            }
        }
    </script>
</body>
</html>
