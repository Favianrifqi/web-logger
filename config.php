<?php
// 1. Konfigurasi Password Login
// Password default: "admin123" (Sangat disarankan untuk diganti)
// Untuk membuat hash baru, gunakan script online atau fungsi password_hash()
define('LOGIN_PASSWORD_HASH', '$2y$10$DuDAvz3Qk4vUQbwDxOngZOk/LlLnZZHj2sr7gpcQRTmwIGFn.j59G');

// 2. Konfigurasi Google reCAPTCHA
// GANTI DENGAN KUNCI YANG ANDA DAPATKAN DARI GOOGLE
define('RECAPTCHA_SITE_KEY', '6LfCDK4rAAAAAIq6hutyvqA7_gogkzMJGcXeeyDb');
define('RECAPTCHA_SECRET_KEY', '6LfCDK4rAAAAAA22z-dzH_pZj_SFXnruzOcKh-dB');

// 3. Konfigurasi Sesi
session_name('WebAppLoggerSession');
?>