<nav class="navbar navbar-dark navbar-expand-lg bg-dark py-3">
  <div class="container">
    <a class="navbar-brand text-warning font-weight-bold" href="index.php">
      <span class="text-warning">☕</span> Kopipiro
    </a>

    <button
      class="navbar-toggler"
      type="button"
      data-toggle="collapse"
      data-target="#navbarSupportedContent"
      aria-controls="navbarSupportedContent"
      aria-expanded="false"
      aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item active">
          <a class="nav-link" href="index.php">
            Home <span class="sr-only">(current)</span>
          </a>
        </li>

        <li class="nav-item dropdown">
          <a
            class="nav-link dropdown-toggle"
            href="#"
            id="navbarDropdown"
            role="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
            Cari Kategori
          </a>

          <div class="dropdown-menu" aria-labelledby="navbarDropdown">
            <a class="dropdown-item" href="index.php?category=kopi">Kopi</a>
            <a class="dropdown-item" href="index.php?category=non-kopi">Non Kopi</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="index.php">Tanpa Filter</a>
          </div>
        </li>
      </ul>

      <ul class="navbar-nav navbar-account">
        <?php if (is_logged_in()): ?>
          <li class="nav-item">
            <div class="user-info">
              <span class="badge badge-light user-badge">
                <i class="fas fa-user mr-1"></i>
                <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
              </span>

              <span class="badge <?= ($_SESSION['role'] === 'admin') ? 'badge-info' : 'badge-warning' ?> role-badge">
                <?= htmlspecialchars(ucfirst($_SESSION['role']), ENT_QUOTES, 'UTF-8') ?>
              </span>
            </div>
          </li>

          <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
              <a class="btn btn-warning navbar-action" href="admin/products.php">
                Products
              </a>
            </li>

            <li class="nav-item">
              <a class="btn btn-warning navbar-action" href="admin/users.php">
                Users
              </a>
            </li>
          <?php endif; ?>

          <?php if (is_customer()): ?>
            <li class="nav-item">
              <a href="cart.php" class="btn btn-outline-light navbar-action cart-button">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count">
                  <?= isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0 ?>
                </span>
              </a>
            </li>
          <?php endif; ?>

          <li class="nav-item">
            <a class="btn btn-outline-danger navbar-action" href="logout.php">
              Logout
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-primary navbar-action" href="login.php">
              Login
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <style>
    .navbar {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1030;
    }

    .navbar.scrolled {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.45);
    }

    .navbar>.container {
      max-width: 1300px;
    }

    .navbar-brand {
      font-size: 1.4rem;
      white-space: nowrap;
    }

    .navbar-account {
      align-items: center;
      gap: 10px;
    }

    .user-info {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 6px;
      line-height: 1.4;
    }

    .user-badge,
    .role-badge {
      padding: 8px 12px;
      border-radius: 50rem;
      font-size: 0.8rem;
    }

    .navbar-action {
      padding: 8px 18px;
      font-weight: 600;
      border-radius: 8px;
      white-space: nowrap;
    }

    @media (max-width: 991.98px) {
      .navbar-collapse {
        padding-top: 16px;
      }

      .navbar-nav {
        align-items: stretch;
      }

      .navbar-account {
        align-items: stretch;
        gap: 10px;
        margin-top: 12px;
        padding-top: 14px;
        border-top: 1px solid rgba(255, 255, 255, 0.15);
      }

      .navbar-account .nav-item {
        width: 100%;
      }

      .user-info {
        margin-bottom: 4px;
      }

      .navbar-action {
        display: block;
        width: 100%;
        text-align: center;
      }

      .navbar-account .btn {
        margin: 0;
      }
    }

    @media (max-width: 575.98px) {
      .navbar {
        padding-top: 12px !important;
        padding-bottom: 12px !important;
      }

      .navbar-brand {
        font-size: 1.25rem;
      }

      .user-badge,
      .role-badge {
        font-size: 0.75rem;
        padding: 7px 10px;
      }
    }
  </style>
  <script>
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 0);
    });
  </script>
</nav>