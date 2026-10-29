<?php
$db_host     = getenv('DB_HOST') ?: 'localhost';
$db_username = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASSWORD') ?: '';
$db_name     = getenv('DB_NAME') ?: 'db_kopipiro';
$conn = mysqli_connect($db_host, $db_username, $db_password, $db_name);
if (!$conn) {
  die("Koneksi gagal: " . mysqli_connect_error());
}
// echo "Koneksi berhasil!";
