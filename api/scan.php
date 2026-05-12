<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Cek login
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Harap login terlebih dahulu']);
    exit;
}

// Ambil URL dari request
$input = json_decode(file_get_contents('php://input'), true);
$url = trim($input['url'] ?? '');

if (empty($url)) {
    echo json_encode(['error' => 'URL tidak boleh kosong']);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'Format URL tidak valid']);
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

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $checkUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apikey: {$vtApiKey}"]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Debug: log response
error_log("VT Check Response: HTTP $httpCode, Error: $curlError");

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
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "url=" . urlencode($url));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-apikey: {$vtApiKey}",
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    error_log("VT Submit Response: HTTP $httpCode, Error: $curlError");

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['error' => 'Invalid JSON response from VirusTotal submit']);
            exit;
        }
        $analysisId = $data['data']['id'] ?? null;

        if ($analysisId) {
            // Tunggu scan selesai
            sleep(15); // Increased wait time

            $resultUrl = "https://www.virustotal.com/api/v3/analyses/{$analysisId}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $resultUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apikey: {$vtApiKey}"]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $resultResponse = curl_exec($ch);
            $resultHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            error_log("VT Analysis Response: HTTP $resultHttpCode");

            if ($resultHttpCode == 200) {
                $resultData = json_decode($resultResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo json_encode(['error' => 'Invalid JSON response from VirusTotal analysis']);
                    exit;
                }
                $stats = $resultData['data']['attributes']['stats'] ?? null;
                $results = $resultData['data']['attributes']['results'] ?? [];
            } else {
                echo json_encode(['error' => "Gagal mendapatkan hasil scan. HTTP Code: $resultHttpCode"]);
                exit;
            }
        } else {
            echo json_encode(['error' => 'Gagal mendapatkan analysis ID dari VirusTotal']);
            exit;
        }
    } else {
        echo json_encode(['error' => "Gagal mengirim URL ke VirusTotal. HTTP Code: $httpCode. Error: $curlError"]);
        exit;
    }
} else {
    echo json_encode(['error' => "Error VirusTotal API. HTTP Code: $httpCode. Error: $curlError"]);
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

if ($safetyScore > 90 && $malicious === 0) {
    $status = 'safe';
} elseif ($safetyScore >= 50 && $safetyScore <= 70) {
    $status = 'suspicious';
} elseif ($safetyScore < 40) {
    $status = 'malicious';
} else {
    // Jika score antara 40-49 atau 71-90, gunakan heuristic lain
    if ($malicious > 0) {
        $status = $safetyScore >= 50 ? 'suspicious' : 'malicious';
    } else {
        $status = 'safe';
    }
}

// ========== AMBIL SCREENSHOT DARI URLSCAN.IO ==========
$screenshotUrl = null;

if (defined('URLSCAN_API_KEY') && URLSCAN_API_KEY && URLSCAN_API_KEY != 'MASUKKAN_API_KEY_URLSCAN_ANDA_DISINI') {
    $submitUrl = 'https://urlscan.io/api/v1/scan/';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $submitUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'url' => $url,
        'visibility' => 'unlisted'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'API-Key: ' . URLSCAN_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        $uuid = $data['uuid'] ?? null;
        
        if ($uuid) {
            // Tunggu 10 detik untuk screenshot siap
            sleep(10);
            
            $resultUrl = "https://urlscan.io/api/v1/result/{$uuid}/";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $resultUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['API-Key: ' . URLSCAN_API_KEY]);
            
            $resultResponse = curl_exec($ch);
            $resultHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($resultHttpCode == 200) {
                $resultData = json_decode($resultResponse, true);
                // Cek berbagai struktur possible dari response
                if (!empty($resultData['task']['screenshot'])) {
                    $screenshotUrl = $resultData['task']['screenshot'];
                } elseif (!empty($resultData['screenshot'])) {
                    $screenshotUrl = $resultData['screenshot'];
                } elseif (!empty($resultData['data']['screenshot'])) {
                    $screenshotUrl = $resultData['data']['screenshot'];
                }
            }
        }
    }
}

// ========== AMBIL DETAIL ENGINE ==========
$details = [];
foreach ($results as $engineName => $engineResult) {
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

// Simpan ke database
try {
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['error' => 'Tidak dapat terhubung ke database']);
        exit;
    }

    // Cek apakah column engine_results ada
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
            $url,
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
            $url,
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
    echo json_encode(['error' => 'Gagal menyimpan hasil scan ke database: ' . $e->getMessage()]);
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