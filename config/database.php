<?php

$hostname = 'e96cun.stackhero-network.com';
$port = '7872';
$user = 'root';
$password = 'MpHYBLAzid0n52I0u0EIYrp4v1Put1HO';
$database = 'dbscan'; // You shouldn't use the "root" database. This is just for the example. The recommended way is to create a dedicated database (and user) in PhpMyAdmin and use it then here.

$mysqli = mysqli_init();
$mysqliConnected = $mysqli->real_connect($hostname, $user, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL);
if (!$mysqliConnected) {
  die("Connect Error: " . $mysqli->connect_error());
}

echo 'Success... ' . $mysqli->host_info . "\n";

$mysqli->close();

?>
