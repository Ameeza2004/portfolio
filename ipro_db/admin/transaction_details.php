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

// GET ORDER
$ostmt = mysqli_prepare($conn,
    "SELECT * FROM orders WHERE id = ?");
mysqli_stmt_bind_param($ostmt, "i", $order_id);
mysqli_stmt_execute($ostmt);
$order = mysqli_fetch_assoc(
    mysqli_stmt_get_result($ostmt));

if (!$order) {
    header("Location: orders.php");
    exit();
}

// GET TRANSACTION
$tstmt = mysqli_prepare($conn,
    "SELECT * FROM transactions
     WHERE order_id = ?
     ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($tstmt, "i", $order_id);
mysqli_stmt_execute($tstmt);
$transaction = mysqli_fetch_assoc(
    mysqli_stmt_get_result($tstmt));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transaction Details – Admin</title>
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
  font-size:13px; color:#6b7280;
}
.top-actions { display:flex; gap:10px; }
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
.detail-layout {
  display:grid;
  grid-template-columns:1fr 1fr;
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
  margin-bottom:22px; padding-bottom:14px;
  border-bottom:2px solid #f3f4f6;
  display:flex; align-items:center; gap:10px;
}
.card-title i { color:#dc2626; }

/* TRANSACTION RECEIPT */
.receipt-box {
  background:linear-gradient(135deg,#0a0a0a,#1a1a2e);
  border-radius:20px; padding:32px;
  color:white; position:relative;
  overflow:hidden; margin-bottom:20px;
}
.receipt-box::before {
  content:'';
  position:absolute; top:-60px; right:-60px;
  width:200px; height:200px;
  background:radial-gradient(circle,
    rgba(220,38,38,0.3) 0%,transparent 70%);
  border-radius:50%;
}
.receipt-box::after {
  content:'';
  position:absolute; bottom:-40px; left:-40px;
  width:150px; height:150px;
  background:radial-gradient(circle,
    rgba(37,99,235,0.2) 0%,transparent 70%);
  border-radius:50%;
}
.receipt-top {
  display:flex; justify-content:space-between;
  align-items:flex-start; margin-bottom:28px;
  position:relative; z-index:1;
}
.receipt-method-icon {
  width:56px; height:56px; border-radius:14px;
  display:flex; align-items:center;
  justify-content:center; font-size:26px;
}
.receipt-status {
  display:inline-flex; align-items:center;
  gap:6px; padding:6px 14px; border-radius:50px;
  font-size:12px; font-weight:700;
}
.receipt-amount {
  font-size:36px; font-weight:900;
  color:white; margin-bottom:6px;
  position:relative; z-index:1;
}
.receipt-amount span {
  font-size:16px; color:rgba(255,255,255,0.5);
  font-weight:400;
}
.receipt-label {
  font-size:12px; color:rgba(255,255,255,0.4);
  text-transform:uppercase; letter-spacing:2px;
  margin-bottom:24px; position:relative; z-index:1;
}
.receipt-divider {
  height:1px; background:rgba(255,255,255,0.1);
  margin:20px 0; position:relative; z-index:1;
}
.receipt-row {
  display:flex; justify-content:space-between;
  align-items:center; margin-bottom:14px;
  position:relative; z-index:1;
}
.receipt-row:last-child { margin-bottom:0; }
.receipt-key {
  font-size:12px; color:rgba(255,255,255,0.4);
  font-weight:600; text-transform:uppercase;
  letter-spacing:0.5px;
}
.receipt-val {
  font-size:14px; color:white;
  font-weight:700; text-align:right;
  max-width:60%;
}

/* CARD VISUAL */
.card-visual {
  background:linear-gradient(135deg,#1a1a2e,#16213e);
  border-radius:16px; padding:24px;
  color:white; margin-bottom:20px;
  position:relative; overflow:hidden;
  border:1px solid rgba(255,255,255,0.08);
}
.card-visual::before {
  content:''; position:absolute;
  top:-30px; right:-30px;
  width:120px; height:120px;
  border-radius:50%;
  background:rgba(220,38,38,0.15);
}
.card-chip {
  width:40px; height:30px;
  background:linear-gradient(135deg,#ffd700,#ffa500);
  border-radius:6px; margin-bottom:20px;
  position:relative; z-index:1;
  display:flex; align-items:center;
  justify-content:center;
}
.card-chip i { color:#111; font-size:16px; }
.card-number-display {
  font-size:18px; font-weight:700;
  letter-spacing:3px; margin-bottom:20px;
  font-family:'Courier New',monospace;
  position:relative; z-index:1;
}
.card-bottom {
  display:flex; justify-content:space-between;
  align-items:flex-end; position:relative; z-index:1;
}
.card-holder-label {
  font-size:9px; color:rgba(255,255,255,0.4);
  text-transform:uppercase; letter-spacing:1px;
  margin-bottom:4px;
}
.card-holder-name {
  font-size:14px; font-weight:700;
  text-transform:uppercase; letter-spacing:1px;
}
.card-expiry-label {
  font-size:9px; color:rgba(255,255,255,0.4);
  text-transform:uppercase; letter-spacing:1px;
  margin-bottom:4px; text-align:right;
}
.card-expiry-val {
  font-size:14px; font-weight:700;
  text-align:right;
}
.card-type-badge {
  position:absolute; top:20px; right:20px;
  font-size:28px; z-index:1;
}

/* INFO ROWS */
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

/* NO TRANSACTION */
.no-transaction {
  text-align:center; padding:50px 20px;
}
.no-transaction i {
  font-size:60px; color:#e5e7eb;
  margin-bottom:16px; display:block;
}
.no-transaction h3 {
  font-size:18px; font-weight:800;
  color:#111; margin-bottom:8px;
}
.no-transaction p {
  font-size:14px; color:#9ca3af;
}

/* STATUS BADGES */
.status-completed {
  background:#dcfce7; color:#16a34a;
}
.status-pending {
  background:#fef3c7; color:#d97706;
}
.status-verification {
  background:#dbeafe; color:#2563eb;
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
        Logout (<?= htmlspecialchars(
          $_SESSION['username']) ?>)
      </a>
    </div>
  </div>

  <!-- MAIN -->
  <div class="admin-main">

    <div class="admin-topbar">
      <div>
        <h2>Transaction Details</h2>
        <p>
          Order #<?= str_pad($order_id,4,'0',STR_PAD_LEFT) ?>
          — <?= htmlspecialchars($order['name']) ?>
        </p>
      </div>
      <div class="top-actions">
        <a href="order_details.php?id=<?= $order_id ?>"
          class="btn-action btn-dark">
          <i class="fa-solid fa-eye"></i>
          Order Details
        </a>
        <a href="orders.php"
          class="btn-action btn-outline">
          <i class="fa-solid fa-arrow-left"></i>
          Back
        </a>
      </div>
    </div>

    <?php if (!$transaction): ?>

    <!-- NO TRANSACTION FOUND -->
    <div class="detail-card">
      <div class="no-transaction">
        <i class="fa-solid fa-receipt"></i>
        <h3>No Transaction Found</h3>
        <p>
          No payment transaction recorded
          for this order yet.
        </p>
      </div>
    </div>

    <?php else:
    $method = $transaction['payment_method'];
    $is_card = $method === 'Card Payment';
    $is_bank = $method === 'Bank Transfer';
    $is_cod  = $method === 'Cash on Delivery';

    // Status badge class
    $stat = $transaction['status'];
    if ($stat === 'Completed') {
        $stat_class = 'status-completed';
        $stat_icon  = 'fa-circle-check';
    } elseif ($stat === 'Pending Verification') {
        $stat_class = 'status-verification';
        $stat_icon  = 'fa-clock-rotate-left';
    } else {
        $stat_class = 'status-pending';
        $stat_icon  = 'fa-clock';
    }
    ?>

    <div class="detail-layout">

      <!-- LEFT — RECEIPT -->
      <div>

        <!-- TRANSACTION RECEIPT -->
        <div class="receipt-box">
          <div class="receipt-top">
            <div class="receipt-method-icon"
              style="background:<?= $is_card
                ? 'rgba(37,99,235,0.3)'
                : ($is_bank
                  ? 'rgba(124,58,237,0.3)'
                  : 'rgba(22,163,74,0.3)') ?>;">
              <i class="fa-solid <?= $is_card
                ? 'fa-credit-card'
                : ($is_bank
                  ? 'fa-building-columns'
                  : 'fa-money-bill-wave') ?>"
                style="color:<?= $is_card
                  ? '#60a5fa'
                  : ($is_bank
                    ? '#a78bfa'
                    : '#4ade80') ?>;">
              </i>
            </div>
            <span class="receipt-status
              <?= $stat_class ?>">
              <i class="fa-solid <?= $stat_icon ?>"></i>
              <?= $stat ?>
            </span>
          </div>

          <div class="receipt-amount">
            <span>Rs</span>
            <?= number_format(
              $transaction['amount'],2) ?>
          </div>
          <div class="receipt-label">
            Total Amount Paid
          </div>

          <div class="receipt-divider"></div>

          <div class="receipt-row">
            <span class="receipt-key">
              Transaction ID
            </span>
            <span class="receipt-val">
              TXN-<?= str_pad(
                $transaction['id'],6,'0',STR_PAD_LEFT) ?>
            </span>
          </div>

          <div class="receipt-row">
            <span class="receipt-key">Order ID</span>
            <span class="receipt-val">
              #<?= str_pad(
                $order_id,5,'0',STR_PAD_LEFT) ?>
            </span>
          </div>

          <div class="receipt-row">
            <span class="receipt-key">
              Payment Method
            </span>
            <span class="receipt-val">
              <?= htmlspecialchars($method) ?>
            </span>
          </div>

          <div class="receipt-row">
            <span class="receipt-key">Date & Time</span>
            <span class="receipt-val">
              <?= date('d M Y, h:i A',
                strtotime(
                  $transaction['transaction_date'])) ?>
            </span>
          </div>

          <?php if ($is_card): ?>
          <div class="receipt-row">
            <span class="receipt-key">Card Type</span>
            <span class="receipt-val">
              <?= htmlspecialchars(
                $transaction['card_type'] ?? 'Card') ?>
            </span>
          </div>
          <div class="receipt-row">
            <span class="receipt-key">
              Card Number
            </span>
            <span class="receipt-val"
              style="letter-spacing:2px;
              font-family:'Courier New',monospace;">
              <?= htmlspecialchars(
                $transaction['card_number_masked']
                ?? '**** **** **** ****') ?>
            </span>
          </div>
          <div class="receipt-row">
            <span class="receipt-key">Cardholder</span>
            <span class="receipt-val">
              <?= htmlspecialchars(
                $transaction['card_holder'] ?? '-') ?>
            </span>
          </div>
          <div class="receipt-row">
            <span class="receipt-key">Expiry</span>
            <span class="receipt-val">
              <?= htmlspecialchars(
                $transaction['card_expiry'] ?? '-') ?>
            </span>
          </div>
          <?php endif; ?>

          <?php if ($is_bank): ?>
          <div class="receipt-row">
            <span class="receipt-key">Bank</span>
            <span class="receipt-val">
              <?= htmlspecialchars(
                $transaction['bank_name'] ?? '-') ?>
            </span>
          </div>
          <div class="receipt-row">
            <span class="receipt-key">Reference</span>
            <span class="receipt-val">
              <?= htmlspecialchars(
                $transaction['bank_reference'] ?? '-') ?>
            </span>
          </div>
          <?php endif; ?>

          <div class="receipt-row">
            <span class="receipt-key">Customer</span>
            <span class="receipt-val">
              <?= htmlspecialchars($order['name']) ?>
            </span>
          </div>
          <div class="receipt-row">
            <span class="receipt-key">Phone</span>
            <span class="receipt-val">
              <?= htmlspecialchars($order['phone']) ?>
            </span>
          </div>

        </div>

        <?php if ($is_bank): ?>
        <!-- BANK VERIFICATION NOTE -->
        <div class="detail-card" style="
          border-color:#bfdbfe;
          background:linear-gradient(
            135deg,#eff6ff,#dbeafe);">
          <div class="card-title">
            <i class="fa-solid fa-triangle-exclamation"
              style="color:#2563eb;"></i>
            Bank Transfer Verification
          </div>
          <div style="font-size:14px;color:#1e40af;
            line-height:1.8;">
            <p style="margin-bottom:10px;">
              <strong>Reference Number:</strong>
              <?= htmlspecialchars(
                $transaction['bank_reference']
                ?? 'N/A') ?>
            </p>
            <p style="margin-bottom:10px;">
              <strong>Expected Amount:</strong>
              Rs <?= number_format(
                $transaction['amount'],2) ?>
            </p>
            <p style="margin-bottom:10px;">
              <strong>Bank:</strong>
              <?= htmlspecialchars(
                $transaction['bank_name'] ?? 'N/A') ?>
            </p>
            <p>
              Please verify the bank transfer
              and update the order status to
              <strong>Processing</strong>
              once confirmed.
            </p>
          </div>
          <div style="margin-top:16px;">
            <a href="update_status.php?id=<?= $order_id ?>"
              style="display:inline-flex;
              align-items:center;gap:8px;
              padding:11px 22px;
              background:#2563eb;color:white;
              border-radius:50px;font-size:13px;
              font-weight:700;text-decoration:none;
              transition:all 0.3s ease;">
              <i class="fa-solid fa-pen"></i>
              Verify & Update Status
            </a>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- RIGHT -->
      <div>

        <?php if ($is_card): ?>
        <!-- CARD VISUAL -->
        <div class="detail-card">
          <div class="card-title">
            <i class="fa-solid fa-credit-card"></i>
            Card Details
          </div>

          <!-- VIRTUAL CARD -->
          <div class="card-visual">
            <div class="card-type-badge">
              <?php
              $ctype = $transaction['card_type'] ?? '';
              if ($ctype === 'Visa'): ?>
                <i class="fa-brands fa-cc-visa"
                  style="color:#1a1f71;"></i>
              <?php elseif ($ctype === 'Mastercard'): ?>
                <i class="fa-brands fa-cc-mastercard"
                  style="color:#eb001b;"></i>
              <?php elseif ($ctype === 'Amex'): ?>
                <i class="fa-brands fa-cc-amex"
                  style="color:#2e77bc;"></i>
              <?php else: ?>
                <i class="fa-solid fa-credit-card"
                  style="color:#9ca3af;"></i>
              <?php endif; ?>
            </div>
            <div class="card-chip">
              <i class="fa-solid fa-microchip"></i>
            </div>
            <div class="card-number-display">
              <?= htmlspecialchars(
                $transaction['card_number_masked']
                ?? '**** **** **** ****') ?>
            </div>
            <div class="card-bottom">
              <div>
                <div class="card-holder-label">
                  Card Holder
                </div>
                <div class="card-holder-name">
                  <?= strtoupper(htmlspecialchars(
                    $transaction['card_holder']
                    ?? 'Unknown')) ?>
                </div>
              </div>
              <div>
                <div class="card-expiry-label">
                  Expires
                </div>
                <div class="card-expiry-val">
                  <?= htmlspecialchars(
                    $transaction['card_expiry']
                    ?? '--/--') ?>
                </div>
              </div>
            </div>
          </div>

          <!-- CARD INFO ROWS -->
          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-user"></i>
            </div>
            <div>
              <div class="info-label">
                Cardholder Name
              </div>
              <div class="info-value">
                <?= htmlspecialchars(
                  $transaction['card_holder'] ?? '-') ?>
              </div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-credit-card"></i>
            </div>
            <div>
              <div class="info-label">Card Number</div>
              <div class="info-value"
                style="font-family:'Courier New',
                monospace;letter-spacing:2px;">
                <?= htmlspecialchars(
                  $transaction['card_number_masked']
                  ?? '**** **** **** ****') ?>
              </div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-regular fa-calendar"></i>
            </div>
            <div>
              <div class="info-label">Expiry Date</div>
              <div class="info-value">
                <?= htmlspecialchars(
                  $transaction['card_expiry'] ?? '-') ?>
              </div>
            </div>
          </div>

          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-tag"></i>
            </div>
            <div>
              <div class="info-label">Card Type</div>
              <div class="info-value">
                <?= htmlspecialchars(
                  $transaction['card_type'] ?? '-') ?>
              </div>
            </div>
          </div>

        </div>
        <?php endif; ?>

        <!-- CUSTOMER INFO -->
        <div class="detail-card">
          <div class="card-title">
            <i class="fa-solid fa-user"></i>
            Customer Info
          </div>
          <div class="info-row">
            <div class="info-icon">
              <i class="fa-solid fa-user"></i>
            </div>
            <div>
              <div class="info-label">Name</div>
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
              <div class="info-label">Amount</div>
              <div class="info-value"
                style="font-size:20px;
                font-weight:900;color:#dc2626;">
                Rs <?= number_format(
                  $order['total'],2) ?>
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
            style="display:flex;align-items:center;
            gap:10px;padding:13px 16px;
            background:#f9fafb;border-radius:12px;
            text-decoration:none;color:#111;
            font-size:13px;font-weight:600;
            border:1px solid #f0f0f0;
            margin-bottom:10px;
            transition:all 0.2s;"
            onmouseover="this.style.background='#f3f4f6'"
            onmouseout="this.style.background='#f9fafb'">
            <i class="fa-solid fa-eye"
              style="color:#dc2626;"></i>
            View Order Details
          </a>
          <a href="update_status.php?id=<?= $order_id ?>"
            style="display:flex;align-items:center;
            gap:10px;padding:13px 16px;
            background:#f9fafb;border-radius:12px;
            text-decoration:none;color:#111;
            font-size:13px;font-weight:600;
            border:1px solid #f0f0f0;
            margin-bottom:10px;
            transition:all 0.2s;"
            onmouseover="this.style.background='#f3f4f6'"
            onmouseout="this.style.background='#f9fafb'">
            <i class="fa-solid fa-pen"
              style="color:#dc2626;"></i>
            Update Order Status
          </a>
          <a href="../invoice.php?id=<?= $order_id ?>"
            target="_blank"
            style="display:flex;align-items:center;
            gap:10px;padding:13px 16px;
            background:#f9fafb;border-radius:12px;
            text-decoration:none;color:#111;
            font-size:13px;font-weight:600;
            border:1px solid #f0f0f0;
            transition:all 0.2s;"
            onmouseover="this.style.background='#f3f4f6'"
            onmouseout="this.style.background='#f9fafb'">
            <i class="fa-solid fa-receipt"
              style="color:#dc2626;"></i>
            View Invoice
          </a>
        </div>

      </div>

    </div>

    <?php endif; ?>

  </div>
</div>

</body>
</html>