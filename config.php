<?php
session_start();

$servername = "localhost";
$username = "uymv2tv5hcwii";
$password = "edu4j6qdrsww";
$dbname = "dbin2eb9njkkb4";

$db = new mysqli($servername, $username, $password, $dbname);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
?>