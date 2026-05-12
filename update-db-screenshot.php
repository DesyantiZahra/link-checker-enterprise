<?php
require_once 'includes/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cek apakah kolom screenshot_url sudah ada
    $stmt = $pdo->query("SHOW COLUMNS FROM scan_history LIKE 'screenshot_url'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE scan_history ADD COLUMN screenshot_url VARCHAR(2048) AFTER vt_scan_id");
        echo "✅ Kolom screenshot_url berhasil ditambahkan<br>";
    } else {
        echo "ℹ️ Kolom screenshot_url sudah ada<br>";
    }
    
    // Cek apakah kolom engine_results sudah ada
    $stmt = $pdo->query("SHOW COLUMNS FROM scan_history LIKE 'engine_results'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE scan_history ADD COLUMN engine_results LONGTEXT AFTER screenshot_url");
        echo "✅ Kolom engine_results berhasil ditambahkan<br>";
    } else {
        echo "ℹ️ Kolom engine_results sudah ada<br>";
    }
    
    echo "<br>✅ Database update selesai!";
} catch (PDOException $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}
?>
