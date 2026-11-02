<?php
function e(string $string): string
{
  return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
}

function get_safe_redirect(string $url)
{
  if (
    !is_string($url) ||
    $url === '' ||
    $url[0] !== '/' ||
    str_starts_with($url, '//') ||
    str_contains($url, '\\') ||
    preg_match('/[\r\n]/', $url)
  ) {
    return 'index.php';
  }

  return $url;
}
