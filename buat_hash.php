<?php
// Ganti 'password_baru_anda' dengan password yang Anda inginkan
$password_baru = 'admin123'; 
echo "Password: " . $password_baru . "<br>";
echo "Hash: " . password_hash($password_baru, PASSWORD_DEFAULT);
?>