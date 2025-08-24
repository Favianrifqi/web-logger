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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
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

    // FUNGSI BARU 2: Untuk memproses perubahan password
    public function handleAdminUpdate() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit;
        }

        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error_message'] = 'Semua field password wajib diisi.';
            header('Location: index.php?action=admin');
            exit;
        }

        if ($new_password !== $confirm_password) {
            $_SESSION['error_message'] = 'Password baru dan konfirmasi tidak cocok.';
            header('Location: index.php?action=admin');
            exit;
        }

        $userModel = new UserModel(getDbConnection());
        $user = $userModel->findById($_SESSION['user_id']);

        if (!$user || !password_verify($old_password, $user['password'])) {
            $_SESSION['error_message'] = 'Password lama salah.';
            header('Location: index.php?action=admin');
            exit;
        }

        $newHashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        $userModel->updatePassword($_SESSION['user_id'], $newHashedPassword);

        $_SESSION['success_message'] = 'Password berhasil diperbarui!';
        header('Location: index.php?action=admin');
        exit;
    }
}
?>