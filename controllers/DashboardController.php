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

        $searchTerm = isset($_GET['q']) ? trim(htmlspecialchars($_GET['q'])) : '';
        $startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : '';

        $sortOrder = (!empty($startDate) || !empty($endDate)) ? 'ASC' : 'DESC';

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
            $currentStartDate = in_array($table, $searchableTables) ? $startDate : '';
            $currentEndDate = in_array($table, $searchableTables) ? $endDate : '';
            $totalRows = $logModel->getTotalRows($table, $currentSearchTerm, $currentStartDate, $currentEndDate);
            
            $paginationData[$param] = [
                'currentPage' => $currentPage,
                'offset' => $offset,
                'totalPages' => ceil($totalRows / $rowsPerPage)
            ];
        }

        $data = [
            'searchTerm' => $searchTerm,
            'summary' => $logModel->getSummary($startDate, $endDate),
            'allStatusCodes' => $logModel->getAllStatusCodes(),
            'realtimeLogs' => $logModel->getLogs('realtime_logs', $rowsPerPage, $paginationData['page_realtime']['offset'], $searchTerm, $startDate, $endDate, $sortOrder),
            'errorLogs' => $logModel->getLogs('error_logs', $rowsPerPage, $paginationData['page_error']['offset'], $searchTerm, $startDate, $endDate, $sortOrder),
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

    public function handleExport() {
        if (!isset($_GET['table'])) {
            die("Error: Nama tabel tidak ditentukan.");
        }
        
        $tableName = $_GET['table'];
        $startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : '';
        
        $logModel = new LogModel(getDbConnection());
        $data = $logModel->getAllDataForExport($tableName, $startDate, $endDate);
        
        if ($data === false) {
            die("Error: Tabel tidak valid atau tidak diizinkan untuk diekspor.");
        }

        if (empty($data)) {
            $_SESSION['error_message'] = "Tidak ada data untuk diekspor pada rentang waktu yang dipilih.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php?action=dashboard'));
            exit;
        }
        
        $filename = "export_" . $tableName . "_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
?>
