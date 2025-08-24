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
            <div class="card"><h2>Status Codes</h2><p><?php foreach($allStatusCodes as $status) echo '<span class="status-code status-'. substr($status['code'], 0, 1) .'xx">'. $status['code'] .': '. number_format($status['hits']) .'</span>'; ?></p></div>
        </div>
        
        <div class="table-card">
            <h2>Aktivitas Terbaru</h2>
            <table>
                <thead><tr><th>Waktu</th><th>Alamat IP</th><th>Negara</th><th>URL</th><th>Status</th></tr></thead>
                <tbody><?php foreach ($realtimeLogs as $log): ?><tr><td><?= htmlspecialchars($log['timestamp']) ?></td><td><?= htmlspecialchars($log['ip']) ?></td><td><?= htmlspecialchars($log['country']) ?></td><td class="url-cell" title="<?= htmlspecialchars($log['url']) ?>"><?= htmlspecialchars(substr($log['url'], 0, 70)) ?></td><td><span class="status-code status-<?= substr($log['status'], 0, 1) ?>xx"><?= htmlspecialchars($log['status']) ?></span></td></tr><?php endforeach; ?></tbody>
            </table>
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
            </div>
        </div>
        
        <div class="main-content-3-col">
            <div class="table-card">
                <h2>Situs Perujuk Teratas</h2>
                <table>
                    <thead><tr><th>Domain Perujuk</th><th>Hits</th></tr></thead>
                    <tbody><?php foreach ($topReferrers as $ref): ?><tr><td class="url-cell" title="<?= htmlspecialchars($ref['domain']) ?>"><?= htmlspecialchars($ref['domain']) ?></td><td><?= number_format($ref['hits']) ?></td></tr><?php endforeach; ?></tbody>
                </table>
            </div>
            <div class="table-card"><h2>Statistik Browser</h2><div class="chart-container"><canvas id="browserChart"></canvas></div></div>
            <div class="table-card"><h2>Statistik Sistem Operasi</h2><div class="chart-container"><canvas id="osChart"></canvas></div></div>
        </div>

        <footer><p>Proyek Mini SIEM - KKN</p></footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ... (Kode JavaScript untuk Chart.js sama seperti sebelumnya)
            const popularPagesChartData = <?= json_encode($chartData); ?>;
            const browserChartData = <?= json_encode($browserChartData); ?>;
            const osChartData = <?= json_encode($osChartData); ?>;
            // (sisanya sama)
        });
    </script>
</body>
</html>