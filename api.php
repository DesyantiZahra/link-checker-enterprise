<?php
// api.php - Backend untuk menghubungi VirusTotal API

// Konfigurasi
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 🔑 GANTI DENGAN API KEY ANDA!
// Dapatkan dari: https://www.virustotal.com > Login > Klik Username > API Key
define('VIRUSTOTAL_API_KEY', '07730b0e000a5d39c1c505d4b7afb5fd5966cf8aada3c1829aba7f7254f11a3e');

// Hanya menerima method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method tidak diizinkan. Gunakan POST.']);
    exit;
}

// Ambil input JSON
$input = json_decode(file_get_contents('php://input'), true);
$url = isset($input['url']) ? trim($input['url']) : '';

// Validasi URL
if (empty($url)) {
    echo json_encode(['error' => 'URL tidak boleh kosong']);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'Format URL tidak valid. Gunakan format: https://example.com']);
    exit;
}

/**
 * Fungsi untuk memanggil VirusTotal API
 */
function callVirusTotal($url, $apiKey) {
    $apiUrl = 'https://www.virustotal.com/api/v3/urls';
    
    // Encode URL untuk dikirim ke API
    $encodedUrl = base64_encode($url);
    
    // Cek dulu apakah URL sudah pernah discan sebelumnya
    $checkUrl = "https://www.virustotal.com/api/v3/urls/{$encodedUrl}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $checkUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-apikey: {$apiKey}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Hanya untuk localhost
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Jika URL sudah ada di database VirusTotal (HTTP 200)
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (isset($data['data']['attributes']['last_analysis_stats'])) {
            return [
                'stats' => $data['data']['attributes']['last_analysis_stats'],
                'results' => $data['data']['attributes']['last_analysis_results'] ?? []
            ];
        }
    }
    
    // Jika URL belum discan, kirim untuk discan
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "url=" . urlencode($url));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-apikey: {$apiKey}",
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        
        // Ambil ID analisis untuk menunggu hasil
        $analysisId = $data['data']['id'] ?? null;
        
        if ($analysisId) {
            // Tunggu 10 detik agar scan selesai
            sleep(10);
            
            // Ambil hasil scan
            $resultUrl = "https://www.virustotal.com/api/v3/analyses/{$analysisId}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $resultUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apikey: {$apiKey}"]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $resultResponse = curl_exec($ch);
            curl_close($ch);
            
            $resultData = json_decode($resultResponse, true);
            if (isset($resultData['data']['attributes']['stats'])) {
                return [
                    'stats' => $resultData['data']['attributes']['stats'],
                    'results' => $resultData['data']['attributes']['results'] ?? []
                ];
            }
        }
    }
    
    return null;
}

// Panggil VirusTotal API
$result = callVirusTotal($url, VIRUSTOTAL_API_KEY);

if (!$result) {
    echo json_encode(['error' => 'Gagal menghubungi VirusTotal API. Cek API key atau coba lagi nanti.']);
    exit;
}

$stats = $result['stats'];
$results = $result['results'];

// Hitung skor keamanan (0-100)
$malicious = $stats['malicious'];
$suspicious = $stats['suspicious'];
$total = $stats['malicious'] + $stats['suspicious'] + $stats['harmless'] + $stats['undetected'];

$score = 100 - ($malicious * 5) - ($suspicious * 2);
$safetyScore = max(0, min(100, $score));

// Klasifikasi status
if ($safetyScore > 90 && $malicious === 0) {
    $status = 'safe';
} elseif ($safetyScore >= 50 && $safetyScore <= 70) {
    $status = 'suspicious';
} elseif ($safetyScore < 40) {
    $status = 'malicious';
} else {
    if ($malicious > 0) {
        $status = $safetyScore >= 50 ? 'suspicious' : 'malicious';
    } else {
        $status = 'safe';
    }
}

// Siapkan detail engine untuk ditampilkan
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

// Batasi jumlah detail yang ditampilkan (max 20)
$details = array_slice($details, 0, 20);

// Kirim response ke frontend
echo json_encode([
    'success' => true,
    'scanned_url' => $url,
    'malicious_count' => $stats['malicious'],
    'suspicious_count' => $stats['suspicious'],
    'harmless_count' => $stats['harmless'],
    'undetected_count' => $stats['undetected'],
    'total_engines' => $total,
    'safety_score' => $safetyScore,
    'details' => $details
]);