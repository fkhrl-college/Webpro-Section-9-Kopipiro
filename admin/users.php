<?php
include '../config.php';
include '../auth.php';
require_admin();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
  <style>
    /* Data table toolbar: search, a columns menu (<details>), a density toggle (aria-pressed), export,
   and removable filter chips. The script filters rows, shows and hides columns, and toggles density. */
    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0
    }

    .tb-title {
      font-size: 1.15rem;
      font-weight: 700;
      letter-spacing: -.01em;
      margin: 0 0 4px
    }

    .tb-sub {
      color: #6b717d;
      font-size: .87rem;
      margin: 0 0 22px;
      line-height: 1.6
    }

    .tb svg {
      display: block
    }

    .tb :focus-visible {
      outline: 2px solid #4f46e5;
      outline-offset: 2px
    }

    .tb {
      position: relative;
      background: #fff;
      color: #16181c;
      border: 1px solid #e7e9f0;
      border-radius: 14px;
      overflow: hidden
    }

    .tb-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 13px 14px;
      flex-wrap: wrap
    }

    .tb-search {
      flex: 1;
      min-width: 180px;
      position: relative
    }

    .tb-search svg {
      position: absolute;
      left: 11px;
      top: 50%;
      transform: translateY(-50%);
      width: 15px;
      height: 15px;
      stroke: #667085;
      fill: none;
      stroke-width: 2
    }

    .tb-search input {
      width: 100%;
      padding: 9px 12px 9px 34px;
      border: 1px solid #8a94a6;
      border-radius: 9px;
      font: inherit;
      font-size: .87rem;
      color: inherit;
      background: #fff
    }

    .tb-search input:focus {
      outline: none;
      border-color: #4f46e5;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, .25)
    }

    .tb-tool {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      border: 1px solid #e4e7ec;
      background: #fff;
      border-radius: 9px;
      padding: 8px 13px;
      font: inherit;
      font-size: .85rem;
      font-weight: 600;
      color: #475467;
      cursor: pointer
    }

    .tb-tool:hover {
      background: #f7f8fa;
      color: #101828
    }

    .tb-tool[aria-pressed="true"] {
      background: #eef2ff;
      border-color: #c7d2fe;
      color: #3730a3
    }

    .tb-tool svg {
      width: 14px;
      height: 14px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2
    }

    .tb-menu {
      position: relative
    }

    .tb-menu>summary {
      list-style: none
    }

    .tb-menu>summary::-webkit-details-marker {
      display: none
    }

    .tb-pop {
      position: absolute;
      top: calc(100% + 6px);
      right: 0;
      background: #fff;
      border: 1px solid #e4e7ec;
      border-radius: 11px;
      padding: 7px;
      min-width: 190px;
      z-index: 5;
      box-shadow: 0 16px 34px -18px rgba(16, 24, 40, .3)
    }

    @media (max-width:480px) {
      .tb-pop {
        right: auto;
        left: 0
      }
    }

    .tb-pop label {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 10px;
      border-radius: 8px;
      font-size: .86rem;
      color: #344054;
      cursor: pointer
    }

    .tb-pop label:hover {
      background: #f5f6fa
    }

    .tb-pop input {
      accent-color: #4f46e5
    }

    .tb-chips {
      display: flex;
      gap: 7px;
      padding: 0 14px 13px;
      flex-wrap: wrap
    }

    .tb-chips[hidden] {
      display: none
    }

    .tb-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 5px 7px 5px 12px;
      border-radius: 999px;
      font-size: .79rem;
      font-weight: 600;
      background: #eef2ff;
      color: #3730a3
    }

    .tb-chip button {
      border: 0;
      background: rgba(55, 48, 163, .13);
      color: #3730a3;
      width: 17px;
      height: 17px;
      border-radius: 50%;
      cursor: pointer;
      display: grid;
      place-items: center;
      padding: 0
    }

    .tb-chip button:hover {
      background: rgba(55, 48, 163, .26)
    }

    .tb-chip svg {
      width: 8px;
      height: 8px;
      stroke: currentColor;
      fill: none;
      stroke-width: 3.4
    }

    .tb-clear {
      font: inherit;
      font-size: .79rem;
      font-weight: 700;
      color: #667085;
      background: none;
      border: 0;
      cursor: pointer;
      padding: 5px 8px;
      border-radius: 7px
    }

    .tb-clear:hover {
      background: #f2f4f7;
      color: #101828
    }

    .tb-scroll {
      position: relative;
      overflow-x: auto
    }

    .tb .tb-scroll:focus-visible {
      outline-offset: -2px
    }

    .tb-table {
      width: 100%;
      border-collapse: collapse;
      font-size: .89rem
    }

    .tb-table th,
    .tb-table td {
      text-align: left
    }

    .tb-table thead th {
      font-size: .74rem;
      letter-spacing: .06em;
      text-transform: uppercase;
      color: #667085;
      font-weight: 700;
      padding: 13px 16px;
      background: #fafbfc;
      border-top: 1px solid #eceef3;
      border-bottom: 1px solid #eceef3;
      white-space: nowrap
    }

    .tb-table tbody th,
    .tb-table td {
      padding: 13px 16px;
      border-bottom: 1px solid #f2f4f7;
      color: #3c4048;
      font-weight: 400;
      white-space: nowrap
    }

    .tb-table tbody tr:last-child>* {
      border-bottom: 0
    }

    .tb-table tbody tr:hover {
      background: #fafbfd
    }

    .tb-table .num {
      font-variant-numeric: tabular-nums;
      text-align: right
    }

    .tb-table[data-dense] tbody th,
    .tb-table[data-dense] td {
      padding: 7px 16px
    }

    .tb-table[data-dense] thead th {
      padding: 8px 16px
    }

    .tb-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: .75rem;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 999px
    }

    .tb-pill.ok {
      background: #e8f8ee;
      color: #15803d
    }

    .tb-pill.warn {
      background: #fff7e6;
      color: #b45309
    }
  </style>
</head>

<body>
  <?php $hideNavbarFilter = true;
  include '../navbar.php' ?>
  <div class="container" style="padding-top: 10vh !important;">
    <h2 class="tb-title" id="tb-title">Data table toolbar</h2>
    <p class="tb-sub">Try the search, the density toggle and the columns menu. The chips below the bar are the filters already applied — each removable on its own.</p>
    <section class="tb" data-tb aria-labelledby="tb-title">
      <div class="tb-bar">
        <label class="tb-search">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="6" />
            <path d="M20 20l-4.3-4.3" />
          </svg>
          <input type="search" data-tb-search placeholder="Search invoices…" aria-label="Search invoices">
        </label>
        <details class="tb-menu" data-tb-menu>
          <summary class="tb-tool"><svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M4 6h16M7 12h10M10 18h4" />
            </svg> Columns</summary>
          <div class="tb-pop" role="group" aria-label="Visible columns">
            <label><input type="checkbox" data-col="0" checked> Client</label>
            <label><input type="checkbox" data-col="1" checked> Status</label>
            <label><input type="checkbox" data-col="2" checked> Issued</label>
            <label><input type="checkbox" data-col="3"> Due date</label>
            <label><input type="checkbox" data-col="4" checked> Amount</label>
          </div>
        </details>
        <button class="tb-tool" type="button" data-tb-density aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16" />
          </svg> Compact</button>
        <button class="tb-tool" type="button"><svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 4v11M7 11l5 5 5-5M5 20h14" />
          </svg> Export</button>
      </div>
      <div class="tb-chips" data-tb-chips>
        <span class="tb-chip">Status: Paid <button type="button" data-tb-remove aria-label="Remove filter: Status is Paid"><svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" />
            </svg></button></span>
        <span class="tb-chip">Last 30 days <button type="button" data-tb-remove aria-label="Remove filter: Last 30 days"><svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" />
            </svg></button></span>
        <button class="tb-clear" type="button" data-tb-clear>Clear all</button>
      </div>
      <p class="sr-only" role="status" data-tb-status></p>
      <div class="tb-scroll" role="region" tabindex="0" aria-label="Invoices, scrollable">
        <table class="tb-table" data-tb-table aria-labelledby="tb-title">
          <thead>
            <tr>
              <th scope="col">Client</th>
              <th scope="col">Status</th>
              <th scope="col">Issued</th>
              <th scope="col" hidden>Due date</th>
              <th scope="col" class="num">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th scope="row">Meridian Ltd</th>
              <td><span class="tb-pill ok">Paid</span></td>
              <td>12 Aug 2026</td>
              <td hidden>26 Aug 2026</td>
              <td class="num">$584.40</td>
            </tr>
            <tr>
              <th scope="row">Halcyon Studio</th>
              <td><span class="tb-pill ok">Paid</span></td>
              <td>09 Aug 2026</td>
              <td hidden>23 Aug 2026</td>
              <td class="num">$1,220.00</td>
            </tr>
            <tr>
              <th scope="row">Northwind Co</th>
              <td><span class="tb-pill warn">Pending</span></td>
              <td>04 Aug 2026</td>
              <td hidden>18 Aug 2026</td>
              <td class="num">$318.75</td>
            </tr>
            <tr>
              <th scope="row">Fieldnote</th>
              <td><span class="tb-pill ok">Paid</span></td>
              <td>28 Jul 2026</td>
              <td hidden>11 Aug 2026</td>
              <td class="num">$2,940.00</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
  <script>
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>

</html>