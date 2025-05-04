<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error'=>'Not logged in']); exit;
}

$userId  = $_SESSION['user'];
$current = session_id();

$stmt = $pdo->prepare("
    SELECT session_id, ip_address, user_agent,
           created_at, last_seen
    FROM sessions
    WHERE user_id = ?
    ORDER BY last_seen DESC");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

foreach ($rows as &$r) $r['current'] = ($r['session_id'] === $current);

echo json_encode(['sessions'=>$rows]);
?>