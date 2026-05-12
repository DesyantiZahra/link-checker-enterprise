<?php
require_once 'includes/auth.php';
$user = requireAuth();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan - <?= APP_NAME ?></title>
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
        <h1 class="text-3xl font-bold text-gray-800 mb-8">🎓 Panduan Penggunaan</h1>

        <!-- Quick Links -->
        <div class="grid md:grid-cols-2 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">📍 Navigasi Cepat</h2>
                <ul class="space-y-2">
                    <li><a href="index.php" class="text-blue-600 hover:underline">→ Scan URL Baru</a></li>
                    <li><a href="history.php" class="text-blue-600 hover:underline">→ Lihat Riwayat Scan</a></li>
                    <li><a href="features.php" class="text-blue-600 hover:underline">→ Lihat Fitur Enterprise</a></li>
                    <li><a href="profile.php" class="text-blue-600 hover:underline">→ Pengaturan Profil</a></li>
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">✨ Fitur Sekarang</h2>
                <ul class="space-y-2 text-sm">
                    <li>✅ Scan URL dengan 70+ engine antivirus</li>
                    <li>✅ Lihat screenshot website</li>
                    <li>✅ Export riwayat ke CSV</li>
                    <li>✅ Filter dan cari riwayat</li>
                </ul>
            </div>
        </div>

        <!-- Tutorial Section -->
        <div class="space-y-6">
            <!-- Step 1 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">📝 Step 1: Scan URL</h2>
                <div class="flex gap-4">
                    <div class="text-4xl">1️⃣</div>
                    <div>
                        <p class="text-gray-600 mb-3">Buka halaman <strong>Dashboard</strong> untuk memulai scan URL</p>
                        <ol class="list-decimal list-inside text-gray-700 space-y-1">
                            <li>Masukkan URL website yang ingin dicek</li>
                            <li>Klik tombol "Cek Keamanan"</li>
                            <li>Tunggu hasil scan (10-15 detik)</li>
                        </ol>
                        <p class="text-sm text-gray-500 mt-3">💡 Sistem akan scan URL ke 70+ engine antivirus dan mengambil screenshot website secara otomatis</p>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">📊 Step 2: Lihat Hasil Scan</h2>
                <div class="flex gap-4">
                    <div class="text-4xl">2️⃣</div>
                    <div>
                        <p class="text-gray-600 mb-3">Hasil scan akan menampilkan:</p>
                        <ul class="space-y-2 text-gray-700">
                            <li><strong>🎯 Skor Keamanan</strong> - Nilai 0-100 (semakin tinggi semakin aman)</li>
                            <li><strong>📊 Hasil Engine</strong> - Berapa engine mendeteksi malicious/suspicious</li>
                            <li><strong>📸 Screenshot</strong> - Preview visual dari website</li>
                            <li><strong>🔍 Detail Engine</strong> - Klik untuk melihat hasil dari setiap engine</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">📸 Step 3: Lihat Screenshot Website</h2>
                <div class="flex gap-4">
                    <div class="text-4xl">3️⃣</div>
                    <div>
                        <p class="text-gray-600 mb-3">Setiap scan menyimpan screenshot dari website</p>
                        <ol class="list-decimal list-inside text-gray-700 space-y-1">
                            <li>Buka halaman <strong>Riwayat</strong></li>
                            <li>Cari scan yang memiliki icon 📸</li>
                            <li>Klik link "📸 Screenshot" untuk melihat detail</li>
                            <li>Download atau buka di tab baru jika diperlukan</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">📥 Step 4: Export Hasil ke CSV</h2>
                <div class="flex gap-4">
                    <div class="text-4xl">4️⃣</div>
                    <div>
                        <p class="text-gray-600 mb-3">Export semua riwayat scan untuk analisis lebih lanjut</p>
                        <ol class="list-decimal list-inside text-gray-700 space-y-1">
                            <li>Buka halaman <strong>Riwayat</strong></li>
                            <li>Klik tombol "📥 Export CSV" di atas</li>
                            <li>File akan otomatis didownload</li>
                            <li>Buka di Excel untuk analisis</li>
                        </ol>
                        <p class="text-sm text-gray-500 mt-3">💡 File CSV berisi: Waktu, URL, Skor, Status, Jumlah Engine, dan info screenshot</p>
                    </div>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">🔗 Step 5: Integrasi ke Sistem Lain</h2>
                <div class="flex gap-4">
                    <div class="text-4xl">5️⃣</div>
                    <div>
                        <p class="text-gray-600 mb-3">API endpoint tersedia untuk integrasi (coming soon)</p>
                        <div class="bg-gray-50 p-3 rounded mt-3 font-mono text-sm">
                            POST /api/scan.php<br>
                            Payload: { "url": "https://example.com" }
                        </div>
                        <p class="text-sm text-gray-500 mt-3">💡 Dokumentasi API akan tersedia di fase pengembangan berikutnya</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">❓ FAQ</h2>
            <div class="space-y-4">
                <details class="bg-white rounded-lg shadow-md p-6">
                    <summary class="cursor-pointer font-bold text-gray-800">Berapa lama proses scan?</summary>
                    <p class="text-gray-600 mt-2">Scan biasanya memakan waktu 10-15 detik karena sistem mengirim URL ke multiple engine secara paralel dan mengambil screenshot website.</p>
                </details>

                <details class="bg-white rounded-lg shadow-md p-6">
                    <summary class="cursor-pointer font-bold text-gray-800">Apa itu skor keamanan?</summary>
                    <p class="text-gray-600 mt-2">Skor keamanan adalah nilai 0-100 berdasarkan hasil scan dari 70+ engine antivirus. Semakin tinggi semakin aman. Skor < 40 harus dihindari.</p>
                </details>

                <details class="bg-white rounded-lg shadow-md p-6">
                    <summary class="cursor-pointer font-bold text-gray-800">Kenapa screenshot tidak tersedia?</summary>
                    <p class="text-gray-600 mt-2">Beberapa website populer (Google, Facebook, dll) ditolak oleh URLScan.io untuk alasan keamanan. Screenshot tetap dapat diambil untuk website lain.</p>
                </details>

                <details class="bg-white rounded-lg shadow-md p-6">
                    <summary class="cursor-pointer font-bold text-gray-800">Bagaimana cara menambah fitur?</summary>
                    <p class="text-gray-600 mt-2">Lihat halaman <a href="features.php" class="text-blue-600 hover:underline">Fitur</a> untuk melihat roadmap pengembangan dan fitur-fitur yang akan datang.</p>
                </details>

                <details class="bg-white rounded-lg shadow-md p-6">
                    <summary class="cursor-pointer font-bold text-gray-800">Bagaimana privasi data saya?</summary>
                    <p class="text-gray-600 mt-2">Semua data scan disimpan di database server kami dan hanya dapat diakses oleh akun Anda. Password dienkripsi dengan bcrypt (standar keamanan modern).</p>
                </details>
            </div>
        </div>

        <!-- Tips -->
        <div class="mt-12 bg-blue-50 border-l-4 border-blue-500 rounded-lg p-6">
            <h2 class="text-2xl font-bold text-blue-900 mb-4">💡 Tips & Trik</h2>
            <ul class="space-y-2 text-blue-800">
                <li>✅ Selalu verifikasi URL sebelum membukanya di browser</li>
                <li>✅ Gunakan fitur export untuk membuat laporan berkala</li>
                <li>✅ Filter riwayat berdasarkan status untuk fokus pada URL berbahaya</li>
                <li>✅ Lihat screenshot untuk mencegah phishing (perhatikan visual website)</li>
                <li>✅ Bookmark halaman fitur untuk mengikuti perkembangan aplikasi</li>
            </ul>
        </div>

        <!-- Support -->
        <div class="mt-12 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">🆘 Butuh Bantuan?</h2>
            <p class="text-gray-600">Jika Anda memiliki pertanyaan atau menemukan bug:</p>
            <ul class="mt-2 space-y-1 text-gray-700">
                <li>📧 Email: support@linkchecker.local</li>
                <li>💬 Chat: Hubungi administrator</li>
                <li>📱 Feedback: Bagikan saran fitur di halaman Fitur</li>
            </ul>
        </div>
    </div>
</body>
</html>
