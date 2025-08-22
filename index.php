<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$rowsPerPage = 10;

function getDbConnection() {
    $dbHost = 'localhost'; $dbName = 'proyek_logger'; $dbUser = 'root'; $dbPass = '';
    try {
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) { return null; }
}

function renderPagination($totalPages, $currentPage, $paramName) {
    if ($totalPages <= 1) return;
    echo '<div class="pagination">';
    if ($currentPage > 1) echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $currentPage - 1])).'">&laquo;</a>';
    $start = max(1, $currentPage - 2); $end = min($totalPages, $currentPage + 2);
    if($start > 1) {
        echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => 1])).'">1</a>';
        if($start > 2) echo '<span class="disabled">...</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) echo '<span class="active">'.$i.'</span>';
        else echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $i])).'">'.$i.'</a>';
    }
    if($end < $totalPages) {
        if($end < $totalPages - 1) echo '<span class="disabled">...</span>';
        echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $totalPages])).'">'.$totalPages.'</a>';
    }
    if ($currentPage < $totalPages) echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $currentPage + 1])).'">&raquo;</a>';
    echo '</div>';
}

$pdo = getDbConnection();
$lastUpdatedDate = date('Y-m-d');
$chartData = $browserChartData = $osChartData = ['labels' => [], 'data' => []];
$allData = [];

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
        $summary = $summaryResult[0] ?? ['unique_visitors' => 0, 'total_hits' => 0];
        $lastUpdatedDate = $summary['date'] ?? date('Y-m-d');
        $allStatusCodes = $pdo->query("SELECT code, hits FROM status_codes ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        $realtimeLogs = $pdo->query("SELECT * FROM realtime_logs ORDER BY timestamp DESC LIMIT {$rowsPerPage} OFFSET {$allData['page_realtime']['offset']}")->fetchAll(PDO::FETCH_ASSOC);
        $errorLogs = $pdo->query("SELECT * FROM error_logs ORDER BY timestamp DESC LIMIT {$rowsPerPage} OFFSET {$allData['page_error']['offset']}")->fetchAll(PDO::FETCH_ASSOC);
        $topPages = $pdo->query("SELECT url, hits FROM pages ORDER BY hits DESC LIMIT {$rowsPerPage} OFFSET {$allData['page_popular']['offset']}")->fetchAll(PDO::FETCH_ASSOC);
        $topIps = $pdo->query("SELECT ip, hits FROM ips ORDER BY hits DESC LIMIT {$rowsPerPage} OFFSET {$allData['page_ips']['offset']}")->fetchAll(PDO::FETCH_ASSOC);
        $topReferrers = $pdo->query("SELECT domain, hits FROM referrers ORDER BY hits DESC LIMIT {$rowsPerPage} OFFSET {$allData['page_referrers']['offset']}")->fetchAll(PDO::FETCH_ASSOC);
        
        $topPagesForChart = $pdo->query("SELECT url, hits FROM pages ORDER BY hits DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
        $chartLabels = []; $chartHits = [];
        foreach ($topPagesForChart as $page) {
            $label = preg_replace('#^/(halaman/|berita/)?#', '', $page['url']);
            $label = $label === '/' ? 'Halaman Utama' : $label;
            $label = strlen($label) > 25 ? substr($label, 0, 25) . '...' : $label;
            $chartLabels[] = $label; $chartHits[] = $page['hits'];
        }
        $chartData = ['labels' => $chartLabels, 'data' => $chartHits];
        
        $topBrowsers = $pdo->query("SELECT name, hits FROM browsers ORDER BY hits DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
        $browserLabels = []; $browserHits = [];
        foreach ($topBrowsers as $browser) { $browserLabels[] = $browser['name']; $browserHits[] = $browser['hits']; }
        $browserChartData = ['labels' => $browserLabels, 'data' => $browserHits];
        
        $topOs = $pdo->query("SELECT name, hits FROM operating_systems ORDER BY hits DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
        $osLabels = []; $osHits = [];
        foreach ($topOs as $os) { $osLabels[] = $os['name']; $osHits[] = $os['hits']; }
        $osChartData = ['labels' => $osLabels, 'data' => $osHits];

    } catch (PDOException $e) { $errorMessage = "Tabel di database belum ada. Jalankan `proses_log.php` dulu."; }
} else { $errorMessage = "Koneksi ke database gagal. Pastikan detail koneksi di `config.php` sudah benar dan server MySQL di XAMPP sudah berjalan."; }
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
        <?php if (isset($errorMessage)): ?>
            <div class="card error-card"><h2>Error</h2><p><?= htmlspecialchars($errorMessage) ?></p></div>
        <?php else: ?>
            <div class="summary-cards">
                <div class="card"><h2>Pengunjung Unik</h2><p><?= number_format($summary['unique_visitors'] ?? 0) ?></p></div>
                <div class="card"><h2>Total Permintaan (Hits)</h2><p><?= number_format($summary['total_hits'] ?? 0) ?></p></div>
                <div class="card"><h2>Status Codes</h2><p><?php if (empty($allStatusCodes)) echo '<span>-</span>'; else foreach($allStatusCodes as $status) echo '<span class="status-code status-'. substr($status['code'], 0, 1) .'xx">'. $status['code'] .': '. number_format($status['hits']) .'</span>'; ?></p></div>
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
                    <table>
                        <thead><tr><th>URL</th><th>Hits</th></tr></thead>
                        <tbody><?php foreach ($topPages as $page): ?><tr><td class="url-cell" title="<?= htmlspecialchars($page['url']) ?>"><?= htmlspecialchars(substr($page['url'], 0, 40)) ?></td><td><?= number_format($page['hits']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                    <?php renderPagination($allData['page_popular']['totalPages'], $allData['page_popular']['currentPage'], 'page_popular'); ?>
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
            <div class="table-card">
                <h2>Detail Error Log</h2>
                <table>
                    <thead><tr><th>Waktu</th><th>Alamat IP</th><th>URL Error</th><th>Status</th></tr></thead>
                    <tbody><?php foreach ($errorLogs as $error): ?><tr><td><?= htmlspecialchars($error['timestamp']) ?></td><td><?= htmlspecialchars($error['ip']) ?></td><td class="url-cell" title="<?= htmlspecialchars($error['url']) ?>"><?= htmlspecialchars(substr($error['url'], 0, 70)) ?></td><td><span class="status-code status-<?= substr($error['status'], 0, 1) ?>xx"><?= htmlspecialchars($error['status']) ?></span></td></tr><?php endforeach; ?></tbody>
                </table>
                <?php renderPagination($allData['page_error']['totalPages'], $allData['page_error']['currentPage'], 'page_error'); ?>
            </div>
        <?php endif; ?>
        <footer><p>Proyek Mini SIEM - KKN</p></footer>
    </div>
    <script>
        const popularPagesChartData = <?= json_encode($chartData); ?>;
        const browserChartData = <?= json_encode($browserChartData); ?>;
        const osChartData = <?= json_encode($osChartData); ?>;
        if (document.getElementById('popularPagesChart')) {
            const ctx = document.getElementById('popularPagesChart').getContext('2d');
            new Chart(ctx, { type: 'bar', data: { labels: popularPagesChartData.labels, datasets: [{ label: 'Jumlah Hits', data: popularPagesChartData.data, backgroundColor: 'rgba(54, 162, 235, 0.6)' }] }, options: { indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { color: '#999' }, grid: { color: '#444' } }, y: { ticks: { color: '#999' }, grid: { color: '#444' } } }, plugins: { legend: { display: false } }, maintainAspectRatio: false } });
        }
        if (document.getElementById('browserChart')) {
            const ctxBrowser = document.getElementById('browserChart').getContext('2d');
            new Chart(ctxBrowser, { type: 'pie', data: { labels: browserChartData.labels, datasets: [{ data: browserChartData.data, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'], }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top', labels: {color: '#e0e0e0'} } } } });
        }
        if (document.getElementById('osChart')) {
            const ctxOs = document.getElementById('osChart').getContext('2d');
            new Chart(ctxOs, { type: 'doughnut', data: { labels: osChartData.labels, datasets: [{ data: osChartData.data, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'], }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top', labels: {color: '#e0e0e0'} } } } });
        }
    </script>
</body>
</html>