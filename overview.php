<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
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
                    <a href="overview.php" class="text-blue-600 font-semibold border-b-2 border-blue-600 pb-0.5">Overview</a>
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
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">🎯 Overview & Quick Start</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">Ringkasan fitur dan cara cepat mulai menggunakan aplikasi</p>

        <!-- Main Actions -->
        <div class="grid md:grid-cols-3 gap-4 mb-8">
            <a href="index.php" class="bg-gradient-to-br from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg shadow-md p-6 btn-hover">
                <div class="text-3xl mb-2">🔍</div>
                <h3 class="font-bold text-lg">Scan URL</h3>
                <p class="text-sm text-blue-100">Mulai scan website baru</p>
            </a>
            <a href="history.php" class="bg-gradient-to-br from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg shadow-md p-6 btn-hover">
                <div class="text-3xl mb-2">📋</div>
                <h3 class="font-bold text-lg">Riwayat</h3>
                <p class="text-sm text-green-100"><?= $stats['total'] ?> scan tersimpan</p>
            </a>
            <a href="guide.php" class="bg-gradient-to-br from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-lg shadow-md p-6 btn-hover">
                <div class="text-3xl mb-2">🎓</div>
                <h3 class="font-bold text-lg">Panduan</h3>
                <p class="text-sm text-purple-100">Belajar cara menggunakan</p>
            </a>
        </div>

        <!-- Features Grid -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">✨ Fitur Utama</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">🔍 Scan Multi-Engine</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">Scan URL dengan lebih dari 70 engine antivirus sekaligus</p>
                    <a href="index.php" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-semibold">Mulai Scan →</a>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-purple-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">📸 Screenshot Website</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">Lihat preview visual website dari setiap scan</p>
                    <a href="history.php" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-semibold">Lihat Riwayat →</a>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-green-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">📥 Export CSV</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">Download riwayat scan ke file CSV untuk analisis</p>
                    <a href="history.php" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-semibold">Export Sekarang →</a>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-orange-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">🔗 Fitur Enterprise</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">Lihat roadmap fitur-fitur yang akan datang</p>
                    <a href="features.php" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-semibold">Lihat Fitur →</a>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8 card-hover">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">⚙️ Bagaimana Cara Kerja?</h2>
            <div class="grid md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">1</div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100 mb-1">Masukkan URL</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Paste website yang ingin dicek</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">2</div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100 mb-1">Scan Multi-Engine</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Cek ke 70+ antivirus engine</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">3</div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100 mb-1">Ambil Screenshot</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Capture visual website</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">4</div>
                    <p class="font-semibold text-gray-800 dark:text-gray-100 mb-1">Simpan Riwayat</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Akses kapan saja</p>
                </div>
            </div>
        </div>

        <!-- Recent Scans -->
        <?php if (!empty($recent)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8 card-hover">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">📊 Scan Terbaru</h2>
            <div class="space-y-2">
                <?php foreach ($recent as $scan): ?>
                <div class="flex justify-between items-center p-3 border-b hover:bg-gray-50">
                    <div>
                        <p class="font-mono text-sm truncate"><?= htmlspecialchars($scan['url']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= date('d/m/Y H:i', strtotime($scan['scanned_at'])) ?></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php
                            $score = (int)$scan['safety_score'];
                            $malCount = (int)$scan['malicious_count'];
                            $suspCount = (int)$scan['suspicious_count'];
                            $ovStatus = getScanStatus($score, $malCount, $suspCount);
                            $ovBadgeMap = ['safe' => 'bg-green-100 text-green-700', 'suspicious' => 'bg-yellow-100 text-yellow-700', 'malicious' => 'bg-red-100 text-red-700'];
                            $badgeClass = $ovBadgeMap[$ovStatus] ?? 'bg-gray-100 text-gray-700 dark:text-gray-200';
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
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg shadow-md p-6 card-hover">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">🗺️ Peta Navigasi</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-3">Menu Utama</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="index.php" class="text-blue-600 hover:underline">📊 Dashboard</a> - Halaman utama untuk scan</li>
                        <li><a href="history.php" class="text-blue-600 hover:underline">📋 Riwayat</a> - Kelola riwayat scan</li>
                        <li><a href="features.php" class="text-blue-600 hover:underline">✨ Fitur</a> - Lihat roadmap fitur</li>
                        <li><a href="profile.php" class="text-blue-600 hover:underline">👤 Profil</a> - Pengaturan akun</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-3">Fitur Detail</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="guide.php" class="text-blue-600 hover:underline">🎓 Panduan Lengkap</a> - Tutorial step-by-step</li>
                        <li><a href="history.php" class="text-blue-600 hover:underline">📥 Export CSV</a> - Download riwayat</li>
                        <li><a href="view-screenshot.php" class="text-blue-600 hover:underline">📸 Screenshot</a> - Lihat visual website</li>
                        <li><strong>🚪 Logout</strong> (tombol di nav) - Keluar dari sistem</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="mt-8 grid md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center card-hover">
                <div class="text-4xl font-bold text-blue-600 dark:text-blue-400 mb-2"><?= $stats['total'] ?></div>
                <p class="text-gray-600 dark:text-gray-400">Total Scan</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center card-hover">
                <div class="text-4xl font-bold text-gray-400 dark:text-gray-500 mb-2">70+</div>
                <p class="text-gray-600 dark:text-gray-400">Engine Antivirus</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center card-hover">
                <div class="text-4xl font-bold text-green-600 dark:text-green-400 mb-2">∞</div>
                <p class="text-gray-600 dark:text-gray-400">Penyimpanan Riwayat</p>
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


