<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Panel</h1>
            <p>Di sini Anda bisa mengubah kredensial login.</p>
            <div>
                <a href="index.php?action=dashboard" class="logout-button" style="background-color: var(--primary-color); right: 120px;">Kembali ke Dashboard</a>
                <a href="index.php?action=logout" class="logout-button">Logout</a>
            </div>
        </header>

        <div class="login-card" style="margin: 0 auto;">
            <h2>Ubah Password</h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="login-error" style="background-color: #2e7d32; border-color: #4caf50; color: #e8f5e9;"><?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="login-error"><?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <form action="index.php?action=updateAdmin" method="post">
                <div class="form-group">
                    <label for="old_password">Password Lama</label>
                    <input type="password" name="old_password" id="old_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                 <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                <button type="submit" class="btn-submit">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>