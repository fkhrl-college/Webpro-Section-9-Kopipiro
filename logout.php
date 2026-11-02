<?php
session_start();
session_unset();
session_destroy();
include_once 'utils.php';

// $redirect = get_safe_redirect($_POST['redirect'] ?? 'index.php');

// header("Location: " . $redirect);
header("Location: index.php");
exit();
