<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

$user = requireAuth();
$pdo = getDB();

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$sql = "SELECT * FROM scan_history WHERE user_id = ?";
$params = [$user['id']];

if (!empty($from)) {
    $sql .= " AND scanned_at >= ?";
    $params[] = $from . ' 00:00:00';
}
if (!empty($to)) {
    $sql .= " AND scanned_at <= ?";
    $params[] = $to . ' 23:59:59';
}

$sql .= " ORDER BY scanned_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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
    $mal = (int)$scan['malicious_count'];
    $susp = (int)$scan['suspicious_count'];
    $recalcScore = calculateSafetyScore($mal, $susp);
    $recalcStatus = getScanStatus($recalcScore, $mal, $susp);
    fputcsv($output, [
        $scan['id'],
        date('d/m/Y H:i:s', strtotime($scan['scanned_at'])),
        $scan['url'],
        $recalcScore,
        ucfirst($recalcStatus),
        $mal,
        $susp,
        $scan['harmless_count'],
        $scan['undetected_count'],
        $scan['total_engines'],
        $scan['screenshot_url'] ? 'Ya' : 'Tidak'
    ]);
}

fclose($output);
exit;
