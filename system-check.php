<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

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
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-6 py-8 max-w-2xl">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">🔧 System Status Check</h1>

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
                <a href="install-migration.php" class="inline-block bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded transition">
                    ▶️ Run Migration
                </a>
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
            <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                ← Kembali ke Dashboard
            </a>
            <a href="install-migration.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded transition">
                Update Database
            </a>
        </div>
    </div>
</body>
</html>
