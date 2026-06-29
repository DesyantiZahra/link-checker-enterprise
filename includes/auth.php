<?php
require_once __DIR__ . '/db.php';

function registerUser($username, $email, $password) {
    $pdo = getDB();
    
    // Cek username/email sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username atau email sudah terdaftar'];
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $result = $stmt->execute([$username, $email, $passwordHash]);
    
    if ($result) {
        return ['success' => true, 'user_id' => $pdo->lastInsertId()];
    }
    return ['success' => false, 'message' => 'Gagal mendaftar'];
}

function loginUser($username, $password) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        session_regenerate_id(true);
        
        return ['success' => true, 'user' => $user];
    }
    
    return ['success' => false, 'message' => 'Username atau password salah'];
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ];
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}