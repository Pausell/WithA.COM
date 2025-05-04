<?php
/**
 * /auth/session-kill.php
 * POST JSON: { "mode":"current" | "all" }
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error'=>'Not logged in']); exit;
}

$userId = $_SESSION['user'];
$body   = json_decode(file_get_contents('php://input'), true);
$mode   = $body['mode'] ?? '';

if ($mode === 'all') {
    $pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$userId]);
    echo json_encode(['status'=>'all sessions cleared']); exit;
}

if ($mode === 'current') {
    $pdo->prepare("DELETE FROM sessions WHERE session_id = ?")
        ->execute([session_id()]);
    session_destroy();
    echo json_encode(['status'=>'current session cleared']); exit;
}

http_response_code(400);
echo json_encode(['error'=>'bad mode']);
?>