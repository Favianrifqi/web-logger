<?php
// proses_log.php (VERSI FINAL DENGAN LOGIKA HISTORY)

date_default_timezone_set('Asia/Jakarta');

// --- KONFIGURASI UNTUK SERVER LIVE JAGOAN HOSTING ---
$dbHost = 'localhost';
$dbName = 'wisataju_proyek_logger';
$dbUser = 'wisataju_loggeruser';
$dbPass = '#qUho$a]iRh}R%gn'; // Ganti jika password Anda berbeda
$logFilePath = '/home/wisataju/access-logs/wisatajubung.com-ssl_log';
// --------------------------------------------------

function parseUserAgent($userAgent) {
    $browser = "Lainnya"; $os = "Lainnya";
    if (preg_match('/(Chrome|CriOS)/i', $userAgent)) $browser = 'Chrome';
    elseif (preg_match('/Firefox/i', $userAgent)) $browser = 'Firefox';
    elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) $browser = 'Safari';
    elseif (preg_match('/MSIE|Trident/i', $userAgent)) $browser = 'Internet Explorer';
    elseif (preg_match('/Edge|Edg/i', $userAgent)) $browser = 'Edge';
    elseif (preg_match('/Googlebot/i', $userAgent)) $browser = 'Googlebot';
    if (preg_match('/Windows/i', $userAgent)) $os = 'Windows';
    elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) $os = 'macOS';
    elseif (preg_match('/Linux/i', $userAgent) && !preg_match('/Android/i', $userAgent)) $os = 'Linux';
    elseif (preg_match('/Android/i', $userAgent)) $os = 'Android';
    elseif (preg_match('/iPhone|iPad/i', $userAgent)) $os = 'iOS';
    return ['browser' => $browser, 'os' => $os];
}

function getGeoIpInfo($ip, &$ipCache) {
    if (isset($ipCache[$ip])) return $ipCache[$ip];
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $url = "http://ip-api.com/json/{$ip}?fields=country";
        $context = stream_context_create(['http' => ['timeout' => 2]]);
        $response = @file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            $country = $data['country'] ?? 'Unknown';
            $ipCache[$ip] = $country;
            return $country;
        }
    }
    return 'Internal/Unknown';
}

if (!file_exists($logFilePath) || !is_readable($logFilePath)) {
    exit; // Keluar diam-diam jika file log tidak ada
}

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pastikan semua tabel ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS realtime_logs ( id INT AUTO_INCREMENT PRIMARY KEY, timestamp DATETIME, ip VARCHAR(45), country VARCHAR(100), url TEXT, status INT )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS error_logs ( id INT AUTO_INCREMENT PRIMARY KEY, timestamp DATETIME, ip VARCHAR(45), url TEXT, status INT )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_summary (date DATE PRIMARY KEY, unique_visitors INT, total_hits INT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (url VARCHAR(767) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' PRIMARY KEY, hits INT DEFAULT 0)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS ips (ip VARCHAR(45) PRIMARY KEY, hits INT DEFAULT 0)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS status_codes (code INT PRIMARY KEY, hits INT DEFAULT 0)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS browsers (name VARCHAR(50) PRIMARY KEY, hits INT DEFAULT 0)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS operating_systems (name VARCHAR(50) PRIMARY KEY, hits INT DEFAULT 0)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS referrers (domain VARCHAR(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' PRIMARY KEY, hits INT DEFAULT 0)");
    
    // Inisialisasi variabel agregasi
    $pages = []; $ips = []; $status_codes = []; $browsers = []; $operating_systems = []; $referrers = [];
    $dailyHits = []; $dailyUniqueVisitors = [];
    $realtimeBuffer = []; $errorBuffer = []; $ipCache = [];

    // Proses parsing file log
    $handle = fopen($logFilePath, 'r');
    if ($handle) {
        $lastProcessedTimestamp = $pdo->query("SELECT MAX(timestamp) FROM realtime_logs")->fetchColumn() ?: '1970-01-01 00:00:00';
        
        $regex = '/^(\S+) \S+ \S+ \[([^\]]+)\] "(?:[A-Z]+) (\S+) \S+" (\d+) \d+ "([^"]*)" "([^"]*)"/';
        while (($line = fgets($handle)) !== false) {
            if (preg_match($regex, $line, $matches)) {
                list(, $ip, $timestampStr, $url, $status, $referrer, $userAgent) = $matches;
                
                $dt = DateTime::createFromFormat('d/M/Y:H:i:s O', $timestampStr);
                if (!$dt) continue;
                
                $timestamp = $dt->format('Y-m-d H:i:s');
                
                // Hanya proses log baru yang belum pernah diproses
                if ($timestamp <= $lastProcessedTimestamp) continue;

                $date = $dt->format('Y-m-d');
                $status = (int)$status;
                
                $dailyHits[$date] = ($dailyHits[$date] ?? 0) + 1;
                $dailyUniqueVisitors[$date][$ip] = true;

                $urlForDb = (strlen($url) > 767) ? substr($url, 0, 767) : $url;
                
                $pages[$urlForDb] = ($pages[$urlForDb] ?? 0) + 1;
                $ips[$ip] = ($ips[$ip] ?? 0) + 1;
                $status_codes[$status] = ($status_codes[$status] ?? 0) + 1;
                
                $uaInfo = parseUserAgent($userAgent);
                $browsers[$uaInfo['browser']] = ($browsers[$uaInfo['browser']] ?? 0) + 1;
                $operating_systems[$uaInfo['os']] = ($operating_systems[$uaInfo['os']] ?? 0) + 1;

                if ($referrer !== '-') {
                    $refDomain = parse_url($referrer, PHP_URL_HOST);
                    $refDomain = preg_replace('/^www\./', '', $refDomain);
                    if ($refDomain) {
                        $referrers[$refDomain] = ($referrers[$refDomain] ?? 0) + 1;
                    }
                }
                
                $realtimeBuffer[] = ['timestamp' => $timestamp, 'ip' => $ip, 'url' => $url, 'status' => $status];
                if ($status >= 400) {
                    $errorBuffer[] = ['timestamp' => $timestamp, 'ip' => $ip, 'url' => $url, 'status' => $status];
                }
            }
        }
        fclose($handle);

        if (empty($realtimeBuffer)) {
            exit; // Tidak ada log baru, keluar.
        }

        // Memulai transaksi database
        $pdo->beginTransaction();

        // Gunakan INSERT ... ON DUPLICATE KEY UPDATE untuk mengakumulasi data
        $stmtPages = $pdo->prepare("INSERT INTO pages (url, hits) VALUES (?, ?) ON DUPLICATE KEY UPDATE hits = hits + VALUES(hits)");
        foreach ($pages as $url => $hits) { $stmtPages->execute([$url, $hits]); }
        
        $stmtIps = $pdo->prepare("INSERT INTO ips (ip, hits) VALUES (?, ?) ON DUPLICATE KEY UPDATE hits = hits + VALUES(hits)");
        foreach ($ips as $ip => $hits) { $stmtIps->execute([$ip, $hits]); }
        
        $stmtStatus = $pdo->prepare("INSERT INTO status_codes (code, hits) VALUES (?, ?) ON DUPLICATE KEY UPDATE hits = hits + VALUES(hits)");
        foreach ($status_codes as $code => $hits) { $stmtStatus->execute([$code, $hits]); }
        
        $stmtBrowsers = $pdo->prepare("INSERT INTO browsers (name, hits) VALUES (?, ?) ON DUPLICATE KEY UPDATE hits = hits + VALUES(hits)");
        foreach ($browsers as $name => $hits) { $stmtBrowsers->execute([$name, $hits]); }

        $stmtOs = $pdo->prepare("INSERT INTO operating_systems (name, hits) VALUES (?, ?) ON DUPLICATE KEY UPDATE hits = hits + VALUES(hits)");
        foreach ($operating_systems as $name => $hits) { $stmtOs->execute([$name, $hits]); }

        $stmtRef = $pdo->prepare("INSERT INTO referrers (domain, hits) VALUES (?, ?) ON DUPLICATE KEY UPDATE hits = hits + VALUES(hits)");
        foreach ($referrers as $domain => $hits) { $stmtRef->execute([$domain, $hits]); }

        // Update daily summary untuk setiap hari yang terpengaruh
        $stmtSummary = $pdo->prepare("INSERT INTO daily_summary (date, unique_visitors, total_hits) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE unique_visitors = unique_visitors + VALUES(unique_visitors), total_hits = total_hits + VALUES(total_hits)");
        foreach ($dailyHits as $date => $hits) {
            $uniqueCount = count($dailyUniqueVisitors[$date]);
            $stmtSummary->execute([$date, $uniqueCount, $hits]);
        }

        // Masukkan log terbaru
        $stmtRealtime = $pdo->prepare("INSERT INTO realtime_logs (timestamp, ip, country, url, status) VALUES (?, ?, ?, ?, ?)");
        foreach ($realtimeBuffer as $log) { 
            $country = getGeoIpInfo($log['ip'], $ipCache); 
            $stmtRealtime->execute([$log['timestamp'], $log['ip'], $country, $log['url'], $log['status']]); 
        }
        
        $stmtError = $pdo->prepare("INSERT INTO error_logs (timestamp, ip, url, status) VALUES (?, ?, ?, ?)");
        foreach ($errorBuffer as $error) { 
            $stmtError->execute([$error['timestamp'], $error['ip'], $error['url'], $error['status']]); 
        }

        // Bersihkan log yang lebih lama dari 7 hari
        $pdo->exec("DELETE FROM realtime_logs WHERE timestamp < NOW() - INTERVAL 7 DAY");
        $pdo->exec("DELETE FROM error_logs WHERE timestamp < NOW() - INTERVAL 7 DAY");

        $pdo->commit();
    }
} catch (PDOException $e) {
    exit; // Keluar diam-diam jika ada error DB
}
?>