<?php
session_start();
include_once 'config.php';
include_once 'functions.php';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $session_id = $_SESSION['session_id'];
    $user = getUserBySessionId($db, $session_id);
    if ($user) {
        updateIpLog($db, $user, "jettison");
    }
    
    invalidateUserSessions($db, $user_id);

    // Destroy the current session
    session_destroy();

    // Clear session data
    $_SESSION = array();

    // Ensure all session variables are removed
    session_unset();

    // Reset the session ID
    session_regenerate_id(true);
}
header("Location: ll");
exit;
?>