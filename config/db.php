<?php
//InfinityFree
$host = 'sql306.infinityfree.com';    //MySQL Host Name
$db   = 'if0_43459468_hotel';    //MySQL Database Name
$user = 'if0_43459468';          //MySQL User Name
$pass = 'KR7feybNSn'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

session_start();
