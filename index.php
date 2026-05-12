<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$user = requireAuth();

// Ambil statistik
$pdo = getDB();
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='safe' THEN 1 ELSE 0 END) as safe, SUM(CASE WHEN status='suspicious' THEN 1 ELSE 0 END) as suspicious, SUM(CASE WHEN status='malicious' THEN 1 ELSE 0 END) as malicious FROM scan_history WHERE user_id = ?");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .loading { display: none; text-align: center; padding: 20px; }
        .result-card { transition: all 0.3s ease; margin-top: 20px; }
        .safety-score-bar { height: 8px; border-radius: 4px; transition: width 0.5s ease; }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="text-xl font-semibold text-gray-700">🔍 <?= APP_NAME ?></div>
                <div class="flex space-x-4">
                    <a href="index.php" class="text-blue-600 font-semibold">Dashboard</a>
                    <a href="history.php" class="text-gray-600 hover:text-gray-800">Riwayat</a>
                    <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="text-gray-600 hover:text-gray-800">Admin Panel</a>
                    <?php endif; ?>
                    <a href="profile.php" class="text-gray-600 hover:text-gray-800">Profil</a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-600">Halo, <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Cek Keamanan Link</h1>
            <p class="text-gray-600">Analisis link dengan 70+ mesin antivirus via VirusTotal</p>
        </div>

        <!-- Form Scan -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <form id="scanForm" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Masukkan URL:</label>
                    <input type="url" id="urlInput" name="url" required
                           placeholder="https://example.com"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <button type="submit" id="scanBtn"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition duration-200">
                    🔍 Cek Keamanan
                </button>
            </form>

            <!-- Loading -->
            <div id="loading" class="loading bg-gray-50 rounded-xl mt-6" style="display: none;">
                <div class="flex flex-col items-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                    <p class="text-gray-600">Sedang menganalisis ke VirusTotal...</p>
                    <p class="text-xs text-gray-400 mt-2">Mohon tunggu, ini bisa memakan waktu 10-15 detik</p>
                </div>
            </div>

            <!-- Hasil Scan -->
            <div id="resultContainer" style="display: none;"></div>
        </div>

        <!-- Statistik -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
            <h3 class="font-semibold text-gray-700 mb-3">📊 Statistik Scan Anda</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-600"><?= $stats['total'] ?? 0 ?></div>
                    <div class="text-xs text-gray-500">Total Scan</div>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600"><?= $stats['safe'] ?? 0 ?></div>
                    <div class="text-xs text-gray-500">Aman</div>
                </div>
                <div class="text-center p-3 bg-yellow-50 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600"><?= $stats['suspicious'] ?? 0 ?></div>
                    <div class="text-xs text-gray-500">Mencurigakan</div>
                </div>
                <div class="text-center p-3 bg-red-50 rounded-lg">
                    <div class="text-2xl font-bold text-red-600"><?= $stats['malicious'] ?? 0 ?></div>
                    <div class="text-xs text-gray-500">Berbahaya</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('scanForm');
        const urlInput = document.getElementById('urlInput');
        const scanBtn = document.getElementById('scanBtn');
        const loadingDiv = document.getElementById('loading');
        const resultContainer = document.getElementById('resultContainer');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const url = urlInput.value.trim();
            if (!url) return;

            resultContainer.style.display = 'none';
            loadingDiv.style.display = 'block';
            scanBtn.disabled = true;
            scanBtn.innerHTML = '⏳ Memproses...';

            try {
                const response = await fetch('api/scan.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: url })
                });

                const data = await response.json();

                if (data.error) {
                    showError(data.error);
                } else {
                    showResult(data);
                }
            } catch (error) {
                showError('Gagal terhubung ke server: ' + error.message);
            } finally {
                loadingDiv.style.display = 'none';
                scanBtn.disabled = false;
                scanBtn.innerHTML = '🔍 Cek Keamanan';
                urlInput.value = '';
            }
        });

        function showResult(data) {
            const score = data.safety_score;
            const maliciousCount = data.malicious_count || 0;
            const isSafe = score > 90 && maliciousCount === 0;
            const isSuspicious = score >= 50 && score <= 70;
            const isMalicious = score < 40;
            
            let statusColor, statusText, statusIcon, bgColor, borderColor;
            
            if (isMalicious) {
                statusColor = 'red';
                statusText = 'BERBAHAYA';
                statusIcon = '🔴';
                bgColor = 'bg-red-50';
                borderColor = 'border-red-500';
            } else if (isSuspicious) {
                statusColor = 'yellow';
                statusText = 'MENURIGAKAN';
                statusIcon = '🟡';
                bgColor = 'bg-yellow-50';
                borderColor = 'border-yellow-500';
            } else {
                statusColor = 'green';
                statusText = 'AMAN';
                statusIcon = '🟢';
                bgColor = 'bg-green-50';
                borderColor = 'border-green-500';
            }

            const totalEngines = data.total_engines || 1;
            const safePercent = ((data.harmless_count + data.undetected_count) / totalEngines * 100).toFixed(1);
            const maliciousPercent = (data.malicious_count / totalEngines * 100).toFixed(1);

            // Buat daftar engine
            let engineHtml = '';
            if (data.details && data.details.length > 0) {
                const detectedCount = data.details.filter(d => d.detected).length;
                engineHtml = `
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm text-blue-600 hover:text-blue-800 font-semibold">
                            🔍 Lihat detail engine (${data.details.length} engine) 
                            <span class="text-red-600">${detectedCount} terdeteksi</span>
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
                                    ${data.details.map(d => {
                                        const category = d.category || 'clean';
                                        const isMaliciousEngine = category === 'malicious';
                                        const isSuspiciousEngine = category === 'suspicious';
                                        const cellClass = isMaliciousEngine ? 'text-red-600 font-semibold' : (isSuspiciousEngine ? 'text-yellow-600 font-semibold' : 'text-green-600');
                                        const resultText = isMaliciousEngine ? (d.result || '⚠️ Malicious') : (isSuspiciousEngine ? (d.result || '⚠️ Suspicious') : (d.result || '✅ Clean'));
                                        return `
                                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                                            <td class="py-2 font-medium">${escapeHtml(d.engine)}</td>
                                            <td class="py-2 ${cellClass}">
                                                ${resultText}
                                            </td>
                                        </tr>
                                    `}).join('')}
                                </tbody>
                            </table>
                        </div>
                    </details>
                `;
            }

            const html = `
                <div class="result-card ${bgColor} border-l-4 ${borderColor} rounded-xl shadow-md p-6 mt-6">
                    <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                        <div>
                            <div class="text-3xl mb-1">${statusIcon}</div>
                            <h2 class="text-xl font-bold text-gray-800">Status: ${statusText}</h2>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold ${isMalicious ? 'text-red-600' : (isSuspicious ? 'text-yellow-600' : 'text-green-600')}">
                                ${data.safety_score}/100
                            </div>
                            <div class="text-sm text-gray-500">Skor Keamanan</div>
                            ${isSafe ? '<div class="text-xs text-green-600 mt-1">Skor > 90 = Aman</div>' : (isSuspicious ? '<div class="text-xs text-yellow-600 mt-1">Skor 50-70 = Mencurigakan</div>' : '<div class="text-xs text-red-600 mt-1">Skor < 40 = Berbahaya</div>')}
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-white rounded-lg p-3">
                            <p class="text-xs text-gray-500 mb-1">URL yang dicek:</p>
                            <p class="font-mono text-sm break-all">${escapeHtml(data.scanned_url)}</p>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">📊 Hasil Scan dari ${data.total_engines} Engine:</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div class="text-center p-2 bg-red-100 rounded-lg">
                                    <div class="text-xl font-bold text-red-600">${data.malicious_count}</div>
                                    <div class="text-xs text-gray-600">Malicious</div>
                                </div>
                                <div class="text-center p-2 bg-yellow-100 rounded-lg">
                                    <div class="text-xl font-bold text-yellow-600">${data.suspicious_count}</div>
                                    <div class="text-xs text-gray-600">Suspicious</div>
                                </div>
                                <div class="text-center p-2 bg-green-100 rounded-lg">
                                    <div class="text-xl font-bold text-green-600">${data.harmless_count}</div>
                                    <div class="text-xs text-gray-600">Harmless</div>
                                </div>
                                <div class="text-center p-2 bg-gray-100 rounded-lg">
                                    <div class="text-xl font-bold text-gray-600">${data.undetected_count}</div>
                                    <div class="text-xs text-gray-600">Undetected</div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-green-600">Aman ${safePercent}%</span>
                                <span class="text-red-600">Berbahaya ${maliciousPercent}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-green-500 h-2 float-left" style="width: ${safePercent}%"></div>
                                <div class="bg-red-500 h-2 float-left" style="width: ${maliciousPercent}%"></div>
                            </div>
                        </div>
                        
                        <div class="p-3 rounded-lg ${isMalicious ? 'bg-red-200' : (isSuspicious ? 'bg-yellow-200' : 'bg-green-200')}">
                            <p class="font-medium text-sm">
                                ${isMalicious ? '⚠️ PERINGATAN: Link ini telah dilaporkan berbahaya oleh beberapa engine antivirus. JANGAN dibuka!' : 
                                  (isSuspicious ? '⚠️ HATI-HATI: Link ini mencurigakan. Sebaiknya jangan dibuka tanpa kehati-hatian.' : 
                                   '✅ AMAN: Tidak ada engine antivirus yang melaporkan link ini sebagai berbahaya.')}
                            </p>
                        </div>
                        
                        ${engineHtml}
                        
                        ${data.screenshot_url ? `
                        <div class="mt-6 pt-4 border-t">
                            <h3 class="font-semibold text-gray-700 mb-3">📸 Screenshot Website</h3>
                            <div class="bg-gray-50 rounded-lg p-3 overflow-x-auto">
                                <img src="${data.screenshot_url}" alt="Website Screenshot" class="max-w-full border rounded-lg shadow-md" style="max-height: 600px;">
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Screenshot diambil oleh URLScan.io</p>
                        </div>
                        ` : ''}
                        
                        <div class="flex gap-3 mt-2">
                            <a href="history.php" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition">📋 Lihat Riwayat</a>
                            <button onclick="clearResult()" class="text-sm bg-gray-300 hover:bg-gray-400 px-3 py-1 rounded transition">🗑️ Tutup</button>
                        </div>
                        
                        <p class="text-xs text-gray-400 text-center pt-2 border-t">
                            Scan ID: ${data.scan_id} | ${new Date().toLocaleString()}
                        </p>
                    </div>
                </div>
            `;
            
            resultContainer.innerHTML = html;
            resultContainer.style.display = 'block';
            resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function showError(message) {
            resultContainer.innerHTML = `
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mt-6">
                    <p class="font-bold">❌ Error</p>
                    <p>${escapeHtml(message)}</p>
                </div>
            `;
            resultContainer.style.display = 'block';
        }

        function clearResult() {
            resultContainer.style.display = 'none';
            resultContainer.innerHTML = '';
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
    </script>
</body>
</html>