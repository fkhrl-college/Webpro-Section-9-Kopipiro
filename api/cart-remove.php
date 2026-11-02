<?php
require_once '../auth.php';
require_once '../config.php';
global $conn;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

if (!is_logged_in()) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Login required']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = filter_var($data['product_id'] ?? null, FILTER_VALIDATE_INT);

if (!$product_id || $product_id < 1) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
  exit;
}

$user_id = $_SESSION['user_id'];

try {
  $stmt = mysqli_prepare(
    $conn,
    "UPDATE cart
     SET quantity = quantity - 1
     WHERE user_id = ? AND product_id = ? AND quantity > 1"
  );

  mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
  mysqli_stmt_execute($stmt);

  if (mysqli_stmt_affected_rows($stmt) === 0) {
    $stmt = mysqli_prepare(
      $conn,
      "DELETE FROM cart
       WHERE user_id = ? AND product_id = ? AND quantity <= 1"
    );

    mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
    mysqli_stmt_execute($stmt);
  }

  $stmt = mysqli_prepare(
    $conn,
    "SELECT COALESCE(SUM(quantity), 0) AS total
     FROM cart
     WHERE user_id = ?"
  );

  mysqli_stmt_bind_param($stmt, "i", $user_id);
  mysqli_stmt_execute($stmt);

  $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

  echo json_encode([
    'success' => true,
    'message' => 'Cart updated',
    'cart_count' => (int) $result['total']
  ]);
} catch (mysqli_sql_exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}
