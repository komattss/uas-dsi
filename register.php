<?php
session_start();
require 'koneksi.php';

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email sudah terdaftar!";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO users (username, email, password) 
                                       VALUES('$username', '$email', '$password')");
        if ($insert) {
            header("Location: login.php?registered=1");
            exit;
        } else {
            $error = "Gagal mendaftar.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register | Warkop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #654321 0%, #8B4513 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .register-container {
            width: 420px;
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.2);
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            margin-bottom: 30px;
        }

        .register-header i {
            font-size: 48px;
            color: #8B4513;
            margin-bottom: 10px;
        }

        h2 {
            margin: 10px 0 5px 0;
            font-weight: 700;
            font-size: 28px;
            color: #333;
        }

        .register-subtitle {
            font-size: 13px;
            color: #888;
            margin-top: 5px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            font-size: 13px;
            color: #555;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: "Poppins", sans-serif;
        }

        .input-group input:focus {
            border-color: #8B4513;
            outline: none;
            box-shadow: 0 0 8px rgba(139, 69, 19, 0.2);
        }

        .input-group input::placeholder {
            color: #bbb;
        }

        .btn-register {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #654321 0%, #8B4513 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 69, 19, 0.3);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .alert {
            background: #ffe1e1;
            color: #c40000;
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #c40000;
        }

        .alert.success {
            background: #e1ffe1;
            color: #00a600;
            border-left-color: #00a600;
        }

        .register-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 13px;
        }

        .register-footer p {
            margin: 0;
            color: #666;
        }

        .register-footer a {
            color: #8B4513;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-footer a:hover {
            color: #D2691E;
            text-decoration: underline;
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>

<div class="register-container">
    <!-- Header -->
    <div class="register-header">
        <i class="fas fa-coffee"></i>
        <h2>Daftar Akun</h2>
        <p class="register-subtitle">Bergabunglah dengan Warkop Gondrong 77</p>
    </div>

    <!-- Alert -->
    <?php if(isset($_GET['registered'])) : ?>
        <div class="alert alert-success">Registrasi berhasil! Silakan login.</div>
    <?php endif; ?>

    <?php if(isset($error)) : ?>
        <div class="alert"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST">

        <div class="input-group">
            <label for="username">
                <i class="fas fa-user" style="color: #8B4513; margin-right: 5px;"></i> Username
            </label>
            <input type="text" id="username" name="username" placeholder="Masukkan username..." autocomplete="off" required>
        </div>

        <div class="input-group">
            <label for="email">
                <i class="fas fa-envelope" style="color: #8B4513; margin-right: 5px;"></i> Email
            </label>
            <input type="email" id="email" name="email" placeholder="Masukkan email..." autocomplete="off" required>
        </div>

        <div class="input-group">
            <label for="password">
                <i class="fas fa-lock" style="color: #8B4513; margin-right: 5px;"></i> Password
            </label>
            <input type="password" id="password" name="password" placeholder="Masukkan password (min. 6 karakter)..." autocomplete="off" minlength="6" required>
            <div class="password-strength">
                <div class="strength-bar" id="strengthBar"></div>
            </div>
        </div>

        <button type="submit" name="register" class="btn-register">
            <i class="fas fa-user-plus me-2"></i> Daftar Sekarang
        </button>
    </form>

    <!-- Footer -->
    <div class="register-footer">
        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password strength indicator
    document.getElementById('password').addEventListener('input', function(e) {
        const strength = e.target.value.length;
        const bar = document.getElementById('strengthBar');
        
        if (strength < 6) {
            bar.style.width = (strength / 6 * 33) + '%';
            bar.style.background = '#ff4444';
        } else if (strength < 12) {
            bar.style.width = '66%';
            bar.style.background = '#ffaa00';
        } else {
            bar.style.width = '100%';
            bar.style.background = '#44aa44';
        }
    });
</script>

</body>
</html>