# 🔍 Enterprise Link Checker - Panduan Lengkap

## 📌 Ringkasan Sistem

Aplikasi **Link Checker** adalah platform untuk memeriksa keamanan URL dengan menggunakan lebih dari 70 engine antivirus secara bersamaan. Setiap scan dilengkapi dengan:

- ✅ Hasil dari 70+ engine antivirus (VirusTotal)
- ✅ Screenshot website (URLScan.io) + download PNG
- ✅ Skor keamanan piecewise (0-100) berdasarkan jumlah malicious/suspicious
- ✅ Prioritas status: malicious > suspicious > safe (keamanan diutamakan)
- ✅ Caching otomatis — URL yang pernah di-scan langsung tampil, riwayat baru tetap tercatat
- ✅ Semua nilai skor & status dihitung ulang dengan rumus terbaru saat ditampilkan
- ✅ Penyimpanan riwayat seumur hidup
- ✅ Export ke CSV (dengan recalculate otomatis)
- ✅ Export laporan scan ke PDF
- ✅ Grafik tren statistik 7 hari (Chart.js)
- ✅ Filter & pencarian riwayat (berdasarkan malicious/suspicious count, bukan kolom legacy)
- ✅ Proteksi CSRF di semua form + rate limiting login
- ✅ Panel Admin untuk melihat statistik semua user

---

## 🚀 Quick Start

### Setup Awal

1. Jalankan **XAMPP** (Apache + MySQL)
2. Jalankan migrasi database: buka `install-migration.php` di browser
3. Buka `system-check.php` untuk memverifikasi instalasi
4. Login di `login.php` dengan akun default

## ⚙️ Konfigurasi Lokal

Sebelum menjalankan aplikasi, pastikan file `includes/config.php` berisi pengaturan database dan API key lokal Anda. File ini sudah ada di `.gitignore` sehingga tidak akan dikirim ke GitHub.

- `DB_HOST`: biasanya `localhost`
- `DB_NAME`: `link_checker`
- `DB_USER`: biasanya `root`
- `DB_PASS`: kosong untuk XAMPP default
- `VT_API_KEY`: memasukkan API key VirusTotal Anda
- `URLSCAN_API_KEY`: opsional untuk screenshot via URLScan.io

### 1️⃣ Halaman Utama / Dashboard

```
http://localhost/link-checker/index.php
```

- Masuk dengan akun Anda
- Demo: `admin` / `admin123` atau `user` / `user123`

### 2️⃣ Scan URL Baru

```
http://localhost/link-checker/index.php
```

- **Fitur**: Scan URL dengan 70+ engine antivirus + screenshot
- **Output**: Skor keamanan, detail engine, screenshot, grafik tren
- **Waktu**: 10-15 detik per scan

### 3️⃣ Riwayat Scan

```
http://localhost/link-checker/history.php
```

- **Filter**: Semua, Aman, Mencurigakan, Berbahaya
- **Search**: Cari berdasarkan URL
- **Export**: Download ke CSV
- **Actions**: Lihat detail, screenshot, hapus riwayat

### 4️⃣ Detail Scan & Export PDF

```
http://localhost/link-checker/detail.php?id=1
```

- **Fitur**: Ringkasan lengkap hasil scan
- **Export**: Download laporan scan ke PDF
- **Detail Engine**: Lihat hasil deteksi per engine antivirus

### 5️⃣ Lihat Screenshot Website

```
http://localhost/link-checker/view-screenshot.php?id=1
```

- **Fitur**: Preview dan download screenshot website
- **Redirect**: Buka website di URLScan.io

### 6️⃣ Export Riwayat ke CSV

```
http://localhost/link-checker/api/export-csv.php
```

- **Format**: CSV (dapat dibuka di Excel)
- **Isi**: ID, Waktu, URL, Skor, Status, Engine, Screenshot

### 7️⃣ Admin Panel

```
http://localhost/link-checker/admin/dashboard.php
```

- Akses hanya untuk user dengan role `admin`
- **Fitur**: Statistik semua user, total scan, scan berbahaya

### 8️⃣ Profil & Pengaturan

```
http://localhost/link-checker/profile.php
```

- Lihat info akun, ubah password

### 9️⃣ Setup & Troubleshooting

```
http://localhost/link-checker/system-check.php
```

- Cek koneksi database, API key, PHP extensions
- Panduan troubleshooting langsung dari halaman



---

## 📊 Fitur Yang Aktif

| Fitur                | Status | Keterangan |
|----------------------|--------|------------|
| Scan Multi-Engine    | ✅     | 70+ antivirus engine via VirusTotal v3 |
| Caching Scan         | ✅     | URL berulang langsung dari cache, riwayat baru tetap tersimpan |
| Skor Keamanan        | ✅     | Piecewise: mal>0 → max(0, 65-(mal-1)×20-susp×5), else → max(0, 100-susp×10) |
| Prioritas Status     | ✅     | malicious > suspicious > safe (meski skor masih tinggi) |
| Recalculate Otomatis | ✅     | Semua tampilan & export hitung ulang skor/status dengan helper |
| Screenshot Website   | ✅     | URLScan.io integration dengan polling 12 iterasi + download PNG |
| Riwayat Scan         | ✅     | Unlimited storage, filter (via count), search |
| Export CSV           | ✅     | Download history dengan recalculate otomatis |
| Export PDF           | ✅     | Download laporan scan per-ID |
| Grafik Statistik     | ✅     | Tren 7 hari & distribusi status (Chart.js) — recalculate di PHP |
| User Authentication  | ✅     | Bcrypt + CSRF token + rate limiting login |
| Multi-user           | ✅     | Separate workspaces per user |
| Admin Panel          | ✅     | Statistik semua user (termasuk yg 0 scan) + recalculate otomatis |
| Engine Detail        | ✅     | Hasil deteksi per engine antivirus |
| System Check         | ✅     | Diagnostik otomatis termasuk live test URLScan API key |

---

## 🔐 Keamanan

- **Password**: Dienkripsi dengan bcrypt (PASSWORD_DEFAULT)
- **Session**: PHP session dengan CSRF token pada form login
- **Database**: MySQL dengan prepared statements (SQL injection protection)
- **User Data**: Isolated per user ID (`user_id` foreign key)
- **API Keys**: Dikonfigurasi di `includes/config.php`
- **Admin Check**: Diakses hanya jika `$_SESSION['role'] === 'admin'`
- **HTML Escaping**: Semua output pengguna di-escape dengan `htmlspecialchars()`

---

## 💾 Database Schema

### Tabel `users`

```sql
id, username (UNIQUE), email (UNIQUE), password_hash, role ENUM('admin','user'), created_at, last_login
```

### Tabel `scan_history`

```sql
id, user_id (FK→users), url, final_url, malicious_count, suspicious_count,
harmless_count, undetected_count, total_engines, safety_score,
status ENUM('safe','suspicious','malicious','error'),
vt_scan_id, screenshot_url, engine_results (LONGTEXT JSON), response_time_ms, scanned_at
```

> **Catatan:** Kolom `safety_score` dan `status` di database menyimpan nilai legacy (rumus lama).
> Sistem selalu menghitung ulang skor & status dengan `calculateSafetyScore()` dan `getScanStatus()` saat ditampilkan di halaman mana pun (dashboard, riwayat, detail, export CSV, admin panel).

---

## 📁 Struktur File

```
link-checker/
├── index.php              # Dashboard + form scan + grafik tren
├── login.php              # Login dengan CSRF token
├── register.php           # Registrasi user baru
├── history.php            # Riwayat scan (filter, search, export, styled actions)
├── detail.php             # Detail scan + download PDF
├── profile.php            # Profil & pengaturan akun
├── view-screenshot.php    # Lihat / download screenshot
├── features.php           # Roadmap fitur
├── guide.php              # Panduan penggunaan & FAQ
├── overview.php           # Ringkasan fitur
├── logout.php             # Logout (via POST + CSRF)
├── install-migration.php  # Migrasi DB
├── system-check.php       # Cek status sistem (DB, API key, PHP extensions)
├── database.sql           # SQL schema (referensi)
├── README.md              # Dokumentasi ini
│
├── admin/
│   └── dashboard.php      # Admin panel (total user, total scan, scan berbahaya)
│
├── api/
│   ├── scan.php           # Endpoint scan (polling loop VT + URLScan)
│   └── export-csv.php     # Export riwayat ke CSV
│
├── includes/
│   ├── config.php         # Konfigurasi (DB, API keys, APP_URL)
│   ├── config.example.php # Template konfigurasi (tanpa API key)
│   ├── db.php             # Koneksi PDO database
│   ├── auth.php           # Auth: login, register, CSRF, requireAuth, requireAdmin
│   └── helpers.php        # Fungsi bantu: calculateSafetyScore (piecewise),
│                          #   getScanStatus (prioritas mal>susp>score),
│                          #   getStatusBadgeClass, getStatusLabel
```

---

## 🔧 Konfigurasi

File: `includes/config.php`

```php
// Database
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'link_checker');
define('DB_USER', 'root');
define('DB_PASS', '');

// VirusTotal API v3
define('VT_API_KEY', 'YOUR_API_KEY');

// URLScan.io API
define('URLSCAN_API_KEY', 'YOUR_API_KEY');

// Aplikasi
define('APP_NAME', 'Enterprise Link Checker');
define('APP_URL', 'http://localhost/link-checker');
```

---

## 🧪 Akun Demo

| Role  | Username  | Email                       | Password  |
|-------|-----------|-----------------------------|-----------|
| Admin | `admin`   | admin@linkchecker.local     | `admin123`|
| User  | `user`    | user@linkchecker.local      | `user123` |

### URL Test

```
https://google.com       # Safe
https://example.com      # Safe
https://httpbin.org      # Safe (API testing)
```

---

## ⚡ Performance

- **Scan Time (URL baru)**: 10-15 detik (termasuk screenshot)
- **Scan Time (cache)**: < 1 detik — hasil langsung dari cache, riwayat baru tetap tercatat
- **Database Query**: < 100ms
- **API Response**: < 5 detik (VirusTotal)
- **Screenshot**: < 10 detik (URLScan.io, dengan polling loop 12 iterasi)
- **Polling Strategy**: Kedua API (VT & URLScan) menggunakan polling loop bukan `sleep()` tetap

---

## 🐛 Troubleshooting

### Screenshot tidak muncul?

1. Cek API key URLScan di `includes/config.php`
2. Beberapa website ditolak oleh URLScan (Google, Facebook, dll)
3. Jalankan `install-migration.php` untuk memastikan kolom database lengkap
4. Cek error di browser console (F12)

### Hasil scan lama statusnya salah?

Tidak perlu khawatir — sistem otomatis menghitung ulang skor dan status setiap kali data ditampilkan (dashboard, riwayat, detail, export CSV, admin panel). Kolom database `status` dan `safety_score` tidak digunakan langsung oleh antarmuka.

### Scan gagal?

1. Pastikan API key VirusTotal aktif
2. Cek koneksi internet
3. Buka `system-check.php` untuk diagnostik otomatis (termasuk live test URLScan API key)

### Database error?

1. Pastikan MySQL running di XAMPP
2. Cek kredensial di `includes/config.php`
3. Jalankan `install-migration.php` untuk setup ulang schema

### Lupa password admin?

1. Hubungi administrator database untuk mereset password manual via SQL
2. Atau jalankan ulang `install-migration.php` untuk reset user admin

---

## 📞 Dukungan

- **Email**: support@linkchecker.local
- **Bug Report**: Hubungi administrator

---

## 📄 License

Enterprise Link Checker - 2026

---

**Last Updated**: 30 Juni 2026
**Version**: 2.0
**Status**: Production Ready
