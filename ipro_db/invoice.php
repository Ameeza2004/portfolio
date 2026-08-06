<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: my_orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

$stmt = mysqli_prepare($conn,
    "SELECT * FROM orders WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header("Location: my_orders.php");
    exit();
}

$istmt = mysqli_prepare($conn,
    "SELECT oi.*, p.image
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

// PAYMENT METHOD DETAILS
$pay_method = $order['payment_method'] ?? 'Cash on Delivery';

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

$status_colors = [
    'Pending'    => ['bg'=>'#fef3c7','color'=>'#d97706'],
    'Processing' => ['bg'=>'#dbeafe','color'=>'#2563eb'],
    'Delivered'  => ['bg'=>'#dcfce7','color'=>'#16a34a'],
    'Cancelled'  => ['bg'=>'#fee2e2','color'=>'#dc2626'],
];
$sc = $status_colors[$order['status']]
    ?? ['bg'=>'#f3f4f6','color'=>'#6b7280'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice #<?= str_pad($order_id,5,'0',STR_PAD_LEFT) ?></title>
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
body {
  background:#f0f0f0;
  padding:40px 20px; color:#111;
}

/* ACTIONS BAR */
.actions-bar {
  max-width:860px; margin:0 auto 24px;
  display:flex; align-items:center;
  justify-content:space-between;
  flex-wrap:wrap; gap:12px;
}
.back-link {
  display:inline-flex; align-items:center;
  gap:8px; color:#6b7280; text-decoration:none;
  font-size:14px; font-weight:600;
  transition:all 0.2s;
}
.back-link:hover { color:#dc2626; gap:10px; }
.action-btns { display:flex; gap:10px; }
.btn-print {
  display:inline-flex; align-items:center;
  gap:8px; padding:11px 24px;
  background:#dc2626; color:white;
  border:none; border-radius:50px;
  font-size:14px; font-weight:700;
  cursor:pointer; transition:all 0.3s ease;
  font-family:'Poppins',sans-serif;
}
.btn-print:hover {
  background:#b91c1c; transform:translateY(-2px);
  box-shadow:0 8px 20px rgba(220,38,38,0.4);
}
.btn-back-dark {
  display:inline-flex; align-items:center;
  gap:8px; padding:11px 24px;
  background:#111; color:white;
  border:none; border-radius:50px;
  font-size:14px; font-weight:700;
  cursor:pointer; transition:all 0.3s ease;
  text-decoration:none;
  font-family:'Poppins',sans-serif;
}
.btn-back-dark:hover {
  background:#333; transform:translateY(-2px);
  color:white;
}

/* INVOICE BOX */
.invoice-box {
  background:white; max-width:860px;
  margin:0 auto; border-radius:20px;
  overflow:hidden;
  box-shadow:0 10px 60px rgba(0,0,0,0.15);
  animation:fadeUp 0.5s ease both;
}
@keyframes fadeUp {
  from { opacity:0; transform:translateY(20px); }
  to   { opacity:1; transform:translateY(0); }
}

/* TOP BAR */
.invoice-top-bar {
  background:#dc2626; padding:28px 44px;
  display:flex; justify-content:space-between;
  align-items:center; flex-wrap:wrap; gap:16px;
}
.inv-store-info {
  display:flex; align-items:center; gap:14px;
}
.inv-logo-circle {
  width:56px; height:56px; background:white;
  border-radius:50%; display:flex;
  align-items:center; justify-content:center;
  flex-shrink:0;
  box-shadow:0 4px 14px rgba(0,0,0,0.2);
}
.inv-logo-circle img {
  width:38px; height:38px; object-fit:contain;
}
.inv-store-name {
  font-size:20px; font-weight:900;
  color:white; letter-spacing:3px;
}
.inv-store-tag {
  font-size:11px; color:rgba(255,255,255,0.65);
  font-weight:400; letter-spacing:1px; margin-top:2px;
}
.inv-title-wrap { text-align:right; }
.inv-title {
  font-size:42px; font-weight:900;
  color:white; letter-spacing:4px;
  text-transform:uppercase; line-height:1;
}
.inv-num {
  font-size:13px; color:rgba(255,255,255,0.7);
  font-weight:500; margin-top:6px;
}

/* BODY */
.invoice-body { padding:40px 44px; }

/* INFO GRID */
.info-grid {
  display:grid; grid-template-columns:1fr 1fr;
  gap:24px; margin-bottom:36px;
}
.info-box {
  background:#f9fafb; border-radius:14px;
  padding:20px 22px; border:1px solid #f0f0f0;
}
.info-box h4 {
  font-size:10px; font-weight:700;
  text-transform:uppercase; letter-spacing:2px;
  color:#9ca3af; margin-bottom:14px;
  display:flex; align-items:center; gap:7px;
}
.info-box h4 i { color:#dc2626; }
.info-row {
  display:flex; align-items:flex-start;
  gap:10px; margin-bottom:10px;
}
.info-row:last-child { margin-bottom:0; }
.info-row i {
  font-size:13px; color:#dc2626;
  margin-top:2px; width:16px; flex-shrink:0;
}
.info-row span {
  font-size:13px; font-weight:500;
  color:#111; line-height:1.5;
}
.inv-status-chip {
  display:inline-flex; align-items:center;
  gap:6px; padding:5px 14px;
  border-radius:50px; font-size:12px;
  font-weight:700;
}

/* PAYMENT BADGE */
.payment-badge {
  display:inline-flex; align-items:center;
  gap:7px; padding:6px 14px;
  border-radius:50px; font-size:12px;
  font-weight:700;
}

/* TABLE */
.inv-table-wrap {
  margin-bottom:32px; border-radius:14px;
  overflow:hidden; border:1px solid #f0f0f0;
}
.inv-table {
  width:100%; border-collapse:collapse;
}
.inv-table thead tr { background:#111; }
.inv-table thead th {
  padding:14px 18px; font-size:11px;
  font-weight:700; text-transform:uppercase;
  letter-spacing:1.5px; color:white;
  text-align:left;
}
.inv-table thead th:last-child { text-align:right; }
.inv-table tbody tr {
  border-bottom:1px solid #f3f4f6;
}
.inv-table tbody tr:last-child { border-bottom:none; }
.inv-table tbody tr:hover { background:#fafafa; }
.inv-table tbody td {
  padding:16px 18px; font-size:13px;
  font-weight:500; color:#111;
  vertical-align:middle;
}
.inv-table tbody td:last-child {
  text-align:right; font-weight:700;
  color:#dc2626;
}
.inv-product-cell {
  display:flex; align-items:center; gap:12px;
}
.inv-product-img {
  width:46px; height:46px; border-radius:10px;
  object-fit:cover; background:#f5f5f5;
  flex-shrink:0;
}
.inv-product-name {
  font-size:14px; font-weight:600; color:#111;
}
.inv-product-cat {
  font-size:11px; color:#9ca3af;
  font-weight:400; margin-top:2px;
}
.inv-row-num {
  width:28px; height:28px; background:#f3f4f6;
  border-radius:8px; display:inline-flex;
  align-items:center; justify-content:center;
  font-size:12px; font-weight:700; color:#6b7280;
}

/* TOTALS */
.totals-section {
  display:flex; justify-content:flex-end;
  margin-bottom:36px;
}
.totals-box { width:320px; }
.total-row {
  display:flex; justify-content:space-between;
  align-items:center; padding:10px 0;
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
.grand-total-row {
  display:flex; justify-content:space-between;
  align-items:center; background:#111;
  padding:16px 20px; border-radius:12px;
  margin-top:12px;
}
.grand-total-row span:first-child {
  font-size:14px; font-weight:700;
  color:rgba(255,255,255,0.7);
  text-transform:uppercase; letter-spacing:1px;
}
.grand-total-row span:last-child {
  font-size:22px; font-weight:900; color:#dc2626;
}

/* FOOTER */
.invoice-footer {
  border-top:2px solid #f3f4f6; padding-top:28px;
  display:flex; justify-content:space-between;
  align-items:flex-end; flex-wrap:wrap; gap:20px;
}
.thank-you {
  font-size:22px; font-weight:900;
  color:#111; margin-bottom:6px;
}
.footer-note {
  font-size:12px; color:#9ca3af;
  font-weight:400; line-height:1.8;
  max-width:340px;
}

/* BARCODE */
.inv-barcode { text-align:right; }
.barcode-lines {
  display:flex; gap:2px;
  justify-content:flex-end; margin-bottom:6px;
}
.barcode-line {
  background:#111; height:40px; border-radius:1px;
}
.barcode-num {
  font-size:11px; color:#9ca3af;
  letter-spacing:3px; font-weight:500;
}

/* BOTTOM STRIPE */
.invoice-bottom-stripe {
  background:#dc2626; height:8px;
}

/* PRINT */
@media print {
  body { background:white; padding:0; }
  .actions-bar { display:none !important; }
  .invoice-box {
    border-radius:0; box-shadow:none;
    max-width:100%;
  }
}

/* RESPONSIVE */
@media (max-width:600px) {
  .invoice-top-bar { flex-direction:column; }
  .inv-title-wrap  { text-align:left; }
  .info-grid       { grid-template-columns:1fr; }
  .invoice-footer  { flex-direction:column; }
  .inv-barcode     { text-align:left; }
  .totals-section  { justify-content:stretch; }
  .totals-box      { width:100%; }
  .invoice-body    { padding:24px 20px; }
  .invoice-top-bar { padding:22px 20px; }
}
</style>
</head>
<body>

<!-- ACTIONS BAR -->
<div class="actions-bar">
  <a href="my_orders.php" class="back-link">
    <i class="fa-solid fa-arrow-left"></i>
    Back to My Orders
  </a>
  <div class="action-btns">
    <button class="btn-print" onclick="window.print()">
      <i class="fa-solid fa-print"></i>
      Print Invoice
    </button>
    <a href="my_orders.php" class="btn-back-dark">
      <i class="fa-solid fa-box"></i>
      My Orders
    </a>
  </div>
</div>

<!-- INVOICE BOX -->
<div class="invoice-box">

  <!-- TOP BAR -->
  <div class="invoice-top-bar">
    <div class="inv-store-info">
      <div class="inv-logo-circle">
        <img src="images/logo.png" alt="Logo"
          onerror="this.parentElement.innerHTML=
          '<b style=\'color:#dc2626;font-size:16px;\'>iP</b>'">
      </div>
      <div>
        <div class="inv-store-name">TECH STORE</div>
        <div class="inv-store-tag">Premium Accessories</div>
      </div>
    </div>
    <div class="inv-title-wrap">
      <div class="inv-title">Invoice</div>
      <div class="inv-num">
        #<?= str_pad($order_id,5,'0',STR_PAD_LEFT) ?>
      </div>
    </div>
  </div>

  <!-- BODY -->
  <div class="invoice-body">

    <!-- INFO GRID -->
    <div class="info-grid">

      <!-- BILL TO -->
      <div class="info-box">
        <h4>
          <i class="fa-solid fa-user"></i>
          Bill To
        </h4>
        <div class="info-row">
          <i class="fa-solid fa-user"></i>
          <span><?= htmlspecialchars($order['name']) ?></span>
        </div>
        <div class="info-row">
          <i class="fa-solid fa-phone"></i>
          <span><?= htmlspecialchars($order['phone']) ?></span>
        </div>
        <div class="info-row">
          <i class="fa-solid fa-location-dot"></i>
          <span><?= htmlspecialchars($order['address']) ?></span>
        </div>
      </div>

      <!-- ORDER INFO -->
      <div class="info-box">
        <h4>
          <i class="fa-solid fa-receipt"></i>
          Order Info
        </h4>
        <div class="info-row">
          <i class="fa-solid fa-hashtag"></i>
          <span>
            Order #<?= str_pad($order_id,5,'0',STR_PAD_LEFT) ?>
          </span>
        </div>
        <div class="info-row">
          <i class="fa-regular fa-calendar"></i>
          <span>
            <?= date('d M Y, h:i A',
              strtotime($order['order_date'])) ?>
          </span>
        </div>

        <!-- PAYMENT METHOD - SHOWS CORRECT METHOD -->
        <div class="info-row">
          <i class="fa-solid <?= $pay_icon ?>"
            style="color:<?= $pay_color ?>;"></i>
          <span>
            <span class="payment-badge"
              style="background:<?= $pay_color ?>20;
              color:<?= $pay_color ?>;">
              <i class="fa-solid <?= $pay_icon ?>"></i>
              <?= htmlspecialchars($pay_method) ?>
            </span>
          </span>
        </div>

        <!-- ORDER STATUS -->
        <div class="info-row">
          <i class="fa-solid fa-circle-dot"></i>
          <span>
            <span class="inv-status-chip"
              style="background:<?= $sc['bg'] ?>;
              color:<?= $sc['color'] ?>;">
              <?= $order['status'] ?>
            </span>
          </span>
        </div>
      </div>

    </div>

    <!-- ITEMS TABLE -->
    <div class="inv-table-wrap">
      <table class="inv-table">
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
              <span class="inv-row-num"><?= $i++ ?></span>
            </td>
            <td>
              <div class="inv-product-cell">
                <img
                  src="uploads/<?= htmlspecialchars(
                    $item['image'] ?? '') ?>"
                  class="inv-product-img"
                  onerror="this.src='images/logo.png'"
                  alt="">
                <div>
                  <div class="inv-product-name">
                    <?= htmlspecialchars(
                      $item['product_name']) ?>
                  </div>
                  <div class="inv-product-cat">
                    Tech Accessory
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
              Rs <?= number_format($item['subtotal'],2) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- TOTALS -->
    <div class="totals-section">
      <div class="totals-box">
        <div class="total-row">
          <span>Subtotal</span>
          <span>Rs <?= number_format($subtotal,2) ?></span>
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
        <div class="grand-total-row">
          <span>Total Amount</span>
          <span>
            Rs <?= number_format($order['total'],2) ?>
          </span>
        </div>
      </div>
    </div>

    <!-- INVOICE FOOTER -->
    <div class="invoice-footer">
      <div>
        <div class="thank-you">Thank You! 🎉</div>
        <div class="footer-note">
          Thank you for shopping with
          <strong>Tech Accessories Store</strong>.<br>
          For queries contact us:<br>
          📞 +94 72 576 4060 &nbsp;|&nbsp;
          ✉️ info@techstore.lk<br>
          📍 90,Penithudumulla,Nawalapitiya, Sri Lanka
        </div>
      </div>
      <div class="inv-barcode">
        <div class="barcode-lines">
          <?php
          $widths = [2,1,3,1,2,1,4,1,2,1,
                     3,2,1,1,3,1,2,1,3,1];
          foreach ($widths as $w): ?>
          <div class="barcode-line"
            style="width:<?= $w ?>px;"></div>
          <?php endforeach; ?>
        </div>
        <div class="barcode-num">
          <?= str_pad($order_id,5,'0',STR_PAD_LEFT) ?>-TECH-<?= date('Y') ?>
        </div>
      </div>
    </div>

  </div>

  <!-- BOTTOM STRIPE -->
  <div class="invoice-bottom-stripe"></div>

</div>

</body>
</html>