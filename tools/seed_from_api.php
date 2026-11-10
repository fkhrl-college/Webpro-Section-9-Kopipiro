<?php
// Run once, AFTER seed_base.php: C:\xampp\php\php.exe tools\seed_from_api.php
// Consumes a public API (no key needed) and copies the data into OUR database.
require __DIR__ . '/../config.php';
$ch = curl_init('https://api.sampleapis.com/coffee/hot');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);
$list = json_decode((string) $resp, true);
if ($code !== 200 || !is_array($list)) {
  exit("Public API failed (HTTP $code). Try again later.\n");
}
$coffee = $pdo->query("SELECT id FROM categories WHERE name = 'Coffee'")->fetchColumn();
$exists = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
$ins = $pdo->prepare(
  "INSERT INTO products (category_id, name, description, price, image) VALUES (?, ?, ?, ?, ?)"
);
foreach ($list as $c) {
  $exists->execute([$c['title']]);
  if ($exists->fetchColumn()) continue;
  $desc = $c['description'] . ' Ingredients: ' . implode(', ', $c['ingredients'] ?? []);
  $price = 20000 + ($c['id'] % 6) * 2500; // the API has no prices, so we invent them
  $ins->execute([$coffee, $c['title'], $desc, $price, $c['image'] ?? null]);
}
// Demo ratings so the catalog shows stars (3 random 3-5 ratings per product).
$customer = $pdo->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1")->fetchColumn();
$rate = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating) VALUES (?, ?, ?)");
foreach (
  $pdo->query("SELECT id FROM products WHERE id NOT IN (SELECT product_id FROM reviews)")
    ->fetchAll(PDO::FETCH_COLUMN) as $pid
) {
  for ($i = 0; $i < 3; $i++) $rate->execute([$pid, $customer, random_int(3, 5)]);
}
echo "Imported " . count($list) . " coffees from the public API.\n";
