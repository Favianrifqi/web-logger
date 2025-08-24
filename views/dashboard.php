<?php
// Fungsi untuk render paginasi, kita letakkan di sini agar view tetap rapi
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
            <div class="card"><h2>Status Codes</h2><p><?php if(empty($allStatusCodes)){ echo '<span>-</span>'; } else { foreach($allStatusCodes as $status){ echo '<span class="status-code status-'. substr($status['code'], 0, 1) .'xx">'. $status['code'] .': '. number_format($status['hits']) .'</span>'; } } ?></p></div>
        </div>
        
        <div class="table-card">
            <h2>Aktivitas Terbaru</h2>
            <table>
                <thead><tr><th>Waktu</th><th>Alamat IP</th><th>Negara</th><th>URL</th><th>Status</th></tr></thead>
                <tbody><?php foreach ($realtimeLogs as $log): ?><tr><td><?= htmlspecialchars($log['timestamp']) ?></td><td><?= htmlspecialchars($log['ip']) ?></td><td><?= htmlspecialchars($log['country']) ?></td><td class="url-cell" title="<?= htmlspecialchars($log['url']) ?>"><?= htmlspecialchars(substr($log['url'], 0, 70)) ?></td><td><span class="status-code status-<?= substr($log['status'], 0, 1) ?>xx"><?= htmlspecialchars($log['status']) ?></span></td></tr><?php endforeach; ?></tbody>
            </table>
            <?php renderPagination($paginationData['page_realtime']['totalPages'], $paginationData['page_realtime']['currentPage'], 'page_realtime'); ?>
        </div>

        <div class="main-content">
            <div class="table-card">
                <h2>Halaman Terpopuler</h2>
                <div class="chart-container"><canvas id="popularPagesChart"></canvas></div>
                <table>
                    <thead><tr><th>URL</th><th>Hits</th></tr></thead>
                    <tbody><?php foreach ($topPages as $page): ?><tr><td class="url-cell" title="<?= htmlspecialchars($page['url']) ?>"><?= htmlspecialchars(substr($page['url'], 0, 40)) ?></td><td><?= number_format($page['hits']) ?></td></tr><?php endforeach; ?></tbody>
                </table>
                <?php renderPagination($paginationData['page_popular']['totalPages'], $paginationData['page_popular']['currentPage'], 'page_popular'); ?>
            </div>
            <div class="table-card">
                <h2>Alamat IP Teratas</h2>
                <table>
                    <thead><tr><th>Alamat IP</th><th>Hits</th></tr></thead>
                    <tbody><?php foreach ($topIps as $ip): ?><tr><td><?= htmlspecialchars($ip['ip']) ?></td><td><?= number_format($ip['hits']) ?></td></tr><?php endforeach; ?></tbody>
                </table>
                <?php renderPagination($paginationData['page_ips']['totalPages'], $paginationData['page_ips']['currentPage'], 'page_ips'); ?>
            </div>
        </div>
        
        <div class="main-content-3-col">
            <div class="table-card">
                <h2>Situs Perujuk Teratas</h2>
                <table>
                    <thead><tr><th>Domain Perujuk</th><th>Hits</th></tr></thead>
                    <tbody><?php foreach ($topReferrers as $ref): ?><tr><td class="url-cell" title="<?= htmlspecialchars($ref['domain']) ?>"><?= htmlspecialchars($ref['domain']) ?></td><td><?= number_format($ref['hits']) ?></td></tr><?php endforeach; ?></tbody>
                </table>
                <?php renderPagination($paginationData['page_referrers']['totalPages'], $paginationData['page_referrers']['currentPage'], 'page_referrers'); ?>
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
            <?php renderPagination($paginationData['page_error']['totalPages'], $paginationData['page_error']['currentPage'], 'page_error'); ?>
        </div>

        <footer><p>Proyek Mini SIEM - KKN</p></footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const createChart = (ctx, config) => { if(ctx) new Chart(ctx, config); };
            const popularPagesCtx = document.getElementById('popularPagesChart')?.getContext('2d');
            const browserCtx = document.getElementById('browserChart')?.getContext('2d');
            const osCtx = document.getElementById('osChart')?.getContext('2d');
            
            const popularPagesChartData = <?= json_encode($chartData); ?>;
            const browserChartData = <?= json_encode($browserChartData); ?>;
            const osChartData = <?= json_encode($osChartData); ?>;
            
            createChart(popularPagesCtx, { type: 'bar', data: { labels: popularPagesChartData.labels, datasets: [{ label: 'Jumlah Hits', data: popularPagesChartData.data, backgroundColor: 'rgba(54, 162, 235, 0.6)' }] }, options: { indexAxis: 'y', scales: { x: { beginAtZero: true }, y: {} }, plugins: { legend: { display: false } }, maintainAspectRatio: false } });
            createChart(browserCtx, { type: 'pie', data: { labels: browserChartData.labels, datasets: [{ data: browserChartData.data, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } } });
            createChart(osCtx, { type: 'doughnut', data: { labels: osChartData.labels, datasets: [{ data: osChartData.data, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } } });
        });
    </script>
</body>
</html>