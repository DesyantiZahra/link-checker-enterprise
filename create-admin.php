<?php
require_once 'includes/db.php';

$pdo = getDB();

// Hapus user admin lama
$pdo->exec("DELETE FROM users WHERE username = 'admin'");

// Buat password hash untuk "admin123"
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user admin
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
$result = $stmt->execute(['admin', 'admin@test.com', $hash, 'admin']);

if ($result) {
    echo "✅ User admin berhasil dibuat!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "<a href='login.php'>Klik disini untuk login</a>";
} else {
    echo "❌ Gagal membuat user admin";
}
?>