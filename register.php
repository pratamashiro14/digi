<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun - DensCreative</title>

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
            border-radius: 50px; padding: 11px; width: 100%; border: none; transition: .25s; margin-top: 12px;
        }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }

        .btn-outline-brand {
            background: transparent; color: var(--brand); font-weight: 600; font-size: 16px;
            border-radius: 50px; padding: 11px; width: 100%; border: 2px solid var(--brand); transition: .25s; margin-top: 12px;
            display: inline-block; text-align: center; text-decoration: none; box-sizing: border-box;
        }
        .btn-outline-brand:hover { background: var(--brand); color: #fff; }

        .hint { font-size: 12px; color: #888; text-align: center; margin-top: 16px; }
        .badge-role { font-size: 11px; background:#eef1ff; color:var(--brand); padding:3px 9px; border-radius:50px; }
        .back-home { position: absolute; top: 22px; left: 24px; color:#888; text-decoration:none; font-size:14px; }
        .back-home:hover { color: var(--brand); }

        .form-info-box {
            background: #fafbff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; font-size: 12px; color: #555; margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <a href="login.php" class="back-home">&larr; Kembali ke Login</a>

    <div class="auth-card">
        <h3 class="brand-title">DensCreative</h3>
        <p class="brand-sub">Buat Akun Baru</p>

        <!-- TAB HEADER -->
        <ul class="nav nav-pills nav-justified" id="authTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-pembeli" type="button">Daftar Pembeli</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-desainer" type="button">Daftar Desainer</button>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ===== TAB DAFTAR PEMBELI ===== -->
            <div class="tab-pane fade show active" id="tab-pembeli">
                <form action="proses_daftar.php" method="POST" class="needs-validation" novalidate>
                    <div class="text-center mb-3"><span class="badge-role">Akun Pembeli</span></div>

                    <div class="form-info-box">
                        <i class="fa-solid fa-circle-info text-primary"></i> Tidak perlu KTP. Setelah daftar, kami
                        akan mengirim <strong>kode verifikasi ke email</strong> Anda — masukkan kodenya untuk
                        mengaktifkan akun.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama" placeholder="Contoh: Budi Santoso" required>
                        <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Email</label>
                        <input type="email" class="form-control" name="email" placeholder="email@contoh.com" required>
                        <div class="invalid-feedback">Alamat email wajib diisi dengan format yang benar.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi</label>
                        <input type="password" class="form-control" name="password" placeholder="Buat Kata Sandi" required>
                        <div class="invalid-feedback">Kata sandi wajib dibuat.</div>
                    </div>
                    
                    <button type="submit" class="btn-brand">Daftar sebagai Pembeli</button>
                    <p class="hint">Sudah punya akun? <a href="login.php" style="color:var(--brand); text-decoration:none; font-weight:600;">Masuk di sini</a></p>
                </form>
            </div>

            <!-- ===== TAB DAFTAR DESAINER ===== -->
            <div class="tab-pane fade" id="tab-desainer">
                <form action="proses_daftar_desainer.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="text-center mb-3"><span class="badge-role">Akun Desainer (Kemitraan)</span></div>
                    
                    <div class="form-info-box">
                        <i class="fa-solid fa-circle-info text-primary"></i> <strong>Persyaratan Mitra Desainer:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Masukkan NIK sesuai kartu identitas Anda.</li>
                            <li>Unggah foto KTP yang jelas untuk verifikasi Admin secara manual.</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap Sesuai KTP</label>
                        <input type="text" class="form-control" name="nama" placeholder="Contoh: Budi Santoso" required>
                        <div class="invalid-feedback">Nama sesuai KTP wajib diisi.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Email</label>
                        <input type="email" class="form-control" name="email" placeholder="email@contoh.com" required>
                        <div class="invalid-feedback">Alamat email wajib diisi dengan format yang benar.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi</label>
                        <input type="password" class="form-control" name="password" placeholder="Buat Kata Sandi" required>
                        <div class="invalid-feedback">Kata sandi wajib dibuat.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIK (Nomor Induk Kependudukan)</label>
                        <input type="text" class="form-control" name="nik" placeholder="Masukkan 16 digit NIK" maxlength="16" pattern="\d{16}" title="NIK harus berupa 16 digit angka" required>
                        <div class="invalid-feedback">NIK wajib diisi dengan format 16 digit angka.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unggah Foto KTP</label>
                        <input type="file" class="form-control" name="foto_ktp" accept="image/*" required>
                        <div class="invalid-feedback">Foto KTP wajib diunggah.</div>
                        <div class="form-text text-muted" style="font-size:11px;">Maksimal 4MB (jpg, jpeg, png).</div>
                    </div>

                    <button type="submit" class="btn-brand">Daftar sebagai Desainer</button>
                    <p class="hint">Sudah punya akun? <a href="login.php" style="color:var(--brand); text-decoration:none; font-weight:600;">Masuk di sini</a></p>
                </form>
            </div>

        </div>

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
