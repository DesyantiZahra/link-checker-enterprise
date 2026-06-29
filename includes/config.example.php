<?php
// =============================================
// KONFIGURASI APLIKASI — FILE TEMPLATE
// =============================================
// Salin file ini sebagai config.php dan isi API key Anda.
// File config.php tidak akan ikut commit ke git.

// ---------- DATABASE ----------
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'link_checker');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---------- VIRUSTOTAL API ----------
// Dapatkan dari: https://www.virustotal.com > Login > API Key
define('VT_API_KEY', '');

// ---------- URLSCAN.IO API (untuk screenshot, opsional) ----------
define('URLSCAN_API_KEY', '');

// ---------- APLIKASI ----------
define('APP_NAME', 'Link Checker');
define('APP_URL', 'http://localhost/link-checker');

// ---------- TIMEZONE ----------
date_default_timezone_set('Asia/Jakarta');

// ---------- ERROR REPORTING ----------
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ---------- SESSION ----------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- SECURITY HEADERS ----------
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; img-src 'self' https: data:;");
