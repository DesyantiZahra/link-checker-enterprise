<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');

set_time_limit(120);

// Cek login
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Harap login terlebih dahulu']);
    exit;
}

// Normalisasi URL (tambahkan https:// jika tidak ada protocol)
// Perlu dilakukan SEBELUM validasi filter_var agar URL tanpa protocol tetap bisa divalidasi setelah ditambah https://
function normalizeUrl($url) {
    $url = trim($url);
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }
    return $url;
}

// Ambil URL dari request
$input = json_decode(file_get_contents('php://input'), true);
$url = trim($input['url'] ?? '');

if (empty($url)) {
    echo json_encode(['error' => 'URL tidak boleh kosong']);
    exit;
}

// Normalisasi URL sebelum validasi & scan
$url = normalizeUrl($url);

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'Format URL tidak valid']);
    exit;
}

// Rate limiting per user: max 10 scan per menit
$stmt = getDB()->prepare("SELECT COUNT(*) FROM scan_history WHERE user_id = ? AND scanned_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetchColumn() > 10) {
    echo json_encode(['error' => 'Terlalu banyak scan. Maksimal 10 scan per menit.']);
    exit;
}

// ========== CEK CACHE (24 JAM) ==========
$stmt = getDB()->prepare("
    SELECT * FROM scan_history 
    WHERE user_id = ? AND url = ? AND scanned_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
    ORDER BY scanned_at DESC LIMIT 1
");
$stmt->execute([$_SESSION['user_id'], $url]);
$cached = $stmt->fetch();

if ($cached) {
    $details = [];
    if (!empty($cached['engine_results'])) {
        $parsed = json_decode($cached['engine_results'], true);
        if (is_array($parsed)) {
            $details = $parsed;
        }
    }

    echo json_encode([
        'success' => true,
        'cached' => true,
        'scan_id' => $cached['id'],
        'scanned_url' => $cached['url'],
        'malicious_count' => (int)$cached['malicious_count'],
        'suspicious_count' => (int)$cached['suspicious_count'],
        'harmless_count' => (int)$cached['harmless_count'],
        'undetected_count' => (int)$cached['undetected_count'],
        'total_engines' => (int)$cached['total_engines'],
        'safety_score' => (int)$cached['safety_score'],
        'status' => $cached['status'],
        'screenshot_url' => $cached['screenshot_url'],
        'details' => $details
    ]);
    exit;
}

// ========== PANGGIL VIRUSTOTAL API ==========
$vtApiKey = VT_API_KEY;

// Check if API key is configured
if (!$vtApiKey || $vtApiKey === 'MASUKKAN_API_KEY_ANDA_DISINI') {
    echo json_encode(['error' => 'VirusTotal API key belum dikonfigurasi. Hubungi administrator.']);
    exit;
}

$encodedUrl = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
$checkUrl = "https://www.virustotal.com/api/v3/urls/{$encodedUrl}";

$vtCh = curl_init();
curl_setopt($vtCh, CURLOPT_URL, $checkUrl);
curl_setopt($vtCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($vtCh, CURLOPT_HTTPHEADER, ["x-apikey: {$vtApiKey}"]);
curl_setopt($vtCh, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($vtCh, CURLOPT_TIMEOUT, 30);

$response = curl_exec($vtCh);
$httpCode = curl_getinfo($vtCh, CURLINFO_HTTP_CODE);
$curlError = curl_error($vtCh);
curl_close($vtCh);

error_log("VT Check Response: HTTP $httpCode" . ($curlError ? ", Error: $curlError" : ""));

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['error' => 'Invalid JSON response from VirusTotal']);
        exit;
    }
    $stats = $data['data']['attributes']['last_analysis_stats'] ?? null;
    $results = $data['data']['attributes']['last_analysis_results'] ?? [];
} elseif ($httpCode == 404) {
    // URL belum pernah discan, kirim untuk scan baru
    $apiUrl = 'https://www.virustotal.com/api/v3/urls';
    $vtSubmitCh = curl_init();
    curl_setopt($vtSubmitCh, CURLOPT_URL, $apiUrl);
    curl_setopt($vtSubmitCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($vtSubmitCh, CURLOPT_POST, true);
    curl_setopt($vtSubmitCh, CURLOPT_POSTFIELDS, "url=" . urlencode($url));
    curl_setopt($vtSubmitCh, CURLOPT_HTTPHEADER, [
        "x-apikey: {$vtApiKey}",
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    curl_setopt($vtSubmitCh, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($vtSubmitCh, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($vtSubmitCh);
    $httpCode = curl_getinfo($vtSubmitCh, CURLINFO_HTTP_CODE);
    $curlError = curl_error($vtSubmitCh);
    curl_close($vtSubmitCh);

    error_log("VT Submit Response: HTTP $httpCode" . ($curlError ? ", Error: $curlError" : ""));

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['error' => 'Invalid JSON response from VirusTotal submit']);
            exit;
        }
        $analysisId = $data['data']['id'] ?? null;

        if ($analysisId) {
            // Poll hasil analisis (max 12 percobaan, interval 5 detik)
            $resultUrl = "https://www.virustotal.com/api/v3/analyses/{$analysisId}";
            for ($attempt = 1; $attempt <= 12; $attempt++) {
                if ($attempt > 1) {
                    sleep(5);
                }
                $vtPollCh = curl_init();
                curl_setopt($vtPollCh, CURLOPT_URL, $resultUrl);
                curl_setopt($vtPollCh, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($vtPollCh, CURLOPT_HTTPHEADER, ["x-apikey: {$vtApiKey}"]);
                curl_setopt($vtPollCh, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($vtPollCh, CURLOPT_TIMEOUT, 30);
                $resultResponse = curl_exec($vtPollCh);
                $resultHttpCode = curl_getinfo($vtPollCh, CURLINFO_HTTP_CODE);
                curl_close($vtPollCh);

                error_log("VT Analysis Poll Attempt $attempt: HTTP $resultHttpCode");

                if ($resultHttpCode == 200) {
                    $resultData = json_decode($resultResponse, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("VT Analysis Poll JSON error on attempt $attempt");
                        if ($attempt < 12) continue;
                        echo json_encode(['error' => 'Invalid JSON response from VirusTotal analysis']);
                        exit;
                    }
                    if (isset($resultData['data']['attributes']['stats'])) {
                        $stats = $resultData['data']['attributes']['stats'] ?? null;
                        $results = $resultData['data']['attributes']['results'] ?? [];
                        break;
                    }
                }

                if ($attempt >= 12) {
                    error_log("VT Analysis timeout after $attempt attempts. Last HTTP: $resultHttpCode");
        echo json_encode(['error' => 'Gagal mendapatkan hasil scan. Server sibuk, coba lagi nanti.']);
                    exit;
                }
            }
        } else {
            echo json_encode(['error' => 'Gagal mendapatkan analysis ID dari VirusTotal']);
            exit;
        }
    } else {
        error_log("VT Submit failed. HTTP: $httpCode" . ($curlError ? ", Error: $curlError" : ""));
        echo json_encode(['error' => 'Gagal menghubungi VirusTotal. Coba lagi nanti.']);
        exit;
    }
} else {
    error_log("VT API error. HTTP: $httpCode" . ($curlError ? ", Error: $curlError" : ""));
    echo json_encode(['error' => 'Error dari VirusTotal API. Coba lagi nanti.']);
    exit;
}

if (!isset($stats) || !is_array($stats)) {
    echo json_encode(['error' => 'Tidak dapat mendapatkan statistik scan dari VirusTotal']);
    exit;
}

$malicious = $stats['malicious'] ?? 0;
$suspicious = $stats['suspicious'] ?? 0;
$harmless = $stats['harmless'] ?? 0;
$undetected = $stats['undetected'] ?? 0;
$total = $malicious + $suspicious + $harmless + $undetected;

// Hitung skor keamanan berdasarkan engine
// Aman: skor > 90 dan tidak ada engine berbahaya
// Mencurigakan: skor 50-70 (ada deteksi berbahaya)
// Berbahaya: skor < 40
$score = 100 - ($malicious * 5) - ($suspicious * 2);
$safetyScore = max(0, min(100, $score));

$status = getScanStatus($safetyScore, $malicious, $suspicious);

// ========== AMBIL SCREENSHOT DARI URLSCAN.IO ==========
$screenshotUrl = null;

if (defined('URLSCAN_API_KEY') && URLSCAN_API_KEY && URLSCAN_API_KEY != 'MASUKKAN_API_KEY_URLSCAN_ANDA_DISINI') {
    $submitUrl = 'https://urlscan.io/api/v1/scan/';
    $urlscanCh = curl_init();
    curl_setopt($urlscanCh, CURLOPT_URL, $submitUrl);
    curl_setopt($urlscanCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($urlscanCh, CURLOPT_POST, true);
    curl_setopt($urlscanCh, CURLOPT_POSTFIELDS, json_encode([
        'url' => $url,
        'visibility' => 'unlisted'
    ]));
    curl_setopt($urlscanCh, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'API-Key: ' . URLSCAN_API_KEY
    ]);
    curl_setopt($urlscanCh, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($urlscanCh, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($urlscanCh);
    $httpCode = curl_getinfo($urlscanCh, CURLINFO_HTTP_CODE);
    $curlError = curl_error($urlscanCh);
    curl_close($urlscanCh);
    
    error_log("URLScan Submit HTTP: $httpCode" . ($curlError ? ", Error: $curlError" : ""));

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        $uuid = $data['uuid'] ?? null;
        
        if ($uuid) {
            // Poll hasil screenshot (max 8 percobaan, interval 5 detik)
            $resultUrl = "https://urlscan.io/api/v1/result/{$uuid}/";
            for ($attempt = 1; $attempt <= 8; $attempt++) {
                if ($attempt > 1) {
                    sleep(5);
                }
                $urlscanPollCh = curl_init();
                curl_setopt($urlscanPollCh, CURLOPT_URL, $resultUrl);
                curl_setopt($urlscanPollCh, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($urlscanPollCh, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($urlscanPollCh, CURLOPT_TIMEOUT, 20);
                curl_setopt($urlscanPollCh, CURLOPT_HTTPHEADER, ['API-Key: ' . URLSCAN_API_KEY]);
                $resultResponse = curl_exec($urlscanPollCh);
                $resultHttpCode = curl_getinfo($urlscanPollCh, CURLINFO_HTTP_CODE);
                curl_close($urlscanPollCh);
                
                error_log("URLScan Poll Attempt $attempt: HTTP $resultHttpCode");
                
                if ($resultHttpCode == 200) {
                    $resultData = json_decode($resultResponse, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("URLScan Poll JSON error on attempt $attempt");
                        continue;
                    }
                    // Periksa berbagai struktur response dari URLScan.io
                    if (!empty($resultData['task']['screenshot'])) {
                        $screenshotUrl = $resultData['task']['screenshot'];
                        error_log("URLScan screenshot found in task.screenshot on attempt $attempt");
                        break;
                    } elseif (!empty($resultData['screenshot'])) {
                        $screenshotUrl = $resultData['screenshot'];
                        error_log("URLScan screenshot found in root.screenshot on attempt $attempt");
                        break;
                    } elseif (!empty($resultData['data']['screenshot'])) {
                        $screenshotUrl = $resultData['data']['screenshot'];
                        error_log("URLScan screenshot found in data.screenshot on attempt $attempt");
                        break;
                    } else {
                        error_log("URLScan screenshot not ready on attempt $attempt. Keys: " . implode(', ', array_keys($resultData)));
                    }
                }
                
                if ($attempt >= 8) {
                    error_log("URLScan polling timeout after $attempt attempts. Last HTTP: $resultHttpCode");
                }
            }
        } else {
            error_log("URLScan submit response missing uuid. Response: " . substr($response, 0, 500));
        }
    } else {
        error_log("URLScan submit failed. HTTP: $httpCode, Error: $curlError");
    }
}

// ========== AMBIL DETAIL ENGINE ==========
$details = [];
foreach ($results as $engineName => $engineResult) {
    if (!is_array($engineResult)) {
        continue;
    }
    $category = strtolower($engineResult['category'] ?? '');
    $details[] = [
        'engine' => $engineName,
        'category' => $category,
        'detected' => in_array($category, ['malicious', 'suspicious'], true),
        'result' => $engineResult['result'] ?? ucfirst($category)
    ];
}

// Simpan engine results ke JSON
$engineResultsJson = json_encode($details);

// Ambil URL akhir setelah redirect
$redirectUrl = $url;
$redirectCh = curl_init();
curl_setopt($redirectCh, CURLOPT_URL, $url);
curl_setopt($redirectCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($redirectCh, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($redirectCh, CURLOPT_NOBODY, true);
curl_setopt($redirectCh, CURLOPT_TIMEOUT, 10);
curl_setopt($redirectCh, CURLOPT_SSL_VERIFYPEER, true);
curl_exec($redirectCh);
$effectiveUrl = curl_getinfo($redirectCh, CURLINFO_EFFECTIVE_URL);
curl_close($redirectCh);
if ($effectiveUrl && $effectiveUrl !== $url) {
    $redirectUrl = $effectiveUrl;
}

// Simpan ke database
try {
    $pdo = getDB();

    $stmt = $pdo->prepare("SHOW COLUMNS FROM scan_history LIKE 'engine_results'");
    $stmt->execute();
    $hasEngineResults = $stmt->rowCount() > 0;

    if ($hasEngineResults) {
        $stmt = $pdo->prepare("
            INSERT INTO scan_history
            (user_id, url, final_url, malicious_count, suspicious_count,
             harmless_count, undetected_count, total_engines, safety_score, status, screenshot_url, engine_results)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $url,
            $redirectUrl,
            $malicious,
            $suspicious,
            $harmless,
            $undetected,
            $total,
            $safetyScore,
            $status,
            $screenshotUrl,
            $engineResultsJson
        ]);
    } else {
        // Fallback tanpa engine_results
        $stmt = $pdo->prepare("
            INSERT INTO scan_history
            (user_id, url, final_url, malicious_count, suspicious_count,
             harmless_count, undetected_count, total_engines, safety_score, status, screenshot_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $url,
            $redirectUrl,
            $malicious,
            $suspicious,
            $harmless,
            $undetected,
            $total,
            $safetyScore,
            $status,
            $screenshotUrl
        ]);
    }

    $scanId = $pdo->lastInsertId();

    // Log successful scan
    error_log("Scan berhasil: ID $scanId, URL: $url, Status: $status");

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['error' => 'Gagal menyimpan hasil scan ke database. Coba lagi nanti.']);
    exit;
}

// Kirim response
echo json_encode([
    'success' => true,
    'scan_id' => $scanId,
    'scanned_url' => $url,
    'malicious_count' => $malicious,
    'suspicious_count' => $suspicious,
    'harmless_count' => $harmless,
    'undetected_count' => $undetected,
    'total_engines' => $total,
    'safety_score' => $safetyScore,
    'status' => $status,
    'screenshot_url' => $screenshotUrl,
    'details' => $details  // SEKARANG SEMUA ENGINE (TIDAK DIPOTONG)
]); 