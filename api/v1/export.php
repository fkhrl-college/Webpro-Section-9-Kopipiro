<?php
require __DIR__ . '/../../helpers.php';
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

need_role(['owner']);

$rows = $pdo->query(
  "SELECT o.order_code, o.created_at, o.customer_name, c.name AS cashier,
  o.subtotal, o.discount, o.total, o.status
  FROM orders o LEFT JOIN users c ON c.id = o.cashier_id
  ORDER BY o.id DESC"
)->fetchAll();

$book = new Spreadsheet();
$sheet = $book->getActiveSheet();
$sheet->setTitle('Orders');
$sheet->fromArray(['Order code', 'Date', 'Customer', 'Cashier', 'Subtotal', 'Discount', 'Total', 'Status'], null, 'A1');
$sheet->fromArray(array_map('array_values', $rows), null, 'A2');

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="kopipiro-orders.xlsx"');
(new Xlsx($book))->save('php://output');
