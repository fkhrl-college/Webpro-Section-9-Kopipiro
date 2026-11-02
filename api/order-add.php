<?php
require_once '../auth.php';
require_once '../config.php';
global $conn;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);

  echo json_encode([
    'success' => false,
    'message' => 'Method tidak diizinkan.'
  ]);

  exit;
}

try {
  $input = json_decode(file_get_contents('php://input'), true);

  if (!is_array($input)) {
    throw new Exception('Data pesanan tidak valid.');
  }

  if (
    !isset($input['total_price']) ||
    !is_numeric($input['total_price'])
  ) {
    throw new Exception('Total pesanan tidak valid.');
  }

  $totalPrice = filter_var(
    $input['total_price'],
    FILTER_VALIDATE_INT
  );

  if ($totalPrice === false || $totalPrice < 0) {
    throw new Exception('Total pesanan tidak valid.');
  }

  // ID pelanggan yang login.
  // Sesuaikan 'user_id' dengan nama session di aplikasi kamu.
  $userId = isset($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : null;

  // Checkout online belum ditangani kasir tertentu.
  $cashierId = null;

  $sql = "
        INSERT INTO orders (
            user_id,
            cashier_id,
            total_price,
            checkout_at,
            served_at,
            is_serve
        )
        VALUES (?, ?, ?, CURRENT_TIMESTAMP, NULL, FALSE)
    ";

  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    throw new Exception('Gagal menyiapkan penyimpanan pesanan.');
  }

  $cashierId = $cashierId ?? 1;

  $stmt->bind_param(
    'iii',
    $userId,
    $cashierId,
    $totalPrice
  );

  if (!$stmt->execute()) {
    throw new Exception('Gagal menyimpan pesanan.');
  }

  $orderId = $conn->insert_id;

  $stmt->close();

  http_response_code(201);

  echo json_encode([
    'success' => true,
    'message' => 'Pesanan berhasil dibuat.',
    'data' => [
      'order_id' => $orderId,
      'total_price' => $totalPrice
    ]
  ]);
} catch (Throwable $error) {
  http_response_code(400);

  echo json_encode([
    'success' => false,
    'message' => $error->getMessage()
  ]);
}
