<?php
// logout.php

// Panggil konfigurasi untuk memulai sesi
require_once 'config.php';

// Hapus semua variabel sesi
$_SESSION = array();

// Hancurkan sesi
session_destroy();

// Arahkan kembali ke halaman login
header("location: login.php");
exit;
?>