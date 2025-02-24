<?php

$hostname = 'xuff1k.stackhero-network.com';
$port = '5349';
$user = 'root';
$password = 'zbbwJObxcRMLEAkHQ90KxbR7ni4aTXIK';
$database = 'diabetes_clustering'; // You shouldn't use the "root" database. This is just for the example. The recommended way is to create a dedicated database (and user) in PhpMyAdmin and use it then here.

$dsn = "mysql:host=$hostname;port=$port;dbname=$database";

$options = array(
  // See below if you have an error like "Uncaught PDOException: PDO::__construct(): SSL operation failed with code 1. OpenSSL Error messages: error:0A000086:SSL routines::certificate verify failed".
  PDO::MYSQL_ATTR_SSL_CAPATH => '/etc/ssl/certs/',
  // PDO::MYSQL_ATTR_SSL_CA => 'isrgrootx1.pem',
  PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
);

$pdo = new PDO($dsn, $user, $password, $options);

$stm = $pdo->query("SELECT VERSION()");
$version = $stm->fetch();

?>
