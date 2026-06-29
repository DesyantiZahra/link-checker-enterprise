<?php
require_once 'includes/auth.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Permintaan tidak sah. Muat ulang halaman dan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validasi
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Semua field harus diisi';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email tidak valid';
        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password harus mengandung minimal 1 huruf besar';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = 'Password harus mengandung minimal 1 huruf kecil';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password harus mengandung minimal 1 angka';
        } elseif ($password !== $confirm_password) {
            $error = 'Password dan konfirmasi password tidak sama';
        } else {
            $result = registerUser($username, $email, $password);
            if ($result['success']) {
                $success = 'Pendaftaran berhasil! Silakan login.';
            } else {
                $error = $result['message'];
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
    <title>Register - <?= APP_NAME ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔗</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={darkMode:'class',theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <style>.btn-hover{transition:all .15s ease}.btn-hover:hover{transform:scale(1.02)}.btn-hover:active{transform:scale(.98)}</style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-800 dark:to-gray-900 min-h-screen dark:text-gray-100">
    <div class="container mx-auto px-4 py-12 max-w-md">
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🔍</div>
            <div class="flex items-center justify-center gap-2">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Daftar Akun</h1>
                <button onclick="toggleDark()" class="text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100 text-lg" title="Toggle Dark Mode">
                    <span id="darkIcon">🌙</span>
                </button>
            </div>
            <p class="text-gray-600 dark:text-gray-400">Buat akun untuk menyimpan riwayat scan</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                    <?= htmlspecialchars($success) ?>
                    <a href="login.php" class="underline font-semibold">Login di sini</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Username</label>
                    <input type="text" name="username" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none dark:bg-gray-700 dark:text-gray-100">
                </div>
                
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Email</label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none dark:bg-gray-700 dark:text-gray-100">
                </div>
                
                <div class="relative">
                    <label class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Password</label>
                    <input type="password" name="password" required minlength="8" id="regPassword"
                           class="w-full pl-4 pr-12 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none dark:bg-gray-700 dark:text-gray-100">
                    <button type="button" onclick="togglePassword('regPassword', this)"
                            class="absolute right-3 top-9 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        <svg id="regPasswordIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimal 8 karakter, harus ada huruf besar, huruf kecil, dan angka</p>
                </div>
                
                <div class="relative">
                    <label class="block text-gray-700 dark:text-gray-300 font-medium mb-1">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" required id="regConfirmPassword"
                           class="w-full pl-4 pr-12 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none dark:bg-gray-700 dark:text-gray-100">
                    <button type="button" onclick="togglePassword('regConfirmPassword', this)"
                            class="absolute right-3 top-9 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        <svg id="regConfirmPasswordIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-2.5 px-4 rounded-lg btn-hover shadow-md">
                    ✨ Daftar
                </button>
            </form>
            
            <p class="text-center text-gray-600 dark:text-gray-400 mt-6">
                Sudah punya akun? <a href="login.php" class="text-blue-600 dark:text-blue-400 hover:underline">Login di sini</a>
            </p>
        </div>
    </div>
    <script>
        function togglePassword(inputId, btn) {
            var input = document.getElementById(inputId);
            var icon = btn.querySelector('svg');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
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