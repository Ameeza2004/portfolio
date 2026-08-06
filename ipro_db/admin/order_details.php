<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

if (!isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

$stmt = mysqli_prepare($conn,
    "SELECT * FROM orders WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header("Location: orders.php");
    exit();
}

$istmt = mysqli_prepare($conn,
    "SELECT oi.*, p.image, p.category
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?");
mysqli_stmt_bind_param($istmt, "i", $order_id);
mysqli_stmt_execute($istmt);
$items = mysqli_stmt_get_result($istmt);
$items_array = [];
while ($row = mysqli_fetch_assoc($items)) {
    $items_array[] = $row;
}

$subtotal = 0;
foreach ($items_array as $item) {
    $subtotal += $item['subtotal'];
}
$delivery = $order['total'] - $subtotal;
if ($delivery < 0) $delivery = 0;

$status_colors = [
    'Pending'    => ['bg'=>'#fef3c7','color'=>'#d97706'],
    'Processing' => ['bg'=>'#dbeafe','color'=>'#2563eb'],
    'Delivered'  => ['bg'=>'#dcfce7','color'=>'#16a34a'],
    'Cancelled'  => ['bg'=>'#fee2e2','color'=>'#dc2626'],
];
$sc = $status_colors[$order['status']]
    ?? ['bg'=>'#f3f4f6','color'=>'#6b7280'];

// PAYMENT METHOD DETAILS
$pay_method = $order['payment_method']
    ?? 'Cash on Delivery';

if ($pay_method === 'Card Payment') {
    $pay_icon  = 'fa-credit-card';
    $pay_color = '#2563eb';
} elseif ($pay_method === 'Bank Transfer') {
    $pay_icon  = 'fa-building-columns';
    $pay_color = '#7c3aed';
} else {
    $pay_icon  = 'fa-money-bill-wave';
    $pay_color = '#16a34a';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order #<?= str_pad($order_id,4,'0',STR_PAD_LEFT) ?> – Admin</title>

<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
  rel="stylesheet">

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
  padding:12px 22px; font-size:13px;
  font-weight:600; transition:all 0.3s ease;
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
.top-actions { display:flex; gap:10px; flex-wrap:wrap; }
.btn-action {
  display:inline-flex; align-items:center;
  gap:8px; padding:11px 22px; border-radius:50px;
  font-size:13px; font-weight:700;
  text-decoration:none; transition:all 0.3s ease;
  cursor:pointer; border:none;
  font-family:'Poppins',sans-serif;
}
.btn-red { background:#dc2626; color:white; }
.btn-red:hover {
  background:#b91c1c; transform:translateY(-2px);
  box-shadow:0 8px 20px rgba(220,38,38,0.35);
  color:white;
}
.btn-dark { background:#111; color:white; }
.btn-dark:hover {
  background:#333; transform:translateY(-2px);
  color:white;
}
.btn-outline {
  background:white; color:#6b7280;
  border:1.5px solid #e5e7eb;
}
.btn-outline:hover {
  background:#f3f4f6; color:#111;
  transform:translateY(-2px);
}

/* LAYOUT */
.detail-layout {
  display:grid;
  grid-template-columns:1fr 340px;
  gap:22px; align-items:start;
}

/* CARDS */
.detail-card {
  background:white; border-radius:18px;
  padding:26px;
  box-shadow:0 2px 14px rgba(0,0,0,0.06);
  border:1.5px solid #f0f0f0;
  margin-bottom:20px;
  animation:fadeUp 0.5s ease both;
}
@keyframes fadeUp {
  from { opacity:0; transform:translateY(16px); }
  to   { opacity:1; transform:translateY(0); }
}
.card-title {
  font-size:16px; font-weight:800; color:#111;
  margin-bottom:20px; padding-bottom:14px;
  border-bottom:2px solid #f3f4f6;
  display:flex; align-items:center; gap:10px;
}
.card-title i { color:#dc2626; }

/* INFO ROWS */
.info-row {
  display:flex; align-items:flex-start;
  gap:12px; margin-bottom:16px;
  padding-bottom:16px; border-bottom:1px solid #f3f4f6;
}
.info-row:last-child {
  border-bottom:none; margin-bottom:0; padding-bottom:0;
}
.info-icon {
  width:36px; height:36px; border-radius:10px;
  background:#fef2f2; display:flex;
  align-items:center; justify-content:center;
  font-size:14px; color:#dc2626; flex-shrink:0;
}
.info-label {
  font-size:11px; font-weight:700;
  text-transform:uppercase; letter-spacing:1px;
  color:#9ca3af; margin-bottom:3px;
}
.info-value {
  font-size:14px; font-weight:600;
  color:#111; line-height:1.5;
}

/* STATUS CHIP */
.status-chip {
  display:inline-flex; align-items:center;
  gap:7px; padding:7px 16px; border-radius:50px;
  font-size:13px; font-weight:700;
}

/* PAYMENT BADGE */
.payment-badge {
  display:inline-flex; align-items:center;
  gap:7px; padding:7px 16px; border-radius:50px;
  font-size:13px; font-weight:700;
}

/* ITEMS TABLE */
.items-table {
  width:100%; border-collapse:collapse;
}
.items-table thead th {
  background:#111; color:white;
  padding:13px 16px; font-size:11px;
  font-weight:700; text-transform:uppercase;
  letter-spacing:1px; text-align:left;
}
.items-table thead th:last-child { text-align:right; }
.items-table tbody td {
  padding:14px 16px; font-size:13px;
  font-weight:500; border-bottom:1px solid #f3f4f6;
  color:#111; vertical-align:middle;
}
.items-table tbody tr:last-child td {
  border-bottom:none;
}
.items-table tbody tr:hover { background:#fafafa; }
.items-table tbody td:last-child {
  text-align:right; font-weight:800; color:#dc2626;
}
.product-cell {
  display:flex; align-items:center; gap:12px;
}
.product-img {
  width:50px; height:50px; border-radius:10px;
  object-fit:cover; background:#f5f5f5; flex-shrink:0;
}
.product-name {
  font-size:14px; font-weight:700;
  color:#111; margin-bottom:2px;
}
.product-cat { font-size:11px; color:#9ca3af; }
.row-num {
  width:26px; height:26px; background:#f3f4f6;
  border-radius:8px; display:inline-flex;
  align-items:center; justify-content:center;
  font-size:12px; font-weight:700; color:#6b7280;
}

/* TOTALS */
.totals-box { margin-top:4px; }
.total-row {
  display:flex; justify-content:space-between;
  align-items:center; padding:11px 0;
  border-bottom:1px solid #f3f4f6; font-size:14px;
}
.total-row:last-child { border-bottom:none; }
.total-row span:first-child {
  color:#6b7280; font-weight:500;
}
.total-row span:last-child {
  color:#111; font-weight:700;
}
.total-row.free span:last-child {
  color:#16a34a; font-weight:800;
}
.grand-row {
  display:flex; justify-content:space-between;
  align-items:center; background:#111;
  padding:16px 20px; border-radius:12px;
  margin-top:12px;
}
.grand-row span:first-child {
  font-size:14px; font-weight:700;
  color:rgba(255,255,255,0.6);
  text-transform:uppercase; letter-spacing:1px;
}
.grand-row span:last-child {
  font-size:22px; font-weight:900; color:#dc2626;
}

/* TIMELINE */
.timeline {
  display:flex; align-items:center;
  margin-bottom:6px;
}
.tl-step {
  display:flex; flex-direction:column;
  align-items:center; gap:6px; flex:1;
}
.tl-dot {
  width:30px; height:30px; border-radius:50%;
  display:flex; align-items:center;
  justify-content:center; font-size:12px;
  border:2px solid #e5e7eb;
  background:white; color:#9ca3af; z-index:1;
}
.tl-dot.done {
  background:#dc2626; border-color:#dc2626;
  color:white;
}
.tl-dot.current {
  border-color:#dc2626; color:#dc2626;
  box-shadow:0 0 0 4px rgba(220,38,38,0.15);
  animation:pulse 2s infinite;
}
@keyframes pulse {
  0%,100% { box-shadow:0 0 0 4px rgba(220,38,38,0.15); }
  50%     { box-shadow:0 0 0 8px rgba(220,38,38,0.08); }
}
.tl-label {
  font-size:10px; font-weight:600;
  color:#9ca3af; text-align:center;
}
.tl-label.done    { color:#dc2626; }
.tl-label.current { color:#dc2626; font-weight:800; }
.tl-line {
  flex:1; height:2px; background:#e5e7eb;
  margin-bottom:18px;
}
.tl-line.done { background:#dc2626; }

/* QUICK ACTIONS */
.qa-btn {
  display:flex; align-items:center; gap:12px;
  padding:14px 18px; border-radius:12px;
  font-size:14px; font-weight:700;
  text-decoration:none; transition:all 0.3s ease;
  cursor:pointer; border:none;
  font-family:'Poppins',sans-serif;
  margin-bottom:10px;
}
.qa-btn:last-child { margin-bottom:0; }
.qa-red { background:#dc2626; color:white; }
.qa-red:hover {
  background:#b91c1c; transform:translateY(-2px);
  box-shadow:0 8px 20px rgba(220,38,38,0.35);
  color:white;
}
.qa-dark { background:#111; color:white; }
.qa-dark:hover {
  background:#333; transform:translateY(-2px);
  color:white;
}
.qa-light { background:#f3f4f6; color:#111; }
.qa-light:hover {
  background:#e5e7eb; transform:translateY(-2px);
}

@media (max-width:900px) {
  .detail-layout { grid-template-columns:1fr; }
  .admin-sidebar { transform:translateX(-100%); }
  .admin-main    { margin-left:0; padding:16px; }
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
        <h2>
          Order #<?= str_pad($order_id,4,'0',STR_PAD_LEFT) ?>
        </h2>
        <p>Order details and item breakdown</p>
      </div>

      <div class="top-actions">
  <a href="transaction_details.php?id=<?= $order_id ?>"
    class="btn-action btn-dark"
    style="background:#7c3aed;">
    <i class="fa-solid fa-money-bill-transfer"></i>
    Transaction
  </a>
  <a href="update_status.php?id=<?= $order_id ?>"
    class="btn-action btn-red">
    
      <div class="top-actions">
        <a href="update_status.php?id=<?= $order_id ?>"
          class="btn-action btn-red">
          <i class="fa-solid fa-pen"></i>
          Update Status
        </a>
        <a href="../invoice.php?id=<?= $order_id ?>"
          class="btn-action btn-dark" target="_blank">
          <i class="fa-solid fa-receipt"></i>
          View Invoice
        </a>
        <a href="orders.php"
          class="btn-action btn-outline">
          <i class="fa-solid fa-arrow-left"></i>
          Back
        </a>
      </div>
    </div>

    <div class="detail-layout">

      <!-- LEFT -->
      <div>

        <!-- ORDER ITEMS -->
        <div class="detail-card">
          <div class="card-title">
            <i class="fa-solid fa-box-open"></i>
            Order Items
          </div>
          <div style="overflow-x:auto;
            border-radius:12px;overflow:hidden;
            border:1px solid #f0f0f0;">
            <table class="items-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Product</th>
                  <th>Unit Price</th>
                  <th>Qty</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1;
                foreach ($items_array as $item): ?>
                <tr>
                  <td>
                    <span class="row-num">
                      <?= $i++ ?>
                    </span>
                  </td>
                  <td>
                    <div class="product-cell">
                      <img
                        src="../uploads/<?= htmlspecialchars(
                          $item['image'] ?? '') ?>"
                        class="product-img"
                        onerror="this.src='../images/logo.png'"
                        alt="">
                      <div>
                        <div class="product-name">
                          <?= htmlspecialchars(
                            $item['product_name']) ?>
                        </div>
                        <div class="product-cat">
                          <?= htmlspecialchars(
                            $item['category']
                            ?? 'Tech Accessory') ?>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    Rs <?= number_format($item['price'],2) ?>
                  </td>
                  <td>
                    <strong><?= $item['quantity'] ?></strong>
                  </td>
                  <td>
                    Rs <?= number_format(
                      $item['subtotal'],2) ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- TOTALS -->
          <div class="totals-box">
            <div class="total-row">
              <span>Subtotal</span>
              <span>
                Rs <?= number_format($subtotal,2) ?>
              </span>
            </div>
            <div class="total-row
              <?= $delivery==0?'free':'' ?>">
              <span>Delivery</span>
              <span>
                <?= $delivery==0
                  ? '🎉 FREE'
                  : 'Rs '.number_format($delivery,2) ?>
              </span>
            </div>
            <div class="grand-row">
              <span>Grand Total</span>
              <span>
                Rs <?= number_format($order['total'],2) ?>
              </span>
            </div>
          </div>
        </div>

        <!-- TIMELINE -->
        <?php if ($order['status'] !== 'Cancelled'): ?>
        <div class="detail-card">
          <div class="card-title">
            <i class="fa-solid fa-timeline"></i>
            Order Progress
          </div>
          <?php
          $steps   = ['Pending','Processing','Delivered'];
          $current = array_search($order['status'],$steps);
          ?>
          <div class="timeline">
            <?php foreach ($steps as $i => $step):
              $done    = $current !== false && $i < $current;
              $cur_step= $current !== false && $i === $current;
            ?>
            <div class="tl-step">
              <div class="tl-dot
                <?= $done?'done':($cur_step?'current':'') ?>">
                <?php if ($done): ?>
                  <i class="fa-solid fa-check"
                    style="font-size:11px;"></i>
                <?php elseif ($cur_step): ?>
                  <i class="fa-solid fa-circle-dot"
                    style="font-size:11px;"></i>
                <?php else: ?>
                  <?= $i+1 ?>
                <?php endif; ?>
              </div>
              <div class="tl-label
                <?= $done?'done':($cur_step?'current':'') ?>">
                <?= $step ?>
              </div>
            </div>
            <?php if ($i < count($steps)-1): ?>
            <div class="tl-line
              <?= $done?'done':'' ?>">
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
        <?php else: ?>
        <div class="detail-card">
          <div style="background:#fee2e2;
            border-radius:12px;padding:16px 20px;
            display:flex;align-items:center;gap:12px;
            font-size:14px;color:#991b1b;
            font-weight:600;">
            <i class="fa-solid fa-circle-xmark"
              style="font-size:20px;"></i>
            This order has been cancelled.
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- RIGHT -->
      <div>

        <!-- CUSTOMER DETAILS -->
        <div class="detail-card">
          <div class="card-title">
            <i class="fa-solid fa-user"></i>
            Customer Details
          </div>

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-user"></i>
            </div>
            <div>
              <div class="info-label">Customer Name</div>
              <div class="info-value">
                <?= htmlspecialchars($order['name']) ?>
              </div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-phone"></i>
            </div>
            <div>
              <div class="info-label">Phone</div>
              <div class="info-value">
                <?= htmlspecialchars($order['phone']) ?>
              </div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-location-dot"></i>
            </div>
            <div>
              <div class="info-label">Address</div>
              <div class="info-value">
                <?= htmlspecialchars($order['address']) ?>
              </div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-regular fa-calendar"></i>
            </div>
            <div>
              <div class="info-label">Order Date</div>
              <div class="info-value">
                <?= date('d M Y, h:i A',
                  strtotime($order['order_date'])) ?>
              </div>
            </div>
          </div>

          <!-- PAYMENT METHOD - SHOWS CORRECT METHOD -->
          <div class="info-row">
            <div class="info-icon"
              style="background:<?= $pay_color ?>20;">
              <i class="fa-solid <?= $pay_icon ?>"
                style="color:<?= $pay_color ?>;"></i>
            </div>
            <div>
              <div class="info-label">Payment Method</div>
              <div class="info-value">
                <span class="payment-badge"
                  style="background:<?= $pay_color ?>15;
                  color:<?= $pay_color ?>;">
                  <i class="fa-solid <?= $pay_icon ?>"></i>
                  <?= htmlspecialchars($pay_method) ?>
                </span>
              </div>
            </div>
          </div>

          <!-- STATUS -->
          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-circle-dot"></i>
            </div>
            <div>
              <div class="info-label">Status</div>
              <div class="info-value">
                <span class="status-chip"
                  style="background:<?= $sc['bg'] ?>;
                  color:<?= $sc['color'] ?>;">
                  <?= $order['status'] ?>
                </span>
              </div>
            </div>
          </div>

        </div>

        <!-- QUICK ACTIONS -->
        <div class="detail-card">
          <div class="card-title">
            <i class="fa-solid fa-bolt"></i>
            Quick Actions
          </div>
          <a href="update_status.php?id=<?= $order_id ?>"
            class="qa-btn qa-red">
            <i class="fa-solid fa-pen"></i>
            Update Order Status
          </a>
          <a href="../invoice.php?id=<?= $order_id ?>"
            class="qa-btn qa-dark" target="_blank">
            <i class="fa-solid fa-receipt"></i>
            View / Print Invoice
          </a>
          <a href="orders.php" class="qa-btn qa-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Orders
          </a>
        </div>

      </div>

    </div>

  </div>
</div>

</body>
</html>