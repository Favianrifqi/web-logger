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
        
        $data = [
            'summary' => $logModel->getSummary(),
            'realtimeLogs' => $logModel->getLogs('realtime_logs', 10),
            'errorLogs' => $logModel->getLogs('error_logs', 10),
            'topPages' => $logModel->getTopItems('pages', 'url', 10),
            'topIps' => $logModel->getTopItems('ips', 'ip', 10),
            'topReferrers' => $logModel->getTopItems('referrers', 'domain', 10),
            'allStatusCodes' => $logModel->getAllStatusCodes(),
            'chartData' => $logModel->getTopPagesForChart(7),
            'browserChartData' => $logModel->getTopStats('browsers', 7),
            'osChartData' => $logModel->getTopStats('operating_systems', 7)
        ];
        extract($data);
        
        require_once __DIR__ . '/../views/dashboard.php';
    }
}
?>