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

$productId = filter_var(
  $data['product_id'] ?? null,
  FILTER_VALIDATE_INT
);

if ($productId === false || $productId === null || $productId <= 0) {
  http_response_code(400);
  echo json_encode([
    'success' => false,
    'message' => 'Invalid product ID'
  ]);
  exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare(
  $conn,
  "DELETE FROM cart WHERE user_id = ? AND product_id = ?"
);

mysqli_stmt_bind_param($stmt, "ii", $userId, $productId);

if (!mysqli_stmt_execute($stmt)) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Failed to delete product'
  ]);
  exit;
}

$countStmt = mysqli_prepare(
  $conn,
  "SELECT COALESCE(SUM(quantity), 0) AS cart_count
     FROM cart
     WHERE user_id = ?"
);

mysqli_stmt_bind_param($countStmt, "i", $userId);
mysqli_stmt_execute($countStmt);

$result = mysqli_stmt_get_result($countStmt);
$row = mysqli_fetch_assoc($result);

echo json_encode([
  'success' => true,
  'message' => 'Product removed from cart',
  'cart_count' => (int) $row['cart_count']
]);
