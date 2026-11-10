<?php
require __DIR__ . '/../../helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['message' => 'Method not allowed'], 405);
$st = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? AND status = 'pending'");
$st->execute([body()['order_code'] ?? '']);
$order = $st->fetch();
if (!$order) out(['message' => 'Pending order not found'], 404);
$payload = [
  'transaction_details' => [
    'order_id' => $order['order_code'],
    'gross_amount' => (int) $order['total']
  ],
  'customer_details' => ['first_name' => $order['customer_name']],
];
$ch = curl_init('https://app.sandbox.midtrans.com/snap/v1/transactions');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 15,
  CURLOPT_USERPWD => MIDTRANS_SERVER_KEY . ':', // Basic auth: key as username
  CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
  CURLOPT_POSTFIELDS => json_encode($payload),
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);
$data = json_decode((string) $resp, true);
if ($code !== 201 || empty($data['redirect_url'])) {
  out(['message' => 'Payment gateway error'], 502);
}
out(['redirect_url' => $data['redirect_url']]);
