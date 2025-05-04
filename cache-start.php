<?php
$mc = new Memcached();
$mc->addServer('127.0.0.1', 11211);

$cacheKey = basename($_SERVER['SCRIPT_NAME']) . ':' . date('Ymd');
if (!empty($_GET['nocache'])) $cacheKey .= ':nocache';

if (($cached = $mc->get($cacheKey)) !== false) {
    echo $cached;
    exit;
}

ob_start();
srand(intval(date('Ymd'))); // daily shuffle consistency
?>