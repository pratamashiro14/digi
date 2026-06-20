<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk / Daftar - DensCreative</title>

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
        .auth-brand {
            display: flex; justify-content: center; align-items: center;
            margin: 0 auto 30px; text-decoration: none;
        }
        .auth-brand img {
            display: block; width: min(250px, 78%); height: auto;
            object-fit: contain;
        }

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

        .btn-outline-brand {
            background: transparent; color: var(--brand); font-weight: 600; font-size: 16px;
            border-radius: 50px; padding: 11px; width: 100%; border: 2px solid var(--brand); transition: .25s; margin-top: 12px;
            display: inline-block; text-align: center; text-decoration: none; box-sizing: border-box;
        }
        .btn-outline-brand:hover { background: var(--brand); color: #fff; }

        .hint { font-size: 12.5px; color: #999; text-align: center; margin-top: 16px; }
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
        <a href="index.php" class="auth-brand" aria-label="Kembali ke beranda DensCreative">
            <img src="images/icons/dens.png" alt="DensCreative">
        </a>

        <form action="proses_login.php" method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">Alamat Email</label>
                <input type="email" class="form-control" name="email" placeholder="email@contoh.com" required>
                <div class="invalid-feedback">Alamat email wajib diisi dengan format yang benar.</div>
            </div>
            <div class="mb-3">
                <label class="form-label d-flex justify-content-between align-items-center">
                    Kata Sandi
                    <a href="forgot_password.php" style="font-size:12px;font-weight:500;color:var(--brand);text-decoration:none;">Lupa password?</a>
                </label>
                <input type="password" class="form-control" name="password" placeholder="Kata Sandi" required>
                <div class="invalid-feedback">Kata sandi wajib diisi.</div>
            </div>
            <button type="submit" class="btn-brand">Masuk</button>
            <a href="register.php" class="btn-outline-brand">Registrasi</a>
            <p class="hint">Sistem akan mengarahkan otomatis sesuai role akunmu (Pembeli / Desainer).</p>
        </form>

        <a href="admin/" class="admin-link">Masuk sebagai Admin &rarr;</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
