<?php
// public/index.php

require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

// Simple Router
if ($action === 'login') {
    require_once __DIR__ . '/../controllers/AuthController.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        (new AuthController())->handleLogin();
    } else {
        (new AuthController())->showLoginForm();
    }
} elseif ($action === 'logout') {
    require_once __DIR__ . '/../controllers/AuthController.php';
    (new AuthController())->handleLogout();
} else { // Default action is the dashboard
    require_once __DIR__ . '/../controllers/DashboardController.php';
    (new DashboardController())->show();
}
?>