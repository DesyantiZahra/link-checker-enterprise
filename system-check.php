<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireAdmin();

$errors = [];
$warnings = [];
$success = [];

// Check 1: Database connection
try {
    $pdo = getDB();
    $pdo->query("SELECT 1");
    $success[] = "✅ Database connected";
} catch (Exception $e) {
    $errors[] = "❌ Database error: " . $e->getMessage();
}

// Check 2: VirusTotal API Key
if (defined('VT_API_KEY') && VT_API_KEY && VT_API_KEY != 'MASUKKAN_API_KEY_ANDA_DISINI') {
    $success[] = "✅ VirusTotal API key configured";
} else {
    $warnings[] = "⚠️ VirusTotal API key not configured (required for scanning)";
}

// Check 3: URLScan API Key
if (defined('URLSCAN_API_KEY') && URLSCAN_API_KEY && URLSCAN_API_KEY != 'MASUKKAN_API_KEY_URLSCAN_ANDA_DISINI') {
    $success[] = "✅ URLScan API key configured";
    // Test API key dengan request ke URLScan
    $testCh = curl_init();
    curl_setopt($testCh, CURLOPT_URL, 'https://urlscan.io/api/v1/scan/');
    curl_setopt($testCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($testCh, CURLOPT_POST, true);
    curl_setopt($testCh, CURLOPT_POSTFIELDS, json_encode(['url' => 'https://example.com', 'visibility' => 'unlisted']));
    curl_setopt($testCh, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'API-Key: ' . URLSCAN_API_KEY]);
    curl_setopt($testCh, CURLOPT_TIMEOUT, 10);
    curl_setopt($testCh, CURLOPT_SSL_VERIFYPEER, true);
    $testResponse = curl_exec($testCh);
    $testHttpCode = curl_getinfo($testCh, CURLINFO_HTTP_CODE);
    curl_close($testCh);
    
    if ($testHttpCode == 200) {
        $success[] = "✅ URLScan API key valid (API merespon dengan 200)";
    } elseif ($testHttpCode == 401 || $testHttpCode == 403) {
        $warnings[] = "⚠️ URLScan API key tidak valid atau revoked (HTTP $testHttpCode). Screenshot tidak akan berfungsi.";
    } elseif ($testHttpCode == 429) {
        $warnings[] = "⚠️ URLScan API rate limit tercapai (HTTP 429). Screenshot mungkin gagal.";
    } else {
        $warnings[] = "⚠️ URLScan API merespon dengan HTTP $testHttpCode. Screenshot mungkin tidak berfungsi.";
    }
} else {
    $warnings[] = "⚠️ URLScan API key not configured (screenshots won't work)";
}

// Check 4: Database schema
try {
    $pdo = getDB();
    $stmt = $pdo->query("SHOW COLUMNS FROM scan_history");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $requiredCols = ['id', 'user_id', 'url', 'screenshot_url'];
    $missingCols = array_diff($requiredCols, $columns);
    
    if (empty($missingCols)) {
        $success[] = "✅ Database schema valid";
        
        // Check for engine_results column
        if (in_array('engine_results', $columns)) {
            $success[] = "✅ engine_results column exists";
        } else {
            $warnings[] = "⚠️ engine_results column missing (run install-migration.php)";
        }
    } else {
        $errors[] = "❌ Missing columns: " . implode(', ', $missingCols);
    }
} catch (Exception $e) {
    $errors[] = "❌ Schema check failed: " . $e->getMessage();
}

// Check 5: PHP extensions
if (extension_loaded('curl')) {
    $success[] = "✅ cURL extension loaded";
} else {
    $errors[] = "❌ cURL extension not loaded";
}

if (extension_loaded('json')) {
    $success[] = "✅ JSON extension loaded";
} else {
    $errors[] = "❌ JSON extension not loaded";
}

// Check 6: Write permissions
if (is_writable('.')) {
    $success[] = "✅ Write permissions OK";
} else {
    $warnings[] = "⚠️ Write permissions restricted";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status - <?= APP_NAME ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔗</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={darkMode:'class',theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <style>.btn-hover{transition:all .15s ease}.btn-hover:hover{transform:scale(1.02)}.btn-hover:active{transform:scale(.98)}.card-hover{transition:all .2s ease}.card-hover:hover{box-shadow:0 10px 25px -5px rgba(0,0,0,.1);transform:translateY(-2px)}</style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-gray-100">
    <div class="container mx-auto px-6 py-8 max-w-2xl">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">🔧 System Status Check</h1>
            <button onclick="toggleDark()" class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:text-gray-100 dark:hover:text-gray-100 text-lg" title="Toggle Dark Mode">
                <span id="darkIcon">🌙</span>
            </button>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-red-800 mb-3">🚨 ERRORS</h2>
            <ul class="space-y-2">
                <?php foreach ($errors as $error): ?>
                    <li class="text-red-700"><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($warnings)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-yellow-800 mb-3">⚠️ WARNINGS</h2>
            <ul class="space-y-2">
                <?php foreach ($warnings as $warning): ?>
                    <li class="text-yellow-700"><?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (in_array("⚠️ engine_results column missing (run install-migration.php)", $warnings)): ?>
            <div class="mt-4">
                <p class="text-sm text-yellow-700 mb-2">Jalankan update database:</p>
                <form method="POST" action="install-migration.php">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <button type="submit" class="inline-block bg-gradient-to-r from-yellow-600 to-yellow-700 hover:from-yellow-700 hover:to-yellow-800 text-white px-4 py-2 rounded-lg btn-hover shadow-sm cursor-pointer">
                        ▶️ Run Migration
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-6">
            <h2 class="text-xl font-bold text-green-800 mb-3">✅ OK</h2>
            <ul class="space-y-2">
                <?php foreach ($success as $s): ?>
                    <li class="text-green-700"><?= htmlspecialchars($s) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Fix Instructions -->
        <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 rounded-lg p-6">
            <h2 class="text-xl font-bold text-blue-800 mb-3">📋 Troubleshooting Steps</h2>
            <ol class="list-decimal list-inside space-y-2 text-blue-700">
                <li>Pastikan MySQL running</li>
                <li>Check API keys di <code class="bg-white px-2 py-1 rounded">includes/config.php</code></li>
                <li>Run <code class="bg-white px-2 py-1 rounded">install-migration.php</code> jika ada warning database</li>
                <li>Cek koneksi internet</li>
                <li>Lihat browser console untuk error detail (F12)</li>
            </ol>
        </div>

        <!-- Navigation -->
        <div class="mt-8 flex gap-3">
            <a href="index.php" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 rounded-lg btn-hover shadow-sm">
                ← Kembali ke Dashboard
            </a>
            <form method="POST" action="install-migration.php" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button type="submit" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2 rounded-lg btn-hover shadow-sm cursor-pointer">
                    Update Database
                </button>
            </form>
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

