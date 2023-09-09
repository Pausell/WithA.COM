<?php
session_start();
require_once 'config.php';

$user_id = $_SESSION['user_id'];

$sql = "INSERT INTO view (user_id, view_loc, the_data, the_style, the_body, the_tags, can_edit) VALUES (?, '', '', '', '', '', 'yes')";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->close();

header("Location: wall.php");
exit;
?>