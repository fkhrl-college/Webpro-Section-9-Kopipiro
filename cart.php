<?php
include 'auth.php';
include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    @import url("https://use.fontawesome.com/releases/v5.13.0/css/all.css");

    .cart-wrapper {
      background-color: #f8f9fa;
      min-height: 100vh;
      padding: 40px 0;
    }

    .product-card {
      background: white;
      border-radius: 12px;
      transition: transform 0.2s;
    }

    .product-card:hover {
      transform: translateY(-2px);
    }

    .quantity-input {
      width: 60px;
      text-align: center;
      border: 1px solid #dee2e6;
      border-radius: 6px;
    }

    .product-image {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 8px;
    }

    .summary-card {
      background: white;
      border-radius: 12px;
      position: sticky;
      top: 20px;
    }

    .checkout-btn {
      background: linear-gradient(135deg, #6366f1, #4f46e5);
      border: none;
      transition: transform 0.2s;
    }

    .checkout-btn:hover {
      transform: translateY(-2px);
      background: linear-gradient(135deg, #4f46e5, #4338ca);
    }

    .remove-btn {
      color: #dc2626;
      cursor: pointer;
      transition: all 0.2s;
    }

    .remove-btn:hover {
      color: #991b1b;
    }

    .quantity-btn {
      width: 28px;
      height: 28px;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      background: #f3f4f6;
      border: none;
      transition: all 0.2s;
    }

    .quantity-btn:hover {
      background: #e5e7eb;
    }

    .discount-badge {
      background: #dcfce7;
      color: #166534;
      font-size: 0.875rem;
      padding: 4px 8px;
      border-radius: 6px;
    }

    .cart-item {
      background: white;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 16px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .cart-product-image {
      width: 90px;
      height: 90px;
      object-fit: contain;
      border-radius: 8px;
    }

    .cart-quantity {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .cart-quantity button {
      width: 30px;
      height: 30px;
      border: none;
      border-radius: 6px;
      background: #f3f4f6;
    }

    .cart-remove {
      color: #dc3545;
      cursor: pointer;
    }

    .summary-card {
      position: sticky;
      top: 20px;
      background: white;
      border-radius: 12px;
    }

    @media (max-width: 767.98px) {
      .cart-product-image {
        width: 72px;
        height: 72px;
      }

      .summary-card {
        margin-top: 20px;
      }
    }

    .disabled {
      cursor: not-allowed;
    }

    .disabled.checkout-btn:hover {
      transform: translateY(0px);
      background: linear-gradient(135deg, #6366f1, #4f46e5);
    }
  </style>
</head>

<body>
  <?php include 'navbar.php' ?>
  <div class="cart-wrapper" style="padding-top: 10vh !important;">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Keranjang Belanja</h4>
            <span class="text-muted" id="cart-item-count">0 barang</span>
          </div>

          <div id="cart-items" class="d-flex flex-column">
            <div class="text-center text-muted py-5">
              Memuat keranjang...
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="summary-card p-4 shadow-sm">
            <h5 class="mb-4">Ringkasan Pesanan</h5>

            <div class="d-flex justify-content-between mb-3">
              <span class="text-muted">Subtotal</span>
              <span id="cart-subtotal">Rp0</span>
            </div>

            <div class="d-flex justify-content-between mb-3">
              <span class="text-muted">Diskon</span>
              <span class="text-success" id="cart-discount">-Rp0</span>
            </div>

            <hr>

            <div class="d-flex justify-content-between mb-4">
              <strong>Total</strong>
              <strong id="cart-total">Rp0</strong>
            </div>

            <button
              type="button"
              class="btn btn-primary checkout-btn btn-block cursor disabled"
              id="checkout-button"
              disabled>
              Lanjut ke Checkout
            </button>

            <div class="text-center mt-3">
              <small class="text-muted">
                <i class="fas fa-shield-alt text-success mr-1"></i>
                Belanja dengan nyaman di Kopipiro
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function updateQuantity(productId, change) {
      const input = event.target.parentElement.querySelector('.quantity-input');
      let value = parseInt(input.value) + change;
      if (value >= 1) {
        input.value = value;
      }
    }
  </script>
  <script>
    window.cartLoggedIn = <?= is_logged_in() ? 'true' : 'false' ?>;
  </script>

  <script>
    function getGuestCart() {
      try {
        const cart = JSON.parse(localStorage.getItem('kopipiro_cart') || '[]');
        return Array.isArray(cart) ? cart : [];
      } catch {
        return [];
      }
    }

    function saveGuestCart(cart) {
      localStorage.setItem('kopipiro_cart', JSON.stringify(cart));
    }

    function formatRupiah(value) {
      return 'Rp' + Number(value).toLocaleString('id-ID');
    }

    function escapeHtml(value) {
      return String(value).replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      })[character]);
    }

    function updateCartBadge(count) {
      document.querySelectorAll('.cart-count').forEach((badge) => {
        badge.textContent = count;
      });
    }

    async function getCartData() {
      const options = {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        }
      };

      if (!window.cartLoggedIn) {
        options.body = JSON.stringify({
          items: getGuestCart()
        });
      }

      const response = await fetch('api/cart-data.php', options);
      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Gagal memuat keranjang');
      }

      return data;
    }

    function renderCart(data) {
      const container = document.getElementById('cart-items');
      const items = data.items;

      document.getElementById('cart-item-count').textContent =
        `${data.cart_count} barang`;

      document.getElementById('cart-subtotal').textContent =
        formatRupiah(data.subtotal);

      document.getElementById('cart-discount').textContent =
        '-' + formatRupiah(data.discount);

      document.getElementById('cart-total').textContent =
        formatRupiah(data.total);

      updateCheckoutButton(items.length);

      updateCartBadge(data.cart_count);

      if (items.length === 0) {
        container.innerHTML = `
        <div class="text-center bg-white rounded p-5 shadow-sm">
          <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
          <h5>Keranjang masih kosong</h5>
          <p class="text-muted">Yuk, pilih kopi favoritmu!</p>
          <a href="index.php" class="btn btn-warning">Lihat Produk</a>
        </div>
      `;
        return;
      }

      container.innerHTML = items.map((item) => `
      <div class="cart-item">
        <div class="row align-items-center">
          <div class="col-3 col-md-2">
            <img
              src="${escapeHtml(item.url_image)}"
              alt="${escapeHtml(item.name)}"
              class="cart-product-image"
            >
          </div>

          <div class="col-9 col-md-4">
            <h6 class="mb-1">${escapeHtml(item.name)}</h6>
            <small class="text-muted">${escapeHtml(item.category)}</small>

            <div class="mt-2">
              ${item.unit_price < item.price
                ? `<small class="text-muted"><del>${formatRupiah(item.price)}</del></small><br>`
                : ''
              }
              <strong>${formatRupiah(item.unit_price)}</strong>
            </div>
          </div>

          <div class="col-7 col-md-3 mt-3 mt-md-0">
            <div class="cart-quantity">
              <button
                type="button"
                data-action="remove"
                data-id="${item.product_id}"
                aria-label="Kurangi jumlah"
              >−</button>

              <span>${item.quantity}</span>

              <button
                type="button"
                data-action="add"
                data-id="${item.product_id}"
                aria-label="Tambah jumlah"
              >+</button>
            </div>
          </div>

          <div class="col-4 col-md-2 text-right mt-3 mt-md-0">
            <strong>${formatRupiah(item.item_total)}</strong>
          </div>

          <div class="col-1 text-right mt-3 mt-md-0">
            <button
              type="button"
              class="btn btn-link p-0 cart-remove"
              data-action="delete"
              data-id="${item.product_id}"
              aria-label="Hapus produk"
            >
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    `).join('');
    }

    async function changeCart(productId, action) {
      if (window.cartLoggedIn) {
        let endpoint = "";
        if (action === "add") {
          endpoint = "api/cart-add.php";
        } else if (action === "remove") {
          endpoint = "api/cart-remove.php";
        } else if (action === "delete") {
          endpoint = "api/cart-delete.php";
        } else {
          return;
        }

        const response = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            product_id: productId
          })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Gagal mengubah keranjang');
        }
      } else {
        let cart = getGuestCart();
        const item = cart.find(
          (entry) => Number(entry.product_id) === productId
        );

        if (action === 'add') {
          if (item) {
            item.quantity = Math.min(Number(item.quantity) + 1, 99);
          } else {
            cart.push({
              product_id: productId,
              quantity: 1
            });
          }
        } else if (action === 'remove' && item) {
          item.quantity--;

          if (item.quantity <= 0) {
            cart = cart.filter(
              (entry) => Number(entry.product_id) !== productId
            );
          }
        } else if (action === 'delete') {
          cart = cart.filter(
            (entry) => Number(entry.product_id) !== productId
          );
        }

        saveGuestCart(cart);
      }

      await loadCart();
    }

    async function loadCart() {
      const container = document.getElementById('cart-items');

      try {
        const data = await getCartData();
        renderCart(data);
      } catch (error) {
        container.innerHTML = `
        <div class="alert alert-danger">
          ${escapeHtml(error.message)}
        </div>
      `;
      }
    }

    document.getElementById('cart-items').addEventListener('click', async (event) => {
      const button = event.target.closest('[data-action]');

      if (!button) {
        return;
      }

      const productId = Number(button.dataset.id);
      const action = button.dataset.action;

      if (!Number.isInteger(productId) || productId < 1) {
        return;
      }

      const checkoutButton = document.getElementById('checkout-button');

      button.disabled = true;
      checkoutButton.disabled = true;
      checkoutButton.classList.add('disabled');

      try {
        await changeCart(productId, action);
      } catch (error) {
        alert(error.message);
      } finally {
        button.disabled = false;
        const cartCount = Number(
          document.getElementById('cart-item-count').textContent
          .replace(' barang', '')
        );

        updateCheckoutButton(cartCount);
      }
    });

    function updateCheckoutButton(itemCount) {
      const checkoutButton = document.getElementById('checkout-button');
      const isEmpty = itemCount === 0;

      checkoutButton.disabled = isEmpty;
      checkoutButton.classList.toggle('disabled', isEmpty);
    }

    // logika checkout
    async function clearCart() {
      const checkoutButton = document.getElementById('checkout-button');
      const cartItems = document.getElementById('cart-items');

      if (checkoutButton.disabled) {
        return;
      }

      if (!confirm('Apakah kamu yakin ingin mengosongkan keranjang?')) {
        return;
      }

      checkoutButton.disabled = true;
      checkoutButton.classList.add('disabled');

      try {
        if (window.cartLoggedIn) {
          // Ambil semua barang dari keranjang pengguna.
          const response = await fetch('api/cart-data.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            }
          });

          const data = await response.json();

          if (!response.ok || !data.success) {
            throw new Error(data.message || 'Gagal mengambil data keranjang.');
          }

          // Hapus setiap barang satu per satu.
          const failedItems = [];

          for (const item of data.items) {
            try {
              const deleteResponse = await fetch('api/cart-delete.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                  product_id: Number(item.product_id)
                })
              });

              const deleteData = await deleteResponse.json();

              if (!deleteResponse.ok || !deleteData.success) {
                throw new Error(
                  deleteData.message || 'Gagal menghapus barang.'
                );
              }
            } catch (error) {
              failedItems.push(item.name);
            }
          }

          if (failedItems.length > 0) {
            await loadCart();

            throw new Error(
              `Gagal menghapus ${failedItems.length} barang: ${failedItems.join(', ')}.`
            );
          }
        } else {
          // Hapus seluruh keranjang guest.
          localStorage.removeItem('kopipiro_cart');
        }

        // Reset ringkasan pesanan.
        document.getElementById('cart-item-count').textContent = '0 barang';
        document.getElementById('cart-subtotal').textContent = 'Rp0';
        document.getElementById('cart-discount').textContent = '-Rp0';
        document.getElementById('cart-total').textContent = 'Rp0';

        updateCartBadge(0);

        // Tampilkan notifikasi, bukan tampilan keranjang kosong.
        cartItems.innerHTML = `
      <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle mr-2"></i>
        Keranjang berhasil dikosongkan.
      </div>
    `;
      } catch (error) {
        await loadCart();

        cartItems.insertAdjacentHTML('afterbegin', `
      <div class="alert alert-danger" role="alert">
        ${escapeHtml(error.message || 'Gagal mengosongkan keranjang.')}
      </div>
    `);
      } finally {
        const cartCount = Number(
          document.getElementById('cart-item-count').textContent
          .replace(' barang', '')
        );
        updateCheckoutButton(cartCount);
      }
    }
    document.getElementById('checkout-button').addEventListener('click', clearCart);

    loadCart();
  </script>
</body>

</html>