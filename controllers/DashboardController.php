<?php
// controllers/DashboardController.php

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
            $totalRows = $logModel->getTotalRows($table);
            
            $paginationData[$param] = [
                'currentPage' => $currentPage,
                'offset' => $offset,
                'totalPages' => ceil($totalRows / $rowsPerPage)
            ];
        }

        // Mengambil semua data untuk ditampilkan
        $data = [
            'summary' => $logModel->getSummary(),
            'allStatusCodes' => $logModel->getAllStatusCodes(),
            'realtimeLogs' => $logModel->getLogs('realtime_logs', $rowsPerPage, $paginationData['page_realtime']['offset']),
            'errorLogs' => $logModel->getLogs('error_logs', $rowsPerPage, $paginationData['page_error']['offset']),
            'topPages' => $logModel->getTopItems('pages', 'url', $rowsPerPage, $paginationData['page_popular']['offset']),
            'topIps' => $logModel->getTopItems('ips', 'ip', $rowsPerPage, $paginationData['page_ips']['offset']),
            'topReferrers' => $logModel->getTopItems('referrers', 'domain', $rowsPerPage, $paginationData['page_referrers']['offset']),
            'chartData' => $logModel->getTopPagesForChart(7),
            'browserChartData' => $logModel->getTopStats('browsers', 7),
            'osChartData' => $logModel->getTopStats('operating_systems', 7),
            'paginationData' => $paginationData // Kirim data paginasi ke view
        ];
        
        extract($data);
        require_once __DIR__ . '/../views/dashboard.php';
    }
}
?>