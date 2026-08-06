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

$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search) {
    $where   .= " AND (name LIKE ? OR phone LIKE ? OR id = ?)";
    $s        = "%$search%";
    $sid      = is_numeric($search) ? (int)$search : 0;
    $params[] = $s;
    $params[] = $s;
    $params[] = $sid;
    $types   .= "ssi";
}

if ($status_filter) {
    $where   .= " AND status = ?";
    $params[] = $status_filter;
    $types   .= "s";
}

$stmt = mysqli_prepare($conn,
    "SELECT * FROM orders $where ORDER BY order_date DESC");
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
$total  = mysqli_num_rows($orders);

// Status counts for tabs
$all_count        = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM orders"))['t'];
$pending_count    = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM orders WHERE status='Pending'"))['t'];
$processing_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM orders WHERE status='Processing'"))['t'];
$delivered_count  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM orders WHERE status='Delivered'"))['t'];
$cancelled_count  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM orders WHERE status='Cancelled'"))['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders – Admin Panel</title>

<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
  rel="stylesheet">

<style>
* {
  margin: 0; padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
}

body { background: #f0f2f5; color: #111; }

/* SIDEBAR */
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

/* STATUS TABS */
.status-tabs {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 22px;
}

.status-tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
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

.status-tab:hover {
  border-color: #dc2626;
  color: #dc2626;
  transform: translateY(-2px);
}

.status-tab.active {
  background: #dc2626;
  color: white;
  border-color: #dc2626;
  box-shadow: 0 6px 16px rgba(220,38,38,0.3);
}

.tab-count {
  background: rgba(0,0,0,0.1);
  border-radius: 50px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 800;
}

.status-tab.active .tab-count {
  background: rgba(255,255,255,0.25);
}

/* SEARCH BAR */
.search-bar {
  background: white;
  border-radius: 16px;
  padding: 20px 24px;
  margin-bottom: 22px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: flex-end;
}

.search-group { flex: 1; min-width: 200px; }

.search-label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #6b7280;
  margin-bottom: 8px;
}

.search-input-wrap { position: relative; }

.search-input-wrap i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 14px;
}

.search-input {
  width: 100%;
  padding: 11px 16px 11px 40px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 14px;
  outline: none;
  transition: all 0.3s ease;
  background: #f9fafb;
  font-family: 'Poppins', sans-serif;
}

.search-input:focus {
  border-color: #dc2626;
  background: white;
  box-shadow: 0 0 0 4px rgba(220,38,38,0.08);
}

.search-select {
  width: 100%;
  padding: 11px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 14px;
  outline: none;
  transition: all 0.3s ease;
  background: #f9fafb;
  font-family: 'Poppins', sans-serif;
  cursor: pointer;
}

.search-select:focus {
  border-color: #dc2626;
  background: white;
}

.search-btn {
  padding: 12px 24px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: 'Poppins', sans-serif;
  white-space: nowrap;
}

.search-btn:hover {
  background: #b91c1c;
  transform: translateY(-2px);
}

.clear-btn {
  padding: 12px 20px;
  background: #f3f4f6;
  color: #6b7280;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: 'Poppins', sans-serif;
  white-space: nowrap;
}

.clear-btn:hover {
  background: #e5e7eb;
  color: #111;
}

/* RESULTS INFO */
.results-info {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 16px;
  font-weight: 500;
}

.results-info strong { color: #111; font-weight: 800; }

/* TABLE CARD */
.table-card {
  background: white;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 2px 14px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead th {
  background: #111;
  color: white;
  padding: 14px 18px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  text-align: left;
  white-space: nowrap;
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
  padding: 5px 14px;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.badge-pending    { background:#fef3c7; color:#d97706; }
.badge-processing { background:#dbeafe; color:#2563eb; }
.badge-delivered  { background:#dcfce7; color:#16a34a; }
.badge-cancelled  { background:#fee2e2; color:#dc2626; }

/* DOT */
.dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  display: inline-block;
  animation: blink 1.5s infinite;
}

.dot-pending    { background: #d97706; }
.dot-processing { background: #2563eb; }
.dot-delivered  { background: #16a34a; animation: none; }
.dot-cancelled  { background: #dc2626; animation: none; }

@keyframes blink {
  0%,100% { opacity:1; }
  50%     { opacity:0.3; }
}

/* ACTION BUTTONS */
.action-btns {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.tbl-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 13px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-view {
  background: #f3f4f6;
  color: #111;
  border: 1px solid #e5e7eb;
}

.btn-view:hover {
  background: #111;
  color: white;
}

.btn-edit {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.btn-edit:hover {
  background: #dc2626;
  color: white;
}

.btn-invoice {
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
}

.btn-invoice:hover {
  background: #16a34a;
  color: white;
}

/* EMPTY STATE */
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-state i {
  font-size: 60px;
  color: #e5e7eb;
  margin-bottom: 20px;
  display: block;
}

.empty-state h3 {
  font-size: 20px;
  font-weight: 800;
  color: #111;
  margin-bottom: 8px;
}

.empty-state p {
  font-size: 14px;
  color: #9ca3af;
}

@media (max-width: 768px) {
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
        <img src="../images/logo.png" alt="Logo"
          onerror="this.parentElement.innerHTML=
          '<b style=\'color:#dc2626;font-size:14px;\'>iP</b>'">
      </div>
      <span>ADMIN PANEL</span>
    </div>

    <div class="sidebar-section">Main</div>
    <nav class="sidebar-nav">
      <a href="dashboard.php">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
      </a>
      <a href="../add_product.php">
        <i class="fa-solid fa-plus"></i>
        Add Product
      </a>
      <a href="orders.php" class="active">
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

  <!-- MAIN -->
  <div class="admin-main">

    <div class="admin-topbar">
      <div>
        <h2>All Orders</h2>
        <p>Manage and track all customer orders</p>
      </div>
    </div>

    <!-- STATUS TABS -->
    <div class="status-tabs">
      <a href="orders.php"
        class="status-tab <?= !$status_filter?'active':'' ?>">
        <i class="fa-solid fa-list"></i>
        All
        <span class="tab-count"><?= $all_count ?></span>
      </a>
      <a href="orders.php?status=Pending"
        class="status-tab <?= $status_filter==='Pending'?'active':'' ?>">
        <i class="fa-solid fa-clock"></i>
        Pending
        <span class="tab-count"><?= $pending_count ?></span>
      </a>
      <a href="orders.php?status=Processing"
        class="status-tab <?= $status_filter==='Processing'?'active':'' ?>">
        <i class="fa-solid fa-gear"></i>
        Processing
        <span class="tab-count"><?= $processing_count ?></span>
      </a>
      <a href="orders.php?status=Delivered"
        class="status-tab <?= $status_filter==='Delivered'?'active':'' ?>">
        <i class="fa-solid fa-circle-check"></i>
        Delivered
        <span class="tab-count"><?= $delivered_count ?></span>
      </a>
      <a href="orders.php?status=Cancelled"
        class="status-tab <?= $status_filter==='Cancelled'?'active':'' ?>">
        <i class="fa-solid fa-circle-xmark"></i>
        Cancelled
        <span class="tab-count"><?= $cancelled_count ?></span>
      </a>
    </div>

    <!-- SEARCH BAR -->
    <form method="GET">
      <?php if ($status_filter): ?>
      <input type="hidden" name="status"
        value="<?= htmlspecialchars($status_filter) ?>">
      <?php endif; ?>
      <div class="search-bar">
        <div class="search-group">
          <label class="search-label">Search</label>
          <div class="search-input-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search"
              class="search-input"
              placeholder="Name, phone or order ID..."
              value="<?= htmlspecialchars($search) ?>">
          </div>
        </div>

        <div class="search-group"
          style="flex:0;min-width:180px;">
          <label class="search-label">Status</label>
          <select name="status" class="search-select">
            <option value="">All Statuses</option>
            <?php
            $statuses = ['Pending','Processing','Delivered','Cancelled'];
            foreach ($statuses as $s): ?>
            <option value="<?= $s ?>"
              <?= $status_filter===$s?'selected':'' ?>>
              <?= $s ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="search-btn">
          <i class="fa-solid fa-magnifying-glass"></i>
          Search
        </button>

        <?php if ($search || $status_filter): ?>
        <a href="orders.php" class="clear-btn">
          <i class="fa-solid fa-xmark"></i>
          Clear
        </a>
        <?php endif; ?>
      </div>
    </form>

    <!-- RESULTS INFO -->
    <div class="results-info">
      Showing <strong><?= $total ?></strong> orders
      <?php if ($status_filter): ?>
        with status <strong><?= $status_filter ?></strong>
      <?php endif; ?>
      <?php if ($search): ?>
        matching "<strong><?= htmlspecialchars($search) ?></strong>"
      <?php endif; ?>
    </div>

    <!-- ORDERS TABLE -->
    <div class="table-card">
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Phone</th>
              <th>Address</th>
              <th>Total</th>
              <th>Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($total === 0): ?>
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <i class="fa-solid fa-box-open"></i>
                  <h3>No orders found</h3>
                  <p>Try a different search or filter.</p>
                </div>
              </td>
            </tr>
            <?php else: ?>
            <?php while ($row = mysqli_fetch_assoc($orders)): ?>
            <tr>
              <td>
                <strong style="color:#dc2626;">
                  #<?= str_pad($row['id'],4,'0',STR_PAD_LEFT) ?>
                </strong>
              </td>
              <td>
                <div style="font-weight:700;">
                  <?= htmlspecialchars($row['name']) ?>
                </div>
              </td>
              <td style="color:#6b7280;">
                <?= htmlspecialchars($row['phone']) ?>
              </td>
              <td style="max-width:160px;
                white-space:nowrap;
                overflow:hidden;
                text-overflow:ellipsis;
                color:#6b7280;font-size:12px;"
                title="<?= htmlspecialchars($row['address']) ?>">
                <?= htmlspecialchars($row['address']) ?>
              </td>
              <td>
                <strong style="color:#dc2626;">
                  Rs <?= number_format($row['total'],2) ?>
                </strong>
              </td>
              <td style="color:#6b7280;font-size:12px;
                white-space:nowrap;">
                <?= date('d M Y', strtotime($row['order_date'])) ?>
                <div style="font-size:11px;color:#9ca3af;">
                  <?= date('h:i A', strtotime($row['order_date'])) ?>
                </div>
              </td>
              <td>
                <?php $sl = strtolower($row['status']); ?>
                <span class="status-badge badge-<?= $sl ?>">
                  <span class="dot dot-<?= $sl ?>"></span>
                  <?= $row['status'] ?>
                </span>
              </td>
              <td>
                <div class="action-btns">
                  <a href="order_details.php?id=<?= $row['id'] ?>"
                    class="tbl-btn btn-view">
                    <i class="fa-solid fa-eye"></i>
                    View
                  </a>
                  <a href="update_status.php?id=<?= $row['id'] ?>"
                    class="tbl-btn btn-edit">
                    <i class="fa-solid fa-pen"></i>
                    Status
                  </a>
                  <a href="../invoice.php?id=<?= $row['id'] ?>"
                    class="tbl-btn btn-invoice"
                    target="_blank">
                    <i class="fa-solid fa-receipt"></i>
                    Invoice
                  </a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

</body>
</html>