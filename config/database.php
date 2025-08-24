<?php
// config/database.php
// Konfigurasi Terpusat

define('DB_HOST', 'localhost');
define('DB_NAME', 'wisataju_proyek_logger');
define('DB_USER', 'wisataju_loggeruser');
define('DB_PASS', '#qUho$a]iRh}R%gn');
define('RECAPTCHA_SITE_KEY', '6LfCDK4rAAAAAIq6hutyvqA7_gogkzMJGcXeeyDb');
define('RECAPTCHA_SECRET_KEY', '6LfCDK4rAAAAAA22z-dzH_pZj_SFXnruzOcKh-dB');

session_name('WebAppLoggerSession');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Koneksi Database Gagal: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>