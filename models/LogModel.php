<?php

class LogModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function buildWhereClause(array &$params, $tableName, $startDate, $endDate, $searchTerm = '', $dateColumn = 'timestamp') {
        $whereClauses = [];
        if (!empty($startDate)) {
            $whereClauses[] = "DATE({$dateColumn}) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $whereClauses[] = "DATE({$dateColumn}) <= :end_date";
            $params[':end_date'] = $endDate;
        }
        if (!empty($searchTerm)) {
            // Cek apakah searchTerm adalah angka 3 digit (kemungkinan besar status code)
            if (ctype_digit($searchTerm) && strlen($searchTerm) === 3) {
                $whereClauses[] = "status = :term";
                $params[':term'] = $searchTerm;
            } else {
                // Jika bukan status code, lakukan pencarian umum di kolom yang relevan
                $searchableColumnsMap = [
                    'realtime_logs' => ['ip', 'url', 'country'],
                    'error_logs' => ['ip', 'url']
                ];

                if (isset($searchableColumnsMap[$tableName])) {
                    $searchableColumns = $searchableColumnsMap[$tableName];
                    $searchClauses = [];
                    foreach ($searchableColumns as $col) {
                        $searchClauses[] = "{$col} LIKE :term";
                    }
                    $whereClauses[] = "(" . implode(' OR ', $searchClauses) . ")";
                    $params[':term'] = '%' . $searchTerm . '%';
                }
            }
        }
        return count($whereClauses) > 0 ? " WHERE " . implode(' AND ', $whereClauses) : "";
    }

    public function getLogs($table, $limit, $offset, $searchTerm = '', $startDate = '', $endDate = '', $sortOrder = 'DESC') {
        $params = [];
        $whereClause = $this->buildWhereClause($params, $table, $startDate, $endDate, $searchTerm, 'timestamp');
        $order = (strtoupper($sortOrder) === 'ASC') ? 'ASC' : 'DESC';
        $sql = "SELECT * FROM {$table} {$whereClause} ORDER BY timestamp {$order} LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalRows($tableName, $searchTerm = '', $startDate = '', $endDate = '') {
        $params = [];
        $dateColumn = ($tableName === 'daily_summary') ? 'date' : 'timestamp';
        $whereClause = $this->buildWhereClause($params, $tableName, $startDate, $endDate, $searchTerm, $dateColumn);
        $sql = "SELECT COUNT(*) FROM {$tableName} {$whereClause}";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getSummary($startDate = '', $endDate = '') {
        $params = [];
        $whereClause = $this->buildWhereClause($params, 'daily_summary', $startDate, $endDate, '', 'date');
        $sql = "SELECT SUM(unique_visitors) as unique_visitors, SUM(total_hits) as total_hits, MAX(date) as date FROM daily_summary {$whereClause}";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'unique_visitors' => $result['unique_visitors'] ?? 0,
            'total_hits' => $result['total_hits'] ?? 0,
            'date' => $result['date'] ?? date('Y-m-d')
        ];
    }

    public function getTopItems($table, $column, $limit, $offset) {
        $stmt = $this->pdo->prepare("SELECT {$column}, hits FROM {$table} ORDER BY hits DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function getAllDataForExport($tableName, $startDate = '', $endDate = '') {
        $allowedTables = ['realtime_logs', 'error_logs', 'pages', 'ips', 'referrers'];
        if (!in_array($tableName, $allowedTables)) {
            return false;
        }

        $params = [];
        $whereClause = $this->buildWhereClause($params, $tableName, $startDate, $endDate, '', 'timestamp');

        $orderBy = ($tableName === 'realtime_logs' || $tableName === 'error_logs') 
            ? 'timestamp DESC' 
            : 'hits DESC';
        
        $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY {$orderBy}";
        
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>