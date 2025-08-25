<?php
// --- KONFIGURASI ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'wisataju_proyek_logger');
define('DB_USER', 'wisataju_loggeruser');
define('DB_PASS', '#qUho$a]iRh}R%gn');
define('RECAPTCHA_SITE_KEY', '6LfCDK4rAAAAAIq6hutyvqA7_gogkzMJGcXeeyDb');
define('RECAPTCHA_SECRET_KEY', '6LfCDK4rAAAAAA22z-dzH_pZj_SFXnruzOcKh-dB');
define('SESSION_TIMEOUT', 10);

// --- PENGATURAN SESI ---
session_name('WebAppLoggerSession');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// --- LOGIKA SESSION TIMEOUT ---
// Cek apakah pengguna sudah login
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Cek apakah ada aktivitas terakhir yang tercatat
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Jika waktu idle sudah melebihi batas, hancurkan sesi
        session_unset();
        session_destroy();
        header("Location: index.php?action=login&status=session_expired"); // Arahkan ke login dengan notifikasi
        exit;
    }
    // Perbarui waktu aktivitas terakhir pada setiap pemuatan halaman
    $_SESSION['last_activity'] = time();
}

// --- FUNGSI KONEKSI DATABASE ---
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