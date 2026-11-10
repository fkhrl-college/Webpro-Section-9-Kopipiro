const BASE_API_URL = "http://localhost:8079/week-9/api/v1";

const $ = (s) => document.querySelector(s);
const esc = (s) =>
  String(s ?? "").replace(
    /[&<>"']/g,
    (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[
        c
      ],
  );
const rp = (n) => "Rp " + Number(n).toLocaleString("id-ID");
let me = {};
async function api(url, opts = {}) {
  const isForm = opts.body instanceof FormData; // let the browser set multipart headers
  const headers = isForm ? {} : { "Content-Type": "application/json" };
  const res = await fetch(url, { headers, ...opts });
  const data = await res.json();
  if (!res.ok) throw new Error(data.message || "Request failed");
  return data;
}
async function init() {
  me = await api(`${BASE_API_URL}/auth.php?action=me`);
  const staff = ["owner", "cashier"].includes(me.role);
  $("#who").textContent = me.name
    ? me.name + " (" + me.role + ")"
    : "Not logged in";
  $("#loginForm").classList.toggle("hidden", staff);
  $("#ordersBox").classList.toggle("hidden", !staff);
  $("#ownerBox").classList.toggle("hidden", me.role !== "owner");
  if (!staff) return;
  loadOrders();
  if (me.role === "owner") {
    loadCharts();
    loadCategories();
  }
}
async function loadOrders() {
  const rows = await api(`${BASE_API_URL}api/orders.php`);
  $("#ordersTitle").textContent =
    me.role === "owner" ? "All orders" : "My assigned orders";
  $("#rows").innerHTML = rows
    .map(
      (o) => `
      <tr>
        <td>${esc(o.order_code)}</td><td>${esc(o.created_at)}</td><td>${esc(o.customer_name)}</td>
        <td>${esc(o.items)}</td><td>${rp(o.total)}</td><td>${esc(o.cashier ?? "-")}</td>
        <td><select data-id="${o.id}">${["pending", "paid", "done"]
          .map(
            (s) => `<option ${s === o.status ? "selected" : ""}>${s}</option>`,
          )
          .join("")}</select></td>
      </tr>`,
    )
    .join("");
}

$("#rows").addEventListener("change", async (e) => {
  await api(`${BASE_API_URL}/orders.php?id=` + e.target.dataset.id, {
    method: "PUT",
    body: JSON.stringify({ status: e.target.value }),
  });
});
async function loadCharts() {
  const s = await api(`${BASE_API_URL}/sales.php`);
  new Chart($("#c1"), {
    type: "bar",
    data: {
      labels: s.daily.map((r) => r.day),
      datasets: [
        { label: "Revenue (Rp)", data: s.daily.map((r) => +r.revenue) },
      ],
    },
  });
  new Chart($("#c2"), {
    type: "pie",
    data: {
      labels: s.top.map((r) => r.product_name),
      datasets: [{ data: s.top.map((r) => +r.qty) }],
    },
  });
}
async function loadCategories() {
  const d = await api(`${BASE_API_URL}/products.php`);
  $("#catSel").innerHTML = d.categories
    .map((c) => `<option value="${c.id}">${esc(c.name)}</option>`)
    .join("");
}
$("#prodForm").onsubmit = async (e) => {
  e.preventDefault();
  try {
    await api(`${BASE_API_URL}/products.php`, {
      method: "POST",
      body: new FormData(e.target),
    });
    $("#pMsg").textContent = "Product added";
    e.target.reset();
  } catch (err) {
    $("#pMsg").textContent = err.message;
  }
};
$("#loginForm").onsubmit = async (e) => {
  e.preventDefault();
  try {
    await api(`${BASE_API_URL}/auth.php?action=login`, {
      method: "POST",
      body: JSON.stringify({
        email: $("#email").value,
        password: $("#pass").value,
      }),
    });
    init();
  } catch (err) {
    $("#loginMsg").textContent = err.message;
  }
};
init();
