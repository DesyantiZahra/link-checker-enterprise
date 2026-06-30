<?php
require_once 'includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan - <?= APP_NAME ?></title>
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
                    <a href="guide.php" class="text-blue-600 font-semibold border-b-2 border-blue-600 pb-0.5">Panduan</a>
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
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">🎓 Panduan Penggunaan</h1>

        <!-- Quick Links -->
        <div class="grid md:grid-cols-2 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">📍 Navigasi Cepat</h2>
                <ul class="space-y-2">
                    <li><a href="index.php" class="text-blue-600 hover:underline">→ Scan URL Baru</a></li>
                    <li><a href="history.php" class="text-blue-600 hover:underline">→ Lihat Riwayat Scan</a></li>
                    <li><a href="profile.php" class="text-blue-600 hover:underline">→ Pengaturan Profil</a></li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">✨ Fitur Sekarang</h2>
                <ul class="space-y-2 text-sm">
                    <li>✅ Scan URL dengan 70+ engine antivirus + caching otomatis</li>
                    <li>✅ Skor keamanan piecewise (berdasarkan jumlah malicious/suspicious)</li>
                    <li>✅ Prioritas status: malicious > suspicious > safe</li>
                    <li>✅ Lihat screenshot website + download PNG</li>
                    <li>✅ Export riwayat ke CSV (dihitung ulang dengan rumus terbaru)</li>
                    <li>✅ Filter, cari, dan grafik statistik 7 hari</li>
                    <li>✅ Proteksi CSRF di semua form + rate limiting login</li>
                </ul>
            </div>
        </div>

        <!-- Tutorial Section -->
        <div class="space-y-6">
            <!-- Step 1 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">📝 Step 1: Scan URL</h2>
                <div class="flex gap-4">
                    <div class="text-4xl">1️⃣</div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-300 mb-3">Buka halaman <strong>Dashboard</strong> untuk memulai scan URL</p>
                        <ol class="list-decimal list-inside text-gray-700 dark:text-gray-200 space-y-1">
                            <li>Masukkan URL website yang ingin dicek</li>
                            <li>Klik tombol "Cek Keamanan"</li>
                            <li>Tunggu hasil scan (10-15 detik untuk URL baru)</li>
                        </ol>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">💡 URL yang pernah di-scan sebelumnya akan langsung ditampilkan dari <strong>cache</strong> — skor tetap dihitung ulang dengan rumus terbaru, dan riwayat scan baru tetap tercatat</p>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">📊 Step 2: Lihat Hasil Scan</h2>
                <div class="flex gap-4">
                    <div class="text-4xl">2️⃣</div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-300 mb-3">Hasil scan akan menampilkan:</p>
                        <ul class="space-y-2 text-gray-700 dark:text-gray-200">
                            <li><strong>🎯 Skor Keamanan</strong> - Nilai 0-100 dengan rumus piecewise:
                                <ul class="ml-5 mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <li>Jika ada engine <strong>malicious</strong>: skor = max(0, 65 - (mal-1)×20 - susp×5)</li>
                                    <li>Jika <strong>tidak ada</strong> malicious: skor = max(0, 100 - susp×10)</li>
                                </ul>
                            </li>
                            <li><strong>📊 Hasil Engine</strong> - Berapa engine mendeteksi malicious/suspicious/harmless</li>
                            <li><strong>📸 Screenshot</strong> - Preview visual dari website, bisa di-download PNG</li>
                            <li><strong>🔍 Detail Engine</strong> - Klik untuk melihat hasil dari setiap engine antivirus</li>
                        </ul>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">💡 <strong>Prioritas status:</strong> Jika ada 1+ engine malicious → status "Berbahaya", meskipun skor masih tinggi. Keamanan adalah prioritas utama.</p>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">📸 Step 3: Lihat Screenshot Website</h2>
                <div class="flex gap-4">
                    <div class="text-4xl">3️⃣</div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-300 mb-3">Setiap scan menyimpan screenshot dari website</p>
                        <ol class="list-decimal list-inside text-gray-700 dark:text-gray-200 space-y-1">
                            <li>Buka halaman <strong>Riwayat</strong></li>
                            <li>Cari scan yang memiliki icon 📸</li>
                            <li>Klik link "📸 Screenshot" untuk melihat detail</li>
                            <li>Download atau buka di tab baru jika diperlukan</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">📥 Step 4: Export Hasil ke CSV</h2>
                <div class="flex gap-4">
                    <div class="text-4xl">4️⃣</div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-300 mb-3">Export semua riwayat scan untuk analisis lebih lanjut</p>
                        <ol class="list-decimal list-inside text-gray-700 dark:text-gray-200 space-y-1">
                            <li>Buka halaman <strong>Riwayat</strong></li>
                            <li>Klik tombol "📥 Export CSV" di atas</li>
                            <li>File akan otomatis didownload</li>
                            <li>Buka di Excel untuk analisis</li>
                        </ol>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">💡 File CSV berisi: Waktu, URL, Skor, Status, Jumlah Engine, dan info screenshot</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- FAQ Section -->
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">❓ FAQ</h2>
            <div class="space-y-4">
                <details class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                    <summary class="cursor-pointer font-bold text-gray-800 dark:text-gray-100">Berapa lama proses scan?</summary>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">Scan biasanya memakan waktu 10-15 detik karena sistem mengirim URL ke multiple engine secara paralel dan mengambil screenshot website.</p>
                </details>

                <details class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                    <summary class="cursor-pointer font-bold text-gray-800 dark:text-gray-100">Apa itu skor keamanan?</summary>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">Skor keamanan adalah nilai 0-100 yang dihitung dengan rumus <strong>piecewise</strong> berdasarkan jumlah engine yang mendeteksi URL sebagai malicious/suspicious:</p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-gray-300 mt-2 space-y-1">
                        <li><strong>Ada malicious:</strong> skor = max(0, 65 - (mal-1)×20 - susp×5)<br>
                            Contoh: 1 mal → 65, 2 mal → 45, 3 mal → 25</li>
                        <li><strong>Tidak ada malicious:</strong> skor = max(0, 100 - susp×10)<br>
                            Contoh: 0 susp → 100, 5 susp → 50, 10 susp → 0</li>
                    </ul>
                </details>

                <details class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                    <summary class="cursor-pointer font-bold text-gray-800 dark:text-gray-100">Kenapa screenshot tidak tersedia?</summary>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">Beberapa website populer (Google, Facebook, dll) ditolak oleh URLScan.io untuk alasan keamanan. Screenshot tetap dapat diambil untuk website lain.</p>
                </details>

                <details class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                    <summary class="cursor-pointer font-bold text-gray-800 dark:text-gray-100">Apa itu sistem cache?</summary>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">Saat Anda scan URL yang sudah pernah diperiksa, sistem menggunakan data dari scan sebelumnya (cache) agar hasil langsung muncul tanpa menunggu proses ulang ke VirusTotal. Meskipun data dari cache, <strong>riwayat scan baru tetap dicatat</strong> dan skor dihitung ulang dengan rumus terbaru.</p>
                </details>

                <details class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 card-hover">
                    <summary class="cursor-pointer font-bold text-gray-800 dark:text-gray-100">Bagaimana privasi data saya?</summary>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">Semua data scan disimpan di database server kami dan hanya dapat diakses oleh akun Anda. Password dienkripsi dengan bcrypt. Setiap form dilindungi CSRF token dan login memiliki rate limiting untuk mencegah brute force.</p>
                </details>
            </div>
        </div>

        <!-- Tips -->
        <div class="mt-12 bg-blue-50 border-l-4 border-blue-500 rounded-lg p-6">
            <h2 class="text-2xl font-bold text-blue-900 mb-4">💡 Tips & Trik</h2>
            <ul class="space-y-2 text-blue-800">
                <li>✅ Selalu verifikasi URL sebelum membukanya di browser</li>
                <li>✅ Gunakan fitur export untuk membuat laporan berkala (skor & status otomatis diperbarui)</li>
                <li>✅ Filter riwayat berdasarkan status untuk fokus pada URL berbahaya</li>
                <li>✅ Lihat screenshot untuk mencegah phishing (perhatikan visual website)</li>
                <li>✅ Scan ulang URL yang sama untuk memanfaatkan cache — hasil instan tanpa menunggu</li>
            </ul>
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


