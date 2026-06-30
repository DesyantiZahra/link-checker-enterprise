<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
$user = requireAuth();

$pdo = getDB();

// Proses hapus per item (via POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && ctype_digit((string)$_POST['delete'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die('Permintaan tidak sah');
    }
    $id = $_POST['delete'];
    $stmt = $pdo->prepare("DELETE FROM scan_history WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user['id']]);
    header('Location: history.php?deleted=1');
    exit;
}

// Proses hapus semua (via POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all']) && $_POST['delete_all'] === '1') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die('Permintaan tidak sah');
    }
    $stmt = $pdo->prepare("DELETE FROM scan_history WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    header('Location: history.php?deleted_all=1');
    exit;
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'safe', 'suspicious', 'malicious'];
if (!in_array($filter, $allowedFilters)) {
    $filter = 'all';
}
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM scan_history WHERE user_id = ?";
$params = [$user['id']];

if ($filter === 'safe') {
    $sql .= " AND malicious_count = 0 AND suspicious_count = 0";
} elseif ($filter === 'suspicious') {
    $sql .= " AND malicious_count = 0 AND suspicious_count > 0";
} elseif ($filter === 'malicious') {
    $sql .= " AND malicious_count > 0";
}

if (!empty($search)) {
    $escapedSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
    $sql .= " AND (url LIKE ? OR final_url LIKE ?)";
    $params[] = "%$escapedSearch%";
    $params[] = "%$escapedSearch%";
}

$sql .= " ORDER BY scanned_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
foreach ($params as $i => $val) {
    $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$histories = $stmt->fetchAll();

// Hitung total dengan filter yang sama (untuk display "X dari Y")
$totalSql = "SELECT COUNT(*) as total FROM scan_history WHERE user_id = ?";
$totalParams = [$user['id']];
if ($filter === 'safe') {
    $totalSql .= " AND malicious_count = 0 AND suspicious_count = 0";
} elseif ($filter === 'suspicious') {
    $totalSql .= " AND malicious_count = 0 AND suspicious_count > 0";
} elseif ($filter === 'malicious') {
    $totalSql .= " AND malicious_count > 0";
}
if (!empty($search)) {
    $escapedSearchTotal = str_replace(['%', '_'], ['\\%', '\\_'], $search);
    $totalSql .= " AND (url LIKE ? OR final_url LIKE ?)";
    $totalParams[] = "%$escapedSearchTotal%";
    $totalParams[] = "%$escapedSearchTotal%";
}
$totalStmt = $pdo->prepare($totalSql);
$totalStmt->execute($totalParams);
$totalCount = $totalStmt->fetch()['total'];
$totalPages = max(1, ceil($totalCount / $perPage));

// Hitung jumlah per status untuk ditampilkan di tombol filter
$cntStmt = $pdo->prepare("
    SELECT
        CASE
            WHEN malicious_count > 0 THEN 'malicious'
            WHEN suspicious_count > 0 THEN 'suspicious'
            ELSE 'safe'
        END as computed_status,
        COUNT(*) as cnt
    FROM scan_history
    WHERE user_id = ?
    GROUP BY computed_status
");
$cntStmt->execute([$user['id']]);
$cntAll = $safeCnt = $suspCnt = $malCnt = 0;
foreach ($cntStmt->fetchAll() as $row) {
    if ($row['computed_status'] === 'safe')       $safeCnt  = (int)$row['cnt'];
    elseif ($row['computed_status'] === 'suspicious') $suspCnt = (int)$row['cnt'];
    elseif ($row['computed_status'] === 'malicious')  $malCnt  = (int)$row['cnt'];
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
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔗</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={darkMode:'class',theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <style>.btn-hover{transition:all .15s ease}.btn-hover:hover{transform:scale(1.02)}.btn-hover:active{transform:scale(.98)}.card-hover{transition:all .2s ease}.card-hover:hover{box-shadow:0 10px 25px -5px rgba(0,0,0,.1);transform:translateY(-2px)}@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}.fade-in{animation:fadeIn .3s ease forwards}</style>
    <script>
        function getCsrfToken() {
            return '<?= generateCsrfToken() ?>';
        }

        function postAction(params) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'history.php';
            for (const [key, value] of Object.entries(params)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            document.body.appendChild(form);
            form.submit();
        }

        let deleteId = null;
        let deleteAllMode = false;

        function showDeleteModal(id) {
            deleteId = id;
            deleteAllMode = false;
            document.getElementById('modalMessage').textContent = 'Yakin ingin menghapus riwayat scan ini? Data tidak bisa dikembalikan.';
            document.getElementById('modalConfirmBtn').textContent = '🗑️ Hapus';
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function showDeleteAllModal() {
            deleteAllMode = true;
            document.getElementById('modalMessage').textContent = '⚠️ PERINGATAN: Semua riwayat scan akan dihapus permanen! Yakin?';
            document.getElementById('modalConfirmBtn').textContent = '🗑️ Hapus Semua';
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            deleteId = null;
            deleteAllMode = false;
        }

        function confirmDeleteAction() {
            if (deleteAllMode) {
                postAction({ delete_all: 1, csrf_token: getCsrfToken() });
            } else if (deleteId) {
                postAction({ delete: deleteId, csrf_token: getCsrfToken() });
            }
            closeModal();
        }

        function exportCSV() {
            var btn = document.getElementById('exportBtn');
            btn.textContent = '⏳ Mengexport...';
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.7';
            setTimeout(function() {
                btn.textContent = '📥 Export CSV';
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';
            }, 5000);
        }

        // Tutup modal jika klik di luar
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('deleteModal');
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <nav class="bg-white dark:bg-gray-800 shadow-md border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-xl font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">🔍 <?= APP_NAME ?></a>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Dashboard</a>
                    <a href="history.php" class="text-blue-600 font-semibold border-b-2 border-blue-600 pb-0.5">Riwayat</a>
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
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">📋 Riwayat Scan</h1>
            <div class="flex gap-2">
                <?php if ($totalCount > 0): ?>
                    <a href="api/export-csv.php" id="exportBtn" onclick="exportCSV()" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2 rounded-lg text-sm btn-hover shadow-sm">
                        📥 Export CSV
                    </a>
                <?php endif; ?>
                <?php if ($totalCount > 0): ?>
                    <button onclick="showDeleteAllModal()" class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-4 py-2 rounded-lg text-sm btn-hover shadow-sm">
                        🗑️ Hapus Semua
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?= $message ?>

        <!-- Filter dan Search -->
        <div class="flex flex-wrap gap-2 mb-4">
            <a href="?<?= http_build_query(array_merge($_GET, ['filter' => 'all', 'page' => 1])) ?>" class="px-4 py-1.5 rounded-full text-sm font-medium transition-all <?= $filter === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' ?>">
                Semua <?= $filter === 'all' ? "($cntAll)" : '' ?>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['filter' => 'safe', 'page' => 1])) ?>" class="px-4 py-1.5 rounded-full text-sm font-medium transition-all <?= $filter === 'safe' ? 'bg-green-600 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' ?>">
                🟢 Aman <?= $filter === 'safe' ? "($safeCnt)" : '' ?>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['filter' => 'suspicious', 'page' => 1])) ?>" class="px-4 py-1.5 rounded-full text-sm font-medium transition-all <?= $filter === 'suspicious' ? 'bg-yellow-600 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' ?>">
                🟡 Mencurigakan <?= $filter === 'suspicious' ? "($suspCnt)" : '' ?>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['filter' => 'malicious', 'page' => 1])) ?>" class="px-4 py-1.5 rounded-full text-sm font-medium transition-all <?= $filter === 'malicious' ? 'bg-red-600 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' ?>">
                🔴 Berbahaya <?= $filter === 'malicious' ? "($malCnt)" : '' ?>
            </a>
        </div>

        <form method="GET" class="mb-6">
            <div class="flex flex-wrap gap-2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Cari URL..." 
                       class="flex-1 min-w-[200px] px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none dark:bg-gray-700 dark:text-gray-100 transition-shadow">
                <input type="hidden" name="filter" value="<?= $filter ?>">
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 rounded-lg text-sm btn-hover shadow-sm">🔍 Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="history.php" class="bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 text-sm transition">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($histories)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center text-gray-500 dark:text-gray-400 card-hover">
                <?php if (!empty($search)): ?>
                    Tidak ada riwayat yang cocok dengan pencarian "<?= htmlspecialchars($search) ?>".
                    <a href="history.php" class="text-blue-600 dark:text-blue-400">Lihat semua riwayat</a>
                <?php else: ?>
                    Belum ada riwayat scan. <a href="index.php" class="text-blue-600 dark:text-blue-400">Scan link sekarang</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden card-hover">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm whitespace-nowrap dark:text-gray-200">Waktu</th>
                                <th class="px-4 py-3 text-left text-sm dark:text-gray-200">URL</th>
                                <th class="px-4 py-3 text-center text-sm dark:text-gray-200">Skor</th>
                                <th class="px-4 py-3 text-center text-sm dark:text-gray-200">Status</th>
                                <th class="px-4 py-3 text-center text-sm dark:text-gray-200">Engine</th>
                                <th class="px-4 py-3 text-center text-sm whitespace-nowrap dark:text-gray-200" style="min-width:260px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($histories as $h): ?>
                                <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-3 text-sm whitespace-nowrap dark:text-gray-300"><?= date('d/m/Y H:i', strtotime($h['scanned_at'])) ?></td>
                                    <td class="px-4 py-3 text-sm truncate max-w-xs dark:text-gray-300"><?= htmlspecialchars($h['url']) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <?php
                                            $malCount = (int)$h['malicious_count'];
                                            $suspCount = (int)$h['suspicious_count'];
                                            $recalcScore = calculateSafetyScore($malCount, $suspCount);
                                            $status = getScanStatus($recalcScore, $malCount, $suspCount);
                                            $scoreClass = getStatusBadgeClass($status);
                                            $statusText = getStatusLabel($status);
                                        ?>
                                        <span class="px-2 py-1 rounded text-xs <?= $scoreClass ?>">
                                            <?= $recalcScore ?>
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
                                            <button onclick="showDeleteModal(<?= $h['id'] ?>)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 text-xs font-medium transition"
                                                    title="Hapus Riwayat">
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
            
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                Halaman <?= $page ?> dari <?= $totalPages ?> (<?= $totalCount ?> riwayat)
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="mt-4 flex justify-center gap-1">
                <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                   class="px-3 py-1.5 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm text-gray-700 dark:text-gray-300 transition">‹ Sebelumnya</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition <?= $i === $page ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                   class="px-3 py-1.5 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm text-gray-700 dark:text-gray-300 transition">Selanjutnya ›</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <a href="index.php" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-2.5 rounded-lg inline-block btn-hover shadow-md">
                🔍 Scan Link Baru
            </a>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 hidden backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 mx-4 max-w-md w-full transform transition-all scale-95 fade-in">
            <div class="text-center">
                <div class="text-5xl mb-4">🗑️</div>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">Konfirmasi Hapus</h3>
                <p id="modalMessage" class="text-gray-600 dark:text-gray-300 mb-6">Yakin ingin menghapus?</p>
                <div class="flex gap-3 justify-center">
                    <button onclick="closeModal()" 
                            class="px-6 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition font-medium">
                        Batal
                    </button>
                    <button id="modalConfirmBtn" onclick="confirmDeleteAction()" 
                            class="px-6 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-lg transition font-medium btn-hover shadow-sm">
                        🗑️ Hapus
                    </button>
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

