<?php
require_once 'includes/auth.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitur Enterprise - <?= APP_NAME ?></title>
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
                    <a href="features.php" class="text-blue-600 font-semibold border-b-2 border-blue-600 pb-0.5">Fitur</a>
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
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">📚 Fitur Enterprise</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">Rekomendasi fitur untuk meningkatkan nilai aplikasi dan monetisasi</p>

        <!-- Core Features -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">🎯 Fitur Core (Recommended Priority)</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <!-- 1. Export & Report -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">📊 Export & Report</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Export hasil scan ke berbagai format</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ CSV / Excel</li>
                        <li>✅ PDF Report</li>
                        <li>✅ JSON API</li>
                        <li>✅ Custom Report Template</li>
                    </ul>
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">Effort: Medium | Value: High</span>
                </div>

                <!-- 2. Bulk Scanning -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-green-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">🔄 Bulk URL Scanning</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Scan banyak URL sekaligus</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ Upload CSV/TXT file</li>
                        <li>✅ Batch processing queue</li>
                        <li>✅ Progress tracker</li>
                        <li>✅ Download hasil batch</li>
                    </ul>
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">Effort: Medium | Value: Very High</span>
                </div>

                <!-- 3. Scheduled Scanning -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-purple-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">⏰ Scheduled Scanning</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Scan otomatis berkala untuk URL penting</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ Daily/Weekly/Monthly schedule</li>
                        <li>✅ Email notification</li>
                        <li>✅ Status change alerts</li>
                        <li>✅ Dashboard monitoring</li>
                    </ul>
                    <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded">Effort: High | Value: Very High</span>
                </div>

                <!-- 4. Advanced Analytics -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-orange-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">📈 Advanced Analytics</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Insights dan trend analysis mendalam</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ Malware trend charts</li>
                        <li>✅ Top dangerous domains</li>
                        <li>✅ Category breakdown</li>
                        <li>✅ Monthly reports</li>
                    </ul>
                    <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded">Effort: High | Value: High</span>
                </div>

                <!-- 5. API Integration -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-red-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">🔌 Public REST API</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Integrasi ke aplikasi lain</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ API key management</li>
                        <li>✅ Rate limiting (quota)</li>
                        <li>✅ Webhook support</li>
                        <li>✅ API documentation</li>
                    </ul>
                    <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded">Effort: High | Value: Very High</span>
                </div>

                <!-- 6. Team & Collaboration -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-indigo-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">👥 Team Management</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Kolaborasi untuk tim/organisasi</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ Team workspaces</li>
                        <li>✅ Role-based access</li>
                        <li>✅ Shared projects</li>
                        <li>✅ Audit logs</li>
                    </ul>
                    <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded">Effort: Very High | Value: Very High</span>
                </div>
            </div>
        </div>

        <!-- Advanced Features -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">🚀 Fitur Advanced (Monetisasi)</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <!-- 7. Custom Alert Rules -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-cyan-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">🚨 Custom Alert Rules</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Atur trigger notifikasi custom</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ Conditional alerts</li>
                        <li>✅ Multi-channel (Email, SMS, Slack)</li>
                        <li>✅ Alert templates</li>
                        <li>✅ Alert history</li>
                    </ul>
                    <span class="text-xs bg-cyan-100 text-cyan-700 px-2 py-1 rounded">Effort: Medium | Value: High</span>
                </div>

                <!-- 8. IP & Domain Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-pink-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">🌐 IP/Domain Intelligence</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Info detail tentang domain & IP</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ WHOIS lookup</li>
                        <li>✅ IP reputation</li>
                        <li>✅ SSL certificate details</li>
                        <li>✅ Reverse DNS lookup</li>
                    </ul>
                    <span class="text-xs bg-pink-100 text-pink-700 px-2 py-1 rounded">Effort: Medium | Value: High</span>
                </div>

                <!-- 9. Subscription Plans -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-teal-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">💳 Subscription Plans</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Model pricing untuk monetisasi</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ Free / Pro / Enterprise tiers</li>
                        <li>✅ Usage tracking/quota</li>
                        <li>✅ Payment integration (Stripe)</li>
                        <li>✅ Billing dashboard</li>
                    </ul>
                    <span class="text-xs bg-teal-100 text-teal-700 px-2 py-1 rounded">Effort: Very High | Value: Critical</span>
                </div>

                <!-- 10. Browser Extension -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-violet-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">🧩 Browser Extension</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Quick scan langsung dari browser</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ Chrome/Firefox extension</li>
                        <li>✅ Right-click scan URL</li>
                        <li>✅ Real-time badges</li>
                        <li>✅ Quick results popup</li>
                    </ul>
                    <span class="text-xs bg-violet-100 text-violet-700 px-2 py-1 rounded">Effort: Very High | Value: Very High</span>
                </div>

                <!-- 11. Mobile App -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-rose-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">📱 Mobile App</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Native iOS/Android application</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ iOS & Android apps</li>
                        <li>✅ Offline scanning cache</li>
                        <li>✅ Push notifications</li>
                        <li>✅ QR code scanner</li>
                    </ul>
                    <span class="text-xs bg-rose-100 text-rose-700 px-2 py-1 rounded">Effort: Critical | Value: Very High</span>
                </div>

                <!-- 12. White Label Solution -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-amber-500 card-hover">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">🏷️ White Label</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">Solusi untuk reseller & partner</p>
                    <ul class="text-sm text-gray-700 dark:text-gray-200 space-y-1 mb-3">
                        <li>✅ Custom branding</li>
                        <li>✅ Domain custom</li>
                        <li>✅ Reseller pricing</li>
                        <li>✅ Revenue sharing</li>
                    </ul>
                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded">Effort: Very High | Value: Critical</span>
                </div>
            </div>
        </div>

        <!-- Integration Features -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">🔗 Integrasi Third-Party</h2>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 card-hover">
                    <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">Threat Intel</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li>• AlienVault OTX</li>
                        <li>• Shodan API</li>
                        <li>• AbuseIPDB</li>
                    </ul>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 card-hover">
                    <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">Communication</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li>• Slack integration</li>
                        <li>• Email notifications</li>
                        <li>• MS Teams webhook</li>
                    </ul>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 card-hover">
                    <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">Monitoring</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li>• Datadog integration</li>
                        <li>• Sentry logs</li>
                        <li>• Custom webhooks</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Revenue Models -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg shadow-md p-6 mb-12">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">💰 Model Monetisasi</h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div>
                    <h4 class="font-bold text-lg text-gray-800 dark:text-gray-100 mb-2">1. Freemium Model</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Free tier dengan fitur dasar, Pro/Enterprise untuk advanced features</p>
                </div>
                <div>
                    <h4 class="font-bold text-lg text-gray-800 dark:text-gray-100 mb-2">2. Usage-Based Pricing</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Charge berdasarkan jumlah scan per bulan (bayar sesuai pemakaian)</p>
                </div>
                <div>
                    <h4 class="font-bold text-lg text-gray-800 dark:text-gray-100 mb-2">3. Hybrid Model</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Fixed monthly fee + overage charges untuk penggunaan tambahan</p>
                </div>
            </div>
        </div>

        <!-- Implementation Roadmap -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">🗓️ Roadmap Implementasi</h2>
            <div class="space-y-4">
                <div class="flex gap-4">
                    <div class="bg-blue-100 text-blue-700 rounded-full w-10 h-10 flex items-center justify-center font-bold">1</div>
                    <div>
                        <h4 class="font-bold text-gray-800 dark:text-gray-100">Phase 1 (Bulan 1-2)</h4>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Export CSV, Bulk scanning, Advanced filters, Screenshot viewer</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="bg-green-100 text-green-700 rounded-full w-10 h-10 flex items-center justify-center font-bold">2</div>
                    <div>
                        <h4 class="font-bold text-gray-800 dark:text-gray-100">Phase 2 (Bulan 3-4)</h4>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Scheduled scanning, API endpoints, Analytics dashboard, Email notifications</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="bg-purple-100 text-purple-700 rounded-full w-10 h-10 flex items-center justify-center font-bold">3</div>
                    <div>
                        <h4 class="font-bold text-gray-800 dark:text-gray-100">Phase 3 (Bulan 5-6)</h4>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Team management, Subscription system, Billing integration</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="bg-orange-100 text-orange-700 rounded-full w-10 h-10 flex items-center justify-center font-bold">4</div>
                    <div>
                        <h4 class="font-bold text-gray-800 dark:text-gray-100">Phase 4 (Bulan 7+)</h4>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">Browser extension, Mobile app, White label solution, Advanced integrations</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 p-6 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm text-gray-700 dark:text-gray-200">
                <strong>💡 Tips:</strong> Fokus dulu pada fitur yang memberikan value tertinggi dengan effort terendah (Phase 1-2). 
                Setelah memiliki user base yang solid, baru ekspansi ke monetisasi dan platform expansion.
            </p>
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


