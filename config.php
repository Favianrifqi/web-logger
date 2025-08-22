<?php
// config.php
// PUSAT KONFIGURASI APLIKASI WEB LOGGER

// 1. Konfigurasi Password Login
// Hash di bawah ini adalah untuk password: "admin123"
define('LOGIN_PASSWORD_HASH', '$2y$10$DuDAvz3Qk4vUQbwDxOngZOk/LlLnZZHj2sr7gpcQRTmwIGFn.j59G');

// 2. Konfigurasi Google reCAPTCHA (DITAMBAHKAN KEMBALI)
define('RECAPTCHA_SITE_KEY', '6LfCDK4rAAAAAIq6hutyvqA7_gogkzMJGcXeeyDb');
define('RECAPTCHA_SECRET_KEY', '6LfCDK4rAAAAAA22z-dzH_pZj_SFXnruzOcKh-dB');

// 3. Konfigurasi Database (Untuk Server Live Jagoan Hosting)
$dbHost = 'localhost';
$dbName = 'wisataju_proyek_logger';
$dbUser = 'wisataju_loggeruser';
$dbPass = '#qUho$a]iRh}R%gn';

// 4. Pengaturan Sesi
session_name('WebAppLoggerSession');
session_set_cookie_params(0);
session_start();

// 5. Membuat Koneksi Database Global ($pdo)
$pdo = null;
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<h1>Error Koneksi Database</h1><p>Pesan error: " . $e->getMessage() . "</p>");
}
?>