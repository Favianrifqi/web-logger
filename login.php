<?php
// login.php

// Cukup panggil config.php, sesi sudah otomatis dimulai dari sana.
require_once 'config.php';

// Jika pengguna sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}

$login_error = '';

// Proses form jika ada data yang dikirim (metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptcha_response = $_POST['g-recaptcha-response'];
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_data = ['secret' => RECAPTCHA_SECRET_KEY, 'response' => $recaptcha_response];
    $recaptcha_options = ['http' => ['header'  => "Content-type: application/x-www-form-urlencoded\r\n", 'method'  => 'POST', 'content' => http_build_query($recaptcha_data)]];
    $recaptcha_context  = stream_context_create($recaptcha_options);
    $recaptcha_result = json_decode(file_get_contents($recaptcha_url, false, $recaptcha_context), true);

    $password_correct = password_verify($_POST['password'], LOGIN_PASSWORD_HASH);
    
    if ($recaptcha_result['success'] && $password_correct) {
        // Jika verifikasi password DAN reCAPTCHA berhasil
        $_SESSION['loggedin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $login_error = 'Password salah atau verifikasi CAPTCHA gagal.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Dashboard</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            box-sizing: border-box;
        }
        .login-card h1 {
            margin-top: 0;
            margin-bottom: 24px;
            text-align: center;
            color: #1c1e21;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #606770;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1877f2;
            box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.2);
        }
        .g-recaptcha {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background-color: #1877f2;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #166fe5;
        }
        .login-error {
            background-color: #fbebeb;
            color: #d32f2f;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #d32f2f;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Login Dashboard SIEM</h1>
        
        <?php if (!empty($login_error)): ?>
            <div class="login-error"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
            <button type="submit" class="btn-submit">Login</button>
        </form>
    </div>
</body>
</html>