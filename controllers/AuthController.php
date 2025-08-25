<?php
// controllers/AuthController.php

require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    public function showLoginForm() {
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        require_once __DIR__ . '/../views/login.php';
    }

    public function handleLogin() {
        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['g-recaptcha-response'])) {
            $_SESSION['error_message'] = 'Semua field wajib diisi, termasuk CAPTCHA.';
            header('Location: index.php?action=login');
            exit;
        }

        $recaptcha_response = $_POST['g-recaptcha-response'];
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_data = ['secret' => RECAPTCHA_SECRET_KEY, 'response' => $recaptcha_response];
        $recaptcha_options = ['http' => ['header'  => "Content-type: application/x-www-form-urlencoded\r\n", 'method'  => 'POST', 'content' => http_build_query($recaptcha_data)]];
        $recaptcha_context  = stream_context_create($recaptcha_options);
        $recaptcha_result = json_decode(file_get_contents($recaptcha_url, false, $recaptcha_context), true);

        if (!$recaptcha_result['success']) {
            $_SESSION['error_message'] = 'Verifikasi CAPTCHA gagal.';
            header('Location: index.php?action=login');
            exit;
        }
        
        $userModel = new UserModel(getDbConnection());
        $user = $userModel->findByUsername($_POST['username']);

        if ($user && password_verify($_POST['password'], $user['password'])) {
            // 1. Regenerate session ID untuk mencegah session fixation
            session_regenerate_id(true);
            // 2. Simpan semua data sesi yang dibutuhkan
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['last_activity'] = time(); 

            header('Location: index.php?action=dashboard');
            exit;
        } else {
            $_SESSION['error_message'] = 'Username atau password salah.';
            header('Location: index.php?action=login');
            exit;
        }
    }

    public function handleLogout() {
        $_SESSION = [];
        session_destroy();
        header('Location: index.php?action=login');
        exit;
    }
    
    public function showAdminForm() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit;
        }
        require_once __DIR__ . '/../views/admin.php';
    }

    public function handleAdminUpdate() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit;
        }

        $current_password = $_POST['current_password'];
        $new_username = trim($_POST['username']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validasi input dasar
        if (empty($current_password) || empty($new_username)) {
            $_SESSION['error_message'] = 'Username baru dan password saat ini wajib diisi.';
            header('Location: index.php?action=admin');
            exit;
        }

        $userModel = new UserModel(getDbConnection());
        $user = $userModel->findById($_SESSION['user_id']);

        // Verifikasi password saat ini
        if (!$user || !password_verify($current_password, $user['password'])) {
            $_SESSION['error_message'] = 'Password Anda saat ini salah.';
            header('Location: index.php?action=admin');
            exit;
        }
        
        // Cek apakah username baru sudah dipakai oleh user lain
        $existingUser = $userModel->findByUsername($new_username);
        if ($existingUser && $existingUser['id'] !== $_SESSION['user_id']) {
            $_SESSION['error_message'] = 'Username tersebut sudah digunakan. Silakan pilih yang lain.';
            header('Location: index.php?action=admin');
            exit;
        }

        $newHashedPassword = null;
        // Proses perubahan password HANYA JIKA kolom password baru diisi
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $_SESSION['error_message'] = 'Password baru dan konfirmasi tidak cocok.';
                header('Location: index.php?action=admin');
                exit;
            }
            $newHashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        }
        
        // Update ke database
        $userModel->updateCredentials($_SESSION['user_id'], $new_username, $newHashedPassword);

        // Update session dengan username baru
        $_SESSION['username'] = $new_username;
        $_SESSION['success_message'] = 'Kredensial berhasil diperbarui!';
        header('Location: index.php?action=admin');
        exit;
    }
}
?>