<?php
// models/LogModel.php

class LogModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSummary() {
        $stmt = $this->pdo->query("SELECT * FROM daily_summary ORDER BY date DESC LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['unique_visitors' => 0, 'total_hits' => 0, 'date' => date('Y-m-d')];
    }
    
    public function getLogs($table, $limit, $offset) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$table} ORDER BY timestamp DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTopItems($table, $column, $limit, $offset) {
        $stmt = $this->pdo->prepare("SELECT {$column}, hits FROM {$table} ORDER BY hits DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalRows($tableName) {
        return $this->pdo->query("SELECT COUNT(*) FROM {$tableName}")->fetchColumn();
    }

    public function getAllStatusCodes() {
        return $this->pdo->query("SELECT code, hits FROM status_codes ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopPagesForChart($limit) {
        $pages = $this->getTopItems('pages', 'url', $limit, 0);
        $labels = array_map(function($p) {
            $label = preg_replace('#^/(halaman/|berita/)?#', '', $p['url']);
            $label = $label === '/' ? 'Halaman Utama' : $label;
            return strlen($label) > 25 ? substr($label, 0, 25).'...' : $label;
        }, $pages);
        return ['labels' => $labels, 'data' => array_column($pages, 'hits')];
    }
    
    public function getTopStats($table, $limit) {
        $stmt = $this->pdo->prepare("SELECT name, hits FROM {$table} ORDER BY hits DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['labels' => array_column($result, 'name'), 'data' => array_column($result, 'hits')];
    }

    public function getHistoricalSummary($days = 7) {
        $stmt = $this->pdo->prepare("SELECT date, unique_visitors, total_hits FROM daily_summary WHERE date >= CURDATE() - INTERVAL :days DAY ORDER BY date ASC");
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>