<?php
// config/database.php (VERSI FINAL LENGKAP)

// Muat library dari Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Inisialisasi Dotenv untuk membaca file .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Definisikan konstanta reCAPTCHA dari environment variables
// Ini akan memperbaiki masalah hilangnya CAPTCHA
define('RECAPTCHA_SITE_KEY', $_ENV['RECAPTCHA_SITE_KEY'] ?? '');
define('RECAPTCHA_SECRET_KEY', $_ENV['RECAPTCHA_SECRET_KEY'] ?? '');

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
$session_timeout = $_ENV['SESSION_TIMEOUT'] ?? 1800;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
        session_unset();
        session_destroy();
        header("Location: index.php?action=login&status=session_expired");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// --- FUNGSI KONEKSI DATABASE ---
function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4";
            $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Koneksi Database Gagal: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>