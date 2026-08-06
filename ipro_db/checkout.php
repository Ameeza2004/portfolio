<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

if (empty($_SESSION['cart'])) {
    header("Location: product.php");
    exit();
}

$error     = '';
$success   = false;
$new_order = 0;

// PRE FILL FROM SESSION
$pname = $pphone = $paddress = '';
if (isset($_SESSION['user_id'])) {
    $ups = mysqli_prepare($conn,
        "SELECT username, phone, address
         FROM users WHERE id = ?");
    mysqli_stmt_bind_param($ups, "i",
        $_SESSION['user_id']);
    mysqli_stmt_execute($ups);
    $uprow = mysqli_fetch_assoc(
        mysqli_stmt_get_result($ups));
    $pname    = $uprow['username'] ?? '';
    $pphone   = $uprow['phone']    ?? '';
    $paddress = $uprow['address']  ?? '';
}

// CALCULATE TOTALS
$cart     = $_SESSION['cart'] ?? [];
$subtotal = 0;
$count    = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
    $count    += $item['qty'];
}
$delivery    = $subtotal >= 5000 ? 0 : 300;
$grand_total = $subtotal + $delivery;

// PROCESS ORDER
if (isset($_POST['place_order'])) {
    $name           = trim($_POST['name']);
    $phone          = trim($_POST['phone']);
    $address        = trim($_POST['address']);
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $card_number    = trim($_POST['card_number'] ?? '');
    $card_expiry    = trim($_POST['card_expiry']  ?? '');
    $card_cvv       = trim($_POST['card_cvv']     ?? '');

    // VALIDATE DELIVERY
    if (empty($name) || empty($phone) || empty($address)) {
        $error = 'Please fill in all delivery details.';
    }
    // VALIDATE CARD
    elseif ($payment_method === 'card') {
        $clean = str_replace(' ', '', $card_number);
        if (strlen($clean) < 16) {
            $error = 'Please enter a valid 16-digit card number.';
        } elseif (empty($card_expiry)) {
            $error = 'Please enter card expiry date.';
        } elseif (strlen($card_cvv) < 3) {
            $error = 'Please enter a valid CVV.';
        }
    }

    if (empty($error)) {

        // PAYMENT METHOD LABEL
        if ($payment_method === 'card') {
            $pay_label = 'Card Payment';
        } elseif ($payment_method === 'bank') {
            $pay_label = 'Bank Transfer';
        } else {
            $pay_label = 'Cash on Delivery';
        }

        // SAVE ORDER
        $user_id = $_SESSION['user_id'] ?? null;

        if ($user_id) {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO orders
                 (user_id, name, phone, address,
                  total, payment_method)
                 VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isssds",
                $user_id, $name, $phone,
                $address, $grand_total, $pay_label);
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO orders
                 (name, phone, address,
                  total, payment_method)
                 VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssds",
                $name, $phone, $address,
                $grand_total, $pay_label);
        }

        mysqli_stmt_execute($stmt);
        $order_id = mysqli_insert_id($conn);

        // SAVE TRANSACTION DETAILS
if ($payment_method === 'card') {

    // Mask card — show only last 4 digits
    $clean_card = str_replace(' ', '',
        $card_number);
    $masked = '**** **** **** ' .
        substr($clean_card, -4);

    // Detect card type
    $first = $clean_card[0] ?? '4';
    if ($first === '4') {
        $card_type = 'Visa';
    } elseif ($first === '5') {
        $card_type = 'Mastercard';
    } elseif ($first === '3') {
        $card_type = 'Amex';
    } else {
        $card_type = 'Card';
    }

    $card_holder_name = trim(
        $_POST['card_holder'] ?? $name);
    $pay_method_label = 'Card Payment';
    $tran_status      = 'Completed';

    $ts = mysqli_prepare($conn,
        "INSERT INTO transactions
         (order_id, payment_method,
          card_holder, card_number_masked,
          card_expiry, card_type,
          amount, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($ts, "isssssds",
        $order_id,
        $pay_method_label,
        $card_holder_name,
        $masked,
        $card_expiry,
        $card_type,
        $grand_total,
        $tran_status);
    mysqli_stmt_execute($ts);

} elseif ($payment_method === 'bank') {

    $bank_ref    = 'REF-' .
        strtoupper(substr($name, 0, 3)) .
        '-' . $order_id;
    $bank_nm     = 'Bank of Ceylon';
    $bank_method = 'Bank Transfer';
    $bank_status = 'Pending Verification';

    $ts = mysqli_prepare($conn,
        "INSERT INTO transactions
         (order_id, payment_method,
          bank_name, bank_reference,
          amount, status)
         VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($ts, "isssds",
        $order_id,
        $bank_method,
        $bank_nm,
        $bank_ref,
        $grand_total,
        $bank_status);
    mysqli_stmt_execute($ts);

} else {

    // COD
    $cod_method = 'Cash on Delivery';
    $cod_status = 'Pending';

    $ts = mysqli_prepare($conn,
        "INSERT INTO transactions
         (order_id, payment_method,
          amount, status)
         VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($ts, "isds",
        $order_id,
        $cod_method,
        $grand_total,
        $cod_status);
    mysqli_stmt_execute($ts);

}

        // SAVE ORDER ITEMS + UPDATE STOCK
        foreach ($_SESSION['cart'] as $prod_id => $item) {
            $pn  = $item['name'];
            $pr  = (float)$item['price'];
            $qty = (int)$item['qty'];
            $sub = $pr * $qty;

            $is = mysqli_prepare($conn,
                "INSERT INTO order_items
                 (order_id, product_id, product_name,
                  price, quantity, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($is, "iisdid",
                $order_id, $prod_id, $pn,
                $pr, $qty, $sub);
            mysqli_stmt_execute($is);

            $us = mysqli_prepare($conn,
                "UPDATE products
                 SET quantity = GREATEST(0, quantity - ?)
                 WHERE id = ?");
            mysqli_stmt_bind_param($us, "ii",
                $qty, $prod_id);
            mysqli_stmt_execute($us);
        }

        unset($_SESSION['cart']);
        $success   = true;
        $new_order = $order_id;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout – Tech Store</title>
<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap"
  rel="stylesheet">
<style>
* {
  margin:0; padding:0;
  box-sizing:border-box;
  font-family:'Poppins','Segoe UI',Arial,sans-serif;
}
body { background:#f5f5f5; color:#111; }

.page-header {
  background:#111; padding:50px 0 40px;
  position:relative; overflow:hidden;
}
.page-header::before {
  content:''; position:absolute;
  width:400px; height:400px;
  background:radial-gradient(circle,
    rgba(220,38,38,0.15) 0%,transparent 70%);
  top:50%; right:-80px;
  transform:translateY(-50%);
  border-radius:50%; pointer-events:none;
}
.page-header-inner {
  max-width:1100px; margin:0 auto;
  padding:0 30px; position:relative; z-index:1;
}
.breadcrumb {
  display:flex; align-items:center;
  gap:8px; margin-bottom:16px; font-size:13px;
}
.breadcrumb a {
  color:rgba(255,255,255,0.4);
  text-decoration:none;
}
.breadcrumb a:hover { color:#dc2626; }
.breadcrumb span  { color:rgba(255,255,255,0.2); }
.breadcrumb strong{ color:rgba(255,255,255,0.7); }
.page-header h1 {
  font-size:clamp(28px,5vw,44px);
  font-weight:900; color:white;
  letter-spacing:-1px; margin-bottom:6px;
}
.page-header h1 span { color:#dc2626; }
.page-header p {
  color:rgba(255,255,255,0.4);
  font-size:14px;
}

/* STEPS */
.steps-bar {
  background:white;
  border-bottom:1px solid #f0f0f0;
  padding:16px 0;
}
.steps-inner {
  max-width:1100px; margin:0 auto;
  padding:0 30px; display:flex;
  align-items:center;
}
.step {
  display:flex; align-items:center;
  gap:8px; flex:1;
}
.step-num {
  width:30px; height:30px;
  border-radius:50%;
  display:flex; align-items:center;
  justify-content:center;
  font-size:12px; font-weight:800;
  flex-shrink:0;
}
.step.done .step-num    { background:#dcfce7; color:#16a34a; }
.step.active .step-num  { background:#dc2626; color:white; }
.step.inactive .step-num{ background:#f3f4f6; color:#9ca3af; }
.step-label { font-size:13px; font-weight:700; }
.step.done .step-label    { color:#16a34a; }
.step.active .step-label  { color:#dc2626; }
.step.inactive .step-label{ color:#9ca3af; }
.step-line {
  flex:1; height:2px;
  background:#f3f4f6; margin:0 10px;
}
.step-line.done { background:#dc2626; }

/* LAYOUT */
.checkout-layout {
  display:grid;
  grid-template-columns:1fr 360px;
  gap:24px;
  max-width:1100px;
  margin:36px auto 80px;
  padding:0 30px;
  align-items:start;
}

/* FORM CARDS */
.form-card {
  background:white; border-radius:20px;
  padding:28px;
  box-shadow:0 2px 16px rgba(0,0,0,0.06);
  border:1.5px solid #f0f0f0;
  margin-bottom:20px;
  animation:fadeUp 0.5s ease both;
}
@keyframes fadeUp {
  from { opacity:0; transform:translateY(16px); }
  to   { opacity:1; transform:translateY(0); }
}
.form-card h3 {
  font-size:17px; font-weight:800;
  color:#111; margin-bottom:22px;
  padding-bottom:14px;
  border-bottom:2px solid #f3f4f6;
  display:flex; align-items:center; gap:10px;
}
.form-card h3 i { color:#dc2626; }

/* ERROR */
.error-msg {
  background:#fee2e2; color:#991b1b;
  border-left:4px solid #dc2626;
  padding:13px 16px; border-radius:12px;
  margin-bottom:20px; font-size:13px;
  font-weight:600;
  display:flex; align-items:center; gap:8px;
}

/* INPUTS */
.form-group { margin-bottom:18px; }
.form-label {
  display:block; font-size:11px;
  font-weight:700; text-transform:uppercase;
  letter-spacing:1px; color:#6b7280;
  margin-bottom:8px;
}
.form-input-wrap { position:relative; }
.form-input-wrap i {
  position:absolute; left:14px;
  top:50%; transform:translateY(-50%);
  color:#9ca3af; font-size:14px;
  pointer-events:none;
}
.form-input {
  width:100%;
  padding:13px 16px 13px 42px;
  border:2px solid #e5e7eb;
  border-radius:12px; font-size:14px;
  color:#111; background:#f9fafb;
  outline:none; transition:all 0.3s ease;
  font-family:'Poppins',sans-serif;
}
.form-input:focus {
  border-color:#dc2626; background:white;
  box-shadow:0 0 0 4px rgba(220,38,38,0.08);
}
.form-input:hover { border-color:#fca5a5; }
.form-textarea {
  width:100%; padding:13px 16px;
  border:2px solid #e5e7eb;
  border-radius:12px; font-size:14px;
  color:#111; background:#f9fafb;
  outline:none; transition:all 0.3s ease;
  resize:none;
  font-family:'Poppins',sans-serif;
}
.form-textarea:focus {
  border-color:#dc2626; background:white;
  box-shadow:0 0 0 4px rgba(220,38,38,0.08);
}
.form-row {
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:14px;
}

/* PAYMENT TABS */
.method-tabs {
  display:flex; gap:10px;
  margin-bottom:22px; flex-wrap:wrap;
}
.method-tab {
  flex:1; min-width:100px;
  padding:14px 10px;
  border:2px solid #e5e7eb;
  border-radius:14px; cursor:pointer;
  text-align:center;
  transition:all 0.3s ease;
  background:white;
}
.method-tab:hover {
  border-color:#dc2626; background:#fef2f2;
}
.method-tab.active {
  border-color:#dc2626; background:#fef2f2;
  box-shadow:0 4px 14px rgba(220,38,38,0.15);
}
.method-tab i {
  font-size:22px; display:block;
  margin-bottom:7px;
}
.method-tab.active i         { color:#dc2626; }
.method-tab:not(.active) i   { color:#9ca3af; }
.method-tab span {
  font-size:12px; font-weight:700;
  color:#111; display:block;
}

/* CARD ICONS */
.card-icons {
  display:flex; gap:8px;
  margin-bottom:18px; flex-wrap:wrap;
}
.card-icon-badge {
  padding:5px 12px; background:#f3f4f6;
  border-radius:8px; font-size:11px;
  font-weight:700; color:#6b7280;
  display:flex; align-items:center; gap:5px;
}
.card-number-input {
  letter-spacing:3px !important;
  font-size:16px !important;
  font-weight:700 !important;
}

/* COD */
.cod-box {
  background:#f9fafb;
  border:2px solid #e5e7eb;
  border-radius:14px; padding:20px;
  display:flex; align-items:center;
  gap:16px; margin-bottom:16px;
}
.cod-box i {
  font-size:36px; color:#16a34a;
  flex-shrink:0;
}
.cod-box h4 {
  font-size:15px; font-weight:800;
  color:#111; margin-bottom:4px;
}
.cod-box p {
  font-size:13px; color:#6b7280;
  line-height:1.5;
}

/* BANK */
.bank-row {
  padding:13px 0;
  border-bottom:1px solid #f3f4f6;
  display:flex; justify-content:space-between;
  align-items:center;
}
.bank-row:last-child { border-bottom:none; }
.bank-label {
  font-size:12px; font-weight:700;
  text-transform:uppercase;
  letter-spacing:0.5px; color:#9ca3af;
}
.bank-value {
  font-size:14px; font-weight:800; color:#111;
}

/* REFUND */
.refund-item {
  display:flex; align-items:flex-start;
  gap:10px; padding:11px 0;
  border-bottom:1px solid #f3f4f6;
  font-size:13px; color:#374151;
  line-height:1.6;
}
.refund-item:last-child { border-bottom:none; }
.refund-item i {
  color:#16a34a; font-size:14px;
  margin-top:2px; flex-shrink:0;
}

/* PAY BUTTON */
.pay-btn {
  width:100%; padding:16px;
  background:#dc2626; color:white;
  border:none; border-radius:14px;
  font-size:16px; font-weight:800;
  cursor:pointer; transition:all 0.3s ease;
  display:flex; align-items:center;
  justify-content:center; gap:10px;
  font-family:'Poppins',sans-serif;
  position:relative; overflow:hidden;
  margin-top:8px;
}
.pay-btn::after {
  content:''; position:absolute;
  top:0; left:-100%;
  width:50%; height:100%;
  background:linear-gradient(90deg,
    transparent,rgba(255,255,255,0.25),transparent);
}
.pay-btn:hover {
  background:#b91c1c; transform:translateY(-3px);
  box-shadow:0 12px 30px rgba(220,38,38,0.4);
}
.pay-btn:hover::after {
  left:160%; transition:left 0.5s ease;
}

/* SECURE NOTE */
.secure-note {
  text-align:center; margin-top:14px;
  font-size:12px; color:#9ca3af;
  display:flex; align-items:center;
  justify-content:center; gap:6px;
}
.secure-note i { color:#16a34a; }

/* ORDER SUMMARY */
.order-summary {
  background:white; border-radius:20px;
  padding:26px;
  box-shadow:0 2px 16px rgba(0,0,0,0.06);
  border:1.5px solid #f0f0f0;
  position:sticky; top:90px;
  animation:fadeUp 0.5s ease 0.2s both;
}
.summary-title {
  font-size:17px; font-weight:900;
  color:#111; margin-bottom:20px;
  padding-bottom:14px;
  border-bottom:2px solid #f3f4f6;
  display:flex; align-items:center; gap:10px;
}
.summary-title i { color:#dc2626; }
.summary-items {
  margin-bottom:18px; padding-bottom:16px;
  border-bottom:1px solid #f3f4f6;
  max-height:200px; overflow-y:auto;
}
.summary-items::-webkit-scrollbar { width:4px; }
.summary-items::-webkit-scrollbar-thumb {
  background:#dc2626; border-radius:2px;
}
.summary-item {
  display:flex; align-items:center;
  gap:10px; margin-bottom:12px;
}
.summary-item img {
  width:46px; height:46px;
  border-radius:8px; object-fit:cover;
  flex-shrink:0;
}
.summary-item-name {
  flex:1; font-size:13px;
  font-weight:600; color:#111;
}
.summary-item-qty {
  font-size:11px; color:#9ca3af; margin-top:2px;
}
.summary-item-price {
  font-size:13px; font-weight:800;
  color:#111; white-space:nowrap;
}
.summary-row {
  display:flex; justify-content:space-between;
  font-size:14px; margin-bottom:12px;
}
.summary-row span:first-child {
  color:#6b7280; font-weight:500;
}
.summary-row span:last-child {
  color:#111; font-weight:700;
}
.summary-row.free span:last-child {
  color:#16a34a; font-weight:800;
}
.summary-divider {
  height:1px; background:#f3f4f6; margin:14px 0;
}
.summary-total {
  display:flex; justify-content:space-between;
  align-items:center; margin-bottom:20px;
}
.summary-total span:first-child {
  font-size:16px; font-weight:800; color:#111;
}
.summary-total span:last-child {
  font-size:22px; font-weight:900; color:#dc2626;
}

/* SUCCESS */
.success-screen {
  min-height:70vh; display:flex;
  align-items:center; justify-content:center;
  padding:60px 20px;
}
.success-card {
  background:white; border-radius:24px;
  padding:60px 50px; text-align:center;
  box-shadow:0 10px 60px rgba(0,0,0,0.12);
  max-width:500px; width:100%;
  animation:popIn 0.6s ease both;
}
@keyframes popIn {
  from { opacity:0; transform:scale(0.85); }
  to   { opacity:1; transform:scale(1); }
}
.success-icon {
  width:100px; height:100px;
  background:#dcfce7; border-radius:50%;
  display:flex; align-items:center;
  justify-content:center;
  margin:0 auto 28px;
  font-size:48px; color:#16a34a;
  animation:bounce 0.6s ease 0.4s both;
}
@keyframes bounce {
  0%,100% { transform:scale(1); }
  50%     { transform:scale(1.2); }
}
.success-card h2 {
  font-size:28px; font-weight:900;
  color:#111; margin-bottom:10px;
}
.order-num-badge {
  display:inline-block;
  background:#fef2f2; color:#dc2626;
  font-size:14px; font-weight:800;
  padding:6px 18px; border-radius:50px;
  margin-bottom:14px;
  border:1px solid #fecaca;
}
.success-card p {
  color:#6b7280; font-size:15px;
  line-height:1.7; margin-bottom:30px;
}
.success-btns {
  display:flex; gap:12px;
  justify-content:center; flex-wrap:wrap;
}
.btn-red {
  display:inline-flex; align-items:center;
  gap:8px; padding:13px 26px;
  background:#dc2626; color:white;
  border-radius:50px; text-decoration:none;
  font-size:14px; font-weight:700;
  transition:all 0.3s ease;
}
.btn-red:hover {
  background:#b91c1c;
  transform:translateY(-3px); color:white;
}
.btn-dark {
  display:inline-flex; align-items:center;
  gap:8px; padding:13px 26px;
  background:#111; color:white;
  border-radius:50px; text-decoration:none;
  font-size:14px; font-weight:700;
  transition:all 0.3s ease;
}
.btn-dark:hover {
  background:#333;
  transform:translateY(-3px); color:white;
}

@media (max-width:900px) {
  .checkout-layout { grid-template-columns:1fr; }
  .order-summary   { position:static; }
}
@media (max-width:560px) {
  .form-row    { grid-template-columns:1fr; }
  .method-tabs { flex-wrap:wrap; }
  .success-card{ padding:40px 24px; }
}
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<?php if ($success): ?>

<!-- SUCCESS -->
<div class="page-header">
  <div class="page-header-inner">
    <h1>Order <span>Confirmed!</span></h1>
    <p>Your payment was successful</p>
  </div>
</div>
<div class="success-screen">
  <div class="success-card">
    <div class="success-icon">
      <i class="fa-solid fa-check"></i>
    </div>
    <h2>Order Placed!</h2>
    <div class="order-num-badge">
      Order #<?= str_pad($new_order,5,'0',STR_PAD_LEFT) ?>
    </div>
    <p>
      Your order has been placed successfully.<br>
      We will start processing it right away.<br>
      Expected delivery:
      <strong>3–5 business days</strong>
    </p>
    <div class="success-btns">
      <a href="invoice.php?id=<?= $new_order ?>"
        class="btn-red">
        <i class="fa-solid fa-receipt"></i>
        View Invoice
      </a>
      <a href="my_orders.php" class="btn-dark">
        <i class="fa-solid fa-box"></i>
        My Orders
      </a>
    </div>
  </div>
</div>

<?php else: ?>

<!-- CHECKOUT FORM -->
<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="new.php">
        <i class="fa-solid fa-house"></i> Home
      </a>
      <span>/</span>
      <a href="cart.php">Cart</a>
      <span>/</span>
      <strong>Checkout</strong>
    </div>
    <h1>Check<span>out</span></h1>
    <p>Complete your order below</p>
  </div>
</div>

<!-- STEPS -->
<div class="steps-bar">
  <div class="steps-inner">
    <div class="step done">
      <div class="step-num">
        <i class="fa-solid fa-check"
          style="font-size:11px;"></i>
      </div>
      <span class="step-label">Cart</span>
    </div>
    <div class="step-line done"></div>
    <div class="step active">
      <div class="step-num">2</div>
      <span class="step-label">Checkout</span>
    </div>
    <div class="step-line"></div>
    <div class="step inactive">
      <div class="step-num">3</div>
      <span class="step-label">Confirmed</span>
    </div>
  </div>
</div>

<form method="POST" id="checkoutForm">
<input type="hidden" name="payment_method"
  id="payment_method" value="card">

<div class="checkout-layout">

  <!-- LEFT -->
  <div>

    <?php if ($error): ?>
    <div class="error-msg">
      <i class="fa-solid fa-circle-exclamation"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- DELIVERY -->
    <div class="form-card">
      <h3>
        <i class="fa-solid fa-location-dot"></i>
        Delivery Details
      </h3>
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <div class="form-input-wrap">
          <i class="fa-solid fa-user"></i>
          <input type="text" name="name"
            class="form-input"
            placeholder="Enter your full name"
            value="<?= htmlspecialchars(
              $_POST['name'] ?? $pname) ?>"
            required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Phone *</label>
        <div class="form-input-wrap">
          <i class="fa-solid fa-phone"></i>
          <input type="tel" name="phone"
            class="form-input"
            placeholder="072 576 4060"
            value="<?= htmlspecialchars(
              $_POST['phone'] ?? $pphone) ?>"
            required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">
          Delivery Address *
        </label>
        <textarea name="address"
          class="form-textarea" rows="3"
          placeholder="House No, Street, City"
          required><?= htmlspecialchars(
            $_POST['address'] ?? $paddress) ?></textarea>
      </div>
    </div>

    <!-- PAYMENT METHOD -->
    <div class="form-card">
      <h3>
        <i class="fa-solid fa-credit-card"></i>
        Payment Method
      </h3>

      <div class="method-tabs">
        <div class="method-tab"
          id="tab-cod"
          onclick="switchMethod('cod')">
          <i class="fa-solid fa-money-bill-wave"></i>
          <span>Cash on Delivery</span>
        </div>
        <div class="method-tab active"
          id="tab-card"
          onclick="switchMethod('card')">
          <i class="fa-solid fa-credit-card"></i>
          <span>Card Payment</span>
        </div>
        <div class="method-tab"
          id="tab-bank"
          onclick="switchMethod('bank')">
          <i class="fa-solid fa-building-columns"></i>
          <span>Bank Transfer</span>
        </div>
      </div>

      <!-- COD -->
      <div id="section-cod" style="display:none;">
        <div class="cod-box">
          <i class="fa-solid fa-hand-holding-dollar"></i>
          <div>
            <h4>Pay when your order arrives</h4>
            <p>
              Our delivery agent will collect
              payment at your doorstep.
              Please keep the exact amount ready.
            </p>
          </div>
        </div>
        <div style="background:#f9fafb;
          border-radius:12px;padding:16px;
          border:1px solid #f0f0f0;
          text-align:center;">
          <div style="font-size:13px;color:#6b7280;
            margin-bottom:6px;">
            Amount to prepare:
          </div>
          <div style="font-size:26px;
            font-weight:900;color:#dc2626;">
            Rs <?= number_format($grand_total,2) ?>
          </div>
        </div>
      </div>

      <!-- CARD -->
      <div id="section-card">
        <div class="card-icons">
          <div class="card-icon-badge">
            <i class="fa-brands fa-cc-visa"
              style="color:#1a1f71;font-size:18px;"></i>
            Visa
          </div>
          <div class="card-icon-badge">
            <i class="fa-brands fa-cc-mastercard"
              style="color:#eb001b;font-size:18px;"></i>
            Mastercard
          </div>
          <div class="card-icon-badge">
            <i class="fa-brands fa-cc-amex"
              style="color:#2e77bc;font-size:18px;"></i>
            Amex
          </div>
        </div>

        <div style="background:#dbeafe;
          border:1px solid #bfdbfe;
          border-radius:10px;
          padding:12px 14px;font-size:12px;
          color:#1e40af;font-weight:600;
          margin-bottom:18px;">
          <i class="fa-solid fa-circle-info"
            style="margin-right:6px;"></i>
          Demo Mode — Any 16-digit number,
          future expiry and any 3-digit CVV.
        </div>

        <div class="form-group">
          <label class="form-label">Card Number</label>
          <div class="form-input-wrap">
            <i class="fa-solid fa-credit-card"></i>
            <input type="text" name="card_number"
              id="card_number"
              class="form-input card-number-input"
              placeholder="1234  5678  9012  3456"
              maxlength="22" autocomplete="off">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Expiry</label>
            <div class="form-input-wrap">
              <i class="fa-regular fa-calendar"></i>
              <input type="text" name="card_expiry"
                id="card_expiry" class="form-input"
                placeholder="MM / YY" maxlength="7"
                autocomplete="off">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">CVV</label>
            <div class="form-input-wrap">
              <i class="fa-solid fa-lock"></i>
              <input type="password" name="card_cvv"
                class="form-input" placeholder="•••"
                maxlength="4" autocomplete="off">
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Name on Card
          </label>
          <div class="form-input-wrap">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="card_holder"
              class="form-input"
              placeholder="Name as on card"
              autocomplete="off">
          </div>
        </div>
      </div>

      <!-- BANK TRANSFER -->
      <div id="section-bank" style="display:none;">
        <div style="background:#f9fafb;
          border-radius:14px;padding:20px;
          border:1px solid #f0f0f0;
          margin-bottom:16px;">
          <div class="bank-row">
            <span class="bank-label">Bank</span>
            <span class="bank-value">
              Commercial Bank
            </span>
          </div>
          <div class="bank-row">
            <span class="bank-label">
              Account Name
            </span>
            <span class="bank-value">
              Tech Accessories Store
            </span>
          </div>
          <div class="bank-row">
            <span class="bank-label">
              Account No
            </span>
            <span class="bank-value"
              style="color:#dc2626;
              letter-spacing:2px;">
              1234 5678 9012
            </span>
          </div>
          <div class="bank-row">
            <span class="bank-label">Branch</span>
            <span class="bank-value">
              Nawalapitiya Main Branch
            </span>
          </div>
          <div class="bank-row">
            <span class="bank-label">Amount</span>
            <span class="bank-value"
              style="color:#dc2626;">
              Rs <?= number_format($grand_total,2) ?>
            </span>
          </div>
        </div>
        <div style="background:#fef3c7;
          border:1px solid #fde68a;
          border-radius:12px;padding:13px 16px;
          font-size:13px;color:#92400e;
          font-weight:600;">
          <i class="fa-solid fa-triangle-exclamation"
            style="margin-right:6px;"></i>
          Use your <strong>full name</strong>
          as reference. Order confirmed within
          <strong>24 hours</strong>.
        </div>
      </div>

    </div>

    <!-- REFUND POLICY -->
    <div class="form-card">
      <h3>
        <i class="fa-solid fa-rotate-left"></i>
        Refund Policy
      </h3>
      <div class="refund-item">
        <i class="fa-solid fa-check"></i>
        Full refund within <strong>7 days</strong>
        if product is defective or damaged.
      </div>
      <div class="refund-item">
        <i class="fa-solid fa-check"></i>
        Refund processed in
        <strong>3–5 business days</strong>.
      </div>
      <div class="refund-item">
        <i class="fa-solid fa-check"></i>
        Contact <strong>info@techstore.lk</strong>
        or WhatsApp
        <strong>+94 72 576 4060</strong>.
      </div>
      <div class="refund-item">
        <i class="fa-solid fa-check"></i>
        Product must be in
        <strong>original condition</strong>
        with packaging.
      </div>
      <div class="refund-item">
        <i class="fa-solid fa-check"></i>
        Cancelled orders refunded
        <strong>100%</strong> automatically.
      </div>
    </div>

  </div>

  <!-- RIGHT SUMMARY -->
  <div class="order-summary">
    <div class="summary-title">
      <i class="fa-solid fa-receipt"></i>
      Your Order
      (<?= $count ?> item<?= $count>1?'s':'' ?>)
    </div>

    <div class="summary-items">
      <?php foreach ($cart as $item): ?>
      <div class="summary-item">
        <img src="uploads/<?= htmlspecialchars(
            $item['image']) ?>"
          alt="<?= htmlspecialchars($item['name']) ?>"
          onerror="this.src='images/logo.png'">
        <div class="summary-item-name">
          <?= htmlspecialchars($item['name']) ?>
          <div class="summary-item-qty">
            × <?= $item['qty'] ?>
          </div>
        </div>
        <div class="summary-item-price">
          Rs <?= number_format(
            $item['price'] * $item['qty'],2) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="summary-row">
      <span>Subtotal</span>
      <span>Rs <?= number_format($subtotal,2) ?></span>
    </div>
    <div class="summary-row
      <?= $delivery==0?'free':'' ?>">
      <span>Delivery</span>
      <span>
        <?= $delivery==0
          ? '🎉 FREE'
          : 'Rs '.number_format($delivery,2) ?>
      </span>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-total">
      <span>Total</span>
      <span>Rs <?= number_format($grand_total,2) ?></span>
    </div>

    <?php if ($delivery > 0): ?>
    <div style="background:#fef3c7;
      border:1px solid #fde68a;border-radius:10px;
      padding:12px 14px;font-size:12px;
      color:#92400e;font-weight:600;
      margin-bottom:16px;">
      <i class="fa-solid fa-truck"
        style="margin-right:6px;color:#f59e0b;"></i>
      Add Rs <?= number_format(5000-$subtotal,2) ?>
      more for <strong>FREE delivery!</strong>
    </div>
    <?php else: ?>
    <div style="background:#dcfce7;
      border:1px solid #bbf7d0;border-radius:10px;
      padding:12px 14px;font-size:12px;
      color:#166534;font-weight:600;
      margin-bottom:16px;">
      <i class="fa-solid fa-circle-check"
        style="margin-right:6px;"></i>
      You qualify for <strong>FREE delivery!</strong>
    </div>
    <?php endif; ?>

    <button type="submit" name="place_order"
      class="pay-btn"
      onclick="return validateAndConfirm()">
      <i class="fa-solid fa-lock"></i>
      Place Order —
      Rs <?= number_format($grand_total,2) ?>
    </button>

    <div class="secure-note">
      <i class="fa-solid fa-shield-halved"></i>
      Secure &amp; encrypted checkout
    </div>

    <div style="text-align:center;margin-top:16px;">
      <a href="cart.php"
        style="font-size:13px;color:#9ca3af;
        text-decoration:none;
        display:inline-flex;
        align-items:center;gap:6px;">
        <i class="fa-solid fa-pen"></i>
        Edit Cart
      </a>
    </div>
  </div>

</div>
</form>

<?php endif; ?>

<script>
function switchMethod(method) {
  document.getElementById('section-cod').style.display  = 'none';
  document.getElementById('section-card').style.display = 'none';
  document.getElementById('section-bank').style.display = 'none';

  ['cod','card','bank'].forEach(function(m) {
    document.getElementById('tab-'+m)
      .classList.remove('active');
  });

  document.getElementById('section-'+method)
    .style.display = 'block';
  document.getElementById('tab-'+method)
    .classList.add('active');
  document.getElementById('payment_method').value = method;
}

const cardInput = document.getElementById('card_number');
if (cardInput) {
  cardInput.addEventListener('input', function() {
    let val = this.value.replace(/\D/g,'');
    val = val.match(/.{1,4}/g)?.join('  ') || val;
    this.value = val;
  });
}

const expiryInput = document.getElementById('card_expiry');
if (expiryInput) {
  expiryInput.addEventListener('input', function() {
    let val = this.value.replace(/\D/g,'');
    if (val.length >= 2) {
      val = val.slice(0,2)+' / '+val.slice(2,4);
    }
    this.value = val;
  });
}

function validateAndConfirm() {
  const method = document.getElementById(
    'payment_method').value;

  if (method === 'card') {
    const num = document.getElementById(
      'card_number').value.replace(/\s/g,'');
    const exp = document.getElementById(
      'card_expiry').value;
    const cvv = document.querySelector(
      '[name="card_cvv"]').value;

    if (num.length < 16) {
      alert('Please enter a valid 16-digit card number.');
      return false;
    }
    if (!exp || exp.length < 4) {
      alert('Please enter a valid expiry date.');
      return false;
    }
    if (cvv.length < 3) {
      alert('Please enter a valid CVV.');
      return false;
    }
  }

  return confirm(
    'Confirm order of Rs <?= number_format($grand_total,2) ?>?'
  );
}

// Set default tab
switchMethod('card');
</script>

</body>
</html>