<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
$user = requireAuth();

$pdo = getDB();

// Get scan ID from URL
$scanId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$scanId) {
    header('Location: history.php');
    exit;
}

// Fetch scan detail
$stmt = $pdo->prepare("SELECT * FROM scan_history WHERE id = ? AND user_id = ?");
$stmt->execute([$scanId, $user['id']]);
$scan = $stmt->fetch();

if (!$scan) {
    echo "Scan tidak ditemukan";
    exit;
}

// Download screenshot
if (isset($_GET['download']) && $scan['screenshot_url']) {
    $imageUrl = $scan['screenshot_url'];
    $timestamp = date('Y-m-d_His');
    $host = parse_url($scan['url'], PHP_URL_HOST) ?: 'unknown';
    $host = preg_replace('/[^a-zA-Z0-9.-]/', '_', $host);
    $filename = "screenshot_{$host}_{$timestamp}.png";
    
    set_time_limit(60);
    $maxBytes = 10485760; // 10 MB max

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);

    $headerSize = $curlInfo['header_size'];
    $body = substr($response, $headerSize);
    $contentType = $curlInfo['content_type'] ?? '';

    if ($body === false || strlen($body) > $maxBytes) {
        echo "Gagal mengambil screenshot atau file terlalu besar.";
        exit;
    }

    // Validasi Content-Type
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
    $isValidImage = false;
    foreach ($allowedTypes as $type) {
        if (stripos($contentType, $type) !== false) {
            $isValidImage = true;
            break;
        }
    }
    if (!$isValidImage) {
        error_log("Screenshot download: invalid content type '$contentType' for URL: $imageUrl");
        echo "Gagal mengunduh screenshot: tipe konten tidak valid.";
        exit;
    }

    $ext = 'png';
    if (stripos($contentType, 'jpeg') !== false || stripos($contentType, 'jpg') !== false) {
        $ext = 'jpg';
    } elseif (stripos($contentType, 'webp') !== false) {
        $ext = 'webp';
    } elseif (stripos($contentType, 'gif') !== false) {
        $ext = 'gif';
    }

    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($body));
    echo $body;
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screenshot - <?= APP_NAME ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔗</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={darkMode:'class',theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <style>.btn-hover{transition:all .15s ease}.btn-hover:hover{transform:scale(1.02)}.btn-hover:active{transform:scale(.98)}.card-hover{transition:all .2s ease}.card-hover:hover{box-shadow:0 10px 25px -5px rgba(0,0,0,.1);transform:translateY(-2px)}</style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <nav class="bg-white dark:bg-gray-800 shadow-md border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-xl font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">🔍 <?= APP_NAME ?></a>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Dashboard</a>
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

    <div class="container mx-auto px-6 py-8 max-w-6xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">📸 Screenshot Website</h1>
            <a href="history.php" class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Kembali ke Riwayat
            </a>
        </div>

        <!-- Header Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 card-hover">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-gray-600 dark:text-gray-300 font-mono break-all text-sm"><?= htmlspecialchars($scan['url']) ?></p>
                </div>
                <div>
                    <div class="text-right">
                        <?php
                            $malCount = (int)$scan['malicious_count'];
                            $suspCount = (int)$scan['suspicious_count'];
                            $score = calculateSafetyScore($malCount, $suspCount);
                            $viewStatus = getScanStatus($score, $malCount, $suspCount);
                            $colorMap = ['safe' => 'text-green-600', 'suspicious' => 'text-yellow-600', 'malicious' => 'text-red-600'];
                            $scoreColor = $colorMap[$viewStatus] ?? 'text-gray-600 dark:text-gray-300';
                        ?>
                        <div class="text-3xl font-bold <?= $scoreColor ?>"><?= $score ?>/100</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Skor Keamanan</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-red-50 p-3 rounded">
                    <div class="text-xl font-bold text-red-600"><?= $scan['malicious_count'] ?></div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Malicious</div>
                </div>
                <div class="bg-yellow-50 p-3 rounded">
                    <div class="text-xl font-bold text-yellow-600"><?= $scan['suspicious_count'] ?></div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Suspicious</div>
                </div>
                <div class="bg-green-50 p-3 rounded">
                    <div class="text-xl font-bold text-green-600"><?= $scan['harmless_count'] ?></div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Harmless</div>
                </div>
                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-xl font-bold text-gray-600 dark:text-gray-300"><?= $scan['undetected_count'] ?></div>
                    <div class="text-xs text-gray-600 dark:text-gray-300">Undetected</div>
                </div>
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400">
                Scanned at: <?= date('d M Y H:i:s', strtotime($scan['scanned_at'])) ?> | 
                Scan ID: #<?= $scan['id'] ?>
            </div>
        </div>

        <!-- Screenshot Display -->
        <?php if ($scan['screenshot_url']): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 card-hover">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">🖼️ Preview</h2>
            <div class="bg-gray-50 rounded-lg overflow-hidden flex items-center justify-center" style="min-height: 400px;">
                <img src="<?= htmlspecialchars($scan['screenshot_url']) ?>" alt="Website Screenshot" 
                     class="max-w-full max-h-full" style="max-height: 600px;">
            </div>
            
            <div class="mt-4 flex gap-3">
                <a href="view-screenshot.php?id=<?= $scan['id'] ?>&download=1" 
                   class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 rounded-lg btn-hover shadow-sm">
                    ⬇️ Download Screenshot
                </a>
                <a href="<?= htmlspecialchars($scan['screenshot_url']) ?>" target="_blank" 
                   class="bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white px-4 py-2 rounded-lg transition">
                    🔗 Buka di Tab Baru
                </a>
            </div>

            <p class="text-xs text-gray-400 mt-3">
                Source: <a href="https://urlscan.io/" target="_blank" class="text-blue-500 hover:underline">URLScan.io</a>
            </p>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <p class="text-yellow-800">⚠️ Screenshot tidak tersedia untuk scan ini. Kemungkinan:</p>
            <ul class="text-sm text-yellow-700 mt-2 ml-4 list-disc">
                <li>Website ditolak oleh URLScan.io (contoh: Google, Facebook)</li>
                <li>API key URLScan tidak dikonfigurasi</li>
                <li>Scan dilakukan sebelum fitur screenshot ditambahkan</li>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Scan Details -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">📊 Detail Scan</h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">Informasi Scan</h3>
                    <table class="w-full text-sm">
                        <tr class="border-b">
                            <td class="py-2 text-gray-600 dark:text-gray-300">URL:</td>
                            <td class="py-2 font-mono break-all"><?= htmlspecialchars($scan['url']) ?></td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-gray-600 dark:text-gray-300">Status:</td>
                            <td class="py-2">
                                <?php
                                if ($scan['status'] === 'safe') {
                                    $statusBadge = '<span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">🟢 AMAN</span>';
                                } elseif ($scan['status'] === 'suspicious') {
                                    $statusBadge = '<span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">🟡 MENCURIGAKAN</span>';
                                } elseif ($scan['status'] === 'malicious') {
                                    $statusBadge = '<span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">🔴 BERBAHAYA</span>';
                                } else {
                                    $statusBadge = '<span class="bg-gray-100 text-gray-700 dark:text-gray-200 px-2 py-1 rounded text-xs">UNKNOWN</span>';
                                }
                                echo $statusBadge;
                                ?>
                            </td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-gray-600 dark:text-gray-300">Total Engine:</td>
                            <td class="py-2"><?= $scan['total_engines'] ?> engine</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-gray-600 dark:text-gray-300">Keamanan Score:</td>
                            <td class="py-2 font-bold text-lg"><?= $scan['safety_score'] ?>/100</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-300">Waktu Scan:</td>
                            <td class="py-2"><?= date('d M Y H:i:s', strtotime($scan['scanned_at'])) ?></td>
                        </tr>
                    </table>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">Hasil Engine</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-300">Malicious:</span>
                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded font-semibold"><?= $scan['malicious_count'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-300">Suspicious:</span>
                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded font-semibold"><?= $scan['suspicious_count'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-300">Harmless:</span>
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded font-semibold"><?= $scan['harmless_count'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-300">Undetected:</span>
                            <span class="bg-gray-100 text-gray-700 dark:text-gray-200 px-3 py-1 rounded font-semibold"><?= $scan['undetected_count'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
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


