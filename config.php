<?php
// 1. Konfigurasi Password Login
// Hash untuk password: "admin123"
define('LOGIN_PASSWORD_HASH', '$2y$10$DuDAvz3Qk4vUQbwDxOngZOk/LlLnZZHj2sr7gpcQRTmwIGFn.j59G');

// 2. Konfigurasi Google reCAPTCHA
define('RECAPTCHA_SITE_KEY', '6LfCDK4rAAAAAIq6hutyvqA7_gogkzMJGcXeeyDb');
define('RECAPTCHA_SECRET_KEY', '6LfCDK4rAAAAAA22z-dzH_pZj_SFXnruzOcKh-dB');

// 3. Atur nama sesi kustom (sudah bagus dari kode Anda)
session_name('WebAppLoggerSession');

// 4. Atur cookie agar terhapus saat browser ditutup
// Ini adalah kunci untuk keamanan yang lebih ketat yang Anda inginkan.
// Parameter 0 berarti 'sampai sesi browser berakhir'.
session_set_cookie_params(0);

// 5. Mulai sesi
// Kita pindahkan session_start() ke sini agar terpusat dan konsisten.
// File lain cukup memanggil config.php dan sesi sudah otomatis aktif.
session_start();

?>