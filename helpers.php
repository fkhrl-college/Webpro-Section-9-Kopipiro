<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

function out(mixed $data, int $code = 200): void
{
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function body(): array // json req. body
{
  static $b = null;
  if ($b === null) {
    $b = json_decode(file_get_contents('php://input'), true) ?? [];
  }
  return $b;
}

function me(): ?array
{
  return $_SESSION['user'] ?? null;
}

function need_role(array $roles)
{
  $u = me();
  if (!$u) {
    out(['message' => 'Please log in'], 401);
  }
  if (!in_array($u['role'], $roles, true)) {
    out(['message' => 'Forbidden, acccess denied'], 403);
  }
  return $u;
}


function final_price(int $price, ?array $user): int
{
  $member = $user && $user['role'] === 'customer';
  return $member ? (int) round($price * (1 - DISCOUNT_RATE)) : $price;
}

function image_url(?string $img): ?string
{
  if (!$img) return null;
  return preg_match('#^https?://#', $img) ? $img : 'uploads/' . $img;
}
