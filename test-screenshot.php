<?php
require_once 'includes/config.php';

echo "<h1>Test URLScan.io API</h1>";

if (defined('URLSCAN_API_KEY') && URLSCAN_API_KEY && URLSCAN_API_KEY != 'MASUKKAN_API_KEY_URLSCAN_ANDA_DISINI') {
    echo "<p>✅ API Key terdeteksi</p>";
    
    // GANTI URL INI - pilih salah satu yang jarang discan
    // $testUrl = 'https://example.com';           // URL standar
    // $testUrl = 'https://httpbin.org/anything';  // URL testing
    $testUrl = 'https://www.wikipedia.org';        // Wikipedia biasanya diizinkan
    
    echo "<p>Mencoba scan: <code>" . $testUrl . "</code></p>";
    
    $submitUrl = 'https://urlscan.io/api/v1/scan/';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $submitUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'url' => $testUrl,
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
    curl_close($ch);
    
    echo "<p><strong>HTTP Response Code:</strong> " . $httpCode . "</p>";
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        echo "<p style='color:green'>✅ <strong>BERHASIL!</strong></p>";
        echo "<p>UUID: " . ($data['uuid'] ?? 'tidak ada') . "</p>";
        
        // Tunggu sebentar lalu ambil hasil
        echo "<p>Menunggu 8 detik untuk screenshot...</p>";
        sleep(8);
        
        $uuid = $data['uuid'];
        $resultUrl = "https://urlscan.io/api/v1/result/{$uuid}/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $resultUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['API-Key: ' . URLSCAN_API_KEY]);
        $resultResponse = curl_exec($ch);
        curl_close($ch);
        
        $resultData = json_decode($resultResponse, true);
        
        if (isset($resultData['task']['screenshot'])) {
            $screenshotUrl = $resultData['task']['screenshot'];
            echo "<p>📸 <strong>Screenshot URL:</strong> <a href='$screenshotUrl' target='_blank'>$screenshotUrl</a></p>";
            echo "<img src='$screenshotUrl' style='max-width:100%; border:1px solid #ccc; margin-top:10px;'>";
        } else {
            echo "<p>⚠️ Screenshot belum siap atau tidak tersedia</p>";
        }
        
    } elseif ($httpCode == 400) {
        $data = json_decode($response, true);
        echo "<p style='color:orange'>⚠️ <strong>URL ditolak oleh URLScan.io</strong></p>";
        echo "<p>Pesan: " . ($data['message'] ?? $data['description'] ?? 'Unknown') . "</p>";
        echo "<p>Ini normal untuk domain populer seperti Google/Facebook.</p>";
    } else {
        echo "<p style='color:red'>❌ Gagal: HTTP $httpCode</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
    
} else {
    echo "<p style='color:red'>❌ API Key belum diisi!</p>";
    echo "<p>Masukkan API key di <code>includes/config.php</code></p>";
}
?>