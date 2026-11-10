<?php
require __DIR__ . '/../../helpers.php';
$user = me();

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    $sql = "SELECT p.id, p.name, p.description, p.price, p.image, c.name AS category,
    ROUND(AVG(r.rating), 1) AS rating, COUNT(r.id) AS reviews
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN reviews r ON r.product_id = p.id
    WHERE p.is_active = 1";
    $args = [];
    if (!empty($_GET['category'])) {
      $sql .= " AND c.name = ?";
      $args[] = $_GET['category'];
    }
    $sql .= " GROUP BY p.id, c.name ORDER BY c.id, p.name";
    $st = $pdo->prepare($sql);
    $st->execute($args);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
      $r['price'] = (int) $r['price'];
      $r['final_price'] = final_price($r['price'], $user);
      $r['image'] = image_url($r['image']);
    }
    out([
      'categories' => $pdo->query("SELECT id, name FROM categories ORDER BY id")->fetchAll(),
      'products' => $rows,
    ]);

  case 'POST':
    need_role(['owner']);
    $name = trim($_POST['name'] ?? '');
    $cat = (int) ($_POST['category_id'] ?? 0);
    $price = (int) ($_POST['price'] ?? 0);
    if ($name === '' || $cat < 1 || $price < 1) {
      out(['message' => 'name, category_id and price are required'], 400);
    }
    $file = null;
    if (!empty($_FILES['image']['name'])) {
      $f = $_FILES['image'];
      if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) {
        out(['message' => 'Image upload failed or larger than 2 MB'], 400);
      }
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
      $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
      $ext = $allowed[$mime] ?? null;
      if (!$ext) out(['message' => 'Only JPG, PNG or WebP images are allowed'], 400);
      $file = bin2hex(random_bytes(8)) . '.' . $ext; // never trust the original name
      move_uploaded_file($f['tmp_name'], __DIR__ . '/../uploads/' . $file);
    }
    $st = $pdo->prepare(
      "INSERT INTO products (category_id, name, description, price, image)
      VALUES (?, ?, ?, ?, ?)"
    );
    $st->execute([$cat, $name, trim($_POST['description'] ?? ''), $price, $file]);
    out(['message' => 'Product created', 'id' => (int) $pdo->lastInsertId()], 201);

  case 'PUT':
    need_role(['owner']);
    $id = (int) ($_GET['id'] ?? 0);
    $price = (int) (body()['price'] ?? 0);
    if ($id < 1 || $price < 1) out(['message' => 'id and price are required'], 400);
    $st = $pdo->prepare("UPDATE products SET price = ? WHERE id = ?");
    $st->execute([$price, $id]);
    if ($st->rowCount() === 0) out(['message' => 'Product not found'], 404);
    out(['message' => 'Price updated']);

  default:
    out(['message' => 'Method not allowed'], 405);
}
