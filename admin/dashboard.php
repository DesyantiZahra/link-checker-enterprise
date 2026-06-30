<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';
requireAdmin();

$pdo = getDB();

// Statistik semua user
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        COUNT(s.id) as total_scans,
        COALESCE(SUM(CASE WHEN s.status = 'malicious' THEN 1 ELSE 0 END), 0) as malicious_scans
    FROM users u
    LEFT JOIN scan_history s ON u.id = s.user_id
");
$stats = $stmt->fetch();

// Semua user beserta jumlah scan-nya
$userStmt = $pdo->query("
    SELECT u.id, u.username, u.email, u.role, u.created_at,
           COALESCE(COUNT(s.id), 0) as scan_count,
           COALESCE(SUM(CASE WHEN s.status = 'malicious' THEN 1 ELSE 0 END), 0) as malicious_count,
           COALESCE(SUM(CASE WHEN s.status = 'suspicious' THEN 1 ELSE 0 END), 0) as suspicious_count
    FROM users u
    LEFT JOIN scan_history s ON u.id = s.user_id
    GROUP BY u.id
    ORDER BY scan_count DESC, u.username ASC
");
$allUsers = $userStmt->fetchAll();

// Pagination untuk tabel scan
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Hitung total scan
$totalStmt = $pdo->query("SELECT COUNT(*) FROM scan_history");
$totalScans = $totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalScans / $perPage));

// Scan dengan pagination
$stmt = $pdo->prepare("
    SELECT s.*, u.username 
    FROM scan_history s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.scanned_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$recentScans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= APP_NAME ?></title>
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
                <a href="../index.php" class="text-xl font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">🔍 <?= APP_NAME ?> (Admin)</a>
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Dashboard</a>
                    <a href="../history.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Riwayat</a>
                    <a href="../guide.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Panduan</a>
                    <a href="dashboard.php" class="text-blue-600 font-semibold border-b-2 border-blue-600 pb-0.5">Admin</a>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <span class="text-gray-600 dark:text-gray-300 text-sm"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <button onclick="toggleDark()" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 text-lg transition-colors" title="Toggle Dark Mode">
                        <span id="darkIcon">🌙</span>
                    </button>
                    <form method="POST" action="../logout.php" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 cursor-pointer transition-colors font-medium">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">📊 Admin Dashboard</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-md p-6 text-center card-hover">
                <div class="text-4xl font-bold text-white"><?= $stats['total_users'] ?></div>
                <div class="text-indigo-100">Total User</div>
            </div>
            <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-md p-6 text-center card-hover">
                <div class="text-4xl font-bold text-white"><?= $stats['total_scans'] ?></div>
                <div class="text-cyan-100">Total Scan</div>
            </div>
            <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl shadow-md p-6 text-center card-hover">
                <div class="text-4xl font-bold text-white"><?= $stats['malicious_scans'] ?></div>
                <div class="text-rose-100">Scan Berbahaya</div>
            </div>
        </div>

        <!-- Tabel Semua User -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow card-hover mb-8">
            <div class="px-6 py-4 border-b dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-200">👥 Semua User</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left dark:text-gray-200">Username</th>
                            <th class="px-4 py-3 text-left dark:text-gray-200">Email</th>
                            <th class="px-4 py-3 text-center dark:text-gray-200">Role</th>
                            <th class="px-4 py-3 text-center dark:text-gray-200">Total Scan</th>
                            <th class="px-4 py-3 text-left dark:text-gray-200">Bergabung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): ?>
                            <tr class="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-sm dark:text-gray-300"><?= htmlspecialchars($u['username']) ?></td>
                                <td class="px-4 py-3 text-sm dark:text-gray-300"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-xs font-medium">Admin</span>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center text-sm font-medium"><?= (int)$u['scan_count'] ?></td>
                                <td class="px-4 py-3 text-sm dark:text-gray-300"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow card-hover">
            <div class="px-6 py-4 border-b dark:border-gray-700">
                <h2 class="font-semibold text-gray-800 dark:text-gray-200">📋 Scan Terbaru (Semua User)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left dark:text-gray-200">Waktu</th>
                            <th class="px-4 py-3 text-left dark:text-gray-200">User</th>
                            <th class="px-4 py-3 text-left dark:text-gray-200">URL</th>
                            <th class="px-4 py-3 text-center dark:text-gray-200">Skor</th>
                            <th class="px-4 py-3 text-center dark:text-gray-200">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentScans as $scan): ?>
                            <tr class="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-sm dark:text-gray-300"><?= date('d/m/Y H:i', strtotime($scan['scanned_at'])) ?></td>
                                <td class="px-4 py-3 text-sm dark:text-gray-300"><?= htmlspecialchars($scan['username']) ?></td>
                                <td class="px-4 py-3 text-sm truncate max-w-xs dark:text-gray-300"><?= htmlspecialchars($scan['url']) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                        $malCount = (int)$scan['malicious_count'];
                                        $suspCount = (int)$scan['suspicious_count'];
                                        $recalcScore = calculateSafetyScore($malCount, $suspCount);
                                        $adminStatus = getScanStatus($recalcScore, $malCount, $suspCount);
                                        $badgeMap = ['safe' => 'bg-green-500', 'suspicious' => 'bg-yellow-500', 'malicious' => 'bg-red-500'];
                                        $badgeClass = $badgeMap[$adminStatus] ?? 'bg-gray-500';
                                    ?>
                                    <span class="px-2 py-1 rounded text-sm <?= $badgeClass ?> text-white">
                                        <?= $recalcScore ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    $badges = ['safe' => '🟢 Aman', 'suspicious' => '🟡 Mencurigakan', 'malicious' => '🔴 Berbahaya'];
                                    echo $badges[$adminStatus] ?? '-';
                                    ?>
                                </td>
                             </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400 text-center border-t dark:border-gray-700">
                Halaman <?= $page ?> dari <?= $totalPages ?> (<?= $totalScans ?> scan)
            </div>
            <div class="px-6 py-3 flex justify-center gap-1 border-t dark:border-gray-700">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm text-gray-700 dark:text-gray-300 transition">‹ Sebelumnya</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium transition <?= $i === $page ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm text-gray-700 dark:text-gray-300 transition">Selanjutnya ›</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
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
