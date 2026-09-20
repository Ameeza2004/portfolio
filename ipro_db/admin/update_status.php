<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

// Admin guard
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

// GET ORDER
$stmt = mysqli_prepare($conn,
    "SELECT * FROM orders WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt));

if (!$order) {
    header("Location: orders.php");
    exit();
}

$success = '';
$error   = '';

// PROCESS UPDATE
if (isset($_POST['update'])) {
    $new_status = $_POST['status'];
    $allowed = [
        'Pending',
        'Processing',
        'Delivered',
        'Cancelled'
    ];

    if (!in_array($new_status, $allowed)) {
        $error = 'Invalid status selected.';
    } else {
        $upd = mysqli_prepare($conn,
            "UPDATE orders SET status = ?
             WHERE id = ?");
        mysqli_stmt_bind_param($upd, "si",
            $new_status, $order_id);

        if (mysqli_stmt_execute($upd)) {
            $success = 'Order #' .
                str_pad($order_id,4,'0',STR_PAD_LEFT).
                ' status updated to <strong>'.
                $new_status.'</strong> successfully!';
            // Update local variable
            $order['status'] = $new_status;
        } else {
            $error = 'Update failed. Please try again.';
        }
    }
}

$statuses = [
    'Pending' => [
        'icon'  => 'fa-clock',
        'color' => '#d97706',
        'bg'    => '#fef3c7',
        'desc'  => 'Order received, awaiting processing',
    ],
    'Processing' => [
        'icon'  => 'fa-gear',
        'color' => '#2563eb',
        'bg'    => '#dbeafe',
        'desc'  => 'Order is being prepared for delivery',
    ],
    'Delivered' => [
        'icon'  => 'fa-circle-check',
        'color' => '#16a34a',
        'bg'    => '#dcfce7',
        'desc'  => 'Order delivered to customer',
    ],
    'Cancelled' => [
        'icon'  => 'fa-circle-xmark',
        'color' => '#dc2626',
        'bg'    => '#fee2e2',
        'desc'  => 'Order has been cancelled',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Status – Admin</title>
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
.update-layout {
  display:grid;
  grid-template-columns:1fr 340px;
  gap:22px; align-items:start;
}

/* CARDS */
.detail-card {
  background:white; border-radius:18px;
  padding:26px;
  box-shadow:0 2px 14px rgba(0,0,0,0.06);
  border:1.5px solid #f0f0f0; margin-bottom:20px;
  animation:fadeUp 0.5s ease both;
}
@keyframes fadeUp {
  from { opacity:0; transform:translateY(16px); }
  to   { opacity:1; transform:translateY(0); }
}
.card-title {
  font-size:16px; font-weight:800; color:#111;
  margin-bottom:22px; padding-bottom:14px;
  border-bottom:2px solid #f3f4f6;
  display:flex; align-items:center; gap:10px;
}
.card-title i { color:#dc2626; }

/* ALERTS */
.alert-box {
  padding:14px 20px; border-radius:12px;
  margin-bottom:22px; font-size:14px;
  font-weight:600;
  display:flex; align-items:center; gap:10px;
  animation:fadeUp 0.4s ease both;
}
.alert-success {
  background:#dcfce7; color:#166534;
  border-left:4px solid #16a34a;
}
.alert-error {
  background:#fee2e2; color:#991b1b;
  border-left:4px solid #dc2626;
}

/* STATUS OPTION CARDS */
.status-options {
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:14px; margin-bottom:24px;
}
.status-option { position:relative; cursor:pointer; }
.status-option input[type="radio"] {
  position:absolute; opacity:0; width:0; height:0;
}
.status-option-card {
  border:2px solid #e5e7eb; border-radius:14px;
  padding:18px; transition:all 0.3s ease;
  background:white; cursor:pointer;
}
.status-option-card:hover {
  border-color:#dc2626; background:#fef2f2;
  transform:translateY(-2px);
}
.opt-icon {
  width:44px; height:44px; border-radius:12px;
  display:flex; align-items:center;
  justify-content:center; font-size:20px;
  margin-bottom:12px;
}
.opt-name {
  font-size:15px; font-weight:800;
  color:#111; margin-bottom:4px;
}
.opt-desc { font-size:12px; color:#9ca3af; line-height:1.5; }

/* UPDATE BUTTON */
.update-btn {
  width:100%; padding:16px;
  background:#dc2626; color:white;
  border:none; border-radius:14px;
  font-size:16px; font-weight:800;
  cursor:pointer; transition:all 0.3s ease;
  display:flex; align-items:center;
  justify-content:center; gap:10px;
  font-family:'Poppins',sans-serif;
  position:relative; overflow:hidden;
}
.update-btn::after {
  content:''; position:absolute;
  top:0; left:-100%;
  width:50%; height:100%;
  background:linear-gradient(90deg,
    transparent,rgba(255,255,255,0.25),transparent);
}
.update-btn:hover {
  background:#b91c1c; transform:translateY(-3px);
  box-shadow:0 12px 30px rgba(220,38,38,0.4);
}
.update-btn:hover::after {
  left:160%; transition:left 0.5s ease;
}

/* ORDER SUMMARY CARD */
.info-row {
  display:flex; align-items:flex-start;
  gap:12px; margin-bottom:16px;
  padding-bottom:16px;
  border-bottom:1px solid #f3f4f6;
}
.info-row:last-child {
  border-bottom:none; margin-bottom:0;
  padding-bottom:0;
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
.current-status-chip {
  display:inline-flex; align-items:center;
  gap:7px; padding:7px 16px; border-radius:50px;
  font-size:13px; font-weight:700;
}

/* STATUS FLOW */
.status-flow {
  display:flex; align-items:center;
  gap:6px; flex-wrap:wrap; margin-top:20px;
  padding-top:20px;
  border-top:2px solid #f3f4f6;
}
.flow-label {
  font-size:11px; font-weight:700;
  text-transform:uppercase; letter-spacing:1px;
  color:#9ca3af; margin-bottom:12px;
  display:block; width:100%;
}
.flow-badge {
  padding:6px 14px; border-radius:50px;
  font-size:12px; font-weight:700;
}
.flow-arrow { color:#d1d5db; font-size:14px; }

/* QUICK LINKS */
.quick-link {
  display:flex; align-items:center;
  gap:10px; padding:13px 16px;
  background:#f9fafb; border-radius:12px;
  text-decoration:none; color:#111;
  font-size:13px; font-weight:600;
  border:1px solid #f0f0f0;
  transition:all 0.2s ease;
  margin-bottom:10px;
}
.quick-link:last-child { margin-bottom:0; }
.quick-link:hover { background:#f3f4f6; }
.quick-link i { color:#dc2626; }

@media (max-width:900px) {
  .update-layout  { grid-template-columns:1fr; }
  .status-options { grid-template-columns:1fr; }
  .admin-sidebar  { transform:translateX(-100%); }
  .admin-main     { margin-left:0; padding:16px; }
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
        Logout (<?= htmlspecialchars(
          $_SESSION['username']) ?>)
      </a>
    </div>
  </div>

  <!-- MAIN -->
  <div class="admin-main">

    <!-- TOPBAR -->
    <div class="admin-topbar">
      <div>
        <h2>Update Order Status</h2>
        <p>
          Order #<?= str_pad($order_id,4,'0',STR_PAD_LEFT) ?>
          — <?= htmlspecialchars($order['name']) ?>
        </p>
      </div>
      <div class="top-actions">
        <a href="order_details.php?id=<?= $order_id ?>"
          class="btn-action btn-dark">
          <i class="fa-solid fa-eye"></i>
          View Details
        </a>
        <a href="orders.php"
          class="btn-action btn-outline">
          <i class="fa-solid fa-arrow-left"></i>
          Back
        </a>
      </div>
    </div>

    <!-- ALERTS -->
    <?php if ($success): ?>
    <div class="alert-box alert-success">
      <i class="fa-solid fa-circle-check"></i>
      <?= $success ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-box alert-error">
      <i class="fa-solid fa-circle-exclamation"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- LAYOUT -->
    <div class="update-layout">

      <!-- LEFT — STATUS FORM -->
      <div class="detail-card">
        <div class="card-title">
          <i class="fa-solid fa-pen"></i>
          Select New Status
        </div>

        <form method="POST">
          <div class="status-options">

            <?php foreach ($statuses as $key => $info): ?>
            <label class="status-option">
              <input type="radio"
                name="status"
                value="<?= $key ?>"
                <?= $order['status']===$key
                  ? 'checked' : '' ?>
                onchange="highlightCard(
                  '<?= $key ?>',
                  '<?= $info['color'] ?>',
                  '<?= $info['bg'] ?>')">

              <div class="status-option-card"
                id="card_<?= $key ?>"
                style="
                  <?= $order['status']===$key
                    ? 'border-color:'.$info['color'].
                      ';background:'.$info['bg'].';'
                    : '' ?>
                ">
                <div class="opt-icon"
                  style="background:<?= $info['bg'] ?>;
                  color:<?= $info['color'] ?>;">
                  <i class="fa-solid <?= $info['icon'] ?>
                    <?= $key==='Processing'?'fa-spin':'' ?>">
                  </i>
                </div>
                <div class="opt-name"><?= $key ?></div>
                <div class="opt-desc"><?= $info['desc'] ?></div>
              </div>
            </label>
            <?php endforeach; ?>

          </div>

          <!-- UPDATE BUTTON -->
          <button type="submit" name="update"
            class="update-btn">
            <i class="fa-solid fa-circle-check"></i>
            Update Status
          </button>

        </form>

        <!-- STATUS FLOW -->
        <div class="status-flow">
          <span class="flow-label">Normal Order Flow</span>
          <span class="flow-badge"
            style="background:#fef3c7;color:#d97706;">
            Pending
          </span>
          <span class="flow-arrow">
            <i class="fa-solid fa-arrow-right"></i>
          </span>
          <span class="flow-badge"
            style="background:#dbeafe;color:#2563eb;">
            Processing
          </span>
          <span class="flow-arrow">
            <i class="fa-solid fa-arrow-right"></i>
          </span>
          <span class="flow-badge"
            style="background:#dcfce7;color:#16a34a;">
            Delivered
          </span>
          <span style="color:#d1d5db;
            font-size:16px;margin:0 4px;">|</span>
          <span class="flow-badge"
            style="background:#fee2e2;color:#dc2626;">
            Cancelled
          </span>
        </div>

      </div>

      <!-- RIGHT — ORDER SUMMARY -->
      <div>
        <div class="detail-card">
          <div class="card-title">
            <i class="fa-solid fa-receipt"></i>
            Order Summary
          </div>

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-hashtag"></i>
            </div>
            <div>
              <div class="info-label">Order ID</div>
              <div class="info-value">
                #<?= str_pad($order_id,4,'0',STR_PAD_LEFT) ?>
              </div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-user"></i>
            </div>
            <div>
              <div class="info-label">Customer</div>
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

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-money-bill-wave"></i>
            </div>
            <div>
              <div class="info-label">Total</div>
              <div class="info-value"
                style="font-size:20px;
                font-weight:900;color:#dc2626;">
                Rs <?= number_format(
                  $order['total'],2) ?>
              </div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-circle-dot"></i>
            </div>
            <div>
              <div class="info-label">
                Current Status
              </div>
              <div class="info-value"
                style="margin-top:4px;">
                <?php
                $cs = $statuses[$order['status']]
                  ?? ['bg'=>'#f3f4f6','color'=>'#6b7280',
                      'icon'=>'fa-circle'];
                ?>
                <span class="current-status-chip"
                  style="background:<?= $cs['bg'] ?>;
                  color:<?= $cs['color'] ?>;">
                  <i class="fa-solid <?= $cs['icon'] ?>"></i>
                  <?= $order['status'] ?>
                </span>
              </div>
            </div>
          </div>

        </div>

        <!-- QUICK LINKS -->
        <div class="detail-card">
          <div class="card-title">
            <i class="fa-solid fa-link"></i>
            Quick Links
          </div>
          <a href="order_details.php?id=<?= $order_id ?>"
            class="quick-link">
            <i class="fa-solid fa-eye"></i>
            View Order Details
          </a>
          <a href="../invoice.php?id=<?= $order_id ?>"
            target="_blank" class="quick-link">
            <i class="fa-solid fa-receipt"></i>
            View Invoice
          </a>
          <a href="orders.php" class="quick-link">
            <i class="fa-solid fa-list"></i>
            All Orders
          </a>
        </div>

      </div>

    </div>

  </div>
</div>

<script>
function highlightCard(key, color, bg) {
  // Reset all cards
  const allCards = document.querySelectorAll(
    '.status-option-card');
  allCards.forEach(function(card) {
    card.style.borderColor = '#e5e7eb';
    card.style.background  = 'white';
    card.style.transform   = '';
  });

  // Highlight selected
  const sel = document.getElementById('card_' + key);
  if (sel) {
    sel.style.borderColor = color;
    sel.style.background  = bg;
    sel.style.transform   = 'translateY(-2px)';
  }
}
</script>

</body>
</html>