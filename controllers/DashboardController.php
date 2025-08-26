<?php

require_once __DIR__ . '/../models/LogModel.php';

class DashboardController {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit;
        }
    }

    public function show() {
        $logModel = new LogModel(getDbConnection());
        $rowsPerPage = 10;

        // Ambil term pencarian dari URL, bersihkan untuk keamanan
        $searchTerm = isset($_GET['q']) ? trim(htmlspecialchars($_GET['q'])) : '';
        
        // Menyiapkan data paginasi
        $pageParams = [
            'page_realtime' => 'realtime_logs', 
            'page_error' => 'error_logs', 
            'page_popular' => 'pages', 
            'page_ips' => 'ips', 
            'page_referrers' => 'referrers'
        ];
        
        $paginationData = [];
        foreach ($pageParams as $param => $table) {
            $currentPage = isset($_GET[$param]) ? (int)$_GET[$param] : 1; // Halaman saat ini
            $offset = ($currentPage - 1) * $rowsPerPage; // Offset untuk query
            $searchableTables = ['realtime_logs', 'error_logs']; // Tabel yang mendukung pencarian
            $currentSearchTerm = in_array($table, $searchableTables) ? $searchTerm : ''; // Gunakan searchTerm saat menghitung total baris untuk tabel yang difilter
            $totalRows = $logModel->getTotalRows($table, $currentSearchTerm); // Total baris untuk tabel ini
            // Menyimpan data paginasi untuk setiap tabel
            $paginationData[$param] = [
                'currentPage' => $currentPage,
                'offset' => $offset,
                'totalPages' => ceil($totalRows / $rowsPerPage)
            ];
        }

        // Mengambil semua data untuk ditampilkan
        $data = [
            'searchTerm' => $searchTerm, // Kirim searchTerm ke view
            'summary' => $logModel->getSummary(), // ringkasan harian
            'allStatusCodes' => $logModel->getAllStatusCodes(), // semua kode status
            'realtimeLogs' => $logModel->getLogs('realtime_logs', $rowsPerPage, $paginationData['page_realtime']['offset'], $searchTerm), // tabel realtime_logs mendukung pencarian
            'errorLogs' => $logModel->getLogs('error_logs', $rowsPerPage, $paginationData['page_error']['offset'], $searchTerm), // tabel error_logs mendukung pencarian
            'topPages' => $logModel->getTopItems('pages', 'url', $rowsPerPage, $paginationData['page_popular']['offset']), // tabel pages
            'topIps' => $logModel->getTopItems('ips', 'ip', $rowsPerPage, $paginationData['page_ips']['offset']), // tabel ips
            'topReferrers' => $logModel->getTopItems('referrers', 'domain', $rowsPerPage, $paginationData['page_referrers']['offset']), // tabel referrers
            'chartData' => $logModel->getTopPagesForChart(7), // data grafik halaman teratas
            'browserChartData' => $logModel->getTopStats('browsers', 7), // data grafik browser
            'osChartData' => $logModel->getTopStats('operating_systems', 7), // data grafik OS
            'historicalData' => $logModel->getHistoricalSummary(7), // ringkasan historis
            'paginationData' => $paginationData // data paginasi untuk semua tabel 
        ];
         // Memuat view dan mengoper data
        extract($data);
        require_once __DIR__ . '/../views/dashboard.php';
    }
}
?>