<?php
require_once 'includes/auth.php';

$error = '';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Rate limiting
$maxAttempts = 5;
$lockoutTime = 300;
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_block_until'] = 0;
}
if ($_SESSION['login_block_until'] > time()) {
    $remaining = ceil(($_SESSION['login_block_until'] - time()) / 60);
    $error = "Terlalu banyak percobaan login. Coba lagi dalam $remaining menit.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Permintaan tidak sah. Muat ulang halaman dan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Username/email dan password harus diisi';
        } else {
            $result = loginUser($username, $password);
            if ($result['success']) {
                $_SESSION['login_attempts'] = 0;
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= $maxAttempts) {
                    $lockoutMultiplier = 1 + floor(($_SESSION['login_block_count'] ?? 0) / 3);
                    $currentLockout = $lockoutTime * $lockoutMultiplier;
                    $_SESSION['login_block_until'] = time() + $currentLockout;
                    $_SESSION['login_block_count'] = ($_SESSION['login_block_count'] ?? 0) + 1;
                    $minutes = ceil($currentLockout / 60);
                    $error = "Terlalu banyak percobaan login. Coba lagi $minutes menit.";
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔗</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <style>.btn-hover{transition:all .15s ease}.btn-hover:hover{transform:scale(1.02)}.btn-hover:active{transform:scale(.98)}</style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-md">
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🔍</div>
            <h1 class="text-3xl font-bold text-gray-800"><?= APP_NAME ?></h1>
            <p class="text-gray-600">Cek keamanan link dengan VirusTotal</p>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Username atau Email</label>
                    <input type="text" name="username" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                
                <div class="relative">
                    <label class="block text-gray-700 font-medium mb-1">Password</label>
                    <input type="password" name="password" required id="loginPassword"
                           class="w-full pl-4 pr-12 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <button type="button" onclick="togglePassword('loginPassword', this)"
                            class="absolute right-3 top-9 text-gray-500 hover:text-gray-700">
                        <svg id="loginPasswordIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-2.5 px-4 rounded-lg btn-hover shadow-md">
                    🔑 Login
                </button>
            </form>
            
            <p class="text-center text-gray-600 mt-6">
                Belum punya akun? <a href="register.php" class="text-blue-600 hover:underline">Daftar di sini</a>
            </p>
        </div>
        
        <p class="text-center text-gray-500 text-sm mt-6">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
        </p>
            

        </div>
    </div>
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('svg');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
</body>
</html>