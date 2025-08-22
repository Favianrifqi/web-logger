<?php
// proses_log.php
// Script Backend untuk Cron Job

date_default_timezone_set('Asia/Jakarta');

// --- KONFIGURASI UNTUK SERVER LIVE JAGOAN HOSTING ---
$dbHost = 'localhost';
$dbName = 'wisataju_proyek_logger';
$dbUser = 'wisataju_loggeruser';
$dbPass = '#qUho$a]iRh}R%gn'; // Password DB Anda
$logFilePath = '/home/wisataju/access-logs/wisatajubung.com-ssl_log';
// --------------------------------------------------

// Fungsi-fungsi pembantu (parseUserAgent, getGeoIpInfo)
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

// --- Logika Utama Skrip Cron Job ---

if (!file_exists($logFilePath) || !is_readable($logFilePath)) {
    // Keluar diam-diam jika file log tidak ada/tidak bisa dibaca, agar cron tidak mengirim email error
    exit;
}

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $queries = [
        "CREATE TABLE IF NOT EXISTS realtime_logs ( id INT AUTO_INCREMENT PRIMARY KEY, timestamp DATETIME, ip VARCHAR(45), country VARCHAR(100), url TEXT, status INT )",
        "CREATE TABLE IF NOT EXISTS error_logs ( id INT AUTO_INCREMENT PRIMARY KEY, timestamp DATETIME, ip VARCHAR(45), url TEXT, status INT )",
        "CREATE TABLE IF NOT EXISTS daily_summary (date DATE PRIMARY KEY, unique_visitors INT, total_hits INT)",
        "CREATE TABLE IF NOT EXISTS pages (url VARCHAR(767) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' PRIMARY KEY, hits INT)",
        "CREATE TABLE IF NOT EXISTS ips (ip VARCHAR(45) PRIMARY KEY, hits INT)",
        "CREATE TABLE IF NOT EXISTS status_codes (code INT PRIMARY KEY, hits INT)",
        "CREATE TABLE IF NOT EXISTS browsers (name VARCHAR(50) PRIMARY KEY, hits INT)",
        "CREATE TABLE IF NOT EXISTS operating_systems (name VARCHAR(50) PRIMARY KEY, hits INT)",
        "CREATE TABLE IF NOT EXISTS referrers (domain VARCHAR(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' PRIMARY KEY, hits INT)"
    ];
    foreach ($queries as $query) { $pdo->exec($query); }

    $pdo->exec("TRUNCATE TABLE realtime_logs; TRUNCATE TABLE error_logs;");

    $visitors = []; $pages = []; $ips = []; $status_codes = []; $browsers = []; $operating_systems = []; $referrers = [];
    $total_hits = 0; $ipCache = []; $realtimeBuffer = []; $errorBuffer = [];

    $handle = fopen($logFilePath, 'r');
    if ($handle) {
        $regex = '/^(\S+) \S+ \S+ \[([^\]]+)\] "(?:[A-Z]+) (\S+) \S+" (\d+) \d+ "([^"]*)" "([^"]*)"/';
        while (($line = fgets($handle)) !== false) {
            if (preg_match($regex, $line, $matches)) {
                list(, $ip, $timestampStr, $url, $status, $referrer, $userAgent) = $matches;
                $status = (int)$status;
                $dt = DateTime::createFromFormat('d/M/Y:H:i:s O', $timestampStr);
                $timestamp = $dt ? $dt->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
                $total_hits++; $visitors[$ip] = true;
                $urlForDb = (strlen($url) > 767) ? substr($url, 0, 767) : $url;
                isset($pages[$urlForDb]) ? $pages[$urlForDb]++ : $pages[$urlForDb] = 1;
                isset($ips[$ip]) ? $ips[$ip]++ : $ips[$ip] = 1;
                isset($status_codes[$status]) ? $status_codes[$status]++ : $status_codes[$status] = 1;
                $uaInfo = parseUserAgent($userAgent);
                isset($browsers[$uaInfo['browser']]) ? $browsers[$uaInfo['browser']]++ : $browsers[$uaInfo['browser']] = 1;
                isset($operating_systems[$uaInfo['os']]) ? $operating_systems[$uaInfo['os']]++ : $operating_systems[$uaInfo['os']] = 1;
                if ($referrer !== '-') { $refDomain = parse_url($referrer, PHP_URL_HOST); $refDomain = preg_replace('/^www\./', '', $refDomain); if ($refDomain) { isset($referrers[$refDomain]) ? $referrers[$refDomain]++ : $referrers[$refDomain] = 1; } }
                if ($status >= 400) $errorBuffer[] = ['timestamp' => $timestamp, 'ip' => $ip, 'url' => $url, 'status' => $status];
                $realtimeBuffer[] = ['timestamp' => $timestamp, 'ip' => $ip, 'url' => $url, 'status' => $status];
            }
        }
        fclose($handle);

        $pdo->beginTransaction();
        $pdo->prepare("REPLACE INTO daily_summary (date, unique_visitors, total_hits) VALUES (?, ?, ?)")->execute([date('Y-m-d'), count($visitors), $total_hits]);
        $stmtPages = $pdo->prepare("REPLACE INTO pages (url, hits) VALUES (?, ?)"); foreach ($pages as $url => $hits) $stmtPages->execute([$url, $hits]);
        $stmtIps = $pdo->prepare("REPLACE INTO ips (ip, hits) VALUES (?, ?)"); foreach ($ips as $ip => $hits) $stmtIps->execute([$ip, $hits]);
        $stmtStatus = $pdo->prepare("REPLACE INTO status_codes (code, hits) VALUES (?, ?)"); foreach ($status_codes as $code => $hits) $stmtStatus->execute([$code, $hits]);
        $stmtBrowsers = $pdo->prepare("REPLACE INTO browsers (name, hits) VALUES (?, ?)"); foreach ($browsers as $name => $hits) $stmtBrowsers->execute([$name, $hits]);
        $stmtOs = $pdo->prepare("REPLACE INTO operating_systems (name, hits) VALUES (?, ?)"); foreach ($operating_systems as $name => $hits) $stmtOs->execute([$name, $hits]);
        $stmtRef = $pdo->prepare("REPLACE INTO referrers (domain, hits) VALUES (?, ?)"); foreach ($referrers as $domain => $hits) $stmtRef->execute([$domain, $hits]);
        
        $latestLogs = array_slice($realtimeBuffer, -50);
        $stmtRealtime = $pdo->prepare("INSERT INTO realtime_logs (timestamp, ip, country, url, status) VALUES (?, ?, ?, ?, ?)");
        foreach ($latestLogs as $log) { $country = getGeoIpInfo($log['ip'], $ipCache); $stmtRealtime->execute([$log['timestamp'], $log['ip'], $country, $log['url'], $log['status']]); }
        
        $latestErrors = array_slice($errorBuffer, -50);
        $stmtError = $pdo->prepare("INSERT INTO error_logs (timestamp, ip, url, status) VALUES (?, ?, ?, ?)");
        foreach ($latestErrors as $error) $stmtError->execute([$error['timestamp'], $error['ip'], $error['url'], $error['status']]);
        
        $pdo->commit();
    }
} catch (PDOException $e) {
    // Keluar diam-diam jika ada error DB, agar cron tidak mengirim email
    exit;
}
?>