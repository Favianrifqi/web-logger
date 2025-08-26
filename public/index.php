<?php
// public/index.php

require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';

// Router
require_once __DIR__ . '/../controllers/AuthController.php'; // Panggil AuthController di awal
require_once __DIR__ . '/../controllers/DashboardController.php'; // Panggil DashboardController

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new AuthController())->handleLogin();
        } else {
            (new AuthController())->showLoginForm();
        }
        break;

    case 'logout':
        (new AuthController())->handleLogout();
        break;

    case 'admin':
        (new AuthController())->showAdminForm();
        break;

    case 'updateAdmin':
        (new AuthController())->handleAdminUpdate();
        break;

    case 'export':
        (new DashboardController())->handleExport();
        break;

    case 'dashboard':
    default:
        require_once __DIR__ . '/../controllers/DashboardController.php';
        (new DashboardController())->show();
        break;

        
}
?>