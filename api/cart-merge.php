<?php

include_once '../auth.php';
include_once '../config.php';
global $conn;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);

  echo json_encode([
    'success' => false,
    'message' => 'Method not allowed'
  ]);

  exit;
}

if (!is_logged_in()) {
  http_response_code(401);

  echo json_encode([
    'success' => false,
    'message' => 'Unauthorized'
  ]);

  exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (
  !is_array($data) ||
  !isset($data['items']) ||
  !is_array($data['items'])
) {
  http_response_code(400);

  echo json_encode([
    'success' => false,
    'message' => 'Invalid cart data'
  ]);

  exit;
}

$items = array_slice($data['items'], 0, 100);
$userId = (int) $_SESSION['user_id'];

try {
  mysqli_begin_transaction($conn);

  $checkProductStmt = mysqli_prepare(
    $conn,
    "SELECT id FROM products WHERE id = ? LIMIT 1"
  );

  $mergeStmt = mysqli_prepare(
    $conn,
    "INSERT INTO cart (user_id, product_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
             quantity = quantity + VALUES(quantity)"
  );

  foreach ($items as $item) {
    if (!is_array($item)) {
      continue;
    }

    $productId = filter_var(
      $item['product_id'] ?? null,
      FILTER_VALIDATE_INT
    );

    $quantity = filter_var(
      $item['quantity'] ?? null,
      FILTER_VALIDATE_INT
    );

    if (
      $productId === false ||
      $productId === null ||
      $productId <= 0 ||
      $quantity === false ||
      $quantity === null ||
      $quantity < 1 ||
      $quantity > 99
    ) {
      continue;
    }

    mysqli_stmt_bind_param(
      $checkProductStmt,
      'i',
      $productId
    );

    mysqli_stmt_execute($checkProductStmt);

    $result = mysqli_stmt_get_result($checkProductStmt);

    if (!mysqli_fetch_assoc($result)) {
      continue;
    }

    mysqli_stmt_bind_param(
      $mergeStmt,
      'iii',
      $userId,
      $productId,
      $quantity
    );

    mysqli_stmt_execute($mergeStmt);
  }

  mysqli_stmt_close($checkProductStmt);
  mysqli_stmt_close($mergeStmt);

  $countStmt = mysqli_prepare(
    $conn,
    "SELECT COALESCE(SUM(quantity), 0) AS cart_count
         FROM cart
         WHERE user_id = ?"
  );

  mysqli_stmt_bind_param($countStmt, 'i', $userId);
  mysqli_stmt_execute($countStmt);

  $countResult = mysqli_stmt_get_result($countStmt);
  $countRow = mysqli_fetch_assoc($countResult);

  mysqli_stmt_close($countStmt);

  mysqli_commit($conn);

  echo json_encode([
    'success' => true,
    'message' => 'Cart merged successfully',
    'cart_count' => (int) $countRow['cart_count']
  ]);
} catch (Throwable $e) {
  mysqli_rollback($conn);

  error_log('Cart merge failed: ' . $e->getMessage());

  http_response_code(500);

  echo json_encode([
    'success' => false,
    'message' => 'Failed to merge cart'
  ]);
}
