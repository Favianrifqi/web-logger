<?php
// controllers/DashboardController.php (VERSI DENGAN FILTER TANGGAL)

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

        // Ambil dan validasi input dari URL
        $searchTerm = isset($_GET['q']) ? trim(htmlspecialchars($_GET['q'])) : '';
        $startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : '';
        $sortOrder = (!empty($startDate) || !empty($endDate)) ? 'ASC' : 'DESC';

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
            $currentPage = isset($_GET[$param]) ? (int)$_GET[$param] : 1;
            $offset = ($currentPage - 1) * $rowsPerPage;
            
            $searchableTables = ['realtime_logs', 'error_logs'];
            $currentSearchTerm = in_array($table, $searchableTables) ? $searchTerm : '';
            // Hanya tabel log yang difilter tanggal
            $currentStartDate = in_array($table, $searchableTables) ? $startDate : '';
            $currentEndDate = in_array($table, $searchableTables) ? $endDate : '';
            $totalRows = $logModel->getTotalRows($table, $currentSearchTerm, $currentStartDate, $currentEndDate);
            
            $paginationData[$param] = [
                'currentPage' => $currentPage,
                'offset' => $offset,
                'totalPages' => ceil($totalRows / $rowsPerPage)
            ];
        }

        // Mengambil semua data untuk ditampilkan dengan filter
        $data = [
            'searchTerm' => $searchTerm,
            'summary' => $logModel->getSummary($startDate, $endDate),
            'allStatusCodes' => $logModel->getAllStatusCodes(),
            'realtimeLogs' => $logModel->getLogs('realtime_logs', $rowsPerPage, $paginationData['page_realtime']['offset'], $searchTerm, $startDate, $endDate),
            'errorLogs' => $logModel->getLogs('error_logs', $rowsPerPage, $paginationData['page_error']['offset'], $searchTerm, $startDate, $endDate),
            'topPages' => $logModel->getTopItems('pages', 'url', $rowsPerPage, $paginationData['page_popular']['offset']),
            'topIps' => $logModel->getTopItems('ips', 'ip', $rowsPerPage, $paginationData['page_ips']['offset']),
            'topReferrers' => $logModel->getTopItems('referrers', 'domain', $rowsPerPage, $paginationData['page_referrers']['offset']),
            'chartData' => $logModel->getTopPagesForChart(7),
            'browserChartData' => $logModel->getTopStats('browsers', 7),
            'osChartData' => $logModel->getTopStats('operating_systems', 7),
            'historicalData' => $logModel->getHistoricalSummary(7),
            'paginationData' => $paginationData
        ];
        
        extract($data);
        require_once __DIR__ . '/../views/dashboard.php';
    }
}
?>