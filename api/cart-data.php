<?php
require_once '../auth.php';
require_once '../config.php';
global $conn;

header('Content-Type: application/json; charset=utf-8');

$items = [];

if (is_logged_in()) {
  $user_id = $_SESSION['user_id'];

  $stmt = mysqli_prepare(
    $conn,
    "SELECT
        p.id,
        p.name,
        p.price,
        p.url_image,
        p.category,
        c.quantity
     FROM cart AS c
     JOIN products AS p ON p.id = c.product_id
     WHERE c.user_id = ?"
  );

  mysqli_stmt_bind_param($stmt, "i", $user_id);
  mysqli_stmt_execute($stmt);

  $result = mysqli_stmt_get_result($stmt);

  while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
  }
} else {
  $data = json_decode(file_get_contents('php://input'), true);
  $guest_cart = $data['items'] ?? [];

  if (!is_array($guest_cart)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid cart data']);
    exit;
  }

  // Batas untuk menghindari permintaan yang terlalu besar.
  $guest_cart = array_slice($guest_cart, 0, 100);

  $stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, price, url_image, category
     FROM products
     WHERE id = ?"
  );

  foreach ($guest_cart as $item) {
    $product_id = filter_var(
      $item['product_id'] ?? null,
      FILTER_VALIDATE_INT
    );

    $quantity = filter_var(
      $item['quantity'] ?? null,
      FILTER_VALIDATE_INT
    );

    if (
      !$product_id || $product_id < 1 ||
      !$quantity || $quantity < 1
    ) {
      continue;
    }

    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($product) {
      $product['quantity'] = min($quantity, 99);
      $items[] = $product;
    }
  }
}

$cart = [];
$total = 0;
$subtotal = 0;
$discount = 0;

foreach ($items as $item) {
  $price = (int) $item['price'];
  $quantity = (int) $item['quantity'];

  // Harga diskon untuk pengguna yang login.
  $unit_price = is_logged_in()
    ? (int) round($price * 0.9)
    : $price;

  $item_subtotal = $price * $quantity;
  $item_total = $unit_price * $quantity;

  $subtotal += $item_subtotal;
  $total += $item_total;
  $discount += $item_subtotal - $item_total;

  $cart[] = [
    'product_id' => (int) $item['id'],
    'name' => $item['name'],
    'url_image' => $item['url_image'],
    'category' => $item['category'],
    'price' => $price,
    'quantity' => $quantity,
    'unit_price' => $unit_price,
    'item_total' => $item_total
  ];
}

echo json_encode([
  'success' => true,
  'items' => $cart,
  'cart_count' => array_sum(array_column($cart, 'quantity')),
  'subtotal' => $subtotal,
  'discount' => $discount,
  'total' => $total
]);
