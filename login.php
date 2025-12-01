<?php
session_start();
require 'koneksi.php';

if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    
    if (mysqli_num_rows($query) === 1) {
        $user = mysqli_fetch_assoc($query);

        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header("Location: index.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login | Warkop</title>
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

        .login-container {
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

        .login-header {
            margin-bottom: 30px;
        }

        .login-header i {
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

        .login-subtitle {
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

        .btn-login {
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

        .btn-login:hover {
            background: linear-gradient(135deg, #654321 0%, #8B4513 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 69, 19, 0.3);
        }

        .btn-login:active {
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

        .login-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 13px;
        }

        .login-footer p {
            margin: 0;
            color: #666;
        }

        .login-footer a {
            color: #8B4513;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-footer a:hover {
            color: #D2691E;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- Header -->
    <div class="login-header">
        <i class="fas fa-coffee"></i>
        <h2>Login</h2>
        <p class="login-subtitle">Masuk ke Warkop Gondrong 77</p>
    </div>

    <!-- Alert -->
    <?php if(isset($_GET['registered'])) : ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> Registrasi berhasil! Silakan login.
        </div>
    <?php endif; ?>

    <?php if(isset($error)) : ?>
        <div class="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST">

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
            <input type="password" id="password" name="password" placeholder="Masukkan password..." autocomplete="off" required>
        </div>

        <button type="submit" name="login" class="btn-login">
            <i class="fas fa-sign-in-alt me-2"></i> Login Sekarang
        </button>
    </form>

    <!-- Footer -->
    <div class="login-footer">
        <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>