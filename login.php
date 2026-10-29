<?php
include 'auth.php';
include 'config.php';

if (is_logged_in()) {
  header("Location: index.php");
  exit;
}

if (isset($_POST['form-login'])) {
  $username = $_POST['username'];
  $raw_passwd = $_POST['password'];
  $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ?");
  mysqli_stmt_bind_param($stmt, "s", $username);
  mysqli_stmt_execute($stmt);
  $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  if ($user && password_verify($raw_passwd, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    header("Location: index.php");
    exit();
  } else {
    $error = "Incorrect username or password";
  }
}

if (isset($_POST['form-register'])) {
  $username = $_POST['username'];
  $raw_passwd = $_POST['password'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kopipiro | Login</title>

  <!-- Bootstrap 4 -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css"
    integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm"
    crossorigin="anonymous">

  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700"
    rel="stylesheet">

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 30px 20px;

      background: linear-gradient(135deg,
          #c2c16f 0%,
          #b89852 50%,
          #9b7d3c 100%);

      font-family: "Roboto", sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    .login-page {
      width: 100%;
      max-width: 520px;
      margin: auto;
    }

    .form {
      position: relative;
      width: 100%;
      padding: 50px 55px;
      background: #ffffff;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    }

    .form-header {
      margin-bottom: 35px;
    }

    .form-header .logo {
      font-size: 48px;
      line-height: 1.2;
      margin-bottom: 12px;
    }

    .form-header h1 {
      margin: 0 0 10px;
      color: #222222;
      font-size: 32px;
      font-weight: 700;
    }

    .form-header p {
      margin: 0;
      color: #777777;
      font-size: 15px;
      line-height: 1.6;
    }

    .form input {
      display: block;
      width: 100%;
      height: 58px;
      margin: 0 0 20px;
      padding: 0 18px;

      border: 1px solid #e0e0e0;
      border-radius: 10px;
      outline: none;
      background: #f8f9fa;

      color: #333333;
      font-family: "Roboto", sans-serif;
      font-size: 16px;

      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form input:focus {
      border-color: #b8a052;
      background: #ffffff;
      box-shadow: 0 0 0 3px rgba(118, 184, 82, 0.15);
    }

    .form input::placeholder {
      color: #999999;
    }

    .form button {
      display: block;
      width: 100%;
      min-height: 58px;
      margin-top: 10px;
      padding: 15px;

      border: none;
      border-radius: 10px;
      background: #c9c62a;

      color: #ffffff;
      font-family: "Roboto", sans-serif;
      font-size: 16px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;

      cursor: pointer;
      transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
    }

    .form button:hover {
      background: #b9b727;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(158, 160, 67, 0.25);
    }

    .form button:active {
      transform: translateY(0);
    }

    .form .message {
      margin: 28px 0 0;
      color: #888888;
      font-size: 14px;
      line-height: 1.6;
    }

    .form .message a {
      color: #c9c62a;
      font-weight: 700;
      text-decoration: none;
    }

    .form .message a:hover {
      color: #b9b727;
      text-decoration: underline;
    }

    .form .register-form {
      display: none;
    }

    @media (max-width: 576px) {
      body {
        padding: 20px 16px;
      }

      .login-page {
        max-width: 420px;
      }

      .form {
        padding: 38px 25px;
        border-radius: 14px;
      }

      .form-header .logo {
        font-size: 40px;
      }

      .form-header h1 {
        font-size: 27px;
      }

      .form input {
        height: 54px;
        font-size: 15px;
      }

      .form button {
        min-height: 54px;
      }
    }
  </style>
</head>

<body>
  <div class="login-page">
    <div class="form">

      <div class="form-header">
        <div class="logo">☕</div>
        <h1 class="text-warning">Kopipiro</h1>
        <p>Selamat datang kembali! Silakan masuk ke akunmu.</p>
      </div>

      <form class="register-form" name="form-register" method="POST">
        <input
          type="text"
          name="username"
          placeholder="John Doe"
          autocomplete="username">
        <input
          type="password"
          name="password"
          placeholder="Password"
          autocomplete="new-password">

        <button type="submit" name="form-register">Buat Akun</button>

        <p class="message">
          Sudah punya akun?
          <a href="#">Masuk</a>
        </p>
      </form>

      <form class="login-form" name="form-login" method="POST">
        <input
          type="text"
          name="username"
          placeholder="Username"
          autocomplete="username"
          required>

        <input
          type="password"
          name="password"
          placeholder="Password"
          autocomplete="current-password"
          required>

        <button type="submit" name="form-login">Login</button>

        <p class="message">
          Belum punya akun?
          <a href="#">Buat akun</a>
        </p>
      </form>

    </div>
  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <script>
    $('.message a').on('click', function(event) {
      event.preventDefault();

      $('form').animate({
        height: 'toggle',
        opacity: 'toggle'
      }, 'slow');
    });
  </script>
</body>

</html>