<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard%20admin/login%20form/admin_login.php?error=Please+login+as+admin');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../dashboard%20admin/login%20form/index.php');
    exit();
}

require_once __DIR__ . '/../dashboard admin/order_store.php';
$serverOrders = loadAllCustomerOrders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panaderia Pilipina | Admin Dashboard</title>
    <style>
        :root {
            --brown: #7c2d12;
            --dark-brown: #431407;
            --gold: #d97706;
            --cream: #fffaf0;
            --ink: #292524;
            --muted: #78716c;
            --line: #f1d9b5;
            --success: #15803d;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff7ed;
            color: var(--ink);
            background-attachment: fixed;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--dark-brown);
            color: #fff;
            padding: 18px 5vw;
        }
        .brand {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: .04em;
        }
        .brand span {
            display: block;
            font-size: .72rem;
            letter-spacing: .16em;
            color: #fbbf24;
            font-weight: 600;
            margin-top: 4px;
        }
        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .admin-pill {
            color: #fff;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.25);
            padding: 8px 12px;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 700;
        }
        .logout-btn {
            color: #fff;
            text-decoration: none;
            border: 1px solid #fbbf24;
            border-radius: 7px;
            padding: 9px 14px;
            font-weight: 700;
        }
        .layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            min-height: calc(100vh - 80px);
        }
        .sidebar {
            background: var(--dark-brown);
            color: #fff;
            padding: 24px 18px;
        }
        .nav-group {
            margin-bottom: 26px;
        }
        .nav-title {
            color: #fbbf24;
            font-size: .72rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: 800;
        }
        .nav-item {
            display: block;
            color: #fff;
            text-decoration: none;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            background: rgba(255,255,255,0.04);
            font-weight: 700;
        }
        .nav-item[hidden] { display: none; }
        .nav-item.active {
            background: rgba(251,191,36,0.18);
            border: 1px solid rgba(251,191,36,0.45);
        }
        .content {
            padding: 26px;
        }
        .welcome {
            margin-bottom: 24px;
        }
        .eyebrow {
            margin: 0 0 6px;
            color: var(--gold);
            font-size: .78rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            font-weight: 800;
        }
        h1 {
            margin: 0;
            color: var(--brown);
            font-size: clamp(2rem, 4vw, 2.7rem);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 16px;
            margin: 24px 0;
            width: 100%;
        }
        .stat-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 20px;
        }
        .stack-list, .payout-list {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }
        .stack-item, .payout-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--line);
            background: var(--cream);
            border-radius: 10px;
            padding: 12px 14px;
        }
        .stack-item strong, .payout-item strong {
            color: var(--brown);
        }
        .stack-value, .payout-value {
            color: var(--muted);
            font-weight: 700;
        }
        .stat-label {
            color: var(--muted);
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
        }
        .stat-value {
            color: var(--brown);
            font-size: 1.8rem;
            font-weight: 800;
            margin-top: 10px;
        }
        .best-seller-list { display: grid; gap: 6px; margin: 12px 0 0; padding: 0; list-style: none; }
        .best-seller-list li { display: flex; justify-content: space-between; gap: 8px; color: var(--brown); font-size: .82rem; font-weight: 700; }
        .best-seller-list li span:last-child { color: var(--muted); white-space: nowrap; }
        .sales-panel { margin-top: 20px; }
        .sales-summary { margin: 0 0 18px; color: var(--muted); }
        .sales-chart { display: flex; align-items: end; gap: 8px; min-height: 280px; padding: 8px 2px 0; overflow-x: auto; border-bottom: 1px solid var(--line); }
        .sales-row { display: grid; flex: 1 0 62px; grid-template-rows: 20px 1fr 42px; align-items: end; gap: 7px; height: 260px; min-width: 62px; }
        .sales-name { display: flex; align-items: start; justify-content: center; color: var(--brown); font-size: .7rem; font-weight: 700; line-height: 1.15; text-align: center; overflow-wrap: anywhere; }
        .sales-track { display: flex; align-items: end; justify-content: center; width: 100%; height: 100%; overflow: hidden; border-radius: 6px 6px 0 0; background: #f5e8d3; }
        .sales-bar { width: 72%; min-height: 0; border-radius: 6px 6px 0 0; background: var(--gold); }
        .sales-row.is-best .sales-bar { background: var(--success); }
        .sales-row.is-lowest .sales-bar { background: #a8a29e; }
        .sales-count { color: var(--muted); font-size: .78rem; font-weight: 700; text-align: center; }
        .content-grid {
            display: grid;
            grid-template-columns: 1.55fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 22px;
        }
        .panel:not([hidden]) {
            grid-column: 1 / -1;
            min-height: 300px;
            width: 100%;
        }
        .panel h2 {
            margin-top: 0;
            color: var(--brown);
            font-size: 1.2rem;
        }
        .orders-heading, .payments-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .orders-heading .cta, .payments-heading .cta {
            margin-top: 0;
            white-space: nowrap;
        }
        .payments-action {
            display: flex;
            justify-content: flex-end;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .94rem;
        }
        th, td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #f5e8d3;
        }
        th {
            color: var(--muted);
            font-size: .74rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: .75rem;
        }
        .status.success {
            background: #ecfdf5;
            color: var(--success);
        }
        .status.pending {
            background: #fff7ed;
            color: var(--gold);
        }
        .status.out-of-stock {
            background: #fef2f2;
            color: var(--danger);
        }
        .product-list {
            display: grid;
            gap: 12px;
            margin-top: 10px;
        }
        .product-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--cream);
        }
        .product-name {
            font-weight: 700;
            color: var(--brown);
        }
        .stock {
            color: var(--muted);
            font-size: .85rem;
        }
        .inventory-table {
            margin-top: 10px;
        }
        .inventory-table td:last-child {
            text-align: right;
        }
        .action-btn {
            border: none;
            border-radius: 8px;
            padding: 7px 10px;
            font-weight: 700;
            cursor: pointer;
        }
        .action-btn.primary {
            background: var(--gold);
            color: #fff;
        }
        .action-btn.secondary {
            background: #fff;
            color: var(--brown);
            border: 1px solid var(--line);
        }
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(41, 37, 36, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1000;
        }
        .modal-backdrop.open {
            display: flex;
        }
        .modal {
            background: #fff;
            border-radius: 16px;
            border: 1px solid var(--line);
            width: min(480px, 100%);
            padding: 24px;
            box-shadow: 0 18px 42px rgba(67, 20, 7, 0.18);
        }
        .modal h3 {
            margin: 0 0 18px;
            color: var(--brown);
            font-size: 1.35rem;
        }
        .form-grid {
            display: grid;
            gap: 14px;
        }
        .field-group {
            display: grid;
            gap: 6px;
        }
        .field-group label {
            color: var(--muted);
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .field-group input,
        .field-group select {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 1rem;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .cta {
            display: inline-block;
            margin-top: 15px;
            background: var(--gold);
            color: #fff;
            padding: 11px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
        }
        @media (max-width: 860px) {
            .stats, .content-grid { grid-template-columns: 1fr; }
            .topbar { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">Panaderia Pilipina<span>Admin Portal</span></div>
        <div class="top-actions">
            <span class="admin-pill">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a class="logout-btn" href="admin_dashboard.php?logout=1">Log Out</a>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <div class="nav-group">
                <div class="nav-title">Menu</div>
                <a class="nav-item active" href="#overview">Overview</a>
                <a class="nav-item" href="#orders">Orders</a>
                <a class="nav-item" href="#stack">Stack</a>
                <a class="nav-item" href="#inventory">Inventory</a>
                <a class="nav-item" id="payments_nav_item" href="#payments">Payments</a>
                <a class="nav-item" href="#reports">Reports</a>
            </div>
        </aside>

        <main class="content">
            <section class="welcome" id="overview">
                <p class="eyebrow">Overview</p>
                <h1>Admin Dashboard</h1>
            </section>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value" id="total_orders_value">128</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Revenue</div>
                    <div class="stat-value" id="revenue_value">₱18.4K</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Best Sellers</div>
                    <ol class="best-seller-list" id="best_seller_value"><li>No sales yet</li></ol>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value" id="pending_value">8</div>
                </div>
            </div>

            <section class="panel sales-panel" aria-labelledby="sales_chart_title">
                <h2 id="sales_chart_title">Bread sales ranking</h2>
                <p class="sales-summary" id="sales_summary">Loading sales data...</p>
                <div class="sales-chart" id="sales_chart" role="list" aria-label="Bread quantities sold"></div>
            </section>

            <div class="content-grid">
                <section class="panel" id="orders" hidden>
                    <div class="orders-heading">
                        <h2>Orders</h2>
                        <a class="cta" id="view_all_orders" href="#orders">View all orders</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Bread Purchased</th>
                                <th>Address</th>
                                <th>Customer Number</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="admin_orders_body">
                            <tr><td colspan="7">No customer orders have been recorded yet.</td></tr>
                        </tbody>
                    </table>
                </section>

                <section class="panel" id="stack" hidden>
                    <h2>Stack</h2>
                    <div class="stack-list" id="stack_list"></div>
                </section>
            </div>

            <div class="content-grid" style="margin-top: 20px;">
                <section class="panel" id="inventory" hidden>
                    <h2>Inventory</h2>
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Bread</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="inventory_table_body"></tbody>
                    </table>
                </section>

                <section class="panel" id="payments" hidden>
                    <div class="payments-heading">
                        <h2>Payments &amp; Payouts</h2>
                    </div>
                    <div class="payout-list">
                        <div class="payout-item">
                            <strong>GCash</strong>
                            <span class="payout-value" id="gcash_payout_value">₱4,200.00</span>
                        </div>
                        <div class="payout-item">
                            <strong>Maya</strong>
                            <span class="payout-value" id="maya_payout_value">₱3,150.00</span>
                        </div>
                        <div class="payout-item">
                            <strong>Cash on Delivery</strong>
                            <span class="payout-value" id="cod_payout_value">₱2,850.00</span>
                        </div>
                        <div class="payout-item">
                            <strong>Net Payout</strong>
                            <span class="payout-value" id="net_payout_value">₱10,200.00</span>
                        </div>
                        <div class="payout-item">
                            <strong>Processing Fees</strong>
                            <span class="payout-value">₱245.00</span>
                        </div>
                        <div class="payout-item">
                            <strong>Next Payout</strong>
                            <span class="payout-value">Sep 30, 2026</span>
                        </div>
                    </div>
                </section>
            </div>
            <section class="panel" id="reports" hidden style="margin-top: 20px;">
                <h2>Reports</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Top Product</th>
                        </tr>
                    </thead>
                    <tbody id="reports_table_body">
                        <tr>
                            <td>Today</td>
                            <td id="report_today_orders">0</td>
                            <td id="report_today_revenue">PHP 0.00</td>
                            <td id="report_today_top">-</td>
                        </tr>
                        <tr>
                            <td>This week</td>
                            <td id="report_week_orders">0</td>
                            <td id="report_week_revenue">PHP 0.00</td>
                            <td id="report_week_top">-</td>
                        </tr>
                        <tr>
                            <td>This month</td>
                            <td id="report_month_orders">0</td>
                            <td id="report_month_revenue">PHP 0.00</td>
                            <td id="report_month_top">-</td>
                        </tr>
                        <tr>
                            <td>Last month</td>
                            <td id="report_last_month_orders">0</td>
                            <td id="report_last_month_revenue">PHP 0.00</td>
                            <td id="report_last_month_top">-</td>
                        </tr>
                        <tr>
                            <td>Average order</td>
                            <td id="report_average_orders">PHP 0.00</td>
                            <td id="report_average_revenue">Customer orders</td>
                            <td id="report_average_top">-</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    <div class="modal-backdrop" id="inventory_modal">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="inventory_modal_title">
            <h3 id="inventory_modal_title">Edit Inventory</h3>
            <form id="inventory_form">
                <div class="form-grid">
                    <div class="field-group">
                        <label for="inventory_name">Bread</label>
                        <input id="inventory_name" name="name" type="text" required>
                    </div>
                    <div class="field-group">
                        <label for="inventory_stock">Stock</label>
                        <input id="inventory_stock" name="stock" type="number" min="0" required>
                    </div>
                    <div class="field-group">
                        <label for="inventory_status">Status</label>
                        <select id="inventory_status" name="status">
                            <option value="Healthy">Healthy</option>
                            <option value="Low">Low</option>
                            <option value="Out of Stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="action-btn secondary" id="cancel_inventory_edit">Cancel</button>
                    <button type="submit" class="action-btn primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const inventoryStorageKey = 'panaderia_inventory';
        const defaultInventory = [
            { name: 'Pandesal', stock: 120, status: 'Healthy' },
            { name: 'Ensaymada', stock: 42, status: 'Low' },
            { name: 'Spanish Bread', stock: 84, status: 'Healthy' },
            { name: 'Hopia', stock: 28, status: 'Low' },
            { name: 'Ube Bread', stock: 67, status: 'Healthy' },
            { name: 'Cheese Roll', stock: 55, status: 'Healthy' },
            { name: 'Monay', stock: 19, status: 'Low' },
            { name: 'Pan de Coco', stock: 30, status: 'Healthy' },
            { name: 'Putok (Star Bread)', stock: 30, status: 'Healthy' },
            { name: 'Pan de Monggo', stock: 30, status: 'Healthy' },
            { name: 'Bicho-Bicho', stock: 30, status: 'Healthy' },
            { name: 'Pinagong', stock: 30, status: 'Healthy' },
            { name: 'Pan de Leche', stock: 30, status: 'Healthy' },
            { name: 'Pianono', stock: 30, status: 'Healthy' }
        ];

        function formatCurrency(value) {
            return 'PHP ' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function renderSalesChart(orders) {
            const chart = document.getElementById('sales_chart');
            const summary = document.getElementById('sales_summary');
            const quantities = Object.fromEntries(defaultInventory.map((item) => [item.name, 0]));

            orders.forEach((order) => {
                Object.entries(order.items || {}).forEach(([product, quantity]) => {
                    quantities[product] = (quantities[product] || 0) + Math.max(0, Number(quantity) || 0);
                });
            });

            const rankedProducts = Object.entries(quantities).sort((first, second) => second[1] - first[1] || first[0].localeCompare(second[0]));
            const maximumSold = rankedProducts[0]?.[1] || 0;
            const lowestProduct = rankedProducts[rankedProducts.length - 1];
            chart.replaceChildren();

            if (orders.length === 0 || maximumSold === 0) {
                summary.textContent = 'No sales recorded yet.';
            } else {
                summary.textContent = 'Most sold: ' + rankedProducts[0][0] + ' (' + rankedProducts[0][1] + ') · Least sold: ' + lowestProduct[0] + ' (' + lowestProduct[1] + ')';
            }

            rankedProducts.slice().reverse().forEach(([product, quantity]) => {
                const row = document.createElement('div');
                row.className = 'sales-row';
                row.setAttribute('role', 'listitem');
                if (quantity > 0 && quantity === rankedProducts[0][1]) {
                    row.classList.add('is-best');
                }
                if (quantity === lowestProduct[1]) {
                    row.classList.add('is-lowest');
                }

                const name = document.createElement('span');
                name.className = 'sales-name';
                name.textContent = product;

                const track = document.createElement('div');
                track.className = 'sales-track';
                track.setAttribute('role', 'progressbar');
                track.setAttribute('aria-label', product + ' sold');
                track.setAttribute('aria-valuemin', '0');
                track.setAttribute('aria-valuemax', String(maximumSold));
                track.setAttribute('aria-valuenow', String(quantity));
                const bar = document.createElement('div');
                bar.className = 'sales-bar';
                bar.style.height = maximumSold > 0 && quantity > 0 ? Math.max(quantity / maximumSold * 100, 2) + '%' : '0%';
                track.appendChild(bar);

                const count = document.createElement('span');
                count.className = 'sales-count';
                count.textContent = String(quantity);
                count.title = quantity + ' sold';

                row.append(count, track, name);
                chart.appendChild(row);
            });
        }

        function parseOrderDate(dateValue) {
            const parsed = new Date(dateValue);
            return Number.isNaN(parsed.getTime()) ? null : parsed;
        }

        function getTopProductForOrders(orders) {
            const counts = {};
            orders.forEach((order) => {
                Object.entries(order.items || {}).forEach(([product, quantity]) => {
                    counts[product] = (counts[product] || 0) + Number(quantity || 0);
                });
            });
            const topProduct = Object.entries(counts).sort((first, second) => second[1] - first[1])[0];
            return topProduct ? topProduct[0] : '-';
        }

        function renderReports(orders) {
            const reportData = {
                today: [],
                week: [],
                month: [],
                lastMonth: []
            };
            const now = new Date();
            orders.forEach((order) => {
                const parsedDate = parseOrderDate(order.date);
                if (!parsedDate) {
                    return;
                }
                if (parsedDate.toDateString() === now.toDateString()) {
                    reportData.today.push(order);
                }
                const daysDifference = (now - parsedDate) / (1000 * 60 * 60 * 24);
                if (daysDifference >= 0 && daysDifference <= 7) {
                    reportData.week.push(order);
                }
                if (parsedDate.getMonth() === now.getMonth() && parsedDate.getFullYear() === now.getFullYear()) {
                    reportData.month.push(order);
                }
                const previousMonthDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                if (parsedDate.getMonth() === previousMonthDate.getMonth() && parsedDate.getFullYear() === previousMonthDate.getFullYear()) {
                    reportData.lastMonth.push(order);
                }
            });

            const todayRevenue = reportData.today.reduce((sum, order) => sum + Number(order.total || 0), 0);
            const weekRevenue = reportData.week.reduce((sum, order) => sum + Number(order.total || 0), 0);
            const monthRevenue = reportData.month.reduce((sum, order) => sum + Number(order.total || 0), 0);
            const lastMonthRevenue = reportData.lastMonth.reduce((sum, order) => sum + Number(order.total || 0), 0);
            const averageOrder = orders.length ? orders.reduce((sum, order) => sum + Number(order.total || 0), 0) / orders.length : 0;

            document.getElementById('report_today_orders').textContent = String(reportData.today.length);
            document.getElementById('report_today_revenue').textContent = formatCurrency(todayRevenue);
            document.getElementById('report_today_top').textContent = getTopProductForOrders(reportData.today);

            document.getElementById('report_week_orders').textContent = String(reportData.week.length);
            document.getElementById('report_week_revenue').textContent = formatCurrency(weekRevenue);
            document.getElementById('report_week_top').textContent = getTopProductForOrders(reportData.week);

            document.getElementById('report_month_orders').textContent = String(reportData.month.length);
            document.getElementById('report_month_revenue').textContent = formatCurrency(monthRevenue);
            document.getElementById('report_month_top').textContent = getTopProductForOrders(reportData.month);

            document.getElementById('report_last_month_orders').textContent = String(reportData.lastMonth.length);
            document.getElementById('report_last_month_revenue').textContent = formatCurrency(lastMonthRevenue);
            document.getElementById('report_last_month_top').textContent = getTopProductForOrders(reportData.lastMonth);

            document.getElementById('report_average_orders').textContent = formatCurrency(averageOrder);
            document.getElementById('report_average_revenue').textContent = orders.length ? 'Customer orders' : 'No orders';
            document.getElementById('report_average_top').textContent = getTopProductForOrders(orders);
        }

        function loadInventory() {
            const stored = localStorage.getItem(inventoryStorageKey);
            if (!stored) {
                localStorage.setItem(inventoryStorageKey, JSON.stringify(defaultInventory));
                return [...defaultInventory];
            }

            try {
                const parsed = JSON.parse(stored);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    const existingNames = new Set(parsed.map((item) => item.name));
                    const missingItems = defaultInventory.filter((item) => !existingNames.has(item.name));
                    if (missingItems.length > 0) {
                        const mergedInventory = [...parsed, ...missingItems];
                        localStorage.setItem(inventoryStorageKey, JSON.stringify(mergedInventory));
                        return mergedInventory;
                    }
                    return parsed;
                }
            } catch (error) {
                console.error('Inventory parse error:', error);
            }

            localStorage.setItem(inventoryStorageKey, JSON.stringify(defaultInventory));
            return [...defaultInventory];
        }

        function normalizeInventoryStatus(stock) {
            if (Number(stock) <= 0) {
                return 'Out of Stock';
            }
            if (Number(stock) <= 10) {
                return 'Low';
            }
            return 'Healthy';
        }

        function saveInventory(items) {
            const normalizedItems = items.map((item) => ({
                ...item,
                stock: Number(item.stock || 0),
                status: normalizeInventoryStatus(Number(item.stock || 0))
            }));
            localStorage.setItem(inventoryStorageKey, JSON.stringify(normalizedItems));
        }

        function renderStackList() {
            const stackList = document.getElementById('stack_list');
            if (!stackList) {
                return;
            }

            const inventory = loadInventory();
            stackList.innerHTML = inventory.map((item) => {
                const stock = Number(item.stock || 0);
                const statusLabel = normalizeInventoryStatus(stock);
                return `
                    <div class="stack-item">
                        <strong>${item.name}</strong>
                        <span class="stack-value">${stock} pcs</span>
                        <span class="status ${statusLabel === 'Healthy' ? 'success' : statusLabel === 'Low' ? 'pending' : 'out-of-stock'}">${statusLabel}</span>
                    </div>
                `;
            }).join('');
        }

        function renderInventoryTable() {
            const tbody = document.getElementById('inventory_table_body');
            if (!tbody) {
                return;
            }

            tbody.innerHTML = '';
            const inventory = loadInventory();
            inventory.forEach((item) => {
                const safeStock = Number(item.stock || 0);
                const statusLabel = normalizeInventoryStatus(safeStock);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td>${safeStock} pcs</td>
                    <td><span class="status ${statusLabel === 'Healthy' ? 'success' : statusLabel === 'Low' ? 'pending' : 'out-of-stock'}">${statusLabel}</span></td>
                    <td>
                        <button class="action-btn primary" type="button" data-action="edit" data-name="${item.name}">Edit</button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            renderStackList();
        }

        const inventoryModal = document.getElementById('inventory_modal');
        const inventoryForm = document.getElementById('inventory_form');
        const inventoryNameInput = document.getElementById('inventory_name');
        const inventoryStockInput = document.getElementById('inventory_stock');
        const inventoryStatusInput = document.getElementById('inventory_status');
        const cancelInventoryEditButton = document.getElementById('cancel_inventory_edit');
        let editedItemName = null;

        function openInventoryModal(name) {
            const inventory = loadInventory();
            const selected = inventory.find((item) => item.name === name);
            if (!selected) {
                return;
            }

            editedItemName = name;
            inventoryNameInput.value = selected.name;
            inventoryStockInput.value = selected.stock;
            inventoryStatusInput.value = normalizeInventoryStatus(Number(selected.stock || 0));
            inventoryModal.classList.add('open');
        }

        function closeInventoryModal() {
            editedItemName = null;
            inventoryForm.reset();
            inventoryModal.classList.remove('open');
        }

        document.addEventListener('click', (event) => {
            const actionButton = event.target.closest('[data-action="edit"]');
            if (actionButton) {
                openInventoryModal(actionButton.dataset.name);
            }
        });

        cancelInventoryEditButton.addEventListener('click', closeInventoryModal);
        inventoryModal.addEventListener('click', (event) => {
            if (event.target === inventoryModal) {
                closeInventoryModal();
            }
        });

        inventoryForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!editedItemName) {
                return;
            }

            const inventory = loadInventory();
            const updatedInventory = inventory.map((item) => {
                if (item.name === editedItemName) {
                    const nextStock = Number(inventoryStockInput.value) || 0;
                    const nextStatus = normalizeInventoryStatus(nextStock);
                    return {
                        ...item,
                        name: inventoryNameInput.value.trim() || item.name,
                        stock: nextStock,
                        status: nextStatus
                    };
                }
                return item;
            });

            saveInventory(updatedInventory);
            renderInventoryTable();
            closeInventoryModal();
        });

        const exportData = document.getElementById('export_data');
        if (exportData) {
            exportData.addEventListener('click', (event) => {
                event.preventDefault();
                const rows = [
                    ['Bread', 'Stock', 'Status'],
                    ...loadInventory().map((item) => [item.name, String(item.stock), item.status || normalizeInventoryStatus(item.stock)])
                ];
                const csv = rows.map((row) => row.join(',')).join('\n');
                const download = document.createElement('a');
                download.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
                download.download = 'panaderia-inventory.csv';
                download.click();
                URL.revokeObjectURL(download.href);
            });
        }
        const inventoryPanel = document.getElementById('inventory');
        const stackPanel = document.getElementById('stack');
        const paymentsPanel = document.getElementById('payments');
        const ordersPanel = document.getElementById('orders');
        const reportsPanel = document.getElementById('reports');
        const overviewSection = document.getElementById('overview');
        const overviewStats = document.querySelector('.stats');
        const overviewSalesChart = document.querySelector('.sales-panel');
        function showSection(selectedSection) {
            const panels = [ordersPanel, stackPanel, inventoryPanel, paymentsPanel, reportsPanel];
            panels.forEach((panel) => {
                if (panel) {
                    panel.hidden = true;
                }
            });
            if (overviewSection) {
                overviewSection.hidden = selectedSection !== '#overview';
            }
            if (overviewStats) {
                overviewStats.hidden = selectedSection !== '#overview';
            }
            if (overviewSalesChart) {
                overviewSalesChart.hidden = selectedSection !== '#overview';
            }
            const selectedPanel = document.querySelector(selectedSection);
            if (selectedPanel && panels.includes(selectedPanel)) {
                selectedPanel.hidden = false;
            }
        }
        document.querySelectorAll('.nav-item[href^="#"]').forEach((navItem) => {
            navItem.addEventListener('click', (event) => {
                event.preventDefault();
                document.querySelectorAll('.nav-item').forEach((item) => item.classList.remove('active'));
                navItem.classList.add('active');
                showSection(navItem.getAttribute('href'));
            });
        });
        document.querySelectorAll('a[href="#orders"]').forEach((ordersLink) => {
            ordersLink.addEventListener('click', (event) => {
                event.preventDefault();
                showSection('#orders');
            });
        });

        renderInventoryTable();

        const serverOrders = <?php echo json_encode($serverOrders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const recordedOrders = [...serverOrders];
        for (let index = localStorage.length - 1; index >= 0; index -= 1) {
            const key = localStorage.key(index);
            if (!key || !key.startsWith('panaderia_recent_orders_')) {
                continue;
            }
            localStorage.removeItem(key);
        }

        const paymentsNavItem = document.getElementById('payments_nav_item');
        renderSalesChart(serverOrders);
        if (paymentsNavItem) {
            paymentsNavItem.hidden = recordedOrders.length === 0;
        }

        if (recordedOrders.length > 0) {
            const syncedOrders = recordedOrders.map((order) => ({
                ...order,
                status: order.status === 'Pending' || !order.status ? 'Success' : order.status
            }));
            const revenue = syncedOrders.reduce((sum, order) => sum + Number(order.total || 0), 0);
            const pendingOrders = syncedOrders.filter((order) => order.status === 'Pending').length;
            const productCounts = {};
            serverOrders.forEach((order) => {
                Object.entries(order.items || {}).forEach(([product, quantity]) => {
                    productCounts[product] = (productCounts[product] || 0) + Number(quantity || 0);
                });
            });
            const rankedProducts = Object.entries(productCounts)
                .filter(([, quantity]) => Number(quantity) > 0)
                .sort((first, second) => second[1] - first[1] || first[0].localeCompare(second[0]));
            const bestSellerCutoff = rankedProducts[Math.min(2, rankedProducts.length - 1)]?.[1] || 0;
            const bestSellers = rankedProducts.filter(([, quantity]) => bestSellerCutoff > 0 && quantity >= bestSellerCutoff);
            renderReports(syncedOrders);

            document.getElementById('total_orders_value').textContent = String(syncedOrders.length);
            document.getElementById('revenue_value').textContent = 'PHP ' + revenue.toLocaleString('en-PH', { minimumFractionDigits: 2 });
            const bestSellerList = document.getElementById('best_seller_value');
            bestSellerList.replaceChildren();
            if (bestSellers.length === 0) {
                const emptyRow = document.createElement('li');
                emptyRow.textContent = 'No sales yet';
                bestSellerList.appendChild(emptyRow);
            } else {
                bestSellers.forEach(([product, quantity]) => {
                    const row = document.createElement('li');
                    const name = document.createElement('span');
                    name.textContent = product;
                    const sales = document.createElement('span');
                    sales.textContent = quantity + ' sold';
                    row.append(name, sales);
                    bestSellerList.appendChild(row);
                });
            }
            document.getElementById('pending_value').textContent = String(pendingOrders);

            const paymentTotals = { GCash: 0, Maya: 0, 'Cash on Delivery': 0 };
            syncedOrders.forEach((order) => {
                const paymentMethod = order.payment || 'Cash on Delivery';
                paymentTotals[paymentMethod] = (paymentTotals[paymentMethod] || 0) + Number(order.total || 0);
            });
            document.getElementById('gcash_payout_value').textContent = 'PHP ' + paymentTotals.GCash.toFixed(2);
            document.getElementById('maya_payout_value').textContent = 'PHP ' + paymentTotals.Maya.toFixed(2);
            document.getElementById('cod_payout_value').textContent = 'PHP ' + paymentTotals['Cash on Delivery'].toFixed(2);
            document.getElementById('net_payout_value').textContent = 'PHP ' + revenue.toFixed(2);

            const adminOrdersBody = document.getElementById('admin_orders_body');
            const viewAllOrdersLink = document.getElementById('view_all_orders');
            let showingAllOrders = false;
            const renderAdminOrders = () => {
                adminOrdersBody.innerHTML = '';
                const ordersToShow = showingAllOrders ? syncedOrders : syncedOrders.slice(0, 10);
                ordersToShow.forEach((order) => {
                    const row = document.createElement('tr');
                    const normalizedStatus = order.status || 'Success';
                    const purchasedBreads = Object.entries(order.items || {}).map(([product, quantity]) => product + ' x' + quantity).join(', ') || 'Not recorded';
                    const values = [order.id || 'Order', order.customerName || order.customer || 'Unknown customer', purchasedBreads, order.address || 'Not recorded', order.contactNumber || order.gcashNumber || 'Not recorded', 'PHP ' + Number(order.total || 0).toFixed(2), normalizedStatus];
                    values.forEach((value, valueIndex) => {
                        const cell = document.createElement('td');
                        if (valueIndex === 6) {
                            const status = document.createElement('span');
                            status.className = normalizedStatus === 'Pending' ? 'status pending' : 'status success';
                            status.textContent = value;
                            cell.appendChild(status);
                        } else {
                            cell.textContent = value;
                        }
                        row.appendChild(cell);
                    });
                    adminOrdersBody.appendChild(row);
                });
                viewAllOrdersLink.textContent = showingAllOrders ? 'Show latest 10 orders' : 'View all orders';
            };
            renderAdminOrders();
            viewAllOrdersLink.addEventListener('click', (event) => {
                event.preventDefault();
                showingAllOrders = !showingAllOrders;
                renderAdminOrders();
            });
        }
    </script>
</body>
</html>
