<?php
function e(string $string): string
{
  return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
}
