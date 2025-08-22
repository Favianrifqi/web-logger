<?php
// Script Backend (Versi Final Revisi)
// ---------------------------------------------------------------------------------

// Set timezone dan header untuk output teks biasa (membantu saat debugging)
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: text/plain; charset=utf-8');

echo "Memulai proses log pada: " . date('Y-m-d H:i:s') . "\n";

// --- KONFIGURASI DATABASE DAN FILE LOG ---
$dbHost = 'localhost';
$dbName = 'wisataju_proyek_logger';
$dbUser = 'wisataju_loggeruser';
$dbPass = '#qUho$a]iRh}R%gn';
$logFilePath = '/home/wisataju/access-logs/wisatajubung.com-ssl_log';
// -----------------------------------------

// --- Pemeriksaan Awal File Log ---
if (!file_exists($logFilePath)) {
    die("KRITIS: File log tidak ditemukan di jalur: " . $logFilePath . "\n");
}
if (!is_readable($logFilePath)) {
    die("KRITIS: File log ditemukan, tetapi skrip PHP tidak memiliki izin untuk membacanya.\n");
}
echo "File log ditemukan dan bisa dibaca.\n";


// --- Fungsi-fungsi Pembantu ---

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

// --- Proses Utama ---

try {
    // 1. Koneksi ke Database
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Koneksi database MySQL berhasil.\n";

    // 2. Membuat Tabel (Satu per Satu untuk Keamanan)
    $queries = [
        "CREATE TABLE IF NOT EXISTS realtime_logs ( id INT AUTO_INCREMENT PRIMARY KEY, timestamp DATETIME, ip VARCHAR(45), country VARCHAR(100), url TEXT, status INT ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS error_logs ( id INT AUTO_INCREMENT PRIMARY KEY, timestamp DATETIME, ip VARCHAR(45), url TEXT, status INT ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS daily_summary (date DATE PRIMARY KEY, unique_visitors INT, total_hits INT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS pages (url VARCHAR(767) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' PRIMARY KEY, hits INT) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS ips (ip VARCHAR(45) PRIMARY KEY, hits INT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS status_codes (code INT PRIMARY KEY, hits INT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS browsers (name VARCHAR(50) PRIMARY KEY, hits INT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS operating_systems (name VARCHAR(50) PRIMARY KEY, hits INT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS referrers (domain VARCHAR(255) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' PRIMARY KEY, hits INT) ENGINE=InnoDB"
    ];

    echo "Memeriksa dan membuat tabel jika belum ada...\n";
    foreach ($queries as $query) {
        $pdo->exec($query);
    }
    echo "Semua tabel sudah siap.\n";
    
    // 3. Mengosongkan Tabel Summary
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE daily_summary; TRUNCATE TABLE pages; TRUNCATE TABLE ips; TRUNCATE TABLE status_codes; TRUNCATE TABLE realtime_logs; TRUNCATE TABLE error_logs; TRUNCATE TABLE browsers; TRUNCATE TABLE operating_systems; TRUNCATE TABLE referrers; SET FOREIGN_KEY_CHECKS = 1;");
    echo "Semua tabel summary berhasil dikosongkan.\n";

    // 4. Inisialisasi Variabel
    $visitors = []; $pages = []; $ips = []; $status_codes = []; $browsers = []; $operating_systems = []; $referrers = [];
    $total_hits = 0; $ipCache = []; $realtimeBuffer = []; $errorBuffer = [];

    // 5. Membaca dan Memproses File Log
    $handle = fopen($logFilePath, 'r');
    if ($handle) {
        echo "Mulai memproses file log...\n";
        $regex = '/^(\S+) \S+ \S+ \[([^\]]+)\] "(?:[A-Z]+) (\S+) \S+" (\d+) \d+ "([^"]*)" "([^"]*)"/';
        $lineCount = 0;

        while (($line = fgets($handle)) !== false) {
            if (preg_match($regex, $line, $matches)) {
                $lineCount++;
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
                if ($referrer !== '-') {
                    $refDomain = parse_url($referrer, PHP_URL_HOST);
                    $refDomain = preg_replace('/^www\./', '', $refDomain);
                    if ($refDomain) { isset($referrers[$refDomain]) ? $referrers[$refDomain]++ : $referrers[$refDomain] = 1; }
                }
                if ($status >= 400) $errorBuffer[] = ['timestamp' => $timestamp, 'ip' => $ip, 'url' => $url, 'status' => $status];
                $realtimeBuffer[] = ['timestamp' => $timestamp, 'ip' => $ip, 'url' => $url, 'status' => $status];
            }
        }
        fclose($handle);
        echo "Selesai memproses file log. Total baris yang cocok: " . $lineCount . "\n";

        // 6. Memasukkan Data ke Database dalam Satu Transaksi
        $pdo->beginTransaction();
        echo "Memulai transaksi database...\n";
        
        $pdo->prepare("REPLACE INTO daily_summary (date, unique_visitors, total_hits) VALUES (?, ?, ?)")->execute([date('Y-m-d'), count($visitors), $total_hits]);
        $stmtPages = $pdo->prepare("REPLACE INTO pages (url, hits) VALUES (?, ?)"); foreach ($pages as $url => $hits) $stmtPages->execute([$url, $hits]);
        $stmtIps = $pdo->prepare("REPLACE INTO ips (ip, hits) VALUES (?, ?)"); foreach ($ips as $ip => $hits) $stmtIps->execute([$ip, $hits]);
        $stmtStatus = $pdo->prepare("REPLACE INTO status_codes (code, hits) VALUES (?, ?)"); foreach ($status_codes as $code => $hits) $stmtStatus->execute([$code, $hits]);
        $stmtBrowsers = $pdo->prepare("REPLACE INTO browsers (name, hits) VALUES (?, ?)"); foreach ($browsers as $name => $hits) $stmtBrowsers->execute([$name, $hits]);
        $stmtOs = $pdo->prepare("REPLACE INTO operating_systems (name, hits) VALUES (?, ?)"); foreach ($operating_systems as $name => $hits) $stmtOs->execute([$name, $hits]);
        $stmtRef = $pdo->prepare("REPLACE INTO referrers (domain, hits) VALUES (?, ?)"); foreach ($referrers as $domain => $hits) $stmtRef->execute([$domain, $hits]);

        $latestLogs = array_slice($realtimeBuffer, -50);
        $stmtRealtime = $pdo->prepare("INSERT INTO realtime_logs (timestamp, ip, country, url, status) VALUES (?, ?, ?, ?, ?)");
        foreach ($latestLogs as $log) {
            $country = getGeoIpInfo($log['ip'], $ipCache);
            $stmtRealtime->execute([$log['timestamp'], $log['ip'], $country, $log['url'], $log['status']]);
        }
        
        $latestErrors = array_slice($errorBuffer, -50);
        $stmtError = $pdo->prepare("INSERT INTO error_logs (timestamp, ip, url, status) VALUES (?, ?, ?, ?)");
        foreach ($latestErrors as $error) $stmtError->execute([$error['timestamp'], $error['ip'], $error['url'], $error['status']]);

        $pdo->commit();
        echo "Transaksi berhasil. Semua data telah disimpan ke database.\n";
    }
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack(); // Batalkan transaksi jika terjadi error
    }
    die("DATABASE ERROR: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("GENERAL ERROR: " . $e->getMessage() . "\n");
}
?>