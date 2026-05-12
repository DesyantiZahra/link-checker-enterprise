<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$user = requireAuth();

$pdo = getDB();
$userData = getCurrentUser();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        // Verifikasi password lama
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $stored = $stmt->fetch();
        
        if (!password_verify($current, $stored['password_hash'])) {
            $message = 'Password saat ini salah';
        } elseif (strlen($new) < 6) {
            $message = 'Password baru minimal 6 karakter';
        } elseif ($new !== $confirm) {
            $message = 'Konfirmasi password tidak cocok';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newHash, $user['id']]);
            $message = 'Password berhasil diubah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?= APP_NAME ?></title>
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
                    <a href="profile.php" class="text-blue-600 font-semibold">Profil</a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-600"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8 max-w-md">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">👤 Profil Saya</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500">Username</label>
                    <p class="font-medium"><?= htmlspecialchars($userData['username']) ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Email</label>
                    <p class="font-medium"><?= htmlspecialchars($userData['email']) ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Role</label>
                    <p class="font-medium"><?= $userData['role'] === 'admin' ? 'Administrator' : 'User' ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Bergabung sejak</label>
                    <p class="font-medium"><?= date('d F Y', strtotime($userData['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Ganti Password</h2>
            
            <?php if ($message): ?>
                <div class="mb-4 p-3 rounded <?= strpos($message, 'berhasil') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                        <input type="password" name="current_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                        <input type="password" name="new_password" required minlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" name="change_password"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>