<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
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
    $filename = "screenshot_" . urlencode(parse_url($scan['url'], PHP_URL_HOST)) . "_" . $timestamp . ".png";
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $stream = @fopen($imageUrl, 'rb');
    if ($stream !== false) {
        while (!feof($stream)) {
            echo fread($stream, 8192);
        }
        fclose($stream);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screenshot - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    <span class="text-gray-600">Halo, <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8 max-w-6xl">
        <!-- Header Info -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">📸 Screenshot Website</h1>
                    <p class="text-gray-600 font-mono break-all text-sm"><?= htmlspecialchars($scan['url']) ?></p>
                </div>
                <div>
                    <div class="text-right">
                        <?php
                            $score = (int)$scan['safety_score'];
                            $malCount = (int)$scan['malicious_count'];
                            if ($score > 90 && $malCount === 0) {
                                $scoreColor = 'text-green-600';
                            } elseif ($score >= 50 && $score <= 70) {
                                $scoreColor = 'text-yellow-600';
                            } elseif ($score < 40) {
                                $scoreColor = 'text-red-600';
                            } else {
                                $scoreColor = $malCount > 0 ? 'text-yellow-600' : 'text-green-600';
                            }
                        ?>
                        <div class="text-3xl font-bold <?= $scoreColor ?>"><?= $scan['safety_score'] ?>/100</div>
                        <div class="text-sm text-gray-500">Skor Keamanan</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-red-50 p-3 rounded">
                    <div class="text-xl font-bold text-red-600"><?= $scan['malicious_count'] ?></div>
                    <div class="text-xs text-gray-600">Malicious</div>
                </div>
                <div class="bg-yellow-50 p-3 rounded">
                    <div class="text-xl font-bold text-yellow-600"><?= $scan['suspicious_count'] ?></div>
                    <div class="text-xs text-gray-600">Suspicious</div>
                </div>
                <div class="bg-green-50 p-3 rounded">
                    <div class="text-xl font-bold text-green-600"><?= $scan['harmless_count'] ?></div>
                    <div class="text-xs text-gray-600">Harmless</div>
                </div>
                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-xl font-bold text-gray-600"><?= $scan['undetected_count'] ?></div>
                    <div class="text-xs text-gray-600">Undetected</div>
                </div>
            </div>

            <div class="text-xs text-gray-500">
                Scanned at: <?= date('d M Y H:i:s', strtotime($scan['scanned_at'])) ?> | 
                Scan ID: #<?= $scan['id'] ?>
            </div>
        </div>

        <!-- Screenshot Display -->
        <?php if ($scan['screenshot_url']): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">🖼️ Preview</h2>
            <div class="bg-gray-50 rounded-lg overflow-hidden flex items-center justify-center" style="min-height: 400px;">
                <img src="<?= htmlspecialchars($scan['screenshot_url']) ?>" alt="Website Screenshot" 
                     class="max-w-full max-h-full" style="max-height: 600px;">
            </div>
            
            <div class="mt-4 flex gap-3">
                <a href="view-screenshot.php?id=<?= $scan['id'] ?>&download=1" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    ⬇️ Download Screenshot
                </a>
                <a href="<?= htmlspecialchars($scan['screenshot_url']) ?>" target="_blank" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
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
            <h2 class="text-xl font-bold text-gray-800 mb-4">📊 Detail Scan</h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-3">Informasi Scan</h3>
                    <table class="w-full text-sm">
                        <tr class="border-b">
                            <td class="py-2 text-gray-600">URL:</td>
                            <td class="py-2 font-mono break-all"><?= htmlspecialchars($scan['url']) ?></td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-gray-600">Status:</td>
                            <td class="py-2">
                                <?php
                                $statusBadge = match($scan['status']) {
                                    'safe' => '<span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">🟢 AMAN</span>',
                                    'suspicious' => '<span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">🟡 MENCURIGAKAN</span>',
                                    'malicious' => '<span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">🔴 BERBAHAYA</span>',
                                    default => '<span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">UNKNOWN</span>'
                                };
                                echo $statusBadge;
                                ?>
                            </td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-gray-600">Total Engine:</td>
                            <td class="py-2"><?= $scan['total_engines'] ?> engine</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-gray-600">Keamanan Score:</td>
                            <td class="py-2 font-bold text-lg"><?= $scan['safety_score'] ?>/100</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">Waktu Scan:</td>
                            <td class="py-2"><?= date('d M Y H:i:s', strtotime($scan['scanned_at'])) ?></td>
                        </tr>
                    </table>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-700 mb-3">Hasil Engine</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Malicious:</span>
                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded font-semibold"><?= $scan['malicious_count'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Suspicious:</span>
                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded font-semibold"><?= $scan['suspicious_count'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Harmless:</span>
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded font-semibold"><?= $scan['harmless_count'] ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Undetected:</span>
                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded font-semibold"><?= $scan['undetected_count'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-8">
            <a href="history.php" class="text-blue-600 hover:text-blue-800">← Kembali ke Riwayat</a>
        </div>
    </div>
</body>
</html>
