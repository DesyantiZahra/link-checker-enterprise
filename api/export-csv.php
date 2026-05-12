<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$user = requireAuth();
$pdo = getDB();

// Get all scans for the user
$stmt = $pdo->prepare("SELECT * FROM scan_history WHERE user_id = ? ORDER BY scanned_at DESC");
$stmt->execute([$user['id']]);
$scans = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="scan-history-' . date('Y-m-d-His') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Scan ID',
    'Waktu Scan',
    'URL',
    'Skor Keamanan',
    'Status',
    'Malicious',
    'Suspicious',
    'Harmless',
    'Undetected',
    'Total Engine',
    'Screenshot'
]);

// Add data rows
foreach ($scans as $scan) {
    fputcsv($output, [
        $scan['id'],
        date('d/m/Y H:i:s', strtotime($scan['scanned_at'])),
        $scan['url'],
        $scan['safety_score'],
        ucfirst($scan['status']),
        $scan['malicious_count'],
        $scan['suspicious_count'],
        $scan['harmless_count'],
        $scan['undetected_count'],
        $scan['total_engines'],
        $scan['screenshot_url'] ? 'Ya' : 'Tidak'
    ]);
}

fclose($output);
exit;
