<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$user = requireAuth();

$pdo = getDB();

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM scan_history WHERE user_id = ?");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

// Get recent scans
$stmt = $pdo->prepare("SELECT * FROM scan_history WHERE user_id = ? ORDER BY scanned_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$recent = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overview - <?= APP_NAME ?></title>
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
                    <a href="features.php" class="text-gray-600 hover:text-gray-800">Fitur</a>
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
        <h1 class="text-3xl font-bold text-gray-800 mb-2">🎯 Overview & Quick Start</h1>
        <p class="text-gray-600 mb-8">Ringkasan fitur dan cara cepat mulai menggunakan aplikasi</p>

        <!-- Main Actions -->
        <div class="grid md:grid-cols-3 gap-4 mb-8">
            <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-md p-6 transition">
                <div class="text-3xl mb-2">🔍</div>
                <h3 class="font-bold text-lg">Scan URL</h3>
                <p class="text-sm text-blue-100">Mulai scan website baru</p>
            </a>
            <a href="history.php" class="bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-md p-6 transition">
                <div class="text-3xl mb-2">📋</div>
                <h3 class="font-bold text-lg">Riwayat</h3>
                <p class="text-sm text-green-100"><?= $stats['total'] ?> scan tersimpan</p>
            </a>
            <a href="guide.php" class="bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow-md p-6 transition">
                <div class="text-3xl mb-2">🎓</div>
                <h3 class="font-bold text-lg">Panduan</h3>
                <p class="text-sm text-purple-100">Belajar cara menggunakan</p>
            </a>
        </div>

        <!-- Features Grid -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">✨ Fitur Utama</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">🔍 Scan Multi-Engine</h3>
                    <p class="text-gray-600 text-sm mb-3">Scan URL dengan lebih dari 70 engine antivirus sekaligus</p>
                    <a href="index.php" class="text-blue-600 hover:underline text-sm font-semibold">Mulai Scan →</a>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">📸 Screenshot Website</h3>
                    <p class="text-gray-600 text-sm mb-3">Lihat preview visual website dari setiap scan</p>
                    <a href="history.php" class="text-blue-600 hover:underline text-sm font-semibold">Lihat Riwayat →</a>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">📥 Export CSV</h3>
                    <p class="text-gray-600 text-sm mb-3">Download riwayat scan ke file CSV untuk analisis</p>
                    <a href="history.php" class="text-blue-600 hover:underline text-sm font-semibold">Export Sekarang →</a>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">🔗 Fitur Enterprise</h3>
                    <p class="text-gray-600 text-sm mb-3">Lihat roadmap fitur-fitur yang akan datang</p>
                    <a href="features.php" class="text-blue-600 hover:underline text-sm font-semibold">Lihat Fitur →</a>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">⚙️ Bagaimana Cara Kerja?</h2>
            <div class="grid md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">1</div>
                    <p class="font-semibold text-gray-800 mb-1">Masukkan URL</p>
                    <p class="text-sm text-gray-600">Paste website yang ingin dicek</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">2</div>
                    <p class="font-semibold text-gray-800 mb-1">Scan Multi-Engine</p>
                    <p class="text-sm text-gray-600">Cek ke 70+ antivirus engine</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">3</div>
                    <p class="font-semibold text-gray-800 mb-1">Ambil Screenshot</p>
                    <p class="text-sm text-gray-600">Capture visual website</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">4</div>
                    <p class="font-semibold text-gray-800 mb-1">Simpan Riwayat</p>
                    <p class="text-sm text-gray-600">Akses kapan saja</p>
                </div>
            </div>
        </div>

        <!-- Recent Scans -->
        <?php if (!empty($recent)): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">📊 Scan Terbaru</h2>
            <div class="space-y-2">
                <?php foreach ($recent as $scan): ?>
                <div class="flex justify-between items-center p-3 border-b hover:bg-gray-50">
                    <div>
                        <p class="font-mono text-sm truncate"><?= htmlspecialchars($scan['url']) ?></p>
                        <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($scan['scanned_at'])) ?></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php
                            $score = (int)$scan['safety_score'];
                            $malCount = (int)$scan['malicious_count'];
                            if ($score > 90 && $malCount === 0) {
                                $badgeClass = 'bg-green-100 text-green-700';
                            } elseif ($score >= 50 && $score <= 70) {
                                $badgeClass = 'bg-yellow-100 text-yellow-700';
                            } elseif ($score < 40) {
                                $badgeClass = 'bg-red-100 text-red-700';
                            } else {
                                $badgeClass = $malCount > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700';
                            }
                        ?>
                        <span class="px-2 py-1 rounded text-xs font-bold <?= $badgeClass ?>">
                            <?= $scan['safety_score'] ?>/100
                        </span>
                        <a href="detail.php?id=<?= $scan['id'] ?>" class="text-blue-600 hover:underline text-sm">Detail</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="history.php" class="text-blue-600 hover:underline text-sm font-semibold mt-4 inline-block">Lihat Semua Riwayat →</a>
        </div>
        <?php endif; ?>

        <!-- Navigation Guide -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">🗺️ Peta Navigasi</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-bold text-gray-800 mb-3">Menu Utama</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="index.php" class="text-blue-600 hover:underline">📊 Dashboard</a> - Halaman utama untuk scan</li>
                        <li><a href="history.php" class="text-blue-600 hover:underline">📋 Riwayat</a> - Kelola riwayat scan</li>
                        <li><a href="features.php" class="text-blue-600 hover:underline">✨ Fitur</a> - Lihat roadmap fitur</li>
                        <li><a href="profile.php" class="text-blue-600 hover:underline">👤 Profil</a> - Pengaturan akun</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 mb-3">Fitur Detail</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="guide.php" class="text-blue-600 hover:underline">🎓 Panduan Lengkap</a> - Tutorial step-by-step</li>
                        <li><a href="history.php" class="text-blue-600 hover:underline">📥 Export CSV</a> - Download riwayat</li>
                        <li><a href="view-screenshot.php" class="text-blue-600 hover:underline">📸 Screenshot</a> - Lihat visual website</li>
                        <li><a href="logout.php" class="text-blue-600 hover:underline">🚪 Logout</a> - Keluar dari sistem</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="mt-8 grid md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-4xl font-bold text-blue-600 mb-2"><?= $stats['total'] ?></div>
                <p class="text-gray-600">Total Scan</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-4xl font-bold text-gray-400 mb-2">70+</div>
                <p class="text-gray-600">Engine Antivirus</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-4xl font-bold text-green-600 mb-2">∞</div>
                <p class="text-gray-600">Penyimpanan Riwayat</p>
            </div>
        </div>
    </div>
</body>
</html>
