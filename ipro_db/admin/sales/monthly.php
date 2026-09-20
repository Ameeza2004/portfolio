<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../db.php';

if (!isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// SELECTED MONTH AND YEAR
$month = isset($_GET['month'])
    ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])
    ? (int)$_GET['year']  : (int)date('Y');

$month_start = "$year-" .
    str_pad($month,2,'0',STR_PAD_LEFT) . "-01";
$month_end   = date('Y-m-t', strtotime($month_start));
$month_name  = date('F', strtotime($month_start));

// MONTHLY STATS
$s = mysqli_prepare($conn,
    "SELECT
        COUNT(*) as total_orders,
        COALESCE(SUM(total),0) as total_revenue,
        COALESCE(AVG(total),0) as avg_order,
        COUNT(CASE WHEN status='Delivered'
            THEN 1 END) as delivered,
        COUNT(CASE WHEN status='Pending'
            THEN 1 END) as pending,
        COUNT(CASE WHEN status='Processing'
            THEN 1 END) as processing,
        COUNT(CASE WHEN status='Cancelled'
            THEN 1 END) as cancelled
     FROM orders
     WHERE MONTH(order_date) = ?
     AND YEAR(order_date) = ?");
mysqli_stmt_bind_param($s, "ii", $month, $year);
mysqli_stmt_execute($s);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

// DAILY CHART DATA
$daily = mysqli_prepare($conn,
    "SELECT
        DAY(order_date) as day,
        SUM(total) as revenue,
        COUNT(*) as orders
     FROM orders
     WHERE MONTH(order_date) = ?
     AND YEAR(order_date) = ?
     AND status != 'Cancelled'
     GROUP BY DAY(order_date)
     ORDER BY day");
mysqli_stmt_bind_param($daily, "ii", $month, $year);
mysqli_stmt_execute($daily);
$daily_result = mysqli_stmt_get_result($daily);

$days_in_month = (int)date('t',
    strtotime($month_start));
$chart_labels  = [];
$chart_revenue = [];
$daily_map     = [];

while ($row = mysqli_fetch_assoc($daily_result)) {
    $daily_map[(int)$row['day']] = [
        'revenue' => (float)$row['revenue'],
        'orders'  => (int)$row['orders'],
    ];
}

for ($d = 1; $d <= $days_in_month; $d++) {
    $chart_labels[]  = $d;
    $chart_revenue[] = $daily_map[$d]['revenue'] ?? 0;
}

// ALL ORDERS THIS MONTH
$orders_stmt = mysqli_prepare($conn,
    "SELECT * FROM orders
     WHERE MONTH(order_date) = ?
     AND YEAR(order_date) = ?
     ORDER BY order_date DESC");
mysqli_stmt_bind_param($orders_stmt, "ii",
    $month, $year);
mysqli_stmt_execute($orders_stmt);
$orders = mysqli_stmt_get_result($orders_stmt);
$orders_array = [];
while ($row = mysqli_fetch_assoc($orders)) {
    $orders_array[] = $row;
}

// BEST SELLERS THIS MONTH
$best_stmt = mysqli_prepare($conn,
    "SELECT
        oi.product_name,
        SUM(oi.quantity) as total_qty,
        SUM(oi.subtotal) as total_revenue
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     WHERE MONTH(o.order_date) = ?
     AND YEAR(o.order_date) = ?
     AND o.status != 'Cancelled'
     GROUP BY oi.product_name
     ORDER BY total_qty DESC
     LIMIT 5");
mysqli_stmt_bind_param($best_stmt, "ii",
    $month, $year);
mysqli_stmt_execute($best_stmt);
$best_products = mysqli_stmt_get_result($best_stmt);
$best_array = [];
while ($row = mysqli_fetch_assoc($best_products)) {
    $best_array[] = $row;
}

// AVAILABLE YEARS
$years_result = mysqli_query($conn,
    "SELECT DISTINCT YEAR(order_date) as yr
     FROM orders ORDER BY yr DESC");
$available_years = [];
while ($row = mysqli_fetch_assoc($years_result)) {
    $available_years[] = $row['yr'];
}
if (empty($available_years)) {
    $available_years[] = date('Y');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monthly Sales – Admin</title>
<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
  rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
* {
  margin:0; padding:0;
  box-sizing:border-box;
  font-family:'Poppins','Segoe UI',Arial,sans-serif;
}
body { background:#f0f2f5; color:#111; }
.admin-wrapper { display:flex; min-height:100vh; }

/* SIDEBAR */
.admin-sidebar {
  width:250px; background:#0a0a0a;
  position:fixed; top:0; left:0; bottom:0;
  z-index:100; overflow-y:auto;
  box-shadow:4px 0 24px rgba(0,0,0,0.3);
}
.sidebar-brand {
  padding:26px 22px;
  border-bottom:1px solid rgba(255,255,255,0.08);
  display:flex; align-items:center; gap:12px;
}
.sidebar-logo-circle {
  width:42px; height:42px; background:white;
  border-radius:50%; display:flex;
  align-items:center; justify-content:center;
  flex-shrink:0;
}
.sidebar-logo-circle img {
  width:28px; height:28px; object-fit:contain;
}
.sidebar-brand span {
  color:white; font-size:14px;
  font-weight:800; letter-spacing:2px;
}
.sidebar-section {
  padding:22px 22px 10px; font-size:10px;
  text-transform:uppercase; letter-spacing:2px;
  color:rgba(255,255,255,0.25); font-weight:700;
}
.sidebar-nav a {
  display:flex; align-items:center; gap:12px;
  color:rgba(255,255,255,0.6); text-decoration:none;
  padding:12px 22px; font-size:13px; font-weight:600;
  transition:all 0.3s ease;
  border-left:3px solid transparent;
}
.sidebar-nav a:hover,
.sidebar-nav a.active {
  background:rgba(220,38,38,0.12);
  color:white; border-left-color:#dc2626;
}
.sidebar-nav a i { width:18px; font-size:14px; }
.sidebar-logout {
  position:absolute; bottom:0; left:0; right:0;
  padding:16px;
  border-top:1px solid rgba(255,255,255,0.08);
}
.sidebar-logout a {
  display:flex; align-items:center; gap:10px;
  color:rgba(255,255,255,0.5); text-decoration:none;
  padding:11px 16px; border-radius:10px;
  font-size:13px; font-weight:600;
  transition:all 0.3s ease;
}
.sidebar-logout a:hover {
  background:rgba(220,38,38,0.15); color:#dc2626;
}

/* MAIN */
.admin-main {
  margin-left:250px; padding:32px; width:100%;
}
.admin-topbar {
  display:flex; justify-content:space-between;
  align-items:center; margin-bottom:28px;
  flex-wrap:wrap; gap:12px;
}
.admin-topbar h2 {
  font-size:24px; font-weight:800;
  color:#111; margin-bottom:2px;
}
.admin-topbar p {
  font-size:13px; color:#6b7280; font-weight:400;
}

/* REPORT TABS */
.report-tabs {
  display:flex; gap:8px;
  margin-bottom:22px; flex-wrap:wrap;
}
.report-tab {
  display:inline-flex; align-items:center;
  gap:7px; padding:10px 20px; border-radius:50px;
  font-size:13px; font-weight:700;
  text-decoration:none; transition:all 0.3s ease;
  border:1.5px solid #e5e7eb;
  color:#6b7280; background:white;
}
.report-tab:hover {
  border-color:#dc2626; color:#dc2626;
  transform:translateY(-2px);
}
.report-tab.active {
  background:#dc2626; color:white;
  border-color:#dc2626;
  box-shadow:0 6px 16px rgba(220,38,38,0.3);
}

/* FILTER */
.month-filter {
  background:white; border-radius:16px;
  padding:20px 24px; margin-bottom:24px;
  box-shadow:0 2px 12px rgba(0,0,0,0.06);
  border:1.5px solid #f0f0f0;
  display:flex; align-items:flex-end;
  gap:16px; flex-wrap:wrap;
}
.filter-group { flex:1; min-width:140px; }
.filter-label {
  display:block; font-size:11px; font-weight:700;
  text-transform:uppercase; letter-spacing:1px;
  color:#6b7280; margin-bottom:8px;
}
.filter-select {
  width:100%; padding:11px 16px;
  border:2px solid #e5e7eb; border-radius:12px;
  font-size:14px; outline:none;
  transition:all 0.3s ease; background:#f9fafb;
  font-family:'Poppins',sans-serif; cursor:pointer;
}
.filter-select:focus {
  border-color:#dc2626; background:white;
}
.filter-btn {
  padding:12px 24px; background:#dc2626;
  color:white; border:none; border-radius:12px;
  font-size:14px; font-weight:700; cursor:pointer;
  transition:all 0.3s ease;
  display:flex; align-items:center; gap:8px;
  font-family:'Poppins',sans-serif;
  white-space:nowrap;
}
.filter-btn:hover {
  background:#b91c1c; transform:translateY(-2px);
}
.print-btn {
  padding:12px 20px; background:#111; color:white;
  border:none; border-radius:12px; font-size:14px;
  font-weight:700; cursor:pointer;
  transition:all 0.3s ease;
  display:flex; align-items:center; gap:8px;
  font-family:'Poppins',sans-serif;
  white-space:nowrap;
}
.print-btn:hover {
  background:#333; transform:translateY(-2px);
}

/* STAT CARDS */
.stats-grid {
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:16px; margin-bottom:20px;
}
.stat-card {
  background:white; border-radius:18px;
  padding:22px;
  box-shadow:0 2px 14px rgba(0,0,0,0.06);
  border:1.5px solid #f0f0f0;
  transition:all 0.3s ease;
  position:relative; overflow:hidden;
}
.stat-card:hover {
  transform:translateY(-5px);
  box-shadow:0 14px 40px rgba(0,0,0,0.1);
}
.stat-card::before {
  content:''; position:absolute;
  top:0; left:0; right:0; height:4px;
}
.stat-card.red::before {
  background:linear-gradient(90deg,#dc2626,#ef4444);
}
.stat-card.blue::before {
  background:linear-gradient(90deg,#2563eb,#3b82f6);
}
.stat-card.green::before {
  background:linear-gradient(90deg,#16a34a,#22c55e);
}
.stat-card.orange::before {
  background:linear-gradient(90deg,#d97706,#f59e0b);
}
.stat-icon {
  width:48px; height:48px; border-radius:12px;
  display:flex; align-items:center;
  justify-content:center; font-size:20px;
  margin-bottom:14px;
}
.stat-card.red   .stat-icon { background:#fee2e2; color:#dc2626; }
.stat-card.blue  .stat-icon { background:#dbeafe; color:#2563eb; }
.stat-card.green .stat-icon { background:#dcfce7; color:#16a34a; }
.stat-card.orange .stat-icon{ background:#fef3c7; color:#d97706; }
.stat-value {
  font-size:24px; font-weight:900;
  color:#111; margin-bottom:4px; line-height:1;
}
.stat-label {
  font-size:11px; color:#6b7280; font-weight:600;
  text-transform:uppercase; letter-spacing:0.5px;
}

/* STATUS MINI */
.status-stats {
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:12px; margin-bottom:24px;
}
.status-mini {
  background:white; border-radius:14px;
  padding:16px 18px;
  box-shadow:0 2px 10px rgba(0,0,0,0.05);
  text-align:center; border:1.5px solid #f0f0f0;
  transition:all 0.3s ease;
}
.status-mini:hover { transform:translateY(-3px); }
.status-mini-num {
  font-size:24px; font-weight:900; margin-bottom:4px;
}
.status-mini-label {
  font-size:11px; font-weight:700;
  text-transform:uppercase; letter-spacing:0.5px;
}

/* CHART */
.chart-card {
  background:white; border-radius:18px;
  padding:24px;
  box-shadow:0 2px 14px rgba(0,0,0,0.06);
  border:1.5px solid #f0f0f0; margin-bottom:24px;
}
.chart-card h3 {
  font-size:16px; font-weight:800; color:#111;
  margin-bottom:20px;
  display:flex; align-items:center; gap:8px;
}
.chart-card h3 i { color:#dc2626; }

/* TABLES */
.tables-grid {
  display:grid;
  grid-template-columns:2fr 1fr; gap:20px;
}
.table-card {
  background:white; border-radius:18px;
  overflow:hidden;
  box-shadow:0 2px 14px rgba(0,0,0,0.06);
  border:1.5px solid #f0f0f0;
}
.table-card-head {
  padding:18px 22px;
  border-bottom:1px solid #f3f4f6;
  display:flex; justify-content:space-between;
  align-items:center;
}
.table-card-head h3 {
  font-size:15px; font-weight:800; color:#111;
  display:flex; align-items:center; gap:8px;
}
.table-card-head h3 i { color:#dc2626; }
.data-table {
  width:100%; border-collapse:collapse;
}
.data-table thead th {
  background:#111; color:white;
  padding:12px 16px; font-size:11px;
  font-weight:700; text-transform:uppercase;
  letter-spacing:1px; text-align:left;
}
.data-table tbody td {
  padding:12px 16px; font-size:13px;
  font-weight:500;
  border-bottom:1px solid #f3f4f6; color:#111;
}
.data-table tbody tr:last-child td {
  border-bottom:none;
}
.data-table tbody tr:hover { background:#fafafa; }
.status-badge {
  padding:4px 12px; border-radius:50px;
  font-size:11px; font-weight:700;
  display:inline-block;
}
.badge-pending    { background:#fef3c7; color:#d97706; }
.badge-processing { background:#dbeafe; color:#2563eb; }
.badge-delivered  { background:#dcfce7; color:#16a34a; }
.badge-cancelled  { background:#fee2e2; color:#dc2626; }

.best-prod-row {
  display:flex; align-items:center;
  padding:14px 20px;
  border-bottom:1px solid #f3f4f6;
  gap:12px; transition:background 0.2s;
}
.best-prod-row:last-child { border-bottom:none; }
.best-prod-row:hover { background:#fafafa; }
.prod-rank {
  width:28px; height:28px; border-radius:8px;
  display:flex; align-items:center;
  justify-content:center; font-size:12px;
  font-weight:800; flex-shrink:0;
}
.rank-1 { background:#fef3c7; color:#d97706; }
.rank-2 { background:#f3f4f6; color:#6b7280; }
.rank-3 { background:#fef9ec; color:#cd7f32; }
.rank-other { background:#f3f4f6; color:#9ca3af; }

.empty-state {
  text-align:center; padding:40px 20px;
}
.empty-state i {
  font-size:48px; color:#e5e7eb;
  margin-bottom:14px; display:block;
}
.empty-state p { font-size:14px; color:#9ca3af; }

@media print {
  .admin-sidebar { display:none; }
  .admin-main    { margin-left:0; }
  .report-tabs   { display:none; }
  .print-btn     { display:none; }
  .filter-btn    { display:none; }
}
@media (max-width:1000px) {
  .stats-grid   { grid-template-columns:repeat(2,1fr); }
  .status-stats { grid-template-columns:repeat(2,1fr); }
  .tables-grid  { grid-template-columns:1fr; }
}
@media (max-width:768px) {
  .admin-sidebar { transform:translateX(-100%); }
  .admin-main    { margin-left:0; padding:16px; }
  .stats-grid    { grid-template-columns:1fr 1fr; }
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
      <a href="weekly.php">
        <i class="fa-solid fa-calendar-week"></i>
        Weekly Sales
      </a>
      <a href="monthly.php" class="active">
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

    <div class="admin-topbar">
      <div>
        <h2>Monthly Sales Report</h2>
        <p><?= $month_name ?> <?= $year ?> — Overview</p>
      </div>
      <button class="print-btn" onclick="window.print()">
        <i class="fa-solid fa-print"></i>
        Print Report
      </button>
    </div>

    <!-- REPORT TABS -->
    <div class="report-tabs">
      <a href="weekly.php" class="report-tab">
        <i class="fa-solid fa-calendar-week"></i>
        Weekly
      </a>
      <a href="monthly.php" class="report-tab active">
        <i class="fa-solid fa-calendar"></i>
        Monthly
      </a>
      <a href="yearly.php" class="report-tab">
        <i class="fa-solid fa-calendar-days"></i>
        Yearly
      </a>
    </div>

    <!-- MONTH FILTER -->
    <form method="GET">
      <div class="month-filter">
        <div class="filter-group">
          <label class="filter-label">Month</label>
          <select name="month" class="filter-select">
            <?php
            $months_list = [
              1=>'January',  2=>'February',
              3=>'March',    4=>'April',
              5=>'May',      6=>'June',
              7=>'July',     8=>'August',
              9=>'September',10=>'October',
              11=>'November',12=>'December'
            ];
            foreach ($months_list as $num => $name): ?>
            <option value="<?= $num ?>"
              <?= $month===$num?'selected':'' ?>>
              <?= $name ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label">Year</label>
          <select name="year" class="filter-select">
            <?php foreach ($available_years as $yr): ?>
            <option value="<?= $yr ?>"
              <?= $year===$yr?'selected':'' ?>>
              <?= $yr ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="filter-btn">
          <i class="fa-solid fa-magnifying-glass"></i>
          View Report
        </button>
      </div>
    </form>

    <!-- STAT CARDS -->
    <div class="stats-grid">
      <div class="stat-card red">
        <div class="stat-icon">
          <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div class="stat-value">
          Rs <?= number_format(
            $stats['total_revenue'],0) ?>
        </div>
        <div class="stat-label">Monthly Revenue</div>
      </div>
      <div class="stat-card blue">
        <div class="stat-icon">
          <i class="fa-solid fa-box-archive"></i>
        </div>
        <div class="stat-value">
          <?= $stats['total_orders'] ?>
        </div>
        <div class="stat-label">Total Orders</div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon">
          <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="stat-value">
          Rs <?= number_format($stats['avg_order'],0) ?>
        </div>
        <div class="stat-label">Avg Order Value</div>
      </div>
      <div class="stat-card orange">
        <div class="stat-icon">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-value">
          <?= $stats['delivered'] ?>
        </div>
        <div class="stat-label">Delivered</div>
      </div>
    </div>

    <!-- STATUS BREAKDOWN -->
    <div class="status-stats">
      <div class="status-mini">
        <div class="status-mini-num"
          style="color:#d97706;">
          <?= $stats['pending'] ?>
        </div>
        <div class="status-mini-label"
          style="color:#d97706;">Pending</div>
      </div>
      <div class="status-mini">
        <div class="status-mini-num"
          style="color:#2563eb;">
          <?= $stats['processing'] ?>
        </div>
        <div class="status-mini-label"
          style="color:#2563eb;">Processing</div>
      </div>
      <div class="status-mini">
        <div class="status-mini-num"
          style="color:#16a34a;">
          <?= $stats['delivered'] ?>
        </div>
        <div class="status-mini-label"
          style="color:#16a34a;">Delivered</div>
      </div>
      <div class="status-mini">
        <div class="status-mini-num"
          style="color:#dc2626;">
          <?= $stats['cancelled'] ?>
        </div>
        <div class="status-mini-label"
          style="color:#dc2626;">Cancelled</div>
      </div>
    </div>

    <!-- DAILY CHART -->
    <div class="chart-card">
      <h3>
        <i class="fa-solid fa-chart-area"></i>
        Daily Revenue — <?= $month_name ?> <?= $year ?>
      </h3>
      <canvas id="monthlyChart" height="100"></canvas>
    </div>

    <!-- TABLES -->
    <div class="tables-grid">

      <!-- ORDERS TABLE -->
      <div class="table-card">
        <div class="table-card-head">
          <h3>
            <i class="fa-solid fa-receipt"></i>
            Orders in <?= $month_name ?>
          </h3>
          <span style="font-size:13px;color:#6b7280;
            font-weight:600;">
            <?= count($orders_array) ?> orders
          </span>
        </div>
        <?php if (empty($orders_array)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-box-open"></i>
          <p>No orders in <?= $month_name ?> <?= $year ?>.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
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
                <td style="font-size:12px;color:#6b7280;">
                  <?= date('d M Y',
                    strtotime($row['order_date'])) ?>
                </td>
                <td>
                  <strong style="color:#dc2626;">
                    Rs <?= number_format(
                      $row['total'],2) ?>
                  </strong>
                </td>
                <td>
                  <span class="status-badge
                    badge-<?= strtolower(
                      $row['status']) ?>">
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
        <?php if (empty($best_array)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-box-open"></i>
          <p>No sales in <?= $month_name ?>.</p>
        </div>
        <?php else: ?>
        <?php foreach ($best_array as $i => $prod):
          $rank = $i + 1;
          $rank_class = $rank <= 3
            ? 'rank-'.$rank : 'rank-other';
        ?>
        <div class="best-prod-row">
          <span class="prod-rank <?= $rank_class ?>">
            <?= $rank ?>
          </span>
          <div style="flex:1;">
            <div style="font-size:13px;font-weight:700;
              color:#111;">
              <?= htmlspecialchars(
                $prod['product_name']) ?>
            </div>
            <div style="font-size:11px;color:#9ca3af;
              margin-top:2px;">
              <?= $prod['total_qty'] ?> units sold
            </div>
          </div>
          <div style="font-size:13px;font-weight:800;
            color:#dc2626;">
            Rs <?= number_format(
              $prod['total_revenue'],0) ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>

  </div>
</div>

<script>
new Chart(document.getElementById('monthlyChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      label: 'Revenue (Rs)',
      data: <?= json_encode($chart_revenue) ?>,
      borderColor: '#dc2626',
      backgroundColor: 'rgba(220,38,38,0.08)',
      borderWidth: 3,
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#dc2626',
      pointRadius: 4,
      pointHoverRadius: 7,
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
          font: { family: 'Poppins', size: 11 }
        }
      }
    }
  }
});
</script>

</body>
</html>