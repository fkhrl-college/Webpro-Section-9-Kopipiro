<?php
require __DIR__ . '/../../helpers.php';
need_role(['owner']);
out([
  'daily' => $pdo->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total) AS revenue
    FROM orders WHERE status IN ('paid','done')
    GROUP BY DATE(created_at) ORDER BY day"
  )->fetchAll(),
  'top' => $pdo->query(
    "SELECT i.product_name, SUM(i.qty) AS qty
    FROM order_items i JOIN orders o ON o.id = i.order_id
    WHERE o.status IN ('paid','done')
    GROUP BY i.product_name ORDER BY qty DESC LIMIT 5"
  )->fetchAll(),
]);
