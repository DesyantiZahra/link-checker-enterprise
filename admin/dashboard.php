<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$pdo = getDB();

// Statistik semua user
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT s.id) as total_scans,
        SUM(CASE WHEN s.status = 'malicious' THEN 1 ELSE 0 END) as malicious_scans
    FROM users u
    LEFT JOIN scan_history s ON u.id = s.user_id
");
$stats = $stmt->fetch();

// 10 scan terbaru
$stmt = $pdo->query("
    SELECT s.*, u.username 
    FROM scan_history s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.scanned_at DESC 
    LIMIT 20
");
$recentScans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="text-xl font-semibold text-gray-700">🔍 <?= APP_NAME ?> (Admin)</div>
                <div class="flex space-x-4">
                    <a href="../index.php" class="text-gray-600 hover:text-gray-800">Dashboard</a>
                    <a href="../history.php" class="text-gray-600 hover:text-gray-800">Riwayat</a>
                    <a href="dashboard.php" class="text-blue-600 font-semibold">Admin</a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-600">Admin: <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">📊 Admin Dashboard</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <div class="text-3xl font-bold text-blue-600"><?= $stats['total_users'] ?></div>
                <div class="text-gray-600">Total User</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <div class="text-3xl font-bold text-green-600"><?= $stats['total_scans'] ?></div>
                <div class="text-gray-600">Total Scan</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <div class="text-3xl font-bold text-red-600"><?= $stats['malicious_scans'] ?></div>
                <div class="text-gray-600">Scan Berbahaya</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h2 class="font-semibold text-gray-800">Scan Terbaru (Semua User)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Waktu</th>
                            <th class="px-4 py-3 text-left">User</th>
                            <th class="px-4 py-3 text-left">URL</th>
                            <th class="px-4 py-3 text-center">Skor</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentScans as $scan): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 text-sm"><?= date('d/m/Y H:i', strtotime($scan['scanned_at'])) ?></td>
                                <td class="px-4 py-3 text-sm"><?= htmlspecialchars($scan['username']) ?></td>
                                <td class="px-4 py-3 text-sm truncate max-w-xs"><?= htmlspecialchars($scan['url']) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                        $score = (int)$scan['safety_score'];
                                        $malCount = (int)$scan['malicious_count'];
                                        if ($score > 90 && $malCount === 0) {
                                            $badgeClass = 'bg-green-500';
                                        } elseif ($score >= 50 && $score <= 70) {
                                            $badgeClass = 'bg-yellow-500';
                                        } elseif ($score < 40) {
                                            $badgeClass = 'bg-red-500';
                                        } else {
                                            $badgeClass = $malCount > 0 ? 'bg-yellow-500' : 'bg-green-500';
                                        }
                                    ?>
                                    <span class="px-2 py-1 rounded text-sm <?= $badgeClass ?> text-white">
                                        <?= $scan['safety_score'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    $badges = ['safe' => '🟢 Aman', 'suspicious' => '🟡 Mencurigakan', 'malicious' => '🔴 Berbahaya'];
                                    echo $badges[$scan['status']] ?? '-';
                                    ?>
                                </td>
                             </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>