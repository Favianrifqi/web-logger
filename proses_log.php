<?php
// Atur zona waktu ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');
echo "Memulai proses log pada: " . date('Y-m-d H:i:s') . "\n";

// path ke file log
$logFilePath = 'access.log';

// Lokasi file database SQLite
$dbPath = __DIR__ . '/data/logger.db';
// --------------------------

// Cek apakah file log ada dan bisa dibaca
if (!file_exists($logFilePath) || !is_readable($logFilePath)) {
    die("Error: File log tidak ditemukan atau tidak bisa dibaca di path: " . $logFilePath . "\n");
}

try {
    // Koneksi ke database SQLite
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buat tabel jika belum ada
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS daily_summary (
            date TEXT PRIMARY KEY,
            unique_visitors INTEGER,
            total_hits INTEGER
        );
        CREATE TABLE IF NOT EXISTS pages (
            url TEXT PRIMARY KEY,
            hits INTEGER
        );
        CREATE TABLE IF NOT EXISTS ips (
            ip TEXT PRIMARY KEY,
            hits INTEGER
        );
        CREATE TABLE IF NOT EXISTS status_codes (
            code INTEGER PRIMARY KEY,
            hits INTEGER
        );
    ");

    echo "Koneksi database berhasil dan tabel sudah siap.\n";

    // Kosongkan data lama sebelum memproses yang baru agar tidak double counting
    $pdo->exec("DELETE FROM daily_summary;");
    $pdo->exec("DELETE FROM pages;");
    $pdo->exec("DELETE FROM ips;");
    $pdo->exec("DELETE FROM status_codes;");

    // Array untuk menampung data sementara
    $visitors = [];
    $pages = [];
    $ips = [];
    $status_codes = [];
    $total_hits = 0;

    // Buka file log untuk dibaca
    $handle = fopen($logFilePath, 'r');
    if ($handle) {
        echo "Mulai membaca file log...\n";
        // Pola Regex untuk mem-parsing format log Combined
        $regex = '/^(\S+) \S+ \S+ \[(.*?)\].*?"\S+ (\S+) \S+" (\d+)/';

        while (($line = fgets($handle)) !== false) {
            if (preg_match($regex, $line, $matches)) {
                $ip = $matches[1];
                $url = $matches[3];
                $status = (int)$matches[4];

                // Hitung total request
                $total_hits++;

                // Agregasi data
                $visitors[$ip] = true; // Untuk menghitung pengunjung unik
                
                isset($pages[$url]) ? $pages[$url]++ : $pages[$url] = 1;
                isset($ips[$ip]) ? $ips[$ip]++ : $ips[$ip] = 1;
                isset($status_codes[$status]) ? $status_codes[$status]++ : $status_codes[$status] = 1;
            }
        }
        fclose($handle);
        echo "Selesai membaca file log. Total " . $total_hits . " baris diproses.\n";
    }

    // Mulai transaksi database untuk mempercepat proses insert
    $pdo->beginTransaction();

    // Simpan data ringkasan harian
    $stmt = $pdo->prepare("INSERT INTO daily_summary (date, unique_visitors, total_hits) VALUES (?, ?, ?)");
    $stmt->execute([date('Y-m-d'), count($visitors), $total_hits]);

    // Simpan data halaman
    $stmt = $pdo->prepare("INSERT INTO pages (url, hits) VALUES (?, ?)");
    foreach ($pages as $url => $hits) {
        $stmt->execute([$url, $hits]);
    }

    // Simpan data IP
    $stmt = $pdo->prepare("INSERT INTO ips (ip, hits) VALUES (?, ?)");
    foreach ($ips as $ip => $hits) {
        $stmt->execute([$ip, $hits]);
    }

    // Simpan data status code
    $stmt = $pdo->prepare("INSERT INTO status_codes (code, hits) VALUES (?, ?)");
    foreach ($status_codes as $code => $hits) {
        $stmt->execute([$code, $hits]);
    }

    // Selesaikan transaksi
    $pdo->commit();
    echo "Semua data berhasil disimpan ke database.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}

echo "Proses selesai pada: " . date('Y-m-d H:i:s') . "\n";
?>