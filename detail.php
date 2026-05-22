<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$user = requireAuth();

$scan_id = $_GET['id'] ?? 0;

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM scan_history WHERE id = ? AND user_id = ?");
$stmt->execute([$scan_id, $user['id']]);
$scan = $stmt->fetch();

if (!$scan) {
    header('Location: history.php');
    exit;
}

// Ambil detail engine dari database (disimpan sebagai JSON di kolom engine_results)
$details = [];
if (!empty($scan['engine_results'])) {
    $details = json_decode($scan['engine_results'], true);
    if (!is_array($details)) {
        $details = [];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Scan - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="text-xl font-semibold text-gray-700">🔍 <?= APP_NAME ?></div>
                <div class="flex space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-800">Dashboard</a>
                    <a href="history.php" class="text-gray-600 hover:text-gray-800">Riwayat</a>
                    <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="text-gray-600 hover:text-gray-800">Admin Panel</a>
                    <?php endif; ?>
                    <a href="profile.php" class="text-gray-600 hover:text-gray-800">Profil</a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-600"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8 max-w-4xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">📋 Detail Scan</h1>
            <a href="history.php" class="text-blue-600 hover:underline">← Kembali ke Riwayat</a>
        </div>

        <?php
        $score = (int)$scan['safety_score'];
        $maliciousCount = (int)$scan['malicious_count'];
        
        if ($score > 90 && $maliciousCount === 0) {
            $displayStatus = 'safe';
        } elseif ($score >= 50 && $score <= 70) {
            $displayStatus = 'suspicious';
        } elseif ($score < 40) {
            $displayStatus = 'malicious';
        } else {
            // Score 40-49 atau 71-90
            $displayStatus = $maliciousCount > 0 ? 'suspicious' : 'safe';
        }

        $statusColor = match($displayStatus) {
            'safe' => 'green',
            'suspicious' => 'yellow',
            'malicious' => 'red',
            default => 'gray'
        };
        
        $statusText = match($displayStatus) {
            'safe' => 'AMAN',
            'suspicious' => 'MENURIGAKAN',
            'malicious' => 'BERBAHAYA',
            default => 'UNKNOWN'
        };
        
        $statusIcon = match($displayStatus) {
            'safe' => '🟢',
            'suspicious' => '🟡',
            'malicious' => '🔴',
            default => '⚪'
        };
        ?>

        <div class="bg-white rounded-lg shadow p-6">
            <!-- Header Status -->
            <div class="bg-<?= $statusColor ?>-100 p-4 rounded-lg mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl mb-1"><?= $statusIcon ?></div>
                        <p class="font-bold text-<?= $statusColor ?>-700 text-lg">Status: <?= $statusText ?></p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-<?= $statusColor ?>-600"><?= $scan['safety_score'] ?>/100</div>
                        <div class="text-sm text-gray-500">Skor Keamanan</div>
                        <?php if ($displayStatus === 'safe'): ?>
                            <div class="text-xs text-green-600 mt-1">Skor > 90 = Aman</div>
                        <?php elseif ($displayStatus === 'suspicious'): ?>
                            <div class="text-xs text-yellow-600 mt-1">Skor 50-70 = Mencurigakan</div>
                        <?php elseif ($displayStatus === 'malicious'): ?>
                            <div class="text-xs text-red-600 mt-1">Skor < 40 = Berbahaya</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Informasi Scan -->
            <div class="space-y-4">
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-500">Waktu Scan</p>
                    <p class="font-medium"><?= date('d F Y H:i:s', strtotime($scan['scanned_at'])) ?></p>
                </div>
                
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-500">URL yang Discan</p>
                    <p class="font-mono text-sm break-all bg-gray-50 p-2 rounded"><?= htmlspecialchars($scan['url']) ?></p>
                </div>
                
                <?php if ($scan['final_url'] && $scan['final_url'] != $scan['url']): ?>
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-500">URL Tujuan (setelah redirect)</p>
                    <p class="font-mono text-sm break-all bg-gray-50 p-2 rounded"><?= htmlspecialchars($scan['final_url']) ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($scan['screenshot_url'])): ?>
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-500 mb-2">Screenshot Website</p>
                    <div class="rounded-lg overflow-hidden border border-gray-200">
                        <img src="<?= htmlspecialchars($scan['screenshot_url']) ?>" alt="Screenshot" class="w-full max-h-80 object-contain bg-gray-50">
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="view-screenshot.php?id=<?= $scan['id'] ?>" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Lihat Screenshot</a>
                        <a href="view-screenshot.php?id=<?= $scan['id'] ?>&download=1" class="bg-gray-700 text-white px-3 py-2 rounded text-sm hover:bg-gray-800">Download</a>
                        <a href="<?= htmlspecialchars($scan['screenshot_url']) ?>" target="_blank" class="bg-gray-200 text-gray-700 px-3 py-2 rounded text-sm hover:bg-gray-300">Buka di URLScan</a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Hasil Scan Engine -->
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-500 mb-2">Hasil Scan dari <?= $scan['total_engines'] ?> Engine Antivirus</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="text-center p-3 bg-red-100 rounded-lg">
                            <div class="text-2xl font-bold text-red-600"><?= $scan['malicious_count'] ?></div>
                            <div class="text-xs text-gray-600">Malicious</div>
                        </div>
                        <div class="text-center p-3 bg-yellow-100 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600"><?= $scan['suspicious_count'] ?></div>
                            <div class="text-xs text-gray-600">Suspicious</div>
                        </div>
                        <div class="text-center p-3 bg-green-100 rounded-lg">
                            <div class="text-2xl font-bold text-green-600"><?= $scan['harmless_count'] ?></div>
                            <div class="text-xs text-gray-600">Harmless</div>
                        </div>
                        <div class="text-center p-3 bg-gray-100 rounded-lg">
                            <div class="text-2xl font-bold text-gray-600"><?= $scan['undetected_count'] ?></div>
                            <div class="text-xs text-gray-600">Undetected</div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="border-b pb-3">
                    <p class="text-sm text-gray-500 mb-1">Visualisasi Keamanan</p>
                    <?php 
                    $safePercent = $scan['total_engines'] > 0 ? (($scan['harmless_count'] + $scan['undetected_count']) / $scan['total_engines'] * 100) : 100;
                    $maliciousPercent = $scan['total_engines'] > 0 ? ($scan['malicious_count'] / $scan['total_engines'] * 100) : 0;
                    ?>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-green-600">Aman <?= round($safePercent) ?>%</span>
                        <span class="text-red-600">Berbahaya <?= round($maliciousPercent) ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div class="bg-green-500 h-3 float-left" style="width: <?= $safePercent ?>%"></div>
                        <div class="bg-red-500 h-3 float-left" style="width: <?= $maliciousPercent ?>%"></div>
                    </div>
                </div>

                <!-- Rekomendasi -->
                <div class="p-3 rounded-lg bg-<?= $statusColor ?>-100">
                    <p class="font-medium text-sm">
                        <?php if ($displayStatus == 'malicious'): ?>
                            ⚠️ PERINGATAN: Link ini telah dilaporkan berbahaya oleh beberapa engine antivirus. JANGAN dibuka!
                        <?php elseif ($displayStatus == 'suspicious'): ?>
                            ⚠️ HATI-HATI: Link ini mencurigakan. Sebaiknya jangan dibuka tanpa kehati-hatian ekstra.
                        <?php else: ?>
                            ✅ AMAN: Tidak ada engine antivirus yang melaporkan link ini sebagai berbahaya.
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Informasi Detail Engine (Jika Ada) -->
                <?php if (!empty($details)): ?>
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm text-blue-600 hover:text-blue-800 font-semibold">
                            🔍 Lihat detail engine (<?= count($details) ?> engine)
                        </summary>
                        <div class="mt-3 max-h-96 overflow-y-auto bg-gray-50 rounded-lg p-3">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-gray-100">
                                    <tr class="border-b">
                                        <th class="text-left py-2">Engine Antivirus</th>
                                        <th class="text-left py-2">Hasil Deteksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($details as $d): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                                        <td class="py-2 font-medium"><?= htmlspecialchars($d['engine']) ?></td>
                                        <?php
                                            $category = $d['category'] ?? '';
                                            $engineClass = 'text-green-600';
                                            $engineLabel = $d['result'] ?? '✅ Clean';
                                            if ($category === 'malicious') {
                                                $engineClass = 'text-red-600 font-semibold';
                                                $engineLabel = $d['result'] ?? '⚠️ Malicious';
                                            } elseif ($category === 'suspicious') {
                                                $engineClass = 'text-yellow-600 font-semibold';
                                                $engineLabel = $d['result'] ?? '⚠️ Suspicious';
                                            }
                                        ?>
                                        <td class="py-2 <?= $engineClass ?>">
                                            <?= htmlspecialchars($engineLabel) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php else: ?>
                    <div class="mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                        <p class="text-sm text-yellow-700">
                            ℹ️ <strong>Catatan:</strong> Detail per engine antivirus tidak tersedia untuk scan ini. Jika Anda ingin melihat detail lengkap, jalankan scan ulang setelah fitur detail engine diaktifkan.
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Tombol Aksi -->
                <div class="flex flex-wrap gap-3 pt-4">
                    <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition text-sm">🔄 Scan Baru</a>
                    <a href="history.php" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded transition text-sm">📋 Kembali ke Riwayat</a>
                    <button onclick="downloadPDF()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition text-sm">📄 Download PDF</button>
                </div>

                <p class="text-xs text-gray-400 text-center pt-4">
                    Scan ID: <?= $scan['id'] ?> | Waktu: <?= date('d/m/Y H:i:s', strtotime($scan['scanned_at'])) ?>
                </p>
             </div>
         </div>
     </div>
     <script>
         const scanData = {
             id: <?= $scan['id'] ?>,
             url: <?= json_encode($scan['url']) ?>,
             scanned_at: <?= json_encode($scan['scanned_at']) ?>,
             safety_score: <?= (int)$scan['safety_score'] ?>,
             status: <?= json_encode($scan['status']) ?>,
             malicious_count: <?= (int)$scan['malicious_count'] ?>,
             suspicious_count: <?= (int)$scan['suspicious_count'] ?>,
             harmless_count: <?= (int)$scan['harmless_count'] ?>,
             undetected_count: <?= (int)$scan['undetected_count'] ?>,
             total_engines: <?= (int)$scan['total_engines'] ?>,
             screenshot_url: <?= json_encode($scan['screenshot_url'] ?? '') ?>,
             details: <?= json_encode($details) ?>
         };

         function downloadPDF() {
             const { jsPDF } = window.jspdf;
             const doc = new jsPDF();
             const d = scanData;
             let y = 20;

             doc.setFontSize(18);
             doc.text('Hasil Scan Link Checker', 20, y);
             y += 8;

             doc.setFontSize(11);
             doc.text('URL: ' + d.url, 20, y); y += 6;
             doc.text('Waktu Scan: ' + d.scanned_at, 20, y); y += 6;
             doc.text('Skor Keamanan: ' + d.safety_score + '/100   Status: ' + d.status.toUpperCase(), 20, y); y += 10;

             doc.setFontSize(13);
             doc.text('Hasil Engine:', 20, y); y += 7;
             doc.setFontSize(10);
             doc.text('Malicious: ' + d.malicious_count + '   Suspicious: ' + d.suspicious_count + '   Harmless: ' + d.harmless_count + '   Undetected: ' + d.undetected_count, 20, y); y += 10;

             if (d.details && d.details.length > 0) {
                 doc.setFontSize(13);
                 doc.text('Detail Engine (' + d.details.length + '):', 20, y); y += 7;
                 doc.setFontSize(9);
                 d.details.forEach(function(e) {
                     if (y > 270) { doc.addPage(); y = 20; }
                     doc.text(e.engine + ' - ' + e.category.toUpperCase(), 25, y); y += 5;
                 });
             }

             doc.save('scan-' + d.id + '.pdf');
         }
     </script>
 </body>
</html>