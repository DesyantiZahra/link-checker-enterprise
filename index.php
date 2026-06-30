<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
$user = requireAuth();

$pdo = getDB();

// Ambil semua data mentah (malicious_count, suspicious_count) untuk dihitung ulang
$stmt = $pdo->prepare("SELECT malicious_count, suspicious_count, DATE(scanned_at) as tgl FROM scan_history WHERE user_id = ? ORDER BY scanned_at ASC");
$stmt->execute([$user['id']]);
$allScans = $stmt->fetchAll();

// Hitung statistik total dengan rumus baru
$stats = ['total' => 0, 'safe' => 0, 'suspicious' => 0, 'malicious' => 0];
foreach ($allScans as $row) {
    $stats['total']++;
    $mal = (int)$row['malicious_count'];
    $susp = (int)$row['suspicious_count'];
    $score = calculateSafetyScore($mal, $susp);
    $status = getScanStatus($score, $mal, $susp);
    $stats[$status]++;
}

// Statistik 7 hari terakhir untuk grafik tren
$trendLabels = [];
$trendSafe = $trendSuspicious = $trendMalicious = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('d M', strtotime($d));
    $safe = $susp = $mal = 0;
    foreach ($allScans as $row) {
        if ($row['tgl'] === $d) {
            $malCount = (int)$row['malicious_count'];
            $suspCount = (int)$row['suspicious_count'];
            $score = calculateSafetyScore($malCount, $suspCount);
            $status = getScanStatus($score, $malCount, $suspCount);
            if ($status === 'malicious') $mal++;
            elseif ($status === 'suspicious') $susp++;
            else $safe++;
        }
    }
    $trendSafe[] = $safe;
    $trendSuspicious[] = $susp;
    $trendMalicious[] = $mal;
}

$safeCount       = $stats['safe'];
$suspiciousCount = $stats['suspicious'];
$maliciousCount  = $stats['malicious'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔗</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={darkMode:'class',theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .loading { display: none; text-align: center; padding: 20px; }
        .result-card { transition: all 0.3s ease; margin-top: 20px; }
        .safety-score-bar { height: 8px; border-radius: 4px; transition: width 0.5s ease; }
        .card-hover { transition: all 0.2s ease; }
        .card-hover:hover { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .btn-hover { transition: all 0.15s ease; }
        .btn-hover:hover { transform: scale(1.02); }
        .btn-hover:active { transform: scale(0.98); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease forwards; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <nav class="bg-white dark:bg-gray-800 shadow-md border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-xl font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">🔍 <?= APP_NAME ?></a>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-blue-600 font-semibold border-b-2 border-blue-600 pb-0.5">Dashboard</a>
                    <a href="history.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Riwayat</a>
                    <a href="guide.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Panduan</a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Admin Panel</a>
                    <?php endif; ?>
                    <a href="profile.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Profil</a>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <span class="text-gray-600 dark:text-gray-300 text-sm"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <button onclick="toggleDark()" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 text-lg transition-colors" title="Toggle Dark Mode">
                        <span id="darkIcon">🌙</span>
                    </button>
                    <form method="POST" action="logout.php" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 cursor-pointer transition-colors font-medium">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">Cek Keamanan Link</h1>
            <p class="text-gray-600 dark:text-gray-300">Analisis link dengan 70+ mesin antivirus via VirusTotal</p>
        </div>

        <!-- Form Scan -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 card-hover">
            <form id="scanForm" class="space-y-4">
                <div>
                    <label class="block text-gray-700 dark:text-gray-200 font-medium mb-2">Masukkan URL:</label>
                    <input type="url" id="urlInput" name="url" required
                           placeholder="https://example.com"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none dark:bg-gray-700 dark:text-gray-100 transition-shadow">
                </div>
                <button type="submit" id="scanBtn"
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 rounded-xl btn-hover shadow-md">
                    🔍 Cek Keamanan
                </button>
            </form>

            <!-- Loading -->
            <div id="loading" class="loading bg-gray-50 dark:bg-gray-700/50 rounded-xl mt-6" style="display: none;">
                <div class="flex flex-col items-center py-4">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600 mb-4"></div>
                    <p id="loadingText" class="text-gray-600 dark:text-gray-300 font-medium">🔍 Memvalidasi URL...</p>
                    <div class="w-full max-w-xs bg-gray-200 dark:bg-gray-600 rounded-full h-2.5 mt-3">
                        <div id="loadingBar" class="bg-gradient-to-r from-blue-500 to-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Mohon tunggu, ini bisa memakan waktu 10-15 detik</p>
                </div>
            </div>

            <!-- Hasil Scan -->
            <div id="resultContainer" style="display: none;"></div>
        </div>

        <!-- Statistik -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mt-8 card-hover">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">📊 Statistik Scan Anda</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl shadow-sm">
                    <div class="text-2xl font-bold text-white"><?= $stats['total'] ?? 0 ?></div>
                    <div class="text-xs text-gray-200">Total Scan</div>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-sm">
                    <div class="text-2xl font-bold text-white"><?= $stats['safe'] ?? 0 ?></div>
                    <div class="text-xs text-green-100">Aman</div>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-sm">
                    <div class="text-2xl font-bold text-white"><?= $stats['suspicious'] ?? 0 ?></div>
                    <div class="text-xs text-yellow-100">Mencurigakan</div>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-sm">
                    <div class="text-2xl font-bold text-white"><?= $stats['malicious'] ?? 0 ?></div>
                    <div class="text-xs text-red-100">Berbahaya</div>
                </div>
            </div>
        </div>

        <!-- Grafik Statistik -->
        <?php if ($stats['total'] > 0): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mt-8">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">📈 Grafik Statistik</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg">📈</span>
                        <h4 class="font-medium text-gray-700 dark:text-gray-200 text-sm">Tren 7 Hari Terakhir</h4>
                    </div>
                    <div style="position: relative; height: 270px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg">🥧</span>
                        <h4 class="font-medium text-gray-700 dark:text-gray-200 text-sm">Distribusi Status</h4>
                    </div>
                    <div style="position: relative; height: 270px;">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

            startLoadingProgress();

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
                stopLoadingProgress();
                loadingDiv.style.display = 'none';
                scanBtn.disabled = false;
                scanBtn.innerHTML = '🔍 Cek Keamanan';
                urlInput.value = '';
            }
        });

        function showResult(data) {
            // Gunakan status dari API yang sudah dihitung dengan benar
            const status = data.status || 'safe';
            const score = data.safety_score;
            const maliciousCount = data.malicious_count || 0;
            
            let statusColor, statusText, statusIcon, bgColor, borderColor;
            
            if (status === 'malicious') {
                statusColor = 'red';
                statusText = 'BERBAHAYA';
                statusIcon = '🔴';
                bgColor = 'bg-red-50';
                borderColor = 'border-red-500';
            } else if (status === 'suspicious') {
                statusColor = 'yellow';
                statusText = 'MENCURIGAKAN';
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
                <div class="result-card ${bgColor} dark:bg-gray-800 border-l-4 ${borderColor} rounded-xl shadow-md p-6 mt-6 fade-in">
                    <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
                        <div>
                            <div class="text-3xl mb-1">${statusIcon}</div>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Status: ${statusText}</h2>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold ${status === 'malicious' ? 'text-red-600' : (status === 'suspicious' ? 'text-yellow-600' : 'text-green-600')}">
                                ${data.safety_score}/100
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Skor Keamanan</div>
                            ${status === 'safe' ? '<div class="text-xs text-green-600 mt-1">Skor > 90 = Aman</div>' : (status === 'suspicious' ? '<div class="text-xs text-yellow-600 mt-1">Skor 50-70 = Mencurigakan</div>' : '<div class="text-xs text-red-600 mt-1">Skor < 40 = Berbahaya</div>')}
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-white dark:bg-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">URL yang dicek:</p>
                            <p class="font-mono text-sm break-all dark:text-gray-200">${escapeHtml(data.scanned_url)}</p>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-2">📊 Hasil Scan dari ${data.total_engines} Engine:</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div class="text-center p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                    <div class="text-xl font-bold text-red-600 dark:text-red-400">${data.malicious_count}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Malicious</div>
                                </div>
                                <div class="text-center p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                                    <div class="text-xl font-bold text-yellow-600 dark:text-yellow-400">${data.suspicious_count}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Suspicious</div>
                                </div>
                                <div class="text-center p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                    <div class="text-xl font-bold text-green-600 dark:text-green-400">${data.harmless_count}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Harmless</div>
                                </div>
                                <div class="text-center p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                    <div class="text-xl font-bold text-gray-600 dark:text-gray-300">${data.undetected_count}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Undetected</div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-green-600 dark:text-green-400">Aman ${safePercent}%</span>
                                <span class="text-red-600 dark:text-red-400">Berbahaya ${maliciousPercent}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-gradient-to-r from-green-400 to-green-600 h-3 float-left transition-all" style="width: ${safePercent}%"></div>
                                <div class="bg-gradient-to-r from-red-400 to-red-600 h-3 float-left transition-all" style="width: ${maliciousPercent}%"></div>
                            </div>
                        </div>
                        
                        <div class="p-3 rounded-lg ${status === 'malicious' ? 'bg-red-200 dark:bg-red-900/50' : (status === 'suspicious' ? 'bg-yellow-200 dark:bg-yellow-900/50' : 'bg-green-200 dark:bg-green-900/50')}">
                            <p class="font-medium text-sm dark:text-gray-100">
                                ${status === 'malicious' ? '⚠️ PERINGATAN: Link ini telah dilaporkan berbahaya oleh beberapa engine antivirus. JANGAN dibuka!' : 
                                  (status === 'suspicious' ? '⚠️ HATI-HATI: Link ini mencurigakan. Sebaiknya jangan dibuka tanpa kehati-hatian.' : 
                                  '✅ AMAN: Tidak ada engine antivirus yang melaporkan link ini sebagai berbahaya.')}
                            </p>
                        </div>
                        
                         ${engineHtml}
                        
                        ${data.screenshot_url ? `
                        <div class="mt-6 pt-4 border-t dark:border-gray-700">
                            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">📸 Screenshot Website</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 overflow-x-auto">
                                <img src="${data.screenshot_url}" alt="Website Screenshot" class="max-w-full border rounded-lg shadow-md" style="max-height: 600px;" onerror="this.parentElement.innerHTML='<p class=\\'text-yellow-600 text-sm\\'>⚠️ Gambar screenshot tidak dapat dimuat. Kemungkinan domain ini diblokir oleh URLScan.io atau screenshot masih dalam proses.</p>'; this.style.display='none';">
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Screenshot diambil oleh URLScan.io</p>
                        </div>
                        ` : `<div class="mt-6 pt-4 border-t dark:border-gray-700">
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30 p-3 rounded">ℹ️ Screenshot tidak tersedia. Domain ini mungkin ditolak oleh URLScan.io atau screenshot masih dalam proses generate. Cek halaman riwayat beberapa saat lagi.</p>
                        </div>`}
                        
                        <div class="flex gap-3 mt-2">
                            <a href="detail.php?id=${data.scan_id}" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg btn-hover shadow-sm">📋 Lihat Detail</a>
                            <button onclick="clearResult()" class="text-sm bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 px-3 py-1.5 rounded-lg btn-hover">🗑️ Tutup</button>
                        </div>
                        
                        <p class="text-xs text-gray-400 dark:text-gray-500 text-center pt-2 border-t dark:border-gray-700">
                            Scan ID: ${data.scan_id} | ${new Date().toLocaleString()}
                        </p>
                    </div>
                </div>
            `;
            
            resultContainer.innerHTML = html;
            resultContainer.style.display = 'block';
            resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function startLoadingProgress() {
            const steps = [
                { text: '🔍 Memvalidasi URL...', pct: 10 },
                { text: '📡 Menghubungi VirusTotal...', pct: 25 },
                { text: '⏳ Menunggu hasil analisis...', pct: 50 },
                { text: '📊 Memproses hasil scan...', pct: 70 },
                { text: '📸 Mengambil screenshot...', pct: 85 },
                { text: '✅ Menyelesaikan...', pct: 95 }
            ];
            const loadingText = document.getElementById('loadingText');
            const loadingBar = document.getElementById('loadingBar');
            let i = 0;
            const interval = setInterval(function() {
                if (i < steps.length) {
                    loadingText.textContent = steps[i].text;
                    loadingBar.style.width = steps[i].pct + '%';
                    i++;
                }
                if (i >= steps.length) {
                    clearInterval(interval);
                }
            }, 1500);
            window._loadingInterval = interval;
        }

        function stopLoadingProgress() {
            if (window._loadingInterval) {
                clearInterval(window._loadingInterval);
                window._loadingInterval = null;
            }
            document.getElementById('loadingText').textContent = '✅ Selesai';
            document.getElementById('loadingBar').style.width = '100%';
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

        // ====== CHARTS ======
        <?php if ($stats['total'] > 0): ?>
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#d1d5db' : '#4b5563';
        const gridColor = isDark ? '#374151' : '#e5e7eb';

        // Line chart: tren 7 hari
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($trendLabels) ?>,
                    datasets: [
                        { label: 'Aman', data: <?= json_encode($trendSafe) ?>, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)', tension: 0.3, fill: true, pointRadius: 3, pointHoverRadius: 5 },
                        { label: 'Mencurigakan', data: <?= json_encode($trendSuspicious) ?>, borderColor: '#eab308', backgroundColor: 'rgba(234,179,8,0.1)', tension: 0.3, fill: true, pointRadius: 3, pointHoverRadius: 5 },
                        { label: 'Berbahaya', data: <?= json_encode($trendMalicious) ?>, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', tension: 0.3, fill: true, pointRadius: 3, pointHoverRadius: 5 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: textColor, padding: 16, usePointStyle: true }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: textColor },
                            grid: { color: gridColor, drawBorder: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: textColor, stepSize: 1 },
                            grid: { color: gridColor, drawBorder: false }
                        }
                    }
                }
            });
        }

        // Doughnut chart: distribusi status
        const pieCtx = document.getElementById('pieChart');
        if (pieCtx) {
            const total = <?= $stats['total'] ?>;
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Aman', 'Mencurigakan', 'Berbahaya'],
                    datasets: [{ data: [<?= $safeCount ?>, <?= $suspiciousCount ?>, <?= $maliciousCount ?>], backgroundColor: ['#22c55e', '#eab308', '#ef4444'], borderWidth: 0, hoverOffset: 8 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: textColor, padding: 16, usePointStyle: true }
                        }
                    }
                },
                plugins: [{
                    id: 'centerText',
                    beforeDraw(chart) {
                        const { width, height, ctx } = chart;
                        ctx.save();
                        const centerX = width / 2;
                        const centerY = height / 2 - 10;
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.font = '700 28px Inter, sans-serif';
                        ctx.fillStyle = textColor;
                        ctx.fillText(total, centerX, centerY);
                        ctx.font = '12px Inter, sans-serif';
                        ctx.fillStyle = textColor;
                        ctx.fillText('Total Scan', centerX, centerY + 24);
                        ctx.restore();
                    }
                }]
            });
        }
        <?php endif; ?>
    </script>
    <footer class="bg-gray-100 dark:bg-gray-850 border-t border-gray-200 dark:border-gray-700 mt-12 py-6">
        <div class="container mx-auto px-6 text-center">
            <p class="text-gray-500 dark:text-gray-400 text-sm">🔍 <?= APP_NAME ?> v2.0 &copy; <?= date('Y') ?></p>

        </div>
    </footer>
    <script>
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.documentElement.classList.add('dark');
            document.getElementById('darkIcon').textContent = '☀️';
        }
        function toggleDark() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            const isDark = html.classList.contains('dark');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            document.getElementById('darkIcon').textContent = isDark ? '☀️' : '🌙';
        }
    </script>
</body>
</html>


