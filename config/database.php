<?php
$host = 'e96cun.stackhero-network.com';
$port = '7872';
$db   = 'dbscan';
$user = 'root';
$pass = 'MpHYBLAzid0n52I0u0EIYrp4v1Put1HO';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
