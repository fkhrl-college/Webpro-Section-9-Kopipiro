<?php
require_once 'config.php';
require_once 'auth.php';
global $conn;

require_cashier();

$cashierId = (int) $_SESSION['user_id'];

if (empty($_SESSION['orders_csrf_token'])) {
  $_SESSION['orders_csrf_token'] = bin2hex(random_bytes(32));
}

function h(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirectOrders(): void
{
  header('Location: cashier.php');
  exit;
}

if (!empty($_SESSION['orders_flash'])) {
  $flash = $_SESSION['orders_flash'];
  unset($_SESSION['orders_flash']);
} else {
  $flash = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrfToken = $_POST['csrf_token'] ?? '';

  if (
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['orders_csrf_token'], $csrfToken)
  ) {
    $_SESSION['orders_flash'] = [
      'type' => 'danger',
      'message' => 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.'
    ];
    redirectOrders();
  }

  $action = $_POST['action'] ?? '';
  $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);

  try {
    if ($action !== 'serve') {
      throw new RuntimeException('Aksi tidak dikenal.');
    }

    if (!$orderId || $orderId < 1) {
      throw new RuntimeException('ID pesanan tidak valid.');
    }

    // Kondisi cashier_id memastikan kasir hanya dapat memperbarui pesanan miliknya.
    // Kondisi is_serve = FALSE mencegah pesanan dilayani dua kali.
    $stmt = $conn->prepare(
      'UPDATE orders
       SET is_serve = TRUE, served_at = CURRENT_TIMESTAMP
       WHERE id = ? AND cashier_id = ? AND is_serve = FALSE'
    );

    if (!$stmt) {
      throw new RuntimeException('Gagal menyiapkan pembaruan pesanan.');
    }

    $stmt->bind_param('ii', $orderId, $cashierId);

    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException('Gagal memperbarui status pesanan.');
    }

    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if ($affectedRows !== 1) {
      throw new RuntimeException(
        'Pesanan tidak ditemukan, bukan milik kasir ini, atau sudah dilayani.'
      );
    }

    $_SESSION['orders_flash'] = [
      'type' => 'success',
      'message' => 'Pesanan #' . $orderId . ' berhasil dilayani.'
    ];

    redirectOrders();
  } catch (Throwable $error) {
    $_SESSION['orders_flash'] = [
      'type' => 'danger',
      'message' => $error->getMessage()
    ];
    redirectOrders();
  }
}

// Ambil hanya pesanan yang ditugaskan kepada kasir yang sedang login.
$orders = [];

$stmt = $conn->prepare(
  'SELECT
      o.id,
      o.user_id,
      u.username AS customer_name,
      o.total_price,
      o.checkout_at,
      o.served_at,
      o.is_serve
   FROM orders AS o
   LEFT JOIN users AS u ON u.id = o.user_id
   WHERE o.cashier_id = ?
   ORDER BY o.is_serve ASC, o.checkout_at DESC, o.id DESC'
);

if (!$stmt) {
  http_response_code(500);
  exit('Gagal menyiapkan data pesanan.');
}

$stmt->bind_param('i', $cashierId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $orders[] = $row;
}

$stmt->close();

$totalOrders = count($orders);
$pendingOrders = 0;
$servedOrders = 0;

foreach ($orders as $order) {
  if ((int) $order['is_serve'] === 1) {
    $servedOrders++;
  } else {
    $pendingOrders++;
  }
}

function rupiah(mixed $value): string
{
  return 'Rp' . number_format((int) $value, 0, ',', '.');
}

function formatDateTime(?string $value): string
{
  if (!$value) {
    return '-';
  }

  $timestamp = strtotime($value);

  return $timestamp === false
    ? '-'
    : date('d/m/Y H:i', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pesanan Kasir | Kopipiro</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
    body {
      background: #f8f9fc;
      color: #16181c;
    }

    .orders-page {
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

    .orders-card {
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

    .order-search {
      position: relative;
      flex: 1 1 240px;
    }

    .order-search i {
      position: absolute;
      top: 50%;
      left: 12px;
      color: #667085;
      transform: translateY(-50%);
    }

    .order-search input {
      width: 100%;
      min-height: 40px;
      padding: 8px 12px 8px 36px;
      border: 1px solid #d0d5dd;
      border-radius: 9px;
      font-size: .9rem;
    }

    .order-search input:focus {
      border-color: #4f46e5;
      outline: 0;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, .15);
    }

    .table-responsive {
      border-radius: 0 0 14px 14px;
    }

    .orders-table {
      margin-bottom: 0;
      font-size: .9rem;
    }

    .orders-table thead th {
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

    .orders-table tbody td {
      padding: 13px 16px;
      border-top: 1px solid #f2f4f7;
      color: #3c4048;
      vertical-align: middle;
    }

    .orders-table tbody tr:hover {
      background: #fafbfd;
    }

    .order-id {
      font-weight: 700;
      color: #344054;
      white-space: nowrap;
    }

    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 999px;
      font-size: .75rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .status-pending {
      background: #fff7e6;
      color: #b45309;
    }

    .status-served {
      background: #dcfce7;
      color: #166534;
    }

    .btn-serve {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 6px 10px;
      border: 1px solid #15803d;
      border-radius: 7px;
      background: #16a34a;
      color: #fff;
      font-size: .8rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .btn-serve:hover {
      border-color: #166534;
      background: #15803d;
      color: #fff;
    }

    .served-label {
      color: #15803d;
      font-size: .82rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .table-footer {
      padding: 12px 16px;
      border-top: 1px solid #eceef3;
      color: #667085;
      font-size: .84rem;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 20px;
    }

    .summary-box {
      padding: 16px 18px;
      border: 1px solid #e7e9f0;
      border-radius: 12px;
      background: #fff;
      box-shadow: 0 3px 12px rgba(16, 24, 40, .03);
    }

    .summary-label {
      color: #667085;
      font-size: .82rem;
    }

    .summary-value {
      margin-top: 5px;
      font-size: 1.45rem;
      font-weight: 700;
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

    .orders-table.is-compact thead th {
      padding: 8px 12px;
    }

    .orders-table.is-compact tbody td {
      padding: 6px 12px;
    }

    @media (max-width: 767.98px) {
      .summary-grid {
        grid-template-columns: 1fr;
        gap: 10px;
      }

      .summary-box {
        padding: 12px 16px;
      }

      .summary-value {
        font-size: 1.2rem;
      }
    }

    @media (max-width: 575.98px) {
      .orders-page {
        padding-top: 7vh;
      }

      .table-toolbar {
        align-items: stretch;
      }

      .order-search {
        flex-basis: 100%;
      }
    }
  </style>
</head>

<body>
  <?php
  $hideNavbarFilter = true;
  include 'navbar.php';
  ?>

  <main class="container orders-page">
    <div class="mb-2">
      <h1 class="page-title">Pesanan Kasir</h1>
      <p class="page-subtitle">Lihat pesanan yang ditugaskan kepada kamu dan tandai pesanan yang sudah dilayani.</p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= h((string) $flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= h((string) $flash['message']) ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php endif; ?>

    <section class="summary-grid" aria-label="Ringkasan pesanan">
      <div class="summary-box">
        <div class="summary-label">Total Pesanan</div>
        <div class="summary-value"><?= $totalOrders ?></div>
      </div>
      <div class="summary-box">
        <div class="summary-label">Menunggu Dilayani</div>
        <div class="summary-value text-warning"><?= $pendingOrders ?></div>
      </div>
      <div class="summary-box">
        <div class="summary-label">Sudah Dilayani</div>
        <div class="summary-value text-success"><?= $servedOrders ?></div>
      </div>
    </section>

    <section class="orders-card">
      <div class="table-toolbar">
        <label class="order-search mb-0">
          <i class="bi bi-search" aria-hidden="true"></i>
          <input
            type="search"
            id="order-search"
            placeholder="Cari ID pesanan, pelanggan, atau status..."
            aria-label="Cari pesanan">
        </label>

        <button type="button" class="btn btn-outline-secondary" id="toggle-density" aria-pressed="false">
          <i class="bi bi-list"></i>
          Compact
        </button>
      </div>

      <div class="table-responsive">
        <table class="table orders-table" id="orders-table">
          <thead>
            <tr>
              <th scope="col">ID Pesanan</th>
              <th scope="col">Pelanggan</th>
              <th scope="col">Total</th>
              <th scope="col">Waktu Checkout</th>
              <th scope="col">Status</th>
              <th scope="col">Waktu Dilayani</th>
              <th scope="col" class="text-right">Action</th>
            </tr>
          </thead>
          <tbody id="orders-tbody">
            <?php foreach ($orders as $order): ?>
              <?php $isServed = (int) $order['is_serve'] === 1; ?>
              <tr>
                <td class="order-id">#<?= (int) $order['id'] ?></td>
                <td>
                  <?php if ($order['user_id'] === null): ?>
                    <span class="text-muted">Guest</span>
                  <?php else: ?>
                    <?= h((string) ($order['customer_name'] ?? 'Pelanggan')) ?>
                    <small class="d-block text-muted">ID #<?= (int) $order['user_id'] ?></small>
                  <?php endif; ?>
                </td>
                <td class="font-weight-bold"><?= h(rupiah($order['total_price'])) ?></td>
                <td><?= h(formatDateTime($order['checkout_at'])) ?></td>
                <td>
                  <?php if ($isServed): ?>
                    <span class="status-badge status-served">Sudah dilayani</span>
                  <?php else: ?>
                    <span class="status-badge status-pending">Menunggu</span>
                  <?php endif; ?>
                </td>
                <td><?= h(formatDateTime($order['served_at'])) ?></td>
                <td class="text-right">
                  <?php if (!$isServed): ?>
                    <form method="post" class="serve-order-form mb-0">
                      <input type="hidden" name="csrf_token" value="<?= h($_SESSION['orders_csrf_token']) ?>">
                      <input type="hidden" name="action" value="serve">
                      <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                      <button type="submit" class="btn-serve">
                        <i class="bi bi-check2-circle"></i>
                        Layani
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="served-label">
                      <i class="bi bi-check-circle-fill mr-1"></i>
                      Selesai
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="empty-state" id="empty-state" <?= $totalOrders > 0 ? 'hidden' : '' ?>>
          <i class="bi bi-receipt"></i>
          <strong id="empty-state-title">Belum ada pesanan</strong>
          <div id="empty-state-description">Pesanan yang ditugaskan kepada kasir ini akan muncul di sini.</div>
        </div>
      </div>

      <div class="table-footer">
        Menampilkan <strong id="visible-count"><?= $totalOrders ?></strong> dari
        <strong id="total-count"><?= $totalOrders ?></strong> pesanan
      </div>
    </section>
  </main>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

  <script>
    (function() {
      const searchInput = document.getElementById('order-search');
      const table = document.getElementById('orders-table');
      const tbody = document.getElementById('orders-tbody');
      const rows = Array.from(tbody.querySelectorAll('tr'));
      const emptyState = document.getElementById('empty-state');
      const emptyTitle = document.getElementById('empty-state-title');
      const emptyDescription = document.getElementById('empty-state-description');
      const visibleCount = document.getElementById('visible-count');
      const totalCount = document.getElementById('total-count');

      function filterOrders() {
        const query = searchInput.value.trim().toLowerCase();
        let shown = 0;

        rows.forEach(function(row) {
          const matches = row.textContent.toLowerCase().includes(query);
          row.hidden = !matches;

          if (matches) {
            shown++;
          }
        });

        visibleCount.textContent = shown;
        totalCount.textContent = rows.length;

        if (shown === 0) {
          emptyState.hidden = false;

          if (rows.length === 0) {
            emptyTitle.textContent = 'Belum ada pesanan';
            emptyDescription.textContent =
              'Pesanan yang ditugaskan kepada kasir ini akan muncul di sini.';
          } else {
            emptyTitle.textContent = 'Pesanan tidak ditemukan';
            emptyDescription.textContent =
              'Coba gunakan kata kunci pencarian lain.';
          }
        } else {
          emptyState.hidden = true;
        }

        table.hidden = rows.length === 0 || shown === 0;
      }

      searchInput.addEventListener('input', filterOrders);

      document.getElementById('toggle-density').addEventListener('click', function() {
        const compact = this.getAttribute('aria-pressed') !== 'true';

        this.setAttribute('aria-pressed', String(compact));
        table.classList.toggle('is-compact', compact);
        this.innerHTML = compact ?
          '<i class="bi bi-list"></i> Comfortable' :
          '<i class="bi bi-list"></i> Compact';
      });

      document.querySelectorAll('.serve-order-form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
          const button = form.querySelector('button[type="submit"]');

          if (button.disabled) {
            event.preventDefault();
            return;
          }

          // Tidak memakai dialog/konfirmasi; langsung kirim pembaruan.
          button.disabled = true;
          button.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
        });
      });

      filterOrders();
    })();
  </script>
</body>

</html>