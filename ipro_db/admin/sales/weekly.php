<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../db.php';

// Admin guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// GET CURRENT WEEK START AND END
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end   = date('Y-m-d', strtotime('sunday this week'));

// WEEKLY ORDERS
$stmt = mysqli_prepare($conn,
    "SELECT
        o.*,
        DAYNAME(o.order_date) as day_name,
        DATE(o.order_date) as order_day
     FROM orders o
     WHERE DATE(o.order_date) BETWEEN ? AND ?
     AND o.status != 'Cancelled'
     ORDER BY o.order_date DESC");
mysqli_stmt_bind_param($stmt, "ss", $week_start, $week_end);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
$orders_array = [];
while ($row = mysqli_fetch_assoc($orders)) {
    $orders_array[] = $row;
}

// DAILY TOTALS FOR CHART
$daily_stmt = mysqli_prepare($conn,
    "SELECT
        DAYNAME(order_date) as day_name,
        DAYOFWEEK(order_date) as day_num,
        COUNT(*) as order_count,
        SUM(total) as total_revenue
     FROM orders
     WHERE DATE(order_date) BETWEEN ? AND ?
     AND status != 'Cancelled'
     GROUP BY DAYOFWEEK(order_date), DAYNAME(order_date)
     ORDER BY day_num");
mysqli_stmt_bind_param($daily_stmt, "ss",
    $week_start, $week_end);
mysqli_stmt_execute($daily_stmt);
$daily_result = mysqli_stmt_get_result($daily_stmt);

$days_data = [
    'Monday'    => ['revenue'=>0,'orders'=>0],
    'Tuesday'   => ['revenue'=>0,'orders'=>0],
    'Wednesday' => ['revenue'=>0,'orders'=>0],
    'Thursday'  => ['revenue'=>0,'orders'=>0],
    'Friday'    => ['revenue'=>0,'orders'=>0],
    'Saturday'  => ['revenue'=>0,'orders'=>0],
    'Sunday'    => ['revenue'=>0,'orders'=>0],
];

while ($row = mysqli_fetch_assoc($daily_result)) {
    if (isset($days_data[$row['day_name']])) {
        $days_data[$row['day_name']]['revenue'] =
            (float)$row['total_revenue'];
        $days_data[$row['day_name']]['orders']  =
            (int)$row['order_count'];
    }
}

$chart_labels  = array_keys($days_data);
$chart_revenue = array_column($days_data, 'revenue');
$chart_orders  = array_column($days_data, 'orders');

// WEEKLY SUMMARY STATS
$week_stats = mysqli_fetch_assoc(mysqli_prepare_and_execute(
    $conn,
    "SELECT
        COUNT(*) as total_orders,
        COALESCE(SUM(total),0) as total_revenue,
        COALESCE(AVG(total),0) as avg_order
     FROM orders
     WHERE DATE(order_date) BETWEEN ? AND ?
     AND status != 'Cancelled'",
    "ss", $week_start, $week_end
));

// BEST SELLING PRODUCTS THIS WEEK
$best_stmt = mysqli_prepare($conn,
    "SELECT
        oi.product_name,
        SUM(oi.quantity) as total_qty,
        SUM(oi.subtotal) as total_revenue
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     WHERE DATE(o.order_date) BETWEEN ? AND ?
     AND o.status != 'Cancelled'
     GROUP BY oi.product_name
     ORDER BY total_qty DESC
     LIMIT 5");
mysqli_stmt_bind_param($best_stmt, "ss",
    $week_start, $week_end);
mysqli_stmt_execute($best_stmt);
$best_products = mysqli_stmt_get_result($best_stmt);

// Helper function
function mysqli_prepare_and_execute(
    $conn, $sql, $types, ...$params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

$week_stats = mysqli_fetch_assoc(
    mysqli_prepare_and_execute(
        $conn,
        "SELECT
            COUNT(*) as total_orders,
            COALESCE(SUM(total),0) as total_revenue,
            COALESCE(AVG(total),0) as avg_order
         FROM orders
         WHERE DATE(order_date) BETWEEN ? AND ?
         AND status != 'Cancelled'",
        "ss", $week_start, $week_end
    )
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Weekly Sales – Admin</title>

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

.admin-wrapper { display: flex; min-height: 100vh; }

/* SIDEBAR */
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

.sidebar-nav a i { width: 18px; font-size: 14px; }

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

/* MAIN */
.admin-main {
  margin-left: 250px;
  padding: 32px;
  width: 100%;
}

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

.week-badge {
  background: white;
  border: 1.5px solid #e5e7eb;
  padding: 10px 20px;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 700;
  color: #111;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.week-badge i { color: #dc2626; }

/* REPORT TABS */
.report-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.report-tab {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 10px 20px;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.3s ease;
  border: 1.5px solid #e5e7eb;
  color: #6b7280;
  background: white;
}

.report-tab:hover {
  border-color: #dc2626;
  color: #dc2626;
  transform: translateY(-2px);
}

.report-tab.active {
  background: #dc2626;
  color: white;
  border-color: #dc2626;
  box-shadow: 0 6px 16px rgba(220,38,38,0.3);
}

/* STAT CARDS */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3,1fr);
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

.stat-card.red::before    { background: linear-gradient(90deg,#dc2626,#ef4444); }
.stat-card.blue::before   { background: linear-gradient(90deg,#2563eb,#3b82f6); }
.stat-card.green::before  { background: linear-gradient(90deg,#16a34a,#22c55e); }

.stat-icon {
  width: 52px; height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  margin-bottom: 16px;
}

.stat-card.red   .stat-icon { background:#fee2e2; color:#dc2626; }
.stat-card.blue  .stat-icon { background:#dbeafe; color:#2563eb; }
.stat-card.green .stat-icon { background:#dcfce7; color:#16a34a; }

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

/* PRINT BTN */
.print-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 18px;
  background: #111;
  color: white;
  border: none;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: 'Poppins', sans-serif;
}

.print-btn:hover {
  background: #333;
  transform: translateY(-2px);
}

/* DATA TABLE */
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
  padding: 13px 18px;
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

/* STATUS BADGE */
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

/* BEST PRODUCT ROW */
.best-prod-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.2s;
}

.best-prod-row:last-child { border-bottom: none; }
.best-prod-row:hover { background: #fafafa; }

.prod-rank {
  width: 28px; height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  flex-shrink: 0;
  margin-right: 12px;
}

.rank-1 { background:#fef3c7; color:#d97706; }
.rank-2 { background:#f3f4f6; color:#6b7280; }
.rank-3 { background:#fef3c7; color:#d97706; }
.rank-other { background:#f3f4f6; color:#9ca3af; }

.prod-info { flex: 1; }
.prod-name { font-size:13px; font-weight:700; color:#111; }
.prod-qty  { font-size:11px; color:#9ca3af; margin-top:2px; }
.prod-rev  { font-size:13px; font-weight:800; color:#dc2626; }

/* EMPTY STATE */
.empty-state {
  text-align: center;
  padding: 50px 20px;
}

.empty-state i {
  font-size: 50px;
  color: #e5e7eb;
  margin-bottom: 16px;
  display: block;
}

.empty-state p {
  font-size: 14px;
  color: #9ca3af;
  font-weight: 500;
}

@media print {
  .admin-sidebar { display: none; }
  .admin-main    { margin-left: 0; }
  .report-tabs   { display: none; }
  .print-btn     { display: none; }
}

@media (max-width: 900px) {
  .stats-grid  { grid-template-columns: 1fr; }
  .charts-grid { grid-template-columns: 1fr; }
  .tables-grid { grid-template-columns: 1fr; }
  .admin-sidebar { transform: translateX(-100%); }
  .admin-main    { margin-left: 0; padding: 16px; }
}
</style>
</head>
<body>

<div class="admin-wrapper">

  <!-- SIDEBAR -->
  <div class="admin-sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-logo-circle">
        <img src="../../images/logo.png" alt="Logo"
          onerror="this.parentElement.innerHTML=
          '<b style=\'color:#dc2626;font-size:14px;\'>iP</b>'">
      </div>
      <span>ADMIN PANEL</span>
    </div>

    <div class="sidebar-section">Main</div>
    <nav class="sidebar-nav">
      <a href="../dashboard.php">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
      </a>
      <a href="../../add_product.php">
        <i class="fa-solid fa-plus"></i>
        Add Product
      </a>
      <a href="../orders.php">
        <i class="fa-solid fa-box"></i>
        Orders
      </a>
    </nav>

    <div class="sidebar-section">Sales Reports</div>
    <nav class="sidebar-nav">
      <a href="weekly.php" class="active">
        <i class="fa-solid fa-calendar-week"></i>
        Weekly Sales
      </a>
      <a href="monthly.php">
        <i class="fa-solid fa-calendar"></i>
        Monthly Sales
      </a>
      <a href="yearly.php">
        <i class="fa-solid fa-calendar-days"></i>
        Yearly Sales
      </a>
    </nav>

    <div class="sidebar-logout">
      <a href="../../logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout (<?= htmlspecialchars($_SESSION['username']) ?>)
      </a>
    </div>
  </div>

  <!-- MAIN -->
  <div class="admin-main">

    <!-- TOPBAR -->
    <div class="admin-topbar">
      <div>
        <h2>Weekly Sales Report</h2>
        <p>Sales performance for this week</p>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;
        align-items:center;">
        <div class="week-badge">
          <i class="fa-regular fa-calendar"></i>
          <?= date('d M', strtotime($week_start)) ?>
          –
          <?= date('d M Y', strtotime($week_end)) ?>
        </div>
        <button class="print-btn" onclick="window.print()">
          <i class="fa-solid fa-print"></i>
          Print Report
        </button>
      </div>
    </div>

    <!-- REPORT TABS -->
    <div class="report-tabs">
      <a href="weekly.php" class="report-tab active">
        <i class="fa-solid fa-calendar-week"></i>
        Weekly
      </a>
      <a href="monthly.php" class="report-tab">
        <i class="fa-solid fa-calendar"></i>
        Monthly
      </a>
      <a href="yearly.php" class="report-tab">
        <i class="fa-solid fa-calendar-days"></i>
        Yearly
      </a>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-grid">
      <div class="stat-card red">
        <div class="stat-icon">
          <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div class="stat-value">
          Rs <?= number_format($week_stats['total_revenue'],0) ?>
        </div>
        <div class="stat-label">Weekly Revenue</div>
      </div>

      <div class="stat-card blue">
        <div class="stat-icon">
          <i class="fa-solid fa-box-archive"></i>
        </div>
        <div class="stat-value">
          <?= $week_stats['total_orders'] ?>
        </div>
        <div class="stat-label">Total Orders</div>
      </div>

      <div class="stat-card green">
        <div class="stat-icon">
          <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="stat-value">
          Rs <?= number_format($week_stats['avg_order'],0) ?>
        </div>
        <div class="stat-label">Average Order Value</div>
      </div>
    </div>

    <!-- CHARTS -->
    <div class="charts-grid">

      <div class="chart-card">
        <h3>
          <i class="fa-solid fa-chart-bar"></i>
          Daily Revenue This Week
        </h3>
        <canvas id="weeklyChart" height="130"></canvas>
      </div>

      <div class="chart-card">
        <h3>
          <i class="fa-solid fa-chart-line"></i>
          Daily Orders
        </h3>
        <canvas id="ordersChart" height="200"></canvas>
      </div>

    </div>

    <!-- TABLES -->
    <div class="tables-grid">

      <!-- ORDERS TABLE -->
      <div class="table-card">
        <div class="table-card-head">
          <h3>
            <i class="fa-solid fa-receipt"></i>
            This Week's Orders
          </h3>
          <span style="font-size:13px;color:#6b7280;
            font-weight:600;">
            <?= count($orders_array) ?> orders
          </span>
        </div>

        <?php if (empty($orders_array)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-box-open"></i>
          <p>No orders this week yet.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Day</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders_array as $row): ?>
              <tr>
                <td>
                  <strong style="color:#dc2626;">
                    #<?= str_pad($row['id'],4,'0',STR_PAD_LEFT) ?>
                  </strong>
                </td>
                <td>
                  <?= htmlspecialchars($row['name']) ?>
                </td>
                <td style="color:#6b7280;font-size:12px;">
                  <?= $row['day_name'] ?><br>
                  <span style="font-size:11px;color:#9ca3af;">
                    <?= date('d M Y',
                      strtotime($row['order_day'])) ?>
                  </span>
                </td>
                <td>
                  <strong style="color:#dc2626;">
                    Rs <?= number_format($row['total'],2) ?>
                  </strong>
                </td>
                <td>
                  <span class="status-badge
                    badge-<?= strtolower($row['status']) ?>">
                    <?= $row['status'] ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- BEST SELLERS -->
      <div class="table-card">
        <div class="table-card-head">
          <h3>
            <i class="fa-solid fa-star"></i>
            Best Sellers
          </h3>
        </div>

        <?php
        $rank = 1;
        $has_products = false;
        while ($prod = mysqli_fetch_assoc($best_products)):
          $has_products = true;
          $rank_class = $rank <= 3
            ? 'rank-'.$rank : 'rank-other';
        ?>
        <div class="best-prod-row">
          <span class="prod-rank <?= $rank_class ?>">
            <?= $rank ?>
          </span>
          <div class="prod-info">
            <div class="prod-name">
              <?= htmlspecialchars($prod['product_name']) ?>
            </div>
            <div class="prod-qty">
              <?= $prod['total_qty'] ?> units sold
            </div>
          </div>
          <div class="prod-rev">
            Rs <?= number_format($prod['total_revenue'],0) ?>
          </div>
        </div>
        <?php
          $rank++;
        endwhile; ?>

        <?php if (!$has_products): ?>
        <div class="empty-state">
          <i class="fa-solid fa-box-open"></i>
          <p>No sales this week.</p>
        </div>
        <?php endif; ?>
      </div>

    </div>

  </div>
</div>

<script>
// DAILY REVENUE BAR CHART
new Chart(document.getElementById('weeklyChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      label: 'Revenue (Rs)',
      data: <?= json_encode($chart_revenue) ?>,
      backgroundColor: [
        'rgba(220,38,38,0.8)',
        'rgba(220,38,38,0.7)',
        'rgba(220,38,38,0.8)',
        'rgba(220,38,38,0.7)',
        'rgba(220,38,38,0.8)',
        'rgba(220,38,38,0.6)',
        'rgba(220,38,38,0.5)',
      ],
      borderColor: '#dc2626',
      borderWidth: 2,
      borderRadius: 8,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
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
          font: { family: 'Poppins', size: 10 },
          callback: function(val, index) {
            return this.getLabelForValue(val).slice(0,3);
          }
        }
      }
    }
  }
});

// DAILY ORDERS LINE CHART
new Chart(document.getElementById('ordersChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      label: 'Orders',
      data: <?= json_encode($chart_orders) ?>,
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37,99,235,0.08)',
      borderWidth: 3,
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#2563eb',
      pointRadius: 5,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: '#f3f4f6' },
        ticks: {
          stepSize: 1,
          font: { family: 'Poppins', size: 11 }
        }
      },
      x: {
        grid: { display: false },
        ticks: {
          font: { family: 'Poppins', size: 10 },
          callback: function(val, index) {
            return this.getLabelForValue(val).slice(0,3);
          }
        }
      }
    }
  }
});
</script>

</body>
</html>