<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$user = requireAuth();

$pdo = getDB();

// Proses hapus per item
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM scan_history WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user['id']]);
    header('Location: history.php?deleted=1');
    exit;
}

// Proses hapus semua
if (isset($_GET['delete_all']) && $_GET['delete_all'] == '1') {
    $stmt = $pdo->prepare("DELETE FROM scan_history WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    header('Location: history.php?deleted_all=1');
    exit;
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM scan_history WHERE user_id = ?";
$params = [$user['id']];

if ($filter === 'safe') {
    $sql .= " AND status = 'safe'";
} elseif ($filter === 'suspicious') {
    $sql .= " AND status = 'suspicious'";
} elseif ($filter === 'malicious') {
    $sql .= " AND status = 'malicious'";
}

if (!empty($search)) {
    $sql .= " AND (url LIKE ? OR final_url LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY scanned_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$histories = $stmt->fetchAll();

// Hitung total dengan filter yang sama (untuk display "X dari Y")
$totalSql = "SELECT COUNT(*) as total FROM scan_history WHERE user_id = ?";
$totalParams = [$user['id']];
if ($filter === 'safe') {
    $totalSql .= " AND status = 'safe'";
} elseif ($filter === 'suspicious') {
    $totalSql .= " AND status = 'suspicious'";
} elseif ($filter === 'malicious') {
    $totalSql .= " AND status = 'malicious'";
}
if (!empty($search)) {
    $totalSql .= " AND (url LIKE ? OR final_url LIKE ?)";
    $totalParams[] = "%$search%";
    $totalParams[] = "%$search%";
}
$totalStmt = $pdo->prepare($totalSql);
$totalStmt->execute($totalParams);
$totalCount = $totalStmt->fetch()['total'];

// Hitung jumlah per status untuk ditampilkan di tombol filter
$cntStmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM scan_history WHERE user_id = ? GROUP BY status");
$cntStmt->execute([$user['id']]);
$cntAll = $safeCnt = $suspCnt = $malCnt = 0;
foreach ($cntStmt->fetchAll() as $row) {
    if ($row['status'] === 'safe')       $safeCnt  = (int)$row['cnt'];
    elseif ($row['status'] === 'suspicious') $suspCnt = (int)$row['cnt'];
    elseif ($row['status'] === 'malicious')  $malCnt  = (int)$row['cnt'];
    $cntAll += (int)$row['cnt'];
}

$message = '';
if (isset($_GET['deleted'])) {
    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">✅ 1 riwayat berhasil dihapus</div>';
} elseif (isset($_GET['deleted_all'])) {
    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">✅ Semua riwayat berhasil dihapus</div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Scan - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function confirmDelete(id) {
            if (confirm('Yakin ingin menghapus riwayat scan ini?')) {
                window.location.href = 'history.php?delete=' + id;
            }
        }
        
        function confirmDeleteAll() {
            if (confirm('⚠️ PERINGATAN: Semua riwayat scan akan dihapus permanen! Yakin?')) {
                window.location.href = 'history.php?delete_all=1';
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="text-xl font-semibold text-gray-700">🔍 <?= APP_NAME ?></div>
                <div class="flex space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-gray-800">Dashboard</a>
                    <a href="history.php" class="text-blue-600 font-semibold">Riwayat</a>
                    <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="text-gray-600 hover:text-gray-800">Admin Panel</a>
                    <?php endif; ?>
                    <a href="profile.php" class="text-gray-600 hover:text-gray-800">Profil</a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-600"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8 max-w-6xl">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-gray-800">📋 Riwayat Scan</h1>
            <div class="flex gap-2">
                <?php if ($totalCount > 0): ?>
                    <a href="api/export-csv.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition">
                        📥 Export CSV
                    </a>
                <?php endif; ?>
                <?php if ($totalCount > 0): ?>
                    <button onclick="confirmDeleteAll()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition">
                        🗑️ Hapus Semua
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?= $message ?>

        <!-- Filter dan Search -->
        <div class="flex flex-wrap gap-2 mb-4">
            <a href="?filter=all" class="px-3 py-1 rounded text-sm <?= $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                Semua <?= $filter === 'all' ? "($cntAll)" : '' ?>
            </a>
            <a href="?filter=safe" class="px-3 py-1 rounded text-sm <?= $filter === 'safe' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                🟢 Aman <?= $filter === 'safe' ? "($safeCnt)" : '' ?>
            </a>
            <a href="?filter=suspicious" class="px-3 py-1 rounded text-sm <?= $filter === 'suspicious' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                🟡 Mencurigakan <?= $filter === 'suspicious' ? "($suspCnt)" : '' ?>
            </a>
            <a href="?filter=malicious" class="px-3 py-1 rounded text-sm <?= $filter === 'malicious' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                🔴 Berbahaya <?= $filter === 'malicious' ? "($malCnt)" : '' ?>
            </a>
        </div>

        <form method="GET" class="mb-6">
            <div class="flex gap-2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Cari URL..." 
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <input type="hidden" name="filter" value="<?= $filter ?>">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">🔍 Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="history.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">Reset (Semua <?= $cntAll ?>)</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($histories)): ?>
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                <?php if (!empty($search)): ?>
                    Tidak ada riwayat yang cocok dengan pencarian "<?= htmlspecialchars($search) ?>".
                    <a href="history.php" class="text-blue-600">Lihat semua riwayat</a>
                <?php else: ?>
                    Belum ada riwayat scan. <a href="index.php" class="text-blue-600">Scan link sekarang</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm whitespace-nowrap">Waktu</th>
                                <th class="px-4 py-3 text-left text-sm">URL</th>
                                <th class="px-4 py-3 text-center text-sm">Skor</th>
                                <th class="px-4 py-3 text-center text-sm">Status</th>
                                <th class="px-4 py-3 text-center text-sm">Engine</th>
                                <th class="px-4 py-3 text-center text-sm whitespace-nowrap" style="min-width:260px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($histories as $h): ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($h['scanned_at'])) ?></td>
                                    <td class="px-4 py-3 text-sm truncate max-w-xs"><?= htmlspecialchars($h['url']) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <?php
                                            $score = (int)$h['safety_score'];
                                            $malCount = (int)$h['malicious_count'];
                                            if ($score > 90 && $malCount === 0) {
                                                $scoreClass = 'bg-green-500 text-white';
                                                $statusText = '🟢 Aman';
                                            } elseif ($score >= 50 && $score <= 70) {
                                                $scoreClass = 'bg-yellow-500 text-white';
                                                $statusText = '🟡 Mencurigakan';
                                            } elseif ($score < 40) {
                                                $scoreClass = 'bg-red-500 text-white';
                                                $statusText = '🔴 Berbahaya';
                                            } else {
                                                $scoreClass = $malCount > 0 ? 'bg-yellow-500 text-white' : 'bg-green-500 text-white';
                                                $statusText = $malCount > 0 ? '🟡 Mencurigakan' : '🟢 Aman';
                                            }
                                        ?>
                                        <span class="px-2 py-1 rounded text-xs <?= $scoreClass ?>">
                                            <?= $h['safety_score'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm"><?= $statusText ?></td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        <?php
                                        // Parse engine results jika ada
                                        if (!empty($h['engine_results'])) {
                                            $engines = json_decode($h['engine_results'], true);
                                            if (is_array($engines) && count($engines) > 0) {
                                                $detected = count(array_filter($engines, function($e) { return !empty($e['detected']); }));
                                                echo "<span class='bg-gray-100 px-2 py-1 rounded'>" . $detected . "/" . count($engines) . " 🔍</span>";
                                            } else {
                                                echo "<span class='text-gray-400'>-</span>";
                                            }
                                        } else {
                                            echo "<span class='text-gray-400'>-</span>";
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap text-sm">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="detail.php?id=<?= $h['id'] ?>" 
                                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 text-xs font-medium transition"
                                               title="Lihat Detail">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                Detail
                                            </a>
                                            <?php if ($h['screenshot_url']): ?>
                                            <a href="view-screenshot.php?id=<?= $h['id'] ?>" 
                                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-purple-50 text-purple-700 hover:bg-purple-100 hover:text-purple-800 text-xs font-medium transition"
                                               title="Lihat Screenshot">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                                Screenshot
                                            </a>
                                            <?php endif; ?>
                                            <button onclick="confirmDelete(<?= $h['id'] ?>)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 text-xs font-medium transition"
                                                    title="Hapus Ripwayat">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-4 text-sm text-gray-500 text-center">
                Menampilkan <?= count($histories) ?> dari <?= $totalCount ?> riwayat
            </div>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg inline-block transition">
                🔍 Scan Link Baru
            </a>
        </div>
    </div>
</body>
</html>
