<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            background: white;
        }

        /* Styling Tab Header */
        .auth-tabs {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 20px;
            position: relative;
        }

        .auth-tab {
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            padding-bottom: 10px;
            cursor: pointer;
        }

        .auth-tab.active {
            color: #4e60ff; /* Warna Biru sesuai gambar */
        }

        .auth-tab.inactive {
            color: #000000;
        }
        
        /* Garis abu-abu tipis di bawah tab */
        .tab-divider {
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 30px;
            margin-top: -20px; /* Menarik garis ke atas mendekati teks */
        }

        /* Styling Label Form */
        .form-label {
            font-weight: 600;
            font-size: 14px;
            color: #000;
            margin-bottom: 8px;
        }

        /* Styling Input Form */
        .form-control {
            border-radius: 5px;
            padding: 12px 15px;
            border: 1px solid #ccc;
            font-size: 14px;
            color: #666;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #4e60ff;
        }

        /* Link Lupa Password */
        .forgot-password {
            text-decoration: none;
            color: #000;
            font-weight: 600;
            font-size: 14px;
            float: right;
            margin-top: 10px;
            margin-bottom: 30px;
        }

        /* Tombol Google */
        .btn-google {
            background-color: #f2f2f2;
            color: #000;
            font-weight: 500;
            border: none;
            border-radius: 50px; /* Pill shape */
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 0 auto 30px auto; /* Center horizontal */
            width: fit-content;
            min-width: 280px;
        }

        /* Tombol Login Utama */
        .btn-login {
            background-color: #4e60ff; /* Warna ungu/biru mirip gambar */
            color: white;
            font-weight: 600;
            font-size: 18px;
            border-radius: 50px;
            padding: 12px;
            width: 100%;
            border: none;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background-color: #3b4dcc;
        }

    </style>
</head>
<body>

    <div class="login-container">
        <div class="auth-tabs">
            <div class="auth-tab active">Masuk</div>
            <div class="auth-tab inactive">Daftar</div>
        </div>
        <div class="tab-divider"></div>

        <form action="proses_login.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Kata Sandi</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Kata Sandi" required>
            </div>

            <div class="clearfix">
                <a href="#" class="forgot-password">Lupa Password</a>
            </div>

            <button type="button" class="btn btn-google">
                <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Lanjutkan dengan Google
            </button>

            <button type="submit" class="btn btn-login">Login</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>