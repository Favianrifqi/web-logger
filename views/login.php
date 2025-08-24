<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="login-body">
    <div class="login-card">
        <h1>Dashboard Login</h1>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="login-error"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        <form action="index.php?action=login" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
            </div>
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
    </div>

    <script>
        // Script untuk mendeteksi autofill dan menerapkan style
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-group input');
            
            // Beri sedikit waktu bagi browser untuk melakukan autofill
            setTimeout(() => {
                inputs.forEach(input => {
                    // Cek apakah browser sudah mengisi value dan menerapkan pseudo-class-nya
                    if (input.matches(':-webkit-autofill')) {
                        input.classList.add('autofilled');
                    }
                });
            }, 100);

            // Juga tambahkan listener jika user mengetik manual
             inputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        this.classList.add('autofilled');
                    } else {
                        this.classList.remove('autofilled');
                    }
                });
             });
        });
    </script>
</body>
</html>