<?php
// config.php
// PUSAT KONFIGURASI APLIKASI WEB LOGGER

// 1. Konfigurasi Password Login
// Hash di bawah ini adalah untuk password: "admin123"
// Anda bisa mengganti passwordnya dan membuat hash baru jika diperlukan.
define('LOGIN_PASSWORD_HASH', '$2y$10$DuDAvz3Qk4vUQbwDxOngZOk/LlLnZZHj2sr7gpcQRTmwIGFn.j59G');

// 2. Konfigurasi Database (Untuk Server Live Jagoan Hosting)
$dbHost = 'localhost';
$dbName = 'wisataju_proyek_logger';
$dbUser = 'wisataju_loggeruser';
$dbPass = '#qUho$a]iRh}R%gn'; // Password DB Anda

// 3. Pengaturan Sesi
// Mengatur nama sesi kustom
session_name('WebAppLoggerSession');
// Mengatur cookie agar terhapus saat browser ditutup untuk keamanan
session_set_cookie_params(0);
// Memulai sesi
session_start();

// 4. Membuat Koneksi Database Global ($pdo)
$pdo = null;
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Tampilkan pesan error yang jelas jika koneksi gagal
    die("<h1>Error Koneksi Database</h1><p>Tidak dapat terhubung ke database. Mohon periksa file `config.php`. Pesan error: " . $e->getMessage() . "</p>");
}

?>