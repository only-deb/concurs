<?php
$host = 'localhost';
$dbname = 'test1_onlyde';
$username = 'test1';
$password = 'IgNc8Otoa8WBrdrX';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>