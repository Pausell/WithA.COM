<?php
/**
 * /auth/logout.php
 * ?mode=current   — log out only this browser  (default)
 * ?mode=all       — log out ALL devices
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';

$mode    = $_GET['mode'] ?? 'current';
$userId  = $_SESSION['user'] ?? 0;
$thisSID = session_id();

if ($userId) {
    if ($mode === 'all') {
        // kill every session row for this user
        $pdo->prepare("DELETE FROM sessions WHERE user_id = ?")
            ->execute([$userId]);
    } else { /* current */
        // kill just this session row
        $pdo->prepare("DELETE FROM sessions WHERE session_id = ?")
            ->execute([$thisSID]);
    }
    session_destroy();            // remove cookie
}

header('Location: /');            // go home
exit;
?>