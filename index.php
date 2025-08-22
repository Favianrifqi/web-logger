<?php
// index.php

// Panggil konfigurasi. Ini akan otomatis memulai sesi, koneksi DB,
// dan menjadi "penjaga gerbang" halaman ini.
require_once 'config.php';

// Cek apakah pengguna sudah login. Jika belum, tendang kembali ke halaman login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Semua kode di bawah ini hanya akan berjalan jika pengguna sudah login.
// Kita bisa langsung menggunakan variabel $pdo dari config.php.

date_default_timezone_set('Asia/Jakarta');
$rowsPerPage = 10;

function renderPagination($totalPages, $currentPage, $paramName) {
    if ($totalPages <= 1) return;
    echo '<div class="pagination">';
    if ($currentPage > 1) echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $currentPage - 1])).'">&laquo;</a>';
    $start = max(1, $currentPage - 2); $end = min($totalPages, $currentPage + 2);
    if($start > 1) { echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => 1])).'">1</a>'; if($start > 2) echo '<span class="disabled">...</span>'; }
    for ($i = $start; $i <= $end; $i++) { echo ($i == $currentPage) ? '<span class="active">'.$i.'</span>' : '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $i])).'">'.$i.'</a>'; }
    if($end < $totalPages) { if($end < $totalPages - 1) echo '<span class="disabled">...</span>'; echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $totalPages])).'">'.$totalPages.'</a>'; }
    if ($currentPage < $totalPages) echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $currentPage + 1])).'">&raquo;</a>';
    echo '</div>';
}

$lastUpdatedDate = date('Y-m-d');
$chartData = $browserChartData = $osChartData = ['labels' => [], 'data' => []];
$allData = [];
$errorMessage = null;

if ($pdo) {
    try {
        $pageParams = ['page_realtime', 'page_error', 'page_popular', 'page_ips', 'page_referrers'];
        $tableMap = ['realtime_logs', 'error_logs', 'pages', 'ips', 'referrers'];
        foreach ($pageParams as $index => $param) {
            $table = $tableMap[$index];
            $allData[$param]['currentPage'] = isset($_GET[$param]) ? (int)$_GET[$param] : 1;
            $allData[$param]['offset'] = ($allData[$param]['currentPage'] - 1) * $rowsPerPage;
            $totalRows = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            $allData[$param]['totalPages'] = ceil($totalRows / $rowsPerPage);
        }

        $summaryResult = $pdo->query("SELECT * FROM daily_summary LIMIT 1")->fetchAll(PDO::FETCH_ASSOC);
        $summary = $summaryResult[0] ?? ['unique_visitors' => 0, 'total_hits' => 0, 'date' => date('Y-m-d')];
        $lastUpdatedDate = $summary['date'];
        $allStatusCodes = $pdo->query("SELECT code, hits FROM status_codes ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        $realtimeLogs = $pdo->query("SELECT * FROM realtime_logs ORDER BY timestamp DESC LIMIT {$rowsPerPage} OFFSET {$allData['page_realtime']['offset']}")->fetchAll(PDO::FETCH_ASSOC);
        $errorLogs = $pdo->query("SELECT * FROM error_logs ORDER BY timestamp DESC LIMIT {$rowsPerPage} OFFSET {$allData['page_error']['offset']}")->fetchAll(PDO::FETCH_ASSOC);
        $topPages = $pdo->query("SELECT url, hits FROM pages ORDER BY hits DESC LIMIT {$rowsPerPage} OFFSET {$allData['page_popular']['offset']}")->fetchAll(PDO::FETCH_ASSOC);
        $topIps = $pdo->query("SELECT ip, hits FROM ips ORDER BY hits DESC LIMIT {$rowsPerPage} OFFSET {$allData['page_ips']['offset']}")->fetchAll(PDO::FETCH_ASSOC);
        $topReferrers = $pdo->query("SELECT domain, hits FROM referrers ORDER BY hits DESC LIMIT {$rowsPerPage} OFFSET {$allData['page_referrers']['offset']}")->fetchAll(PDO::FETCH_ASSOC);
        
        $topPagesForChart = $pdo->query("SELECT url, hits FROM pages ORDER BY hits DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
        $chartData = ['labels' => array_map(function($p) { $l = preg_replace('#^/.*?/#', '', $p['url']); return strlen($l) > 25 ? substr($l, 0, 25).'...' : ($l === '/' ? 'Home' : $l); }, $topPagesForChart), 'data' => array_column($topPagesForChart, 'hits')];
        $topBrowsers = $pdo->query("SELECT name, hits FROM browsers ORDER BY hits DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
        $browserChartData = ['labels' => array_column($topBrowsers, 'name'), 'data' => array_column($topBrowsers, 'hits')];
        $topOs = $pdo->query("SELECT name, hits FROM operating_systems ORDER BY hits DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
        $osChartData = ['labels' => array_column($topOs, 'name'), 'data' => array_column($topOs, 'hits')];

    } catch (PDOException $e) { $errorMessage = "Error database: Terjadi masalah saat mengambil data. Pastikan cron job `proses_log.php` sudah berjalan setidaknya sekali."; }
} else { $errorMessage = "Koneksi ke database gagal. Periksa file config.php."; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Log Website</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Dasbor Analisis Log Website</h1>
            <a href="logout.php" class="logout-button">Logout</a>
            <p>Data terakhir diperbarui pada: <?= htmlspecialchars($lastUpdatedDate) ?></p>
        </header>
        <?php if ($errorMessage): ?>
            <div class="card error-card"><h2>Error</h2><p><?= htmlspecialchars($errorMessage) ?></p></div>
        <?php else: ?>
            <div class="summary-cards">
                 <div class="card"><h2>Pengunjung Unik</h2><p><?= number_format($summary['unique_visitors']) ?></p></div>
                 <div class="card"><h2>Total Permintaan (Hits)</h2><p><?= number_format($summary['total_hits']) ?></p></div>
                 <div class="card"><h2>Status Codes</h2><p><?php if(empty($allStatusCodes)){ echo '<span>-</span>'; } else { foreach($allStatusCodes as $status){ echo '<span class="status-code status-'. substr($status['code'], 0, 1) .'xx">'. $status['code'] .': '. number_format($status['hits']) .'</span>'; } } ?></p></div>
            </div>
            <div class="table-card">
                <h2>Aktivitas Terbaru</h2>
                <table>
                    <thead><tr><th>Waktu</th><th>Alamat IP</th><th>Negara</th><th>URL</th><th>Status</th></tr></thead>
                    <tbody><?php foreach ($realtimeLogs as $log): ?><tr><td><?= htmlspecialchars($log['timestamp']) ?></td><td><?= htmlspecialchars($log['ip']) ?></td><td><?= htmlspecialchars($log['country']) ?></td><td class="url-cell" title="<?= htmlspecialchars($log['url']) ?>"><?= htmlspecialchars(substr($log['url'], 0, 70)) ?></td><td><span class="status-code status-<?= substr($log['status'], 0, 1) ?>xx"><?= htmlspecialchars($log['status']) ?></span></td></tr><?php endforeach; ?></tbody>
                </table>
                <?php renderPagination($allData['page_realtime']['totalPages'], $allData['page_realtime']['currentPage'], 'page_realtime'); ?>
            </div>
            <div class="main-content">
                <div class="table-card">
                    <h2>Halaman Terpopuler</h2>
                    <div class="chart-container"><canvas id="popularPagesChart"></canvas></div>
                </div>
                <div class="table-card">
                    <h2>Alamat IP Teratas</h2>
                    <table>
                        <thead><tr><th>Alamat IP</th><th>Hits</th></tr></thead>
                        <tbody><?php foreach ($topIps as $ip): ?><tr><td><?= htmlspecialchars($ip['ip']) ?></td><td><?= number_format($ip['hits']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                    <?php renderPagination($allData['page_ips']['totalPages'], $allData['page_ips']['currentPage'], 'page_ips'); ?>
                </div>
            </div>
             <div class="main-content-3-col">
                <div class="table-card">
                    <h2>Situs Perujuk Teratas</h2>
                    <table>
                        <thead><tr><th>Domain Perujuk</th><th>Hits</th></tr></thead>
                        <tbody><?php foreach ($topReferrers as $ref): ?><tr><td class="url-cell" title="<?= htmlspecialchars($ref['domain']) ?>"><?= htmlspecialchars($ref['domain']) ?></td><td><?= number_format($ref['hits']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                     <?php renderPagination($allData['page_referrers']['totalPages'], $allData['page_referrers']['currentPage'], 'page_referrers'); ?>
                </div>
                <div class="table-card"><h2>Statistik Browser</h2><div class="chart-container"><canvas id="browserChart"></canvas></div></div>
                <div class="table-card"><h2>Statistik Sistem Operasi</h2><div class="chart-container"><canvas id="osChart"></canvas></div></div>
            </div>
        <?php endif; ?>
        <footer><p>Proyek Mini SIEM - KKN</p></footer>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const createChart = (ctx, config) => new Chart(ctx, config);
            const popularPagesChartData = <?= json_encode($chartData); ?>;
            const browserChartData = <?= json_encode($browserChartData); ?>;
            const osChartData = <?= json_encode($osChartData); ?>;
            if (document.getElementById('popularPagesChart')) {
                createChart(document.getElementById('popularPagesChart').getContext('2d'), { type: 'bar', data: { labels: popularPagesChartData.labels, datasets: [{ label: 'Jumlah Hits', data: popularPagesChartData.data, backgroundColor: 'rgba(54, 162, 235, 0.6)' }] }, options: { indexAxis: 'y', scales: { x: { beginAtZero: true }, y: {} }, plugins: { legend: { display: false } }, maintainAspectRatio: false } });
            }
            if (document.getElementById('browserChart')) {
                createChart(document.getElementById('browserChart').getContext('2d'), { type: 'pie', data: { labels: browserChartData.labels, datasets: [{ data: browserChartData.data, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } } });
            }
            if (document.getElementById('osChart')) {
                createChart(document.getElementById('osChart').getContext('2d'), { type: 'doughnut', data: { labels: osChartData.labels, datasets: [{ data: osChartData.data, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } } });
            }
        });
    </script>
</body>
</html>