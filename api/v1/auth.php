<?php
require __DIR__ . '/../../helpers.php';
$action = $_GET['action'] ?? '';
$in = body();

switch ($action) {
  case 'register':
    $name = trim($in['name'] ?? '');
    $email = strtolower($in['email'] ?? '');
    $pass = $in['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
      out(['message' => 'Name, valid email and password (min 6 chars) are required'], 400);
    }

    try {
      $ct = $pdo->prepare(
        "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'customer')"
      );
      $ct->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
    } catch (PDOException $e) {
      if ($e->errorInfo[1] == 1062) out(['message' => 'Email already registered'], 409);
      throw $e;
    }
    out(['message' => 'Registered. You can log in now.'], 201);
  case 'login':
    $ct = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $ct->execute([strtolower(trim($in['email'] ?? ''))]);
    $u = $ct->fetch();
    if (!$u || !password_verify($in['password'] ?? '', $u['password_hash'])) {
      out(['message' => 'Wrong email or password'], 401);
    }
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int) $u['id'], 'name' => $u['name'], 'role' => $u['role']];
    out($_SESSION['user']);
  case 'logout':
    session_destroy();
    out(['message' => 'Logged out']);
  case 'me':
    out(me() ?? ['role' => 'guest']);
  default:
    out(['message' => 'Unknown action'], 404);
}
