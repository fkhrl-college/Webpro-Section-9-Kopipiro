<?php
include '../config.php';
include '../auth.php';
global $conn;

require_admin();

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

function h(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$flash = null;

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
 * Admin hanya melihat data. Tidak ada aksi untuk mengubah status pesanan.
 * Asumsi kolom tabel users adalah id dan username.
 */

// Ambil semua pesanan beserta username customer dan kasir.
$orders = [];
$ordersError = null;

try {
  $sql = "
    SELECT
      o.id,
      o.user_id,
      o.cashier_id,
      o.total_price,
      o.checkout_at,
      o.served_at,
      o.is_serve,
      customer.username AS customer_username,
      cashier.username AS cashier_username
    FROM orders AS o
    LEFT JOIN users AS customer ON customer.id = o.user_id
    LEFT JOIN users AS cashier ON cashier.id = o.cashier_id
    ORDER BY o.checkout_at DESC, o.id DESC
  ";

  $result = mysqli_query($conn, $sql);

  while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
  }

  mysqli_free_result($result);
} catch (Throwable $error) {
  $ordersError = 'Data pesanan gagal dimuat. Periksa struktur tabel orders dan users.';
}

// Ambil omzet per tanggal untuk 7 hari terakhir, termasuk hari ini.
// Hari tanpa transaksi akan ditampilkan sebagai Rp0 pada chart.
$dailySales = [];
$dailySalesError = null;

try {
  $salesByDate = [];
  $sqlSales = "
    SELECT
      DATE(checkout_at) AS sales_date,
      COALESCE(SUM(total_price), 0) AS daily_total,
      COUNT(*) AS order_count
    FROM orders
    WHERE checkout_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      AND checkout_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    GROUP BY DATE(checkout_at)
    ORDER BY sales_date ASC
  ";

  $salesResult = mysqli_query($conn, $sqlSales);

  while ($row = mysqli_fetch_assoc($salesResult)) {
    $salesByDate[$row['sales_date']] = [
      'total' => (int) $row['daily_total'],
      'count' => (int) $row['order_count']
    ];
  }

  mysqli_free_result($salesResult);

  $timezone = new DateTimeZone(date_default_timezone_get());
  $today = new DateTimeImmutable('today', $timezone);

  for ($i = 6; $i >= 0; $i--) {
    $date = $today->modify("-{$i} days");
    $dateKey = $date->format('Y-m-d');

    $dailySales[] = [
      'date' => $dateKey,
      'label' => $date->format('d M'),
      'total' => $salesByDate[$dateKey]['total'] ?? 0,
      'count' => $salesByDate[$dateKey]['count'] ?? 0
    ];
  }
} catch (Throwable $error) {
  $dailySalesError = 'Data grafik penjualan gagal dimuat.';
}

$totalOrders = count($orders);
$totalRevenue = 0;
$pendingOrders = 0;
$servedOrders = 0;

foreach ($orders as $order) {
  $totalRevenue += (int) $order['total_price'];

  if ((int) $order['is_serve'] === 1) {
    $servedOrders++;
  } else {
    $pendingOrders++;
  }
}

$chartLabels = array_column($dailySales, 'label');
$chartTotals = array_column($dailySales, 'total');
$chartCounts = array_column($dailySales, 'count');
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Pesanan | Kopipiro Admin</title>

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

    .panel-card {
      overflow: hidden;
      margin-bottom: 22px;
      border: 1px solid #e7e9f0;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 5px 18px rgba(16, 24, 40, .04);
    }

    .panel-heading {
      padding: 17px 18px;
      border-bottom: 1px solid #eceef3;
    }

    .panel-heading h2 {
      margin: 0;
      font-size: 1rem;
      font-weight: 700;
    }

    .panel-heading p {
      margin: 5px 0 0;
      color: #667085;
      font-size: .84rem;
    }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 22px;
    }

    .summary-card {
      padding: 18px;
      border: 1px solid #e7e9f0;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 5px 18px rgba(16, 24, 40, .04);
    }

    .summary-label {
      color: #667085;
      font-size: .82rem;
      font-weight: 600;
    }

    .summary-value {
      margin-top: 7px;
      font-size: 1.35rem;
      font-weight: 750;
      overflow-wrap: anywhere;
    }

    .summary-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      margin-bottom: 12px;
      border-radius: 10px;
      background: #eef2ff;
      color: #4338ca;
      font-size: 1.05rem;
    }

    .summary-card:nth-child(2) .summary-icon {
      background: #ecfdf3;
      color: #027a48;
    }

    .summary-card:nth-child(3) .summary-icon {
      background: #fff7e6;
      color: #b45309;
    }

    .chart-body {
      padding: 18px;
      height: 320px;
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

    .btn-export {
      border-color: #027a48;
      background: #027a48;
      color: #fff;
    }

    .btn-export:hover {
      border-color: #05603a;
      background: #05603a;
      color: #fff;
    }

    .orders-table {
      margin-bottom: 0;
      font-size: .88rem;
    }

    .orders-table thead th {
      padding: 14px 16px;
      border-top: 1px solid #eceef3;
      border-bottom: 1px solid #eceef3;
      background: #fafbfc;
      color: #667085;
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .05em;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .orders-table tbody td {
      padding: 13px 16px;
      border-top: 1px solid #f2f4f7;
      color: #3c4048;
      vertical-align: middle;
      white-space: nowrap;
    }

    .orders-table tbody tr:hover {
      background: #fafbfd;
    }

    .order-id {
      color: #344054;
      font-weight: 700;
    }

    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 999px;
      font-size: .75rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .status-served {
      background: #ecfdf3;
      color: #027a48;
    }

    .status-pending {
      background: #fff7e6;
      color: #b45309;
    }

    .table-footer {
      padding: 12px 16px;
      border-top: 1px solid #eceef3;
      color: #667085;
      font-size: .84rem;
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

    .orders-table.is-compact .status-badge {
      padding: 3px 8px;
      font-size: .7rem;
    }

    @media (max-width: 767.98px) {
      .orders-page {
        padding-top: 7vh;
      }

      .summary-grid {
        grid-template-columns: 1fr;
      }

      .chart-body {
        height: 280px;
      }
    }

    @media (max-width: 575.98px) {
      .table-toolbar {
        align-items: stretch;
      }

      .order-search {
        flex-basis: 100%;
      }

      .table-toolbar .btn-toolbar-custom {
        flex: 1;
      }
    }
  </style>
</head>

<body>
  <?php
  $hideNavbarFilter = true;
  $navbarIndex = "../index.php";
  $navbarLogout = "../logout.php";
  $navbarProducts = "products.php";
  $navbarOrders = "#";
  include '../navbar.php';
  ?>

  <main class="container orders-page">
    <div class="mb-2">
      <h1 class="page-title">Data Pesanan</h1>
      <p class="page-subtitle">Pantau seluruh transaksi dan ringkasan penjualan Kopipiro. Admin hanya memiliki akses untuk melihat data.</p>
    </div>

    <?php if ($ordersError): ?>
      <div class="alert alert-danger" role="alert"><?= h($ordersError) ?></div>
    <?php endif; ?>

    <?php if ($dailySalesError): ?>
      <div class="alert alert-warning" role="alert"><?= h($dailySalesError) ?></div>
    <?php endif; ?>

    <section class="summary-grid" aria-label="Ringkasan pesanan">
      <div class="summary-card">
        <span class="summary-icon"><i class="bi bi-receipt"></i></span>
        <div class="summary-label">Total Pesanan</div>
        <div class="summary-value"><?= number_format($totalOrders, 0, ',', '.') ?></div>
      </div>
      <div class="summary-card">
        <span class="summary-icon"><i class="bi bi-cash-stack"></i></span>
        <div class="summary-label">Total Penjualan</div>
        <div class="summary-value">Rp<?= number_format($totalRevenue, 0, ',', '.') ?></div>
      </div>
      <div class="summary-card">
        <span class="summary-icon"><i class="bi bi-hourglass-split"></i></span>
        <div class="summary-label">Belum Dilayani</div>
        <div class="summary-value"><?= number_format($pendingOrders, 0, ',', '.') ?></div>
      </div>
    </section>

    <section class="panel-card">
      <div class="panel-heading">
        <h2><i class="bi bi-graph-up-arrow mr-2"></i>Penjualan 7 Hari Terakhir</h2>
        <p>Total nilai transaksi berdasarkan tanggal checkout, termasuk hari ini.</p>
      </div>
      <div class="chart-body">
        <canvas id="sales-chart" aria-label="Grafik penjualan harian selama tujuh hari terakhir" role="img"></canvas>
      </div>
    </section>

    <section class="panel-card">
      <div class="table-toolbar">
        <label class="order-search mb-0">
          <i class="bi bi-search" aria-hidden="true"></i>
          <input type="search" id="order-search" placeholder="Cari ID, customer, kasir, status, atau total..." aria-label="Cari pesanan">
        </label>

        <button type="button" class="btn-toolbar-custom" id="toggle-density" aria-pressed="false">
          <i class="bi bi-list"></i>
          Compact
        </button>

        <button type="button" class="btn-toolbar-custom btn-export" id="export-xlsx">
          <i class="bi bi-file-earmark-excel"></i>
          Download .xlsx
        </button>
      </div>

      <div class="table-responsive">
        <table class="table orders-table" id="orders-table">
          <thead>
            <tr>
              <th scope="col">Order ID</th>
              <th scope="col">Customer</th>
              <th scope="col">Kasir</th>
              <th scope="col">Total</th>
              <th scope="col">Checkout</th>
              <th scope="col">Dilayani</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody id="orders-tbody">
            <?php foreach ($orders as $order): ?>
              <tr>
                <td class="order-id">#<?= (int) $order['id'] ?></td>
                <td><?= $order['user_id'] === null ? 'Guest' : h((string) ($order['customer_username'] ?? 'User #' . $order['user_id'])) ?></td>
                <td><?= $order['cashier_id'] === null ? 'Belum ditentukan' : h((string) ($order['cashier_username'] ?? 'Kasir #' . $order['cashier_id'])) ?></td>
                <td data-order-total="<?= (int) $order['total_price'] ?>">Rp<?= number_format((int) $order['total_price'], 0, ',', '.') ?></td>
                <td><?= $order['checkout_at'] ? h(date('d/m/Y H:i', strtotime($order['checkout_at']))) : '-' ?></td>
                <td><?= $order['served_at'] ? h(date('d/m/Y H:i', strtotime($order['served_at']))) : '-' ?></td>
                <td>
                  <?php if ((int) $order['is_serve'] === 1): ?>
                    <span class="status-badge status-served">Sudah dilayani</span>
                  <?php else: ?>
                    <span class="status-badge status-pending">Belum dilayani</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="empty-state" id="empty-state" <?= count($orders) > 0 ? 'hidden' : '' ?>>
          <i class="bi bi-receipt-cutoff"></i>
          <strong id="empty-state-title">Belum ada pesanan</strong>
          <div id="empty-state-description">Data pesanan akan muncul di sini setelah ada transaksi.</div>
        </div>
      </div>

      <div class="table-footer">
        Menampilkan <strong id="visible-count"><?= count($orders) ?></strong> dari
        <strong id="total-count"><?= count($orders) ?></strong> pesanan
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

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
          if (matches) shown++;
        });

        visibleCount.textContent = shown;
        totalCount.textContent = rows.length;

        if (shown === 0) {
          emptyState.hidden = false;

          if (rows.length === 0) {
            emptyTitle.textContent = 'Belum ada pesanan';
            emptyDescription.textContent = 'Data pesanan akan muncul di sini setelah ada transaksi.';
          } else {
            emptyTitle.textContent = 'Pesanan tidak ditemukan';
            emptyDescription.textContent = 'Coba gunakan kata kunci pencarian lain.';
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

      document.getElementById('export-xlsx').addEventListener('click', function() {
        if (typeof XLSX === 'undefined') {
          alert('Library Excel gagal dimuat. Periksa koneksi internet lalu coba lagi.');
          return;
        }

        // Ekspor seluruh data pesanan yang ada di tabel, bukan hanya hasil filter pencarian.
        const exportRows = [
          ['Order ID', 'Customer', 'Kasir', 'Total (Rp)', 'Checkout', 'Dilayani', 'Status']
        ];

        rows.forEach(function(row) {
          const cells = row.cells;
          const totalCell = cells[3];
          const total = Number(totalCell.getAttribute('data-order-total') || 0);
          const status = cells[6].textContent.trim();

          exportRows.push([
            cells[0].textContent.trim().replace(/^#/, ''),
            cells[1].textContent.trim(),
            cells[2].textContent.trim(),
            total,
            cells[4].textContent.trim(),
            cells[5].textContent.trim(),
            status
          ]);
        });

        const worksheet = XLSX.utils.aoa_to_sheet(exportRows);
        worksheet['!cols'] = [{
            wch: 12
          }, {
            wch: 24
          }, {
            wch: 24
          }, {
            wch: 18
          },
          {
            wch: 22
          }, {
            wch: 22
          }, {
            wch: 20
          }
        ];

        // Kolom total tetap numerik di file Excel agar dapat dihitung kembali.
        for (let rowIndex = 1; rowIndex < exportRows.length; rowIndex++) {
          const cellAddress = XLSX.utils.encode_cell({
            r: rowIndex,
            c: 3
          });
          if (worksheet[cellAddress]) {
            worksheet[cellAddress].t = 'n';
            worksheet[cellAddress].z = '#,##0';
          }
        }

        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Data Pesanan');
        XLSX.writeFile(workbook, 'kopipiro-data-pesanan.xlsx');
      });

      filterOrders();

      const chartCanvas = document.getElementById('sales-chart');
      const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      const totals = <?= json_encode($chartTotals) ?>;
      const counts = <?= json_encode($chartCounts) ?>;

      if (typeof Chart !== 'undefined' && chartCanvas) {
        new Chart(chartCanvas, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Penjualan (Rp)',
              data: totals,
              yAxisID: 'y',
              borderWidth: 1,
              borderRadius: 6
            }, {
              label: 'Jumlah pesanan',
              data: counts,
              type: 'line',
              yAxisID: 'y1',
              borderWidth: 2,
              tension: 0.3
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
              mode: 'index',
              intersect: false
            },
            plugins: {
              legend: {
                position: 'bottom'
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    if (context.dataset.yAxisID === 'y1') {
                      return context.dataset.label + ': ' + context.parsed.y + ' pesanan';
                    }

                    return context.dataset.label + ': Rp' +
                      new Intl.NumberFormat('id-ID').format(context.parsed.y);
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Penjualan (Rp)'
                },
                ticks: {
                  callback: function(value) {
                    return 'Rp' + new Intl.NumberFormat('id-ID', {
                      notation: 'compact',
                      maximumFractionDigits: 1
                    }).format(value);
                  }
                }
              },
              y1: {
                beginAtZero: true,
                position: 'right',
                grid: {
                  drawOnChartArea: false
                },
                title: {
                  display: true,
                  text: 'Jumlah pesanan'
                },
                ticks: {
                  precision: 0
                }
              }
            }
          }
        });
      }
    })();
  </script>
</body>

</html>