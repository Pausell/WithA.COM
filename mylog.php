<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'system.php';
$title = 'W / A';
$description = 'Dashboard';
$path = "";
$add = "add/";
$favicon = $path.$add.'favicon.png';
$favicon16 = $path.$add.'favicon16.png';
$iphone = $path.'touch-icon-iphone.png';
$ipad = $path.'touch-icon-ipad.png';
$iphoner = $path.'touch-icon-iphone-retina.png';
$ipadr = $path.'touch-icon-ipad-retina.png';
$browserconfig = $path.$add.'browserconfig.xml';
$style = $path.$add.'style.css';
include $path.$add.'a-html.php';
include $path.$add.'head.php';
include $path.$add.'a-body.php';
include $path.$add.'a-container.php';
include $path.$add.'navigation.php';
include $path.$add.'a-10.php';
?>
    <p>Email: <?= $user['email'] ?></p>
    <p>Padless: <?= $user['access'] ?></p>
   <ul style="display:flex;flex-direction:column-reverse;overflow:auto">
    <?php foreach($ip_log as $ip_log_entry) { ?>
        <li><?php echo $ip_log_entry; ?></li>
    <?php } ?>
   </ul>
<?php
include $path.$add.'c-div.php';
include $path.$add.'c-div.php';
include $path.$add.'script.php';
include $path.$add.'c-body_html.php';
?>