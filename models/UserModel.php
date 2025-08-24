<?php
// models/UserModel.php

class UserModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function findByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCredentials($id, $newUsername, $newHashedPassword = null) {
        if ($newHashedPassword) {
            // Jika password juga diubah
            $stmt = $this->pdo->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
            return $stmt->execute([$newUsername, $newHashedPassword, $id]);
        } else {
            // Jika hanya username yang diubah
            $stmt = $this->pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            return $stmt->execute([$newUsername, $id]);
        }
    }
}
?>