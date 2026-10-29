<?php
session_start();
function is_logged_in(): bool
{
  if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && isset($_SESSION['username'])) {
    return true;
  } else return false;
}

function is_admin(): bool
{
  return $_SESSION['role'] === 'admin';
}

function require_admin()
{
  if (!is_admin()) {
    http_response_code(403);
    die("403 - Access denied. This page is for admins only.");
  }
}
