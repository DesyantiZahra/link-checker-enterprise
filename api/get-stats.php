<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$user = requireAuth();

$pdo = getDB();
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'safe' THEN 1 ELSE 0 END) as safe,
        SUM(CASE WHEN status = 'suspicious' THEN 1 ELSE 0 END) as suspicious,
        SUM(CASE WHEN status = 'malicious' THEN 1 ELSE 0 END) as malicious
    FROM scan_history 
    WHERE user_id = ?
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

echo json_encode([
    'success' => true,
    'total' => (int)$stats['total'],
    'safe' => (int)$stats['safe'],
    'suspicious' => (int)$stats['suspicious'],
    'malicious' => (int)$stats['malicious']
]);