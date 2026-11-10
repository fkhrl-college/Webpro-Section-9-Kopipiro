<?php
session_start();
$dsn = 'mysql:host=localhost;dbname=kopipirov1;charset=utf8mb4';
$username = 'root';
$password = '';
$pdo = new PDO(
  $dsn,
  $username,
  $password,
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    // older version use
    // PDO::MYSQL_ATTR_FOUND_ROWS => true // i use older php btw
    PDO\Mysql::ATTR_FOUND_ROWS => true,
  ]
);

const DISCOUNT_RATE = 0.1;
const MIDTRANS_SERVER_KEY = 'SB-Mid-server-6XG3Uw0Pqvgu8tLd-apEKpQ0'; // for demo only, very dangerous, put it later to .env file
