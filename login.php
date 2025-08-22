<?php
// login.php

// Panggil konfigurasi. Sesi dan koneksi DB sudah otomatis siap.
require_once 'config.php';

// Jika pengguna sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}

$login_error = '';

// Proses form jika ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password'])) {
        // Verifikasi password yang diinput dengan hash yang ada di config
        if (password_verify($_POST['password'], LOGIN_PASSWORD_HASH)) {
            // Jika password benar, set session dan arahkan ke dashboard
            $_SESSION['loggedin'] = true;
            header('Location: index.php');
            exit;
        } else {
            $login_error = 'Password yang Anda masukkan salah.';
        }
    } else {
        $login_error = 'Silakan masukkan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h1>Dashboard Login</h1>
        
        <?php if (!empty($login_error)): ?>
            <div class="login-error"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="btn-submit">Login</button>
        </form>
    </div>
</body>
</html>