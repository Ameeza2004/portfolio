<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

// Admin guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// STATS
$total_orders = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM orders"))['t'];

$total_revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total),0) as t
     FROM orders WHERE status != 'Cancelled'"))['t'];

$pending_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM orders
     WHERE status = 'Pending'"))['t'];

$delivered_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM orders
     WHERE status = 'Delivered'"))['t'];

$processing_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM orders
     WHERE status = 'Processing'"))['t'];

$cancelled_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM orders
     WHERE status = 'Cancelled'"))['t'];

$product_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM products"))['t'];

$low_stock = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM products
     WHERE quantity <= 3 AND quantity > 0"))['t'];

$out_stock = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM products
     WHERE quantity = 0"))['t'];

$customer_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM users
     WHERE role = 'customer'"))['t'];

// MONTHLY REVENUE CHART DATA
$monthly = mysqli_query($conn,
    "SELECT
        DATE_FORMAT(order_date,'%b') as month,
        MONTH(order_date) as mnum,
        SUM(total) as total
     FROM orders
     WHERE YEAR(order_date) = YEAR(CURDATE())
     AND status != 'Cancelled'
     GROUP BY MONTH(order_date),
       DATE_FORMAT(order_date,'%b')
     ORDER BY mnum");

$chart_labels = [];
$chart_data   = [];
while ($row = mysqli_fetch_assoc($monthly)) {
    $chart_labels[] = $row['month'];
    $chart_data[]   = (float)$row['total'];
}

// STATUS PIE CHART
$status_q = mysqli_query($conn,
    "SELECT status, COUNT(*) as cnt
     FROM orders GROUP BY status");
$pie_labels = [];
$pie_data   = [];
while ($row = mysqli_fetch_assoc($status_q)) {
    $pie_labels[] = $row['status'];
    $pie_data[]   = (int)$row['cnt'];
}

// RECENT ORDERS
$recent = mysqli_query($conn,
    "SELECT * FROM orders
     ORDER BY order_date DESC LIMIT 8");

// LOW STOCK PRODUCTS
$low_prods = mysqli_query($conn,
    "SELECT * FROM products
     WHERE quantity <= 5
     ORDER BY quantity ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – Admin Panel</title>

<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
  rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
* {
  margin: 0; padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
}

body { background: #f0f2f5; color: #111; }

/* ── SIDEBAR ── */
.admin-wrapper { display: flex; min-height: 100vh; }

.admin-sidebar {
  width: 250px;
  background: #0a0a0a;
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 100;
  overflow-y: auto;
  box-shadow: 4px 0 24px rgba(0,0,0,0.3);
}

.sidebar-brand {
  padding: 26px 22px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  display: flex;
  align-items: center;
  gap: 12px;
}

.sidebar-logo-circle {
  width: 42px; height: 42px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.sidebar-logo-circle img {
  width: 28px; height: 28px;
  object-fit: contain;
}

.sidebar-brand span {
  color: white;
  font-size: 14px;
  font-weight: 800;
  letter-spacing: 2px;
}

.sidebar-section {
  padding: 22px 22px 10px;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: rgba(255,255,255,0.25);
  font-weight: 700;
}

.sidebar-nav a {
  display: flex;
  align-items: center;
  gap: 12px;
  color: rgba(255,255,255,0.6);
  text-decoration: none;
  padding: 12px 22px;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.sidebar-nav a:hover,
.sidebar-nav a.active {
  background: rgba(220,38,38,0.12);
  color: white;
  border-left-color: #dc2626;
}

.sidebar-nav a i {
  width: 18px;
  font-size: 14px;
}

.sidebar-logout {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  padding: 16px;
  border-top: 1px solid rgba(255,255,255,0.08);
}

.sidebar-logout a {
  display: flex;
  align-items: center;
  gap: 10px;
  color: rgba(255,255,255,0.5);
  text-decoration: none;
  padding: 11px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.sidebar-logout a:hover {
  background: rgba(220,38,38,0.15);
  color: #dc2626;
}

/* ── MAIN ── */
.admin-main {
  margin-left: 250px;
  padding: 32px;
  width: 100%;
}

/* TOPBAR */
.admin-topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
  flex-wrap: wrap;
  gap: 12px;
}

.admin-topbar h2 {
  font-size: 24px;
  font-weight: 800;
  color: #111;
  margin-bottom: 2px;
}

.admin-topbar p {
  font-size: 13px;
  color: #6b7280;
  font-weight: 400;
}

.topbar-date {
  font-size: 13px;
  color: #6b7280;
  background: white;
  padding: 10px 18px;
  border-radius: 50px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  display: flex;
  align-items: center;
  gap: 8px;
}

.topbar-date i { color: #dc2626; }

/* STAT CARDS */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 18px;
  padding: 24px;
  box-shadow: 0 2px 14px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 14px 40px rgba(0,0,0,0.1);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
}

.stat-card.blue::before   { background: linear-gradient(90deg,#2563eb,#3b82f6); }
.stat-card.green::before  { background: linear-gradient(90deg,#16a34a,#22c55e); }
.stat-card.orange::before { background: linear-gradient(90deg,#d97706,#f59e0b); }
.stat-card.red::before    { background: linear-gradient(90deg,#dc2626,#ef4444); }
.stat-card.purple::before { background: linear-gradient(90deg,#7c3aed,#a78bfa); }
.stat-card.pink::before   { background: linear-gradient(90deg,#db2777,#f472b6); }

.stat-icon {
  width: 52px; height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  margin-bottom: 16px;
}

.stat-card.blue   .stat-icon { background:#dbeafe; color:#2563eb; }
.stat-card.green  .stat-icon { background:#dcfce7; color:#16a34a; }
.stat-card.orange .stat-icon { background:#fef3c7; color:#d97706; }
.stat-card.red    .stat-icon { background:#fee2e2; color:#dc2626; }
.stat-card.purple .stat-icon { background:#ede9fe; color:#7c3aed; }
.stat-card.pink   .stat-icon { background:#fce7f3; color:#db2777; }

.stat-value {
  font-size: 28px;
  font-weight: 900;
  color: #111;
  margin-bottom: 4px;
  line-height: 1;
}

.stat-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* SECONDARY STATS */
.secondary-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 24px;
}

.mini-stat {
  background: white;
  border-radius: 14px;
  padding: 16px 18px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  border-left: 4px solid;
  display: flex;
  align-items: center;
  gap: 14px;
  transition: all 0.3s ease;
}

.mini-stat:hover { transform: translateY(-3px); }
.mini-stat.blue   { border-color: #2563eb; }
.mini-stat.green  { border-color: #16a34a; }
.mini-stat.orange { border-color: #d97706; }
.mini-stat.red    { border-color: #dc2626; }

.mini-stat-icon {
  font-size: 20px;
  width: 20px;
  text-align: center;
}

.mini-stat.blue   .mini-stat-icon { color: #2563eb; }
.mini-stat.green  .mini-stat-icon { color: #16a34a; }
.mini-stat.orange .mini-stat-icon { color: #d97706; }
.mini-stat.red    .mini-stat-icon { color: #dc2626; }

.mini-stat-num {
  font-size: 20px;
  font-weight: 900;
  color: #111;
  line-height: 1;
}

.mini-stat-label {
  font-size: 11px;
  color: #9ca3af;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* CHARTS */
.charts-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

.chart-card {
  background: white;
  border-radius: 18px;
  padding: 24px;
  box-shadow: 0 2px 14px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
}

.chart-card h3 {
  font-size: 16px;
  font-weight: 800;
  color: #111;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.chart-card h3 i { color: #dc2626; }

/* TABLES */
.tables-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

.table-card {
  background: white;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 2px 14px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
}

.table-card-head {
  padding: 20px 24px;
  border-bottom: 1px solid #f3f4f6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.table-card-head h3 {
  font-size: 16px;
  font-weight: 800;
  color: #111;
  display: flex;
  align-items: center;
  gap: 8px;
}

.table-card-head h3 i { color: #dc2626; }

.view-all-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 16px;
  background: #dc2626;
  color: white;
  border-radius: 50px;
  text-decoration: none;
  font-size: 12px;
  font-weight: 700;
  transition: all 0.3s ease;
}

.view-all-btn:hover {
  background: #b91c1c;
  transform: translateY(-2px);
  color: white;
}

/* TABLE */
.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead th {
  background: #111;
  color: white;
  padding: 13px 18px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  text-align: left;
}

.data-table tbody td {
  padding: 14px 18px;
  font-size: 13px;
  font-weight: 500;
  border-bottom: 1px solid #f3f4f6;
  color: #111;
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #fafafa;
}

/* STATUS BADGES */
.status-badge {
  padding: 5px 12px;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 700;
  display: inline-block;
}

.badge-pending    { background:#fef3c7; color:#d97706; }
.badge-processing { background:#dbeafe; color:#2563eb; }
.badge-delivered  { background:#dcfce7; color:#16a34a; }
.badge-cancelled  { background:#fee2e2; color:#dc2626; }

/* ACTION BTNS */
.tbl-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s ease;
  margin-right: 4px;
}

.tbl-btn-red {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.tbl-btn-red:hover {
  background: #dc2626;
  color: white;
}

.tbl-btn-dark {
  background: #f3f4f6;
  color: #111;
  border: 1px solid #e5e7eb;
}

.tbl-btn-dark:hover {
  background: #111;
  color: white;
}

/* LOW STOCK */
.stock-bar-wrap {
  background: #f3f4f6;
  border-radius: 50px;
  height: 6px;
  width: 80px;
  overflow: hidden;
  display: inline-block;
}

.stock-bar-fill {
  height: 100%;
  border-radius: 50px;
  transition: width 0.5s ease;
}

@media (max-width: 1100px) {
  .stats-grid     { grid-template-columns: repeat(2,1fr); }
  .secondary-stats{ grid-template-columns: repeat(2,1fr); }
  .charts-grid    { grid-template-columns: 1fr; }
  .tables-grid    { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .admin-sidebar { transform: translateX(-100%); }
  .admin-main    { margin-left: 0; padding: 16px; }
  .stats-grid    { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<div class="admin-wrapper">

  <!-- SIDEBAR -->
  <div class="admin-sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-logo-circle">
        <img src="../images/logo.png" alt="Logo"
          onerror="this.parentElement.innerHTML=
          '<b style=\'color:#dc2626;font-size:14px;\'>iP</b>'">
      </div>
      <span>ADMIN PANEL</span>
    </div>

    <div class="sidebar-section">Main</div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="active">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
      </a>
      <a href="../add_product.php">
        <i class="fa-solid fa-plus"></i>
        Add Product
      </a>
      <a href="orders.php">
        <i class="fa-solid fa-box"></i>
        Orders
      </a>
    </nav>

    <div class="sidebar-section">Sales Reports</div>
    <nav class="sidebar-nav">
      <a href="sales/weekly.php">
        <i class="fa-solid fa-calendar-week"></i>
        Weekly Sales
      </a>
      <a href="sales/monthly.php">
        <i class="fa-solid fa-calendar"></i>
        Monthly Sales
      </a>
      <a href="sales/yearly.php">
        <i class="fa-solid fa-calendar-days"></i>
        Yearly Sales
      </a>
    </nav>

    <div class="sidebar-logout">
      <a href="../logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout (<?= htmlspecialchars($_SESSION['username']) ?>)
      </a>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <div class="admin-main">

    <!-- TOPBAR -->
    <div class="admin-topbar">
      <div>
        <h2>Dashboard</h2>
        <p>
          Welcome back,
          <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>!
          Here is what is happening today.
        </p>
      </div>
      <div class="topbar-date">
        <i class="fa-regular fa-calendar"></i>
        <?= date('l, d M Y') ?>
      </div>
    </div>

    <!-- MAIN STAT CARDS -->
    <div class="stats-grid">

      <div class="stat-card blue">
        <div class="stat-icon">
          <i class="fa-solid fa-box-archive"></i>
        </div>
        <div class="stat-value"><?= $total_orders ?></div>
        <div class="stat-label">Total Orders</div>
      </div>

      <div class="stat-card green">
        <div class="stat-icon">
          <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div class="stat-value">
          Rs <?= number_format($total_revenue, 0) ?>
        </div>
        <div class="stat-label">Total Revenue</div>
      </div>

      <div class="stat-card purple">
        <div class="stat-icon">
          <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-value"><?= $customer_count ?></div>
        <div class="stat-label">Customers</div>
      </div>

      <div class="stat-card orange">
        <div class="stat-icon">
          <i class="fa-solid fa-tags"></i>
        </div>
        <div class="stat-value"><?= $product_count ?></div>
        <div class="stat-label">Products</div>
      </div>

    </div>

    <!-- SECONDARY STATS -->
    <div class="secondary-stats">

      <div class="mini-stat orange">
        <div class="mini-stat-icon">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div>
          <div class="mini-stat-num"><?= $pending_count ?></div>
          <div class="mini-stat-label">Pending</div>
        </div>
      </div>

      <div class="mini-stat blue">
        <div class="mini-stat-icon">
          <i class="fa-solid fa-gear"></i>
        </div>
        <div>
          <div class="mini-stat-num"><?= $processing_count ?></div>
          <div class="mini-stat-label">Processing</div>
        </div>
      </div>

      <div class="mini-stat green">
        <div class="mini-stat-icon">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
          <div class="mini-stat-num"><?= $delivered_count ?></div>
          <div class="mini-stat-label">Delivered</div>
        </div>
      </div>

      <div class="mini-stat red">
        <div class="mini-stat-icon">
          <i class="fa-solid fa-circle-xmark"></i>
        </div>
        <div>
          <div class="mini-stat-num"><?= $cancelled_count ?></div>
          <div class="mini-stat-label">Cancelled</div>
        </div>
      </div>

    </div>

    <!-- CHARTS -->
    <div class="charts-grid">

      <div class="chart-card">
        <h3>
          <i class="fa-solid fa-chart-line"></i>
          Monthly Revenue <?= date('Y') ?>
        </h3>
        <canvas id="revenueChart" height="120"></canvas>
      </div>

      <div class="chart-card">
        <h3>
          <i class="fa-solid fa-chart-pie"></i>
          Order Status
        </h3>
        <canvas id="statusChart" height="200"></canvas>
      </div>

    </div>

    <!-- TABLES -->
    <div class="tables-grid">

      <!-- RECENT ORDERS -->
      <div class="table-card">
        <div class="table-card-head">
          <h3>
            <i class="fa-solid fa-receipt"></i>
            Recent Orders
          </h3>
          <a href="orders.php" class="view-all-btn">
            View All
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($recent)): ?>
              <tr>
                <td>
                  <strong>
                    #<?= str_pad($row['id'],4,'0',STR_PAD_LEFT) ?>
                  </strong>
                </td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td style="color:#dc2626;font-weight:800;">
                  Rs <?= number_format($row['total'],2) ?>
                </td>
                <td style="color:#6b7280;font-size:12px;">
                  <?= date('d M Y', strtotime($row['order_date'])) ?>
                </td>
                <td>
                  <span class="status-badge
                    badge-<?= strtolower($row['status']) ?>">
                    <?= $row['status'] ?>
                  </span>
                </td>
                <td>
                  <a href="order_details.php?id=<?= $row['id'] ?>"
                    class="tbl-btn tbl-btn-dark">
                    <i class="fa-solid fa-eye"></i> View
                  </a>
                  <a href="update_status.php?id=<?= $row['id'] ?>"
                    class="tbl-btn tbl-btn-red">
                    <i class="fa-solid fa-pen"></i> Edit
                  </a>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- LOW STOCK -->
      <div class="table-card">
        <div class="table-card-head">
          <h3>
            <i class="fa-solid fa-triangle-exclamation"></i>
            Low Stock
          </h3>
          <a href="../add_product.php" class="view-all-btn">
            Add Product
            <i class="fa-solid fa-plus"></i>
          </a>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Stock</th>
                <th>Level</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($prod = mysqli_fetch_assoc($low_prods)): ?>
              <?php
              $max    = 10;
              $pct    = min(100, ($prod['quantity'] / $max) * 100);
              $color  = $prod['quantity'] == 0
                ? '#dc2626'
                : ($prod['quantity'] <= 2 ? '#f59e0b' : '#16a34a');
              ?>
              <tr>
                <td>
                  <div style="font-weight:700;font-size:13px;">
                    <?= htmlspecialchars($prod['product_name']) ?>
                  </div>
                  <div style="font-size:11px;color:#9ca3af;">
                    <?= htmlspecialchars($prod['category']) ?>
                  </div>
                </td>
                <td>
                  <span style="font-weight:800;
                    color:<?= $color ?>;">
                    <?= $prod['quantity'] ?>
                  </span>
                  <span style="font-size:11px;color:#9ca3af;">
                    units
                  </span>
                </td>
                <td>
                  <div class="stock-bar-wrap">
                    <div class="stock-bar-fill"
                      style="width:<?= $pct ?>%;
                      background:<?= $color ?>;">
                    </div>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>
</div>

<script>
// REVENUE LINE CHART
const revenueCtx = document.getElementById('revenueChart');
new Chart(revenueCtx, {
  type: 'line',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      label: 'Revenue (Rs)',
      data: <?= json_encode($chart_data) ?>,
      borderColor: '#dc2626',
      backgroundColor: 'rgba(220,38,38,0.08)',
      borderWidth: 3,
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#dc2626',
      pointRadius: 5,
      pointHoverRadius: 8,
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: '#f3f4f6' },
        ticks: {
          callback: function(v) {
            return 'Rs ' + v.toLocaleString();
          },
          font: { family: 'Poppins', size: 11 }
        }
      },
      x: {
        grid: { display: false },
        ticks: {
          font: { family: 'Poppins', size: 11 }
        }
      }
    }
  }
});

// STATUS DOUGHNUT CHART
const statusCtx = document.getElementById('statusChart');
new Chart(statusCtx, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($pie_labels) ?>,
    datasets: [{
      data: <?= json_encode($pie_data) ?>,
      backgroundColor: [
        '#f59e0b',
        '#2563eb',
        '#16a34a',
        '#dc2626'
      ],
      borderWidth: 3,
      borderColor: 'white',
    }]
  },
  options: {
    responsive: true,
    cutout: '68%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          font: { family: 'Poppins', size: 12 },
          padding: 16,
        }
      }
    }
  }
});
</script>

</body>
</html>