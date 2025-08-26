<?php

class LogModel {
    private $pdo;

    // fungsi konstruktor
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // fungsi untuk mendapatkan log dengan fitur pencarian
    public function getLogs($table, $limit, $offset, $searchTerm = '') {
        $sql = "SELECT * FROM {$table}";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " WHERE ip LIKE :term OR url LIKE :term OR status LIKE :term OR country LIKE :term";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        $sql .= " ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        if (!empty($searchTerm)) {
            $stmt->bindValue(':term', $params[':term']);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // fungsi untuk mendapatkan total baris dengan fitur pencarian
    public function getTotalRows($tableName, $searchTerm = '') {
        $sql = "SELECT COUNT(*) FROM {$tableName}";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " WHERE ip LIKE :term OR url LIKE :term OR status LIKE :term OR country LIKE :term";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        if (!empty($searchTerm)) {
            $stmt->bindValue(':term', $params[':term']);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // fungsi untuk mendapatkan ringkasan harian
    public function getSummary() {
        $stmt = $this->pdo->query("SELECT * FROM daily_summary ORDER BY date DESC LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['unique_visitors' => 0, 'total_hits' => 0, 'date' => date('Y-m-d')];
    }
    
    // fungsi untuk mendapatkan item teratas
    public function getTopItems($table, $column, $limit, $offset) {
        $stmt = $this->pdo->prepare("SELECT {$column}, hits FROM {$table} ORDER BY hits DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // fungsi untuk mendapatkan semua kode status
    public function getAllStatusCodes() {
        return $this->pdo->query("SELECT code, hits FROM status_codes ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // fungsi untuk mendapatkan data grafik halaman teratas
    public function getTopPagesForChart($limit) {
        $pages = $this->getTopItems('pages', 'url', $limit, 0);
        $labels = array_map(function($p) {
            $label = preg_replace('#^/(halaman/|berita/)?#', '', $p['url']);
            $label = $label === '/' ? 'Halaman Utama' : $label;
            return strlen($label) > 25 ? substr($label, 0, 25).'...' : $label;
        }, $pages);
        return ['labels' => $labels, 'data' => array_column($pages, 'hits')];
    }
    
    // fungsi untuk mendapatkan statistik teratas (browser atau OS)
    public function getTopStats($table, $limit) {
        $stmt = $this->pdo->prepare("SELECT name, hits FROM {$table} ORDER BY hits DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['labels' => array_column($result, 'name'), 'data' => array_column($result, 'hits')];
    }

    // fungsi untuk mendapatkan ringkasan historis
    public function getHistoricalSummary($days = 7) { 
        $stmt = $this->pdo->prepare("SELECT date, unique_visitors, total_hits FROM daily_summary WHERE date >= CURDATE() - INTERVAL :days DAY ORDER BY date ASC");
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>