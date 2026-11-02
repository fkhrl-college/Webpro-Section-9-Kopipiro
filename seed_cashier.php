<?php
// seed_cashier.php — run ONCE, then delete this file
include 'config.php';
$username = 'cashier';
$password = password_hash('cashier123', PASSWORD_DEFAULT);
$role = 'cashier';
$stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES
  (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $username, $password, $role);
mysqli_stmt_execute($stmt);
echo "Cashier account created. Username: admin, Password: admin123. Delete this file now!";
