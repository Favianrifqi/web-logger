<?php
// Fungsi untuk render paginasi
function renderPagination($totalPages, $currentPage, $paramName, $anchorId = '') {
    if ($totalPages <= 1) return;
    $anchor = $anchorId ? '#' . $anchorId : '';
    echo '<div class="pagination">';
    if ($currentPage > 1) echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $currentPage - 1])).$anchor.'">&laquo;</a>';
    $start = max(1, $currentPage - 2); $end = min($totalPages, $currentPage + 2);
    if($start > 1) { echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => 1])).$anchor.'">1</a>'; if($start > 2) echo '<span class="disabled">...</span>'; }
    for ($i = $start; $i <= $end; $i++) { echo ($i == $currentPage) ? '<span class="active">'.$i.'</span>' : '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $i])).$anchor.'">'.$i.'</a>'; }
    if($end < $totalPages) { if($end < $totalPages - 1) echo '<span class="disabled">...</span>'; echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $totalPages])).$anchor.'">'.$totalPages.'</a>'; }
    if ($currentPage < $totalPages) echo '<a href="?'.http_build_query(array_merge($_GET, [$paramName => $currentPage + 1])).$anchor.'">&raquo;</a>';
    echo '</div>';
}

// FUNGSI BANTU BARU UNTUK MEMBUAT LINK EKSPOR DINAMIS
function getExportLink($tableName) {
    $params = ['action' => 'export', 'table' => $tableName];
    if (!empty($_GET['start_date'])) {
        $params['start_date'] = $_GET['start_date'];
    }
    if (!empty($_GET['end_date'])) {
        $params['end_date'] = $_GET['end_date'];
    }
    return 'index.php?' . http_build_query($params);
}

// Fungsi bantu baru untuk memotong teks URL dengan rapi
function truncateUrl($url, $length = 50) {
    if (strlen($url) > $length) {
        return substr($url, 0, $length) . '...';
    }
    return $url;
}
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
            <a href="index.php?action=admin" class="logout-button" style="background-color: var(--orange-color); right: 120px;">Admin Panel</a>
            <a href="index.php?action=logout" class="logout-button">Logout</a>
            <p>Data terakhir diperbarui pada: <?= htmlspecialchars($summary['date']) ?></p>
        </header>
        
        <div class="summary-cards">
            <div class="card"><h2>Pengunjung Unik</h2><p><?= number_format($summary['unique_visitors']) ?></p></div>
            <div class="card"><h2>Total Permintaan (Hits)</h2><p><?= number_format($summary['total_hits']) ?></p></div>
            <div class="card">
    <h2>Status Codes</h2>
    <div class="status-codes-container">
        <?php 
        if(empty($allStatusCodes)) { 
            echo '<span>-</span>'; 
        } else { 
            foreach($allStatusCodes as $status) {
                echo '<span class="status-code status-'. substr($status['code'], 0, 1) .'xx">'. $status['code'] .': '. number_format($status['hits']) .'</span>'; 
            } 
        } 
        ?>
    </div>
</div>
        </div>
        
        <div class="card" style="margin-bottom: 20px;">
            <h2>Tren Aktivitas 7 Hari Terakhir</h2>
            <div class="chart-container" style="height: 350px;">
                <canvas id="historicalChart"></canvas>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="main-column">
                <div class="table-card" id="aktivitas-terbaru">
                    <div class="table-header">
                        <h2>Aktivitas Terbaru</h2>
                        <a href="<?= getExportLink('realtime_logs') ?>" class="export-button">Ekspor CSV</a>
                    </div>
                    <div class="filter-controls">
                        <form action="index.php" method="GET" class="filter-form">
                            <input type="hidden" name="action" value="dashboard">
                            <div class="form-group-inline"><label for="start_date">Dari:</label><input type="date" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? '' ?>"></div>
                            <div class="form-group-inline"><label for="end_date">Sampai:</label><input type="date" name="end_date" id="end_date" value="<?= $_GET['end_date'] ?? '' ?>"></div>
                            <input type="search" name="q" placeholder="Cari IP, URL, Status..." value="<?= $searchTerm ?? '' ?>">
                            <button type="submit">Filter & Cari</button>
                            <?php if (!empty($searchTerm) || !empty($_GET['start_date']) || !empty($_GET['end_date'])): ?>
                                <a href="index.php?action=dashboard" class="clear-search">Reset Filter</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <table>
                        <thead><tr><th>Waktu</th><th>Alamat IP</th><th>Negara</th><th>URL</th><th>Status</th></tr></thead>
                        <tbody><?php foreach ($realtimeLogs as $log): ?><tr><td><?= htmlspecialchars($log['timestamp']) ?></td><td><a href="index.php?action=dashboard&q=<?= htmlspecialchars($log['ip']) ?>"><?= htmlspecialchars($log['ip']) ?></a></td><td><?= htmlspecialchars($log['country']) ?></td><td class="url-cell" title="<?= htmlspecialchars($log['url']) ?>"><?= htmlspecialchars(substr($log['url'], 0, 50)) ?>...</td><td><span class="status-code status-<?= substr($log['status'], 0, 1) ?>xx"><?= htmlspecialchars($log['status']) ?></span></td></tr><?php endforeach; ?></tbody>
                    </table>
                    <?php renderPagination($paginationData['page_realtime']['totalPages'], $paginationData['page_realtime']['currentPage'], 'page_realtime', 'aktivitas-terbaru'); ?>
                </div>

                <div class="table-card" id="detail-error">
                    <div class="table-header">
                        <h2>Detail Error Log</h2>
                        <a href="<?= getExportLink('error_logs') ?>" class="export-button">Ekspor CSV</a>
                    </div>
                    <table>
                        <thead><tr><th>Waktu</th><th>Alamat IP</th><th>URL Error</th><th>Status</th></tr></thead>
                        <tbody><?php foreach ($errorLogs as $error): ?><tr><td><?= htmlspecialchars($error['timestamp']) ?></td><td><a href="index.php?action=dashboard&q=<?= htmlspecialchars($error['ip']) ?>"><?= htmlspecialchars($error['ip']) ?></a></td><td class="url-cell" title="<?= htmlspecialchars($error['url']) ?>"><?= htmlspecialchars(substr($error['url'], 0, 50)) ?>...</td><td><span class="status-code status-<?= substr($error['status'], 0, 1) ?>xx"><?= htmlspecialchars($error['status']) ?></span></td></tr><?php endforeach; ?></tbody>
                    </table>
                    <?php renderPagination($paginationData['page_error']['totalPages'], $paginationData['page_error']['currentPage'], 'page_error', 'detail-error'); ?>
                </div>
            </div>

            <div class="sidebar-column">
                <div class="table-card" id="halaman-populer">
                    <div class="table-header">
                        <h2>Halaman Terpopuler</h2>
                        <a href="<?= getExportLink('pages') ?>" class="export-button">Ekspor CSV</a>
                    </div>
                    <div class="chart-container" style="height: 200px;"><canvas id="popularPagesChart"></canvas></div>
                    <table>
                        <thead><tr><th>URL</th><th>Hits</th></tr></thead>
                        <tbody><?php foreach ($topPages as $page): ?><tr><td class="url-cell" title="<?= htmlspecialchars($page['url']) ?>"><?= htmlspecialchars(substr($page['url'], 0, 30)) ?>...</td><td><?= number_format($page['hits']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                    <?php renderPagination($paginationData['page_popular']['totalPages'], $paginationData['page_popular']['currentPage'], 'page_popular', 'halaman-populer'); ?>
                </div>
                <div class="table-card" id="alamat-ip-teratas">
                    <div class="table-header">
                        <h2>Alamat IP Teratas</h2>
                        <a href="<?= getExportLink('ips') ?>" class="export-button">Ekspor CSV</a>
                    </div>
                    <table>
                        <thead><tr><th>Alamat IP</th><th>Hits</th></tr></thead>
                        <tbody><?php foreach ($topIps as $ip): ?><tr><td><a href="index.php?action=dashboard&q=<?= htmlspecialchars($ip['ip']) ?>"><?= htmlspecialchars($ip['ip']) ?></a></td><td><?= number_format($ip['hits']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                    <?php renderPagination($paginationData['page_ips']['totalPages'], $paginationData['page_ips']['currentPage'], 'page_ips', 'alamat-ip-teratas'); ?>
                </div>
                <div class="table-card" id="situs-perujuk">
                    <div class="table-header">
                        <h2>Situs Perujuk Teratas</h2>
                        <a href="<?= getExportLink('referrers') ?>" class="export-button">Ekspor CSV</a>
                    </div>
                    <table>
                        <thead><tr><th>Domain Perujuk</th><th>Hits</th></tr></thead>
                        <tbody><?php foreach ($topReferrers as $ref): ?><tr><td class="url-cell" title="<?= htmlspecialchars($ref['domain']) ?>"><?= htmlspecialchars($ref['domain']) ?></td><td><?= number_format($ref['hits']) ?></td></tr><?php endforeach; ?></tbody>
                    </table>
                    <?php renderPagination($paginationData['page_referrers']['totalPages'], $paginationData['page_referrers']['currentPage'], 'page_referrers', 'situs-perujuk'); ?>
                </div>
                <div class="chart-grid">
                    <div class="table-card"><h2>Statistik Browser</h2><div class="chart-container" style="height: 250px;"><canvas id="browserChart"></canvas></div></div>
                    <div class="table-card"><h2>Statistik Sistem Operasi</h2><div class="chart-container" style="height: 250px;"><canvas id="osChart"></canvas></div></div>
                </div>
            </div>
        </div>
        
        <footer><p>Proyek Mini SIEM - KKN</p></footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const createChart = (ctx, config) => { if(ctx) new Chart(ctx, config); };
            const popularPagesCtx = document.getElementById('popularPagesChart')?.getContext('2d');
            const browserCtx = document.getElementById('browserChart')?.getContext('2d');
            const osCtx = document.getElementById('osChart')?.getContext('2d');
            const historicalCtx = document.getElementById('historicalChart')?.getContext('2d');
            const popularPagesChartData = <?= json_encode($chartData); ?>;
            const browserChartData = <?= json_encode($browserChartData); ?>;
            const osChartData = <?= json_encode($osChartData); ?>;
            const historicalDataRaw = <?= json_encode($historicalData); ?>;
            const historicalLabels = historicalDataRaw.map(d => d.date);
            const uniqueVisitorsData = historicalDataRaw.map(d => d.unique_visitors);
            const totalHitsData = historicalDataRaw.map(d => d.total_hits);
            createChart(historicalCtx, { type: 'line', data: { labels: historicalLabels, datasets: [{ label: 'Pengunjung Unik', data: uniqueVisitorsData, borderColor: 'rgba(54, 162, 235, 1)', backgroundColor: 'rgba(54, 162, 235, 0.2)', fill: true, tension: 0.4 }, { label: 'Total Hits', data: totalHitsData, borderColor: 'rgba(75, 192, 192, 1)', backgroundColor: 'rgba(75, 192, 192, 0.2)', fill: true, tension: 0.4 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true }, y: { beginAtZero: true } }, plugins: { legend: { position: 'top' } } } });
            createChart(popularPagesCtx, { type: 'bar', data: { labels: popularPagesChartData.labels, datasets: [{ label: 'Jumlah Hits', data: popularPagesChartData.data, backgroundColor: 'rgba(54, 162, 235, 0.6)' }] }, options: { indexAxis: 'y', scales: { x: { beginAtZero: true }, y: {} }, plugins: { legend: { display: false } }, maintainAspectRatio: false } });
            createChart(browserCtx, { type: 'pie', data: { labels: browserChartData.labels, datasets: [{ data: browserChartData.data, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } } });
            createChart(osCtx, { type: 'doughnut', data: { labels: osChartData.labels, datasets: [{ data: osChartData.data, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } } });
        });
    </script>
</body>
</html>