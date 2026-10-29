<?php
include 'auth.php';
//include after <body> 
?>
<nav class="navbar navbar-dark navbar-expand-lg navbar-light bg-dark py-3">
  <div class="container">
    <a class="navbar-brand text-warning font-weight-bold" href="index.php">
      <span class="text-warning">☕</span> Kopipiro
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item active">
          <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Cari Kategori
          </a>
          <div class="dropdown-menu" aria-labelledby="navbarDropdown">
            <a class="dropdown-item" href="#">Kopi</a>
            <a class="dropdown-item" href="#">Non Kopi</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="#">Tanpa Filter</a>
          </div>
        </li>
      </ul>
      <ul class="navbar-nav">
        <?php if (is_logged_in()): ?>
          <li class="nav-item mr-lg-3 d-flex align-items-center">
            <div class="user-info py-2 py-lg-0">
              <span class="badge badge-light px-3 py-2 rounded-full">
                <i class="fas fa-user mr-1"></i>
                Username: <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
              </span>

              <span class="badge badge-success px-2 py-2 ml-1 rounded-full">
                <?= htmlspecialchars(ucfirst($_SESSION['role']), ENT_QUOTES, 'UTF-8') ?>
              </span>
            </div>
          </li>

          <li class="nav-item">
            <a class="btn btn-outline-danger px-4 font-weight-bold rounded-full" href="logout.php">
              Logout
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-primary px-4 font-weight-bold" href="login.php">Login</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  <style>
    .user-info {
      line-height: 1.4;
    }

    .navbar>.container {
      max-width: 1300px;
    }

    .rounded-full {
      border-radius: 50rem !important;
    }
  </style>
</nav>