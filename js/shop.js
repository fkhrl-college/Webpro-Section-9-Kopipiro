const BASE_API_URL = "http://localhost:8079/week-9/api/v1";

const $ = (s) => document.querySelector(s);

const esc = (s) =>
  String(s ?? "").replace(
    /[&<>"']/g,
    (c) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      })[c],
  );

const rp = (n) => "Rp " + Number(n).toLocaleString("id-ID");
let me = { role: "guest" },
  category = "",
  cart = {},
  known = {};

async function api(url, opts = {}) {
  const res = await fetch(url, {
    headers: { "Content-Type": "application/json" },
    ...opts,
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.message || "Request failed");
  return data;
}

async function init() {
  me = await api(`${BASE_API_URL}/auth.php?action=me`);
  $("#who").textContent = me.name ? me.name + " (" + me.role + ")" : "Guest";
  $("#btnAuth").textContent = me.name ? "Log out" : "Log in";
  $("#promo").textContent =
    me.role === "customer"
      ? "Member discount active: 10% off every item!"
      : "Register and log in as a customer to get 10% off.";
  await loadProducts();
}

async function loadProducts() {
  const q = category ? "?category=" + encodeURIComponent(category) : "";
  const data = await api(`${BASE_API_URL}/products.php` + q);
  data.products.forEach((p) => (known[p.id] = p)); // remember prices for the cart
  $("#cats").innerHTML = ["", ...data.categories.map((c) => c.name)]
    .map(
      (c) =>
        `<button class="${c === category ? "on" : ""}" data-c="${esc(c)}">${esc(c || "All")}</button>`,
    )
    .join(" ");
  $("#grid").innerHTML = data.products
    .map(
      (p) => `
        <div class="card">
          <img src="${esc(p.image || "")}" alt="">
          <h3>${esc(p.name)}</h3>
          <small>${esc(p.category)} | rating ${p.rating ?? "-"} (${p.reviews})</small>
          <p>${esc((p.description || "").slice(0, 100))}</p>
          <p>${
            p.final_price < p.price
              ? `
            <span class="old">${rp(p.price)}</span>`
              : ""
          }
            <b>${rp(p.final_price)}</b>
          </p>
          <button data-add="${p.id}">Add</button> 
        </div>`,
    )
    .join("");
  renderCart();
}

function renderCart() {
  let total = 0;
  $("#cart").innerHTML = Object.entries(cart)
    .map(([id, qty]) => {
      const p = known[id];
      const line = p.final_price * qty;
      total += line;
      return `
        <li>${qty} x ${esc(p.name)} @ ${rp(p.final_price)} = ${rp(line)}
        <button data-rm="${id}">x</button></li>`;
    })
    .join("");
  $("#total").textContent = rp(total);
}

document.addEventListener("click", async (e) => {
  const d = e.target.dataset;
  if (d.c !== undefined) {
    category = d.c;
    loadProducts();
  }
  if (d.add) {
    cart[d.add] = (cart[d.add] || 0) + 1;
    renderCart();
  }
  if (d.rm) {
    delete cart[d.rm];
    renderCart();
  }
  if (e.target.id === "btnPay") {
    try {
      const r = await api(`${BASE_API_URL}/pay.php`, {
        method: "POST",
        body: JSON.stringify({ order_code: d.code }),
      });
      location.href = r.redirect_url; // Midtrans hosted payment page
    } catch (err) {
      $("#result").textContent = err.message;
    }
  }
});
$("#btnCheckout").onclick = async () => {
  const items = Object.entries(cart).map(([id, qty]) => ({
    product_id: +id,
    qty,
  }));
  try {
    const o = await api(`${BASE_API_URL}/orders.php`, {
      method: "POST",
      body: JSON.stringify({
        items,
        customer_name: $("#guestName").value,
      }),
    });
    cart = {};
    renderCart();
    $("#result").innerHTML = `
      Order <b>${esc(o.order_code)}</b><br>
      Subtotal ${rp(o.subtotal)}<br>
      Discount ${rp(o.discount)}<br>
      Total <b>${rp(o.total)}</b><br>
      <button id="btnPay" data-code="${esc(o.order_code)}">
        Pay online (sandbox)  
      </button>`;
  } catch (err) {
    $("#result").textContent = err.message;
  }
};

async function auth(action) {
  try {
    const r = await api(`${BASE_API_URL}/auth.php?action=` + action, {
      method: "POST",
      body: JSON.stringify({
        name: $("#aName").value,
        email: $("#aEmail").value,
        password: $("#aPass").value,
      }),
    });
    if (action === "login") {
      $("#dlg").close();
      cart = {};
      init();
    } else $("#aMsg").textContent = r.message;
  } catch (err) {
    $("#aMsg").textContent = err.message;
  }
}

$("#btnLogin").onclick = () => auth("login");
$("#btnReg").onclick = () => auth("register");
$("#btnCancel").onclick = () => $("#dlg").close();

$("#btnAuth").onclick = async () => {
  if (me.name) {
    await api(`${BASE_API_URL}/auth.php?action=logout`, { method: "POST" });
    cart = {};
    known = {};
    init();
  } else $("#dlg").showModal();
};

init();
