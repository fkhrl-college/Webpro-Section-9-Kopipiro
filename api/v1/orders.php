<?php
require __DIR__ . '/../../helpers.php';
$user = me();

switch ($_SERVER['REQUEST_METHOD']) {
  case 'POST':
    $in = body();
    $qty = [];
    foreach (($in['items'] ?? []) as $it) {
      $pid = (int) ($it['product_id'] ?? 0);
      $q = (int) ($it['qty'] ?? 0);
      if ($pid > 0 && $q > 0) $qty[$pid] = ($qty[$pid] ?? 0) + min($q, 50);
    }
    if (!$qty) out(['message' => 'Your order is empty'], 400);

    // qty & price validate
    $ph = implode(',', array_fill(0, count($qty), '?'));
    $st = $pdo->prepare(
      "SELECT id, name, price FROM products WHERE is_active = 1 AND id IN ($ph)"
    );
    $st->execute(array_keys($qty));
    $prods = $st->fetchAll();
    if (count($prods) !== count($qty)) out(['message' => 'Some products are unavailable'], 400);
    $subtotal = 0;
    foreach ($prods as $p) $subtotal += $p['price'] * $qty[$p['id']];
    $discount = $subtotal - array_sum(array_map(
      fn($p) => final_price((int) $p['price'], $user) * $qty[$p['id']],
      $prods
    ));
    $total = $subtotal - $discount;
    // assign cashier who has fewest unfinished order
    // <> in sql means !== in js
    $cashier = $pdo->query(
      "SELECT u.id FROM users u
      LEFT JOIN orders o ON o.cashier_id = u.id AND o.status <> 'done'
      WHERE u.role = 'cashier'
      GROUP BY u.id ORDER BY COUNT(o.id), u.id LIMIT 1"
    )->fetchColumn();
    $code = 'KP-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $guest = trim($in['customer_name'] ?? '') ?: 'Guest';
    // use transaction so u are able to exec 2 or more queries and only commit if everything fine
    $pdo->beginTransaction();
    try {
      $pdo->prepare(
        "INSERT INTO orders (order_code, user_id, customer_name, cashier_id,
        subtotal, discount, total)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
      )->execute([
        $code,
        $user['id'] ?? null,
        $user['name'] ?? $guest,
        $cashier ?: null,
        $subtotal,
        $discount,
        $total
      ]);
      $orderId = $pdo->lastInsertId();
      $ins = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, product_name, unit_price, qty)
        VALUES (?, ?, ?, ?, ?)"
      );
      foreach ($prods as $p) {
        $ins->execute([$orderId, $p['id'], $p['name'], $p['price'], $qty[$p['id']]]);
      }
      $pdo->commit();
    } catch (Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
    out([
      'order_code' => $code,
      'subtotal' => $subtotal,
      'discount' => $discount,
      'total' => $total
    ], 201);

  case 'GET': // each role sees a different slice
    $u = need_role(['owner', 'cashier', 'customer']);
    $sql = "SELECT o.id, o.order_code, o.customer_name, o.subtotal, o.discount, o.total,
    o.status, o.created_at, c.name AS cashier,
    (SELECT GROUP_CONCAT(CONCAT(i.qty, 'x ', i.product_name) SEPARATOR ', ')
    FROM order_items i WHERE i.order_id = o.id) AS items
    FROM orders o LEFT JOIN users c ON c.id = o.cashier_id";
    $args = [];
    if ($u['role'] === 'cashier') {
      $sql .= " WHERE o.cashier_id = ?";
      $args[] = $u['id'];
    }
    if ($u['role'] === 'customer') {
      $sql .= " WHERE o.user_id = ?";
      $args[] = $u['id'];
    }
    $st = $pdo->prepare($sql . " ORDER BY o.id DESC LIMIT 200");
    $st->execute($args);
    out($st->fetchAll());
  case 'PUT': // update status
    $u = need_role(['owner', 'cashier']);
    $id = (int) ($_GET['id'] ?? 0);
    $status = body()['status'] ?? '';
    if (!in_array($status, ['pending', 'paid', 'done'], true)) {
      out(['message' => 'Invalid status'], 400);
    }
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $args = [$status, $id];
    if ($u['role'] === 'cashier') {
      $sql .= " AND cashier_id = ?";
      $args[] = $u['id'];
    }
    $st = $pdo->prepare($sql);
    $st->execute($args);
    if ($st->rowCount() === 0) out(['message' => 'Order not found'], 404);
    out(['message' => 'Status updated']);
  default:
    out(['message' => 'Method not allowed'], 405);
}
