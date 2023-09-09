<?php
session_start();
include_once 'config.php';
include_once 'functions.php';

if (isset($_SESSION['session_id'])) {
    $session_id = $_SESSION['session_id'];
    $user = getUserBySessionId($db, $session_id);
    if ($user) {
        updateIpLog($db, $user, "jett");
    }
}

session_regenerate_id();
invalidateUserSession($db);
session_destroy();
unset($_SESSION['user_id']);
header("Location: ll");
exit;
?>