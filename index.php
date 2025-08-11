<?php
// Halaman Dasbor untuk Menampilkan Data Log
// ------------------------------------------
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk koneksi ke DB
function getDbConnection() {
    // SOLUSI: Definisikan $dbPath di dalam fungsi ini, tepat di mana ia dibutuhkan.
    $dbPath = __DIR__ . '/data/logger.db'; 
    
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // Jika DB belum ada, tampilkan pesan error yang ramah
        if (strpos($e->getMessage(), 'unable to open database file') !== false) {
            return null;
        }
        die("Error koneksi database: " . $e->getMessage());
    }
}

// Fungsi untuk mengambil data dari DB
function fetchData($pdo, $query) {
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Mulai Logika Utama Halaman ---
$pdo = getDbConnection();
$summary = [];
$topPages = [];
$topIps = [];
$allStatusCodes = [];
$lastUpdatedDate = date('Y-m-d'); // Default date

if ($pdo) {
    $summaryResult = fetchData($pdo, "SELECT * FROM daily_summary LIMIT 1");
    if ($summaryResult) {
        $summary = $summaryResult[0];
        $lastUpdatedDate = $summary['date'];
    } else {
        $summary = ['unique_visitors' => 0, 'total_hits' => 0];
    }
    
    $topPages = fetchData($pdo, "SELECT url, hits FROM pages ORDER BY hits DESC LIMIT 10");
    $topIps = fetchData($pdo, "SELECT ip, hits FROM ips ORDER BY hits DESC LIMIT 10");
    $allStatusCodes = fetchData($pdo, "SELECT code, hits FROM status_codes ORDER BY code ASC");

} else {
    // Pesan jika database belum dibuat oleh skrip backend
    $errorMessage = "Database belum ada. Silakan jalankan skrip `proses_log.php` terlebih dahulu melalui Cron Job (atau secara manual untuk tes lokal) untuk pertama kali.";
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Log Website</title>
    <link rel="stylesheet" href="assets/style.css"> 
</head>
<body>
    <div class="container">
        <header>
            <h1>Dasbor Analisis Log Website</h1>
            <p>Data terakhir diperbarui pada: <?= htmlspecialchars($lastUpdatedDate) ?></p>
        </header>

        <?php if (isset($errorMessage)): ?>
            <div class="card error-card">
                <h2>Error</h2>
                <p><?= htmlspecialchars($errorMessage) ?></p>
            </div>
        <?php else: ?>
            <div class="summary-cards">
                <div class="card">
                    <h2>Pengunjung Unik</h2>
                    <p><?= number_format($summary['unique_visitors']) ?></p>
                </div>
                <div class="card">
                    <h2>Total Permintaan (Hits)</h2>
                    <p><?= number_format($summary['total_hits']) ?></p>
                </div>
                <div class="card">
                    <h2>Status Codes</h2>
                    <p>
                        <?php if (empty($allStatusCodes)): ?>
                            <span>-</span>
                        <?php else: ?>
                            <?php foreach($allStatusCodes as $status): ?>
                                <span class="status-code status-<?= substr($status['code'], 0, 1) ?>xx"><?= $status['code'] ?>: <?= number_format($status['hits']) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="main-content">
                <div class="table-card">
                    <h2>Halaman Terpopuler (Top 10)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th>Hits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topPages)): ?>
                                <tr><td colspan="2">Tidak ada data.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topPages as $page): ?>
                                <tr>
                                    <td class="url-cell" title="<?= htmlspecialchars($page['url']) ?>"><?= htmlspecialchars(strlen($page['url']) > 70 ? substr($page['url'], 0, 70) . '...' : $page['url']) ?></td>
                                    <td><?= number_format($page['hits']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-card">
                    <h2>Alamat IP Teratas (Top 10)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Alamat IP</th>
                                <th>Hits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topIps)): ?>
                                <tr><td colspan="2">Tidak ada data.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topIps as $ip): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ip['ip']) ?></td>
                                    <td><?= number_format($ip['hits']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <footer>
            <p>Proyek Mini SIEM - KKN</p>
        </footer>
    </div>
</body>
</html>