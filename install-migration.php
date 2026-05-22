<?php
/**
 * ============================================================
 *  LINK CHECKER ENTERPRISE — Database Migration (Satu File)
 * ============================================================
 *  File ini menggantikan database.sql dan update-db-screenshot.php.
 *  Cukup jalankan sekali via browser: http://localhost/.../install-migration.php
 *
 *  Yang dilakukan:
 *   1. Buat database   link_checker   (jika belum ada)
 *   2. Buat tabel      users
 *   3. Buat tabel      scan_history  (lengkap dengan semua kolom + indeks)
 *   4. Buat tabel      personal_blocklist
 *   5. Tambahkan user admin   (admin / admin123)
 *   6. Tambahkan user demo    (user  / user123)
 * ============================================================
 */
require_once __DIR__ . '/includes/config.php';

try {
    // ── 1. Buat database ────────────────────────────────────
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database <code>" . DB_NAME . "</code> siap<br>";

    // ── 2 & 3 & 4. Buat semua tabel ──────────────────────────
    $pdo->exec("USE `" . DB_NAME . "`");

    // ── Tabel users ──────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id            INT PRIMARY KEY AUTO_INCREMENT,
            username      VARCHAR(50)  UNIQUE NOT NULL,
            email         VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role          ENUM('admin','user') DEFAULT 'user',
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            last_login    TIMESTAMP    NULL,
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Tabel <code>users</code> siap<br>";

    // ── Tabel scan_history ───────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS scan_history (
            id                INT PRIMARY KEY AUTO_INCREMENT,
            user_id           INT          NOT NULL,
            url               VARCHAR(2048) NOT NULL,
            final_url         VARCHAR(2048),
            malicious_count   INT          DEFAULT 0,
            suspicious_count  INT          DEFAULT 0,
            harmless_count    INT          DEFAULT 0,
            undetected_count  INT          DEFAULT 0,
            total_engines     INT          DEFAULT 0,
            safety_score      INT          DEFAULT 0,
            status            ENUM('safe','suspicious','malicious','error') DEFAULT 'safe',
            vt_scan_id        VARCHAR(100),
            screenshot_url    VARCHAR(2048),
            engine_results    LONGTEXT,
            response_time_ms  INT,
            scanned_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_date  (user_id, scanned_at),
            INDEX idx_status     (status),
            INDEX idx_url        (url(191))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Tabel <code>scan_history</code> siap<br>";

    // ── Tabel personal_blocklist ─────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS personal_blocklist (
            id        INT PRIMARY KEY AUTO_INCREMENT,
            user_id   INT          NOT NULL,
            domain    VARCHAR(255) NOT NULL,
            type      ENUM('trusted','blocked') DEFAULT 'blocked',
            notes     TEXT,
            created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_domain (user_id, domain),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Tabel <code>personal_blocklist</code> siap<br>";

    // ── 5 & 6. Tambahkan user default (jika belum ada) ───────
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username,email,password_hash,role) VALUES (?,?,?,?)")
            ->execute(['admin', 'admin@linkchecker.local', $hash, 'admin']);
        echo "✅ User <strong>admin</strong> dibuat (password: admin123)<br>";
    } else {
        echo "ℹ️  User <strong>admin</strong> sudah ada, tidak ditimpa<br>";
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'user'");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('user123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username,email,password_hash,role) VALUES (?,?,?,?)")
            ->execute(['user', 'user@linkchecker.local', $hash, 'user']);
        echo "✅ User <strong>user</strong> dibuat (password: user123)<br>";
    } else {
        echo "ℹ️  User <strong>user</strong> sudah ada, tidak ditimpa<br>";
    }

    echo "<br><strong>✅ Migrasi selesai! Semua tabel dan user sudah siap digunakan.</strong>";
    echo "<br><a href='login.php' class='bg-blue-600 text-white px-4 py-2 rounded inline-block mt-4'>Login Sekarang</a>";

} catch (PDOException $e) {
    die("<br>❌ Error: " . htmlspecialchars($e->getMessage()));
}
?>
