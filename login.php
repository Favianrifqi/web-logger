<?php
// login.php

require_once 'config.php';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['g-recaptcha-response'])) {
        $login_error = 'Mohon verifikasi bahwa Anda bukan robot.';
    } else {
        // Logika verifikasi reCAPTCHA Anda
        $recaptcha_response = $_POST['g-recaptcha-response'];
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_data = ['secret' => RECAPTCHA_SECRET_KEY, 'response' => $recaptcha_response];
        $recaptcha_options = ['http' => ['header'  => "Content-type: application/x-www-form-urlencoded\r\n", 'method'  => 'POST', 'content' => http_build_query($recaptcha_data)]];
        $recaptcha_context  = stream_context_create($recaptcha_options);
        $recaptcha_result = json_decode(file_get_contents($recaptcha_url, false, $recaptcha_context), true);

        // Verifikasi password
        $password_correct = password_verify($_POST['password'], LOGIN_PASSWORD_HASH);
        
        // Cek keduanya: reCAPTCHA harus sukses DAN password harus benar
        if ($recaptcha_result['success'] && $password_correct) {
            $_SESSION['loggedin'] = true;
            header('Location: index.php');
            exit;
        } else {
            $login_error = 'Password salah atau verifikasi CAPTCHA gagal.';
        }
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
            <div class="form-group">
                 <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
            </div>
            <button type="submit" class="btn-submit">Login</button>
        </form>
    </div>
</body>
</html>