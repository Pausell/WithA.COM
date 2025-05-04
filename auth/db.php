<?php
// db.php
$pdo = new PDO(
    'mysql:host=localhost;dbname=db5bidsmzuqizk;charset=utf8mb4',
    'unxsngqgyv6dy',
    'tiiqcfp85xhu',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
$pdoSites = new PDO(
    'mysql:host=localhost;dbname=dbbcjkaslqzv4z;charset=utf8mb4',
    'ubzjopgs0z5iu',
    'yrhgy8h7hwnh',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
?>