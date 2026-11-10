<?php
require __DIR__ . '/../../helpers.php';
$n = body(); // Midtrans POSTs JSON here
// 1. Is this really from Midtrans? SHA512(order_id + status_code + gross_amount + server key)
$expected = hash(
  'sha512',
  ($n['order_id'] ?? '') . ($n['status_code'] ?? '') .
    ($n['gross_amount'] ?? '') . MIDTRANS_SERVER_KEY
);
if (!hash_equals($expected, $n['signature_key'] ?? '')) {
  out(['message' => 'Invalid signature'], 403);
}
// 2. Success = code 200 + (settlement, or capture with fraud status accept)
$status = $n['transaction_status'] ?? '';
$fraud = $n['fraud_status'] ?? 'accept';
$paid = ($n['status_code'] ?? '') === '200'
  && ($status === 'settlement' || ($status === 'capture' && $fraud === 'accept'));
if ($paid) {
  // Also check the amount, and only touch pending orders (safe if notified twice).
  $pdo->prepare("UPDATE orders SET status = 'paid'
 WHERE order_code = ? AND total = ? AND status = 'pending'")
    ->execute([$n['order_id'], (int) $n['gross_amount']]);
}
out(['ok' => true]);
