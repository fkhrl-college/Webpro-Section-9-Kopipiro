<?php
include '../config.php';
include '../auth.php';
global $conn;

require_admin();

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h(string $value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirectProducts()
{
  header('Location: products.php');
  exit;
}

if (!empty($_SESSION['product_flash'])) {
  $flash = $_SESSION['product_flash'];
  unset($_SESSION['product_flash']);
} else {
  $flash = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrfToken = $_POST['csrf_token'] ?? '';

  if (!is_string($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['product_flash'] = [
      'type' => 'danger',
      'message' => 'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.'
    ];
    redirectProducts();
  }

  $action = $_POST['action'] ?? '';

  try {
    if ($action === 'delete') {
      $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);

      if (!$productId || $productId < 1) {
        throw new RuntimeException('ID produk tidak valid.');
      }

      $stmt = mysqli_prepare($conn, 'DELETE FROM products WHERE id = ?');
      mysqli_stmt_bind_param($stmt, 'i', $productId);
      mysqli_stmt_execute($stmt);
      $affectedRows = mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);

      if ($affectedRows < 1) {
        throw new RuntimeException('Produk tidak ditemukan atau sudah dihapus.');
      }

      $_SESSION['product_flash'] = [
        'type' => 'success',
        'message' => 'Produk berhasil dihapus.'
      ];
      redirectProducts();
    }

    if ($action === 'create' || $action === 'update') {
      $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
      $name = trim($_POST['name'] ?? '');
      $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_INT);
      $urlImage = trim($_POST['url_image'] ?? '');
      $category = $_POST['category'] ?? '';

      if ($name === '' || mb_strlen($name) > 100) {
        throw new RuntimeException('Nama produk wajib diisi dan maksimal 100 karakter.');
      }

      if ($price === false || $price === null || $price < 0) {
        throw new RuntimeException('Harga harus berupa bilangan bulat nol atau lebih.');
      }

      if ($urlImage === '' || mb_strlen($urlImage) > 2048) {
        throw new RuntimeException('URL gambar wajib diisi dan maksimal 2048 karakter.');
      }

      if (!in_array($category, ['kopi', 'non-kopi'], true)) {
        throw new RuntimeException('Kategori produk tidak valid.');
      }

      if ($action === 'create') {
        $stmt = mysqli_prepare(
          $conn,
          'INSERT INTO products (name, price, url_image, category) VALUES (?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'siss', $name, $price, $urlImage, $category);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['product_flash'] = [
          'type' => 'success',
          'message' => 'Produk berhasil ditambahkan.'
        ];
      } else {
        if (!$productId || $productId < 1) {
          throw new RuntimeException('ID produk tidak valid.');
        }

        $stmt = mysqli_prepare(
          $conn,
          'UPDATE products SET name = ?, price = ?, url_image = ?, category = ? WHERE id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'sissi', $name, $price, $urlImage, $category, $productId);
        mysqli_stmt_execute($stmt);
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affectedRows < 0) {
          throw new RuntimeException('Produk gagal diperbarui.');
        }

        $_SESSION['product_flash'] = [
          'type' => 'success',
          'message' => 'Produk berhasil diperbarui.'
        ];
      }

      redirectProducts();
    }

    throw new RuntimeException('Aksi tidak dikenal.');
  } catch (Throwable $error) {
    if ($error instanceof mysqli_sql_exception && (int) $error->getCode() === 1062) {
      $message = 'Nama produk sudah digunakan. Gunakan nama lain.';
    } else {
      $message = $error->getMessage();
    }

    $_SESSION['product_flash'] = [
      'type' => 'danger',
      'message' => $message
    ];
    redirectProducts();
  }
}

$products = [];
$result = mysqli_query(
  $conn,
  'SELECT id, name, price, url_image, category FROM products ORDER BY id DESC'
);

while ($row = mysqli_fetch_assoc($result)) {
  $products[] = $row;
}
mysqli_free_result($result);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Produk | Kopipiro</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
    body {
      background: #f8f9fc;
      color: #16181c;
    }

    .products-page {
      padding-top: 10vh;
      padding-bottom: 48px;
    }

    .page-title {
      margin-bottom: 5px;
      font-size: 1.5rem;
      font-weight: 700;
    }

    .page-subtitle {
      margin-bottom: 24px;
      color: #6b717d;
      font-size: .92rem;
    }

    .product-table-card {
      overflow: visible;
      border: 1px solid #e7e9f0;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 5px 18px rgba(16, 24, 40, .04);
    }

    .table-toolbar {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      padding: 14px;
    }

    .product-search {
      position: relative;
      flex: 1 1 240px;
    }

    .product-search i {
      position: absolute;
      top: 50%;
      left: 12px;
      color: #667085;
      transform: translateY(-50%);
    }

    .product-search input {
      width: 100%;
      min-height: 40px;
      padding: 8px 12px 8px 36px;
      border: 1px solid #d0d5dd;
      border-radius: 9px;
      font-size: .9rem;
    }

    .product-search input:focus {
      border-color: #4f46e5;
      outline: 0;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, .15);
    }

    .btn-toolbar-custom {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      min-height: 40px;
      padding: 8px 13px;
      border: 1px solid #e4e7ec;
      border-radius: 9px;
      background: #fff;
      color: #475467;
      font-size: .87rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .btn-toolbar-custom:hover {
      background: #f7f8fa;
      color: #101828;
    }

    .btn-add-product {
      border-color: #4f46e5;
      background: #4f46e5;
      color: #fff;
    }

    .btn-add-product:hover {
      border-color: #4338ca;
      background: #4338ca;
      color: #fff;
    }

    .table-responsive {
      border-radius: 0 0 14px 14px;
    }

    .product-table {
      margin-bottom: 0;
      font-size: .9rem;
    }

    .product-table thead th {
      padding: 14px 16px;
      border-top: 1px solid #eceef3;
      border-bottom: 1px solid #eceef3;
      background: #fafbfc;
      color: #667085;
      font-size: .73rem;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .product-table tbody td {
      padding: 13px 16px;
      border-top: 1px solid #f2f4f7;
      color: #3c4048;
      vertical-align: middle;
    }

    .product-table tbody tr:hover {
      background: #fafbfd;
    }

    .product-thumbnail {
      width: 52px;
      height: 52px;
      border: 1px solid #edf0f4;
      border-radius: 9px;
      background: #fff;
      object-fit: cover;
    }

    .category-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 999px;
      background: #eef2ff;
      color: #3730a3;
      font-size: .75rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .category-badge.non-kopi {
      background: #fff7e6;
      color: #b45309;
    }

    .action-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 7px;
    }

    .btn-action {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 9px;
      border: 1px solid #e4e7ec;
      border-radius: 7px;
      background: #fff;
      font-size: .8rem;
      font-weight: 600;
    }

    .btn-edit {
      color: #4338ca;
    }

    .btn-edit:hover {
      background: #eef2ff;
    }

    .btn-delete {
      color: #dc2626;
    }

    .btn-delete:hover {
      background: #fef2f2;
    }

    .table-footer {
      padding: 12px 16px;
      border-top: 1px solid #eceef3;
      color: #667085;
      font-size: .84rem;
    }

    .modal-backdrop.show {
      opacity: .65;
    }

    .modal-content {
      overflow: hidden;
      border: 0;
      border-radius: 14px;
      box-shadow: 0 24px 70px rgba(0, 0, 0, .25);
    }

    .modal-header {
      padding: 20px 24px;
      border-bottom: 1px solid #eceef3;
    }

    .modal-title {
      font-weight: 700;
    }

    .modal-body {
      padding: 22px 24px;
    }

    .modal-footer {
      padding: 16px 24px;
      border-top: 1px solid #eceef3;
    }

    .form-label {
      margin-bottom: 6px;
      color: #344054;
      font-size: .87rem;
      font-weight: 600;
    }

    .form-control {
      min-height: 42px;
      border-color: #d0d5dd;
      border-radius: 8px;
    }

    .form-control:focus {
      border-color: #6366f1;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, .13);
    }

    .empty-state {
      padding: 42px 16px;
      color: #667085;
      text-align: center;
    }

    .empty-state i {
      display: block;
      margin-bottom: 10px;
      font-size: 2rem;
    }

    @media (max-width: 575.98px) {
      .products-page {
        padding-top: 7vh;
      }

      .table-toolbar {
        align-items: stretch;
      }

      .product-search {
        flex-basis: 100%;
      }

      .table-toolbar .btn-toolbar-custom {
        flex: 1;
      }

      .modal-body,
      .modal-header,
      .modal-footer {
        padding-right: 18px;
        padding-left: 18px;
      }
    }

    /* Data density: Compact */
    .product-table.is-compact thead th {
      padding: 8px 12px;
    }

    .product-table.is-compact tbody td {
      padding: 6px 12px;
    }

    .product-table.is-compact .product-thumbnail {
      width: 32px;
      height: 32px;
      border-radius: 6px;
    }

    .product-table.is-compact .category-badge {
      padding: 3px 8px;
      font-size: 0.7rem;
    }

    .product-table.is-compact .btn-action {
      padding: 4px 7px;
      font-size: 0.75rem;
    }
  </style>
</head>

<body>
  <?php
  $hideNavbarFilter = true;
  $navbarIndex = "../index.php";
  include '../navbar.php';
  ?>

  <main class="container products-page">
    <div class="d-flex align-items-start justify-content-between flex-wrap mb-2">
      <div>
        <h1 class="page-title">Manajemen Produk</h1>
        <p class="page-subtitle">Kelola produk kopi dan non-kopi Kopipiro.</p>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= h($flash['message']) ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php endif; ?>

    <section class="product-table-card">
      <div class="table-toolbar">
        <label class="product-search mb-0">
          <i class="bi bi-search" aria-hidden="true"></i>
          <input type="search" id="product-search" placeholder="Cari nama, kategori, atau harga..." aria-label="Cari produk">
        </label>

        <button type="button" class="btn-toolbar-custom" id="toggle-density" aria-pressed="false">
          <i class="bi bi-list"></i>
          Compact
        </button>

        <button type="button" class="btn-toolbar-custom btn-add-product" id="add-product-button">
          <i class="bi bi-plus-lg"></i>
          Add Product
        </button>
      </div>

      <div class="table-responsive">
        <table class="table product-table" id="products-table">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Produk</th>
              <th scope="col">Kategori</th>
              <th scope="col">Harga</th>
              <th scope="col" class="text-right">Action</th>
            </tr>
          </thead>
          <tbody id="products-tbody">
            <?php foreach ($products as $product): ?>
              <tr>
                <td><?= (int) $product['id'] ?></td>
                <td>
                  <div class="d-flex align-items-center">
                    <img
                      class="product-thumbnail mr-3"
                      src="<?= h($product['url_image']) ?>"
                      alt="<?= h($product['name']) ?>"
                      loading="lazy"
                      onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2252%22 height=%2252%22 viewBox=%220 0 52 52%22%3E%3Crect width=%2252%22 height=%2252%22 fill=%22%23f3f4f6%22/%3E%3Cpath d=%22M15 34l8-9 6 6 4-4 5 7z%22 fill=%22%239ca3af%22/%3E%3C/svg%3E';">
                    <div>
                      <div class="font-weight-bold"><?= h($product['name']) ?></div>
                      <small class="text-muted">Produk #<?= (int) $product['id'] ?></small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="category-badge <?= $product['category'] === 'non-kopi' ? 'non-kopi' : '' ?>">
                    <?= $product['category'] === 'non-kopi' ? 'Non-kopi' : 'Kopi' ?>
                  </span>
                </td>
                <td>Rp<?= number_format((int) $product['price'], 0, ',', '.') ?></td>
                <td>
                  <div class="action-buttons">
                    <button
                      type="button"
                      class="btn-action btn-edit edit-product-button"
                      data-id="<?= (int) $product['id'] ?>"
                      data-name="<?= h($product['name']) ?>"
                      data-price="<?= (int) $product['price'] ?>"
                      data-url-image="<?= h($product['url_image']) ?>"
                      data-category="<?= h($product['category']) ?>">
                      <i class="bi bi-pencil-square"></i>
                      Edit
                    </button>

                    <form method="post" class="delete-product-form mb-0">
                      <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                      <button type="submit" class="btn-action btn-delete">
                        <i class="bi bi-trash3"></i>
                        Delete
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="empty-state" id="empty-state" <?= count($products) > 0 ? 'hidden' : '' ?>>
          <i class="bi bi-box-seam"></i>
          <strong id="empty-state-title">Belum ada produk</strong>
          <div id="empty-state-description">Klik Add Product untuk menambahkan produk pertama.</div>
        </div>
      </div>

      <div class="table-footer">
        Menampilkan <strong id="visible-count"><?= count($products) ?></strong> dari
        <strong id="total-count"><?= count($products) ?></strong> produk
      </div>
    </section>
  </main>

  <!-- Modal tambah/edit produk -->
  <div class="modal fade" id="product-modal" tabindex="-1" role="dialog" aria-labelledby="product-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form method="post" id="product-form">
          <div class="modal-header">
            <div>
              <h5 class="modal-title" id="product-modal-title">Tambah Produk</h5>
              <small class="text-muted">Lengkapi informasi produk di bawah ini.</small>
            </div>
            <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" id="product-action" value="create">
            <input type="hidden" name="product_id" id="product-id">

            <div class="form-group">
              <label class="form-label" for="product-name">Nama produk</label>
              <input type="text" class="form-control" id="product-name" name="name" maxlength="100" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="product-price">Harga (Rp)</label>
              <input type="number" class="form-control" id="product-price" name="price" min="0" step="1" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="product-category">Kategori</label>
              <select class="form-control" id="product-category" name="category" required>
                <option value="kopi">Kopi</option>
                <option value="non-kopi">Non-kopi</option>
              </select>
            </div>

            <div class="form-group mb-0">
              <label class="form-label" for="product-url-image">URL gambar</label>
              <input type="text" class="form-control" id="product-url-image" name="url_image" maxlength="2048" placeholder="https://... atau path gambar" required>
              <small class="form-text text-muted">Masukkan URL atau path gambar yang digunakan website.</small>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary" id="save-product-button">
              <i class="bi bi-check2 mr-1"></i>
              Simpan Produk
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

  <script>
    (function() {
      const searchInput = document.getElementById('product-search');
      const table = document.getElementById('products-table');
      const tbody = document.getElementById('products-tbody');
      const rows = Array.from(tbody.querySelectorAll('tr'));
      const emptyState = document.getElementById('empty-state');
      const emptyTitle = document.getElementById('empty-state-title');
      const emptyDescription = document.getElementById('empty-state-description');
      const visibleCount = document.getElementById('visible-count');
      const totalCount = document.getElementById('total-count');
      const modal = $('#product-modal');
      const form = document.getElementById('product-form');

      function filterProducts() {
        const query = searchInput.value.trim().toLowerCase();
        let shown = 0;

        rows.forEach(function(row) {
          const matches = row.textContent.toLowerCase().includes(query);
          row.hidden = !matches;
          if (matches) shown++;
        });

        visibleCount.textContent = shown;
        totalCount.textContent = rows.length;

        if (shown === 0) {
          emptyState.hidden = false;

          if (rows.length === 0) {
            emptyTitle.textContent = 'Belum ada produk';
            emptyDescription.textContent = 'Klik Add Product untuk menambahkan produk pertama.';
          } else {
            emptyTitle.textContent = 'Produk tidak ditemukan';
            emptyDescription.textContent = 'Coba gunakan kata kunci pencarian lain.';
          }
        } else {
          emptyState.hidden = true;
        }

        table.hidden = rows.length === 0 || shown === 0;
      }

      searchInput.addEventListener('input', filterProducts);

      document.getElementById('toggle-density').addEventListener('click', function() {
        const compact = this.getAttribute('aria-pressed') !== 'true';

        this.setAttribute('aria-pressed', String(compact));
        table.classList.toggle('is-compact', compact);

        this.innerHTML = compact ?
          '<i class="bi bi-list"></i> Comfortable' :
          '<i class="bi bi-list"></i> Compact';
      });

      document.getElementById('add-product-button').addEventListener('click', function() {
        form.reset();
        document.getElementById('product-modal-title').textContent = 'Tambah Produk';
        document.getElementById('product-action').value = 'create';
        document.getElementById('product-id').value = '';
        document.getElementById('save-product-button').innerHTML =
          '<i class="bi bi-check2 mr-1"></i> Simpan Produk';
        modal.modal('show');
      });

      document.querySelectorAll('.edit-product-button').forEach(function(button) {
        button.addEventListener('click', function() {
          form.reset();

          document.getElementById('product-modal-title').textContent = 'Edit Produk';
          document.getElementById('product-action').value = 'update';
          document.getElementById('product-id').value = this.dataset.id;
          document.getElementById('product-name').value = this.dataset.name;
          document.getElementById('product-price').value = this.dataset.price;
          document.getElementById('product-url-image').value = this.dataset.urlImage;
          document.getElementById('product-category').value = this.dataset.category;
          document.getElementById('save-product-button').innerHTML =
            '<i class="bi bi-check2 mr-1"></i> Simpan Perubahan';

          modal.modal('show');
        });
      });

      document.querySelectorAll('.delete-product-form').forEach(function(deleteForm) {
        deleteForm.addEventListener('submit', function(event) {
          if (!window.confirm('Yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.')) {
            event.preventDefault();
          }
        });
      });

      modal.on('shown.bs.modal', function() {
        document.getElementById('product-name').focus();
      });

      filterProducts();
    })();
    (function() {
      var root = document.querySelector('[data-tb]');
      if (!root) return;
      var table = root.querySelector('[data-tb-table]');
      var rows = Array.prototype.slice.call(table.tBodies[0].rows);
      var status = root.querySelector('[data-tb-status]');
      var chips = root.querySelector('[data-tb-chips]');
      var search = root.querySelector('[data-tb-search]');
      var menu = root.querySelector('[data-tb-menu]');

      // Search: hide rows whose text does not match, and announce the count.
      search.addEventListener('input', function() {
        var q = search.value.trim().toLowerCase(),
          shown = 0;
        rows.forEach(function(r) {
          var hit = r.textContent.toLowerCase().indexOf(q) > -1;
          r.hidden = !hit;
          if (hit) shown++;
        });
        status.textContent = shown + (shown === 1 ? ' invoice' : ' invoices') + ' shown';
      });

      // Columns menu: each checkbox shows or hides one column (header and cells).
      menu.querySelectorAll('input[data-col]').forEach(function(box) {
        box.addEventListener('change', function() {
          var i = Number(box.getAttribute('data-col'));
          Array.prototype.forEach.call(table.rows, function(r) {
            if (r.cells[i]) r.cells[i].hidden = !box.checked;
          });
        });
      });
      // Escape or a click outside closes the menu.
      menu.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && menu.open) {
          menu.open = false;
          menu.querySelector('summary').focus();
        }
      });
      document.addEventListener('click', function(e) {
        if (menu.open && !menu.contains(e.target)) menu.open = false;
      });

      // Density: one attribute on the table, with aria-pressed kept in sync so the button reports its state.
      var density = root.querySelector('[data-tb-density]');
      density.addEventListener('click', function() {
        var on = density.getAttribute('aria-pressed') !== 'true';
        density.setAttribute('aria-pressed', String(on));
        table.toggleAttribute('data-dense', on);
      });

      // Filter chips: remove one, or all; focus moves to the next control so it is never lost.
      function afterRemove(next) {
        if (!chips.querySelector('[data-tb-remove]')) {
          chips.hidden = true;
          search.focus();
        } else(next || chips.querySelector('button')).focus();
      }
      chips.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-tb-remove]');
        if (btn) {
          var chip = btn.closest('span'),
            next = chip.nextElementSibling && chip.nextElementSibling.querySelector('button') || chip.nextElementSibling;
          chip.remove();
          status.textContent = 'Filter removed';
          afterRemove(next);
        } else if (e.target.closest('[data-tb-clear]')) {
          chips.querySelectorAll('[data-tb-remove]').forEach(function(b) {
            b.closest('span').remove();
          });
          status.textContent = 'All filters cleared';
          afterRemove();
        }
      });
    })();
  </script>
</body>

</html>