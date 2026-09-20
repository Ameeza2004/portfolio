<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Redirect if cart empty
if (empty($_SESSION['cart'])) {
    header("Location: product.php");
    exit();
}

// Calculate total
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
$delivery    = $subtotal >= 5000 ? 0 : 300;
$grand_total = $subtotal + $delivery;

// ── YOUR STRIPE KEYS ──
// Replace with your actual keys from stripe.com
define('STRIPE_PUBLIC_KEY',
    'pk_test_1234');
define('STRIPE_SECRET_KEY',
    'sk_test_1234');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment – Tech Store</title>

<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap"
  rel="stylesheet">

<!-- STRIPE JS -->
<script src="https://js.stripe.com/v3/"></script>

<style>
* {
  margin: 0; padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
}

body { background: #f5f5f5; color: #111; }

/* PAGE HEADER */
.page-header {
  background: #111;
  padding: 50px 0 40px;
  position: relative;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  width: 400px; height: 400px;
  background: radial-gradient(circle,
    rgba(220,38,38,0.15) 0%, transparent 70%);
  top: 50%; right: -80px;
  transform: translateY(-50%);
  border-radius: 50%;
  pointer-events: none;
}

.page-header-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 30px;
  position: relative;
  z-index: 1;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  font-size: 13px;
}

.breadcrumb a {
  color: rgba(255,255,255,0.4);
  text-decoration: none;
}

.breadcrumb a:hover { color: #dc2626; }
.breadcrumb span    { color: rgba(255,255,255,0.2); }
.breadcrumb strong  { color: rgba(255,255,255,0.7); }

.page-header h1 {
  font-size: clamp(28px,5vw,44px);
  font-weight: 900;
  color: white;
  letter-spacing: -1px;
  margin-bottom: 6px;
}

.page-header h1 span { color: #dc2626; }

.page-header p {
  color: rgba(255,255,255,0.4);
  font-size: 14px;
}

/* LAYOUT */
.pay-layout {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 24px;
  max-width: 1100px;
  margin: 40px auto 80px;
  padding: 0 30px;
  align-items: start;
}

/* PAYMENT CARD */
.pay-card {
  background: white;
  border-radius: 20px;
  padding: 30px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
  margin-bottom: 20px;
}

.pay-card h3 {
  font-size: 17px;
  font-weight: 800;
  color: #111;
  margin-bottom: 22px;
  padding-bottom: 14px;
  border-bottom: 2px solid #f3f4f6;
  display: flex;
  align-items: center;
  gap: 10px;
}

.pay-card h3 i { color: #dc2626; }

/* PAYMENT METHOD TABS */
.method-tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.method-tab {
  flex: 1;
  min-width: 120px;
  padding: 14px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 14px;
  cursor: pointer;
  text-align: center;
  transition: all 0.3s ease;
  background: white;
}

.method-tab:hover {
  border-color: #dc2626;
  background: #fef2f2;
}

.method-tab.active {
  border-color: #dc2626;
  background: #fef2f2;
  box-shadow: 0 4px 16px rgba(220,38,38,0.15);
}

.method-tab i {
  font-size: 24px;
  display: block;
  margin-bottom: 8px;
}

.method-tab.active i { color: #dc2626; }
.method-tab:not(.active) i { color: #9ca3af; }

.method-tab span {
  font-size: 13px;
  font-weight: 700;
  color: #111;
  display: block;
}

.method-tab small {
  font-size: 11px;
  color: #9ca3af;
  display: block;
  margin-top: 2px;
}

/* FORM FIELDS */
.form-group { margin-bottom: 18px; }

.form-label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #6b7280;
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  padding: 13px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 14px;
  color: #111;
  background: #f9fafb;
  outline: none;
  transition: all 0.3s ease;
  font-family: 'Poppins', sans-serif;
}

.form-input:focus {
  border-color: #dc2626;
  background: white;
  box-shadow: 0 0 0 4px rgba(220,38,38,0.08);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

/* STRIPE CARD ELEMENT */
.stripe-card-element {
  padding: 13px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  background: #f9fafb;
  transition: all 0.3s ease;
}

.stripe-card-element.focused {
  border-color: #dc2626;
  background: white;
  box-shadow: 0 0 0 4px rgba(220,38,38,0.08);
}

/* CARD ICONS */
.card-icons {
  display: flex;
  gap: 8px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.card-icon {
  height: 28px;
  border-radius: 6px;
  padding: 4px 8px;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  font-size: 11px;
  font-weight: 700;
  color: #6b7280;
  gap: 4px;
}

/* COD SECTION */
.cod-box {
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 14px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.cod-box i {
  font-size: 36px;
  color: #16a34a;
  flex-shrink: 0;
}

.cod-box h4 {
  font-size: 15px;
  font-weight: 800;
  color: #111;
  margin-bottom: 4px;
}

.cod-box p {
  font-size: 13px;
  color: #6b7280;
  line-height: 1.5;
}

/* REFUND POLICY */
.refund-box {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 12px;
  padding: 16px 18px;
  margin-top: 20px;
}

.refund-box h4 {
  font-size: 13px;
  font-weight: 800;
  color: #166534;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.refund-box ul {
  list-style: none;
  padding: 0;
}

.refund-box ul li {
  font-size: 12px;
  color: #166534;
  padding: 4px 0;
  display: flex;
  align-items: flex-start;
  gap: 7px;
  line-height: 1.5;
}

.refund-box ul li i {
  font-size: 11px;
  margin-top: 2px;
  flex-shrink: 0;
}

/* ERROR MESSAGE */
.error-msg {
  background: #fee2e2;
  color: #991b1b;
  border-left: 4px solid #dc2626;
  padding: 12px 16px;
  border-radius: 10px;
  margin-bottom: 16px;
  font-size: 13px;
  font-weight: 600;
  display: none;
}

/* PAY BUTTON */
.pay-btn {
  width: 100%;
  padding: 16px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-family: 'Poppins', sans-serif;
  position: relative;
  overflow: hidden;
  margin-top: 8px;
}

.pay-btn:hover {
  background: #b91c1c;
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(220,38,38,0.4);
}

.pay-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* SPINNER */
.spinner {
  display: none;
  width: 18px; height: 18px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.pay-btn.loading .spinner { display: block; }
.pay-btn.loading .btn-text { display: none; }

/* SECURE NOTE */
.secure-note {
  text-align: center;
  margin-top: 14px;
  font-size: 12px;
  color: #9ca3af;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  flex-wrap: wrap;
}

.secure-note i { color: #16a34a; }

/* ORDER SUMMARY */
.order-summary {
  background: white;
  border-radius: 20px;
  padding: 26px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
  position: sticky;
  top: 90px;
}

.summary-title {
  font-size: 17px;
  font-weight: 900;
  color: #111;
  margin-bottom: 20px;
  padding-bottom: 14px;
  border-bottom: 2px solid #f3f4f6;
  display: flex;
  align-items: center;
  gap: 10px;
}

.summary-title i { color: #dc2626; }

.summary-items {
  margin-bottom: 18px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
  max-height: 200px;
  overflow-y: auto;
}

.summary-items::-webkit-scrollbar { width: 4px; }
.summary-items::-webkit-scrollbar-thumb {
  background: #dc2626;
  border-radius: 2px;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.summary-item img {
  width: 46px; height: 46px;
  border-radius: 8px;
  object-fit: cover;
  flex-shrink: 0;
}

.summary-item-name {
  flex: 1;
  font-size: 13px;
  font-weight: 600;
  color: #111;
}

.summary-item-qty {
  font-size: 11px;
  color: #9ca3af;
  margin-top: 2px;
}

.summary-item-price {
  font-size: 13px;
  font-weight: 800;
  color: #111;
  white-space: nowrap;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  margin-bottom: 12px;
}

.summary-row span:first-child {
  color: #6b7280;
  font-weight: 500;
}

.summary-row span:last-child {
  color: #111;
  font-weight: 700;
}

.summary-row.free span:last-child {
  color: #16a34a;
  font-weight: 800;
}

.summary-divider {
  height: 1px;
  background: #f3f4f6;
  margin: 14px 0;
}

.summary-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-total span:first-child {
  font-size: 16px;
  font-weight: 800;
  color: #111;
}

.summary-total span:last-child {
  font-size: 22px;
  font-weight: 900;
  color: #dc2626;
}

/* STRIPE BADGE */
.stripe-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 20px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 10px;
  font-size: 12px;
  color: #9ca3af;
  font-weight: 600;
}

.stripe-badge i {
  color: #6772e5;
  font-size: 18px;
}

@media (max-width: 900px) {
  .pay-layout { grid-template-columns: 1fr; }
  .order-summary { position: static; }
}

@media (max-width: 560px) {
  .form-row { grid-template-columns: 1fr; }
  .method-tabs { flex-direction: column; }
}
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="new.php">
        <i class="fa-solid fa-house"></i> Home
      </a>
      <span>/</span>
      <a href="cart.php">Cart</a>
      <span>/</span>
      <a href="checkout.php">Checkout</a>
      <span>/</span>
      <strong>Payment</strong>
    </div>
    <h1>Secure <span>Payment</span></h1>
    <p>Choose your payment method and complete your order</p>
  </div>
</div>

<!-- PAYMENT LAYOUT -->
<div class="pay-layout">

  <!-- LEFT SIDE -->
  <div>

    <!-- PAYMENT METHOD TABS -->
    <div class="pay-card">
      <h3>
        <i class="fa-solid fa-credit-card"></i>
        Select Payment Method
      </h3>

      <div class="method-tabs">

        <!-- CARD / STRIPE -->
        <div class="method-tab active" id="tab-card"
          onclick="switchMethod('card')">
          <i class="fa-solid fa-credit-card"></i>
          <span>Card</span>
          <small>Visa / Mastercard</small>
        </div>

        <!-- COD -->
        <div class="method-tab" id="tab-cod"
          onclick="switchMethod('cod')">
          <i class="fa-solid fa-money-bill-wave"></i>
          <span>Cash on Delivery</span>
          <small>Pay when delivered</small>
        </div>

        <!-- BANK TRANSFER -->
        <div class="method-tab" id="tab-bank"
          onclick="switchMethod('bank')">
          <i class="fa-solid fa-building-columns"></i>
          <span>Bank Transfer</span>
          <small>Direct transfer</small>
        </div>

      </div>
    </div>

    <!-- STRIPE CARD PAYMENT -->
    <div class="pay-card" id="section-card">
      <h3>
        <i class="fa-solid fa-lock"></i>
        Card Details
      </h3>

      <!-- ACCEPTED CARDS -->
      <div class="card-icons">
        <div class="card-icon">
          <i class="fa-brands fa-cc-visa"
            style="color:#1a1f71;font-size:20px;"></i>
          Visa
        </div>
        <div class="card-icon">
          <i class="fa-brands fa-cc-mastercard"
            style="color:#eb001b;font-size:20px;"></i>
          Mastercard
        </div>
        <div class="card-icon">
          <i class="fa-brands fa-cc-amex"
            style="color:#2e77bc;font-size:20px;"></i>
          Amex
        </div>
        <div class="card-icon">
          <i class="fa-brands fa-stripe"
            style="color:#6772e5;font-size:20px;"></i>
          Stripe
        </div>
      </div>

      <div id="card-error" class="error-msg"></div>

      <form id="stripe-form">

        <div class="form-group">
          <label class="form-label">
            Cardholder Name
          </label>
          <input type="text" id="card-name"
            class="form-input"
            placeholder="Name on card"
            required>
        </div>

        <div class="form-group">
          <label class="form-label">
            Card Information
          </label>
          <!-- Stripe mounts here -->
          <div id="card-element"
            class="stripe-card-element">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Billing Address
          </label>
          <input type="text" id="billing-address"
            class="form-input"
            placeholder="Same as delivery address"
            value="<?php
              if (isset($_SESSION['user_id'])) {
                $us = mysqli_prepare($conn,
                  "SELECT address FROM users WHERE id=?");
                mysqli_stmt_bind_param($us, "i",
                  $_SESSION['user_id']);
                mysqli_stmt_execute($us);
                $ur = mysqli_fetch_assoc(
                  mysqli_stmt_get_result($us));
                echo htmlspecialchars($ur['address'] ?? '');
              }
            ?>">
        </div>

        <button type="submit" class="pay-btn"
          id="stripe-pay-btn">
          <div class="spinner"></div>
          <span class="btn-text">
            <i class="fa-solid fa-lock"></i>
            Pay Rs <?= number_format($grand_total,2) ?>
          </span>
        </button>

      </form>

      <div class="secure-note">
        <i class="fa-solid fa-shield-halved"></i>
        256-bit SSL encrypted &nbsp;|&nbsp;
        <i class="fa-brands fa-stripe"
          style="color:#6772e5;"></i>
        Powered by Stripe
      </div>

    </div>

    <!-- CASH ON DELIVERY -->
    <div class="pay-card" id="section-cod"
      style="display:none;">
      <h3>
        <i class="fa-solid fa-money-bill-wave"></i>
        Cash on Delivery
      </h3>

      <div class="cod-box">
        <i class="fa-solid fa-hand-holding-dollar"></i>
        <div>
          <h4>Pay when your order arrives</h4>
          <p>
            Our delivery agent will collect the payment
            at your doorstep. Please keep the exact amount
            ready.
          </p>
        </div>
      </div>

      <div style="margin-top:20px;padding:16px;
        background:#f9fafb;border-radius:12px;
        border:1px solid #f0f0f0;">
        <div style="font-size:13px;color:#6b7280;
          margin-bottom:8px;font-weight:600;">
          Amount to keep ready:
        </div>
        <div style="font-size:28px;font-weight:900;
          color:#dc2626;">
          Rs <?= number_format($grand_total,2) ?>
        </div>
      </div>

      <form action="checkout.php" method="POST">
        <input type="hidden" name="payment_method"
          value="cod">
        <?php
        // Pre-fill if logged in
        $pname = $pphone = $paddress = '';
        if (isset($_SESSION['user_id'])) {
            $ups = mysqli_prepare($conn,
              "SELECT username, phone, address
               FROM users WHERE id=?");
            mysqli_stmt_bind_param($ups, "i",
              $_SESSION['user_id']);
            mysqli_stmt_execute($ups);
            $uprow = mysqli_fetch_assoc(
              mysqli_stmt_get_result($ups));
            $pname    = $uprow['username'] ?? '';
            $pphone   = $uprow['phone'] ?? '';
            $paddress = $uprow['address'] ?? '';
        }
        ?>
        <div class="form-group" style="margin-top:20px;">
          <label class="form-label">Full Name</label>
          <input type="text" name="name"
            class="form-input"
            value="<?= htmlspecialchars($pname) ?>"
            required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone"
            class="form-input"
            value="<?= htmlspecialchars($pphone) ?>"
            required>
        </div>
        <div class="form-group">
          <label class="form-label">
            Delivery Address
          </label>
          <textarea name="address"
            class="form-input"
            rows="3"
            required
            style="resize:none;"><?= htmlspecialchars($paddress) ?></textarea>
        </div>
        <button type="submit" name="place_order"
          class="pay-btn"
          onclick="return confirm(
            'Place order with Cash on Delivery?')">
          <i class="fa-solid fa-check"></i>
          Place Order — Rs <?= number_format($grand_total,2) ?>
        </button>
      </form>

    </div>

    <!-- BANK TRANSFER -->
    <div class="pay-card" id="section-bank"
      style="display:none;">
      <h3>
        <i class="fa-solid fa-building-columns"></i>
        Bank Transfer Details
      </h3>

      <div style="background:#f9fafb;
        border-radius:14px;padding:22px;
        border:1px solid #f0f0f0;margin-bottom:20px;">

        <div style="margin-bottom:16px;">
          <div style="font-size:11px;font-weight:700;
            text-transform:uppercase;letter-spacing:1px;
            color:#9ca3af;margin-bottom:4px;">
            Bank Name
          </div>
          <div style="font-size:15px;font-weight:700;
            color:#111;">
            Bank of Ceylon
          </div>
        </div>

        <div style="margin-bottom:16px;">
          <div style="font-size:11px;font-weight:700;
            text-transform:uppercase;letter-spacing:1px;
            color:#9ca3af;margin-bottom:4px;">
            Account Name
          </div>
          <div style="font-size:15px;font-weight:700;
            color:#111;">
            Tech Accessories Store
          </div>
        </div>

        <div style="margin-bottom:16px;">
          <div style="font-size:11px;font-weight:700;
            text-transform:uppercase;letter-spacing:1px;
            color:#9ca3af;margin-bottom:4px;">
            Account Number
          </div>
          <div style="font-size:18px;font-weight:900;
            color:#dc2626;letter-spacing:2px;">
            1234 5678 9012
          </div>
        </div>

        <div>
          <div style="font-size:11px;font-weight:700;
            text-transform:uppercase;letter-spacing:1px;
            color:#9ca3af;margin-bottom:4px;">
            Branch
          </div>
          <div style="font-size:15px;font-weight:700;
            color:#111;">
            Colombo Main Branch
          </div>
        </div>

      </div>

      <div style="background:#fef3c7;
        border:1px solid #fde68a;
        border-radius:12px;padding:14px 16px;
        font-size:13px;color:#92400e;
        font-weight:600;margin-bottom:20px;">
        <i class="fa-solid fa-triangle-exclamation"
          style="margin-right:6px;"></i>
        Transfer exactly
        <strong>Rs <?= number_format($grand_total,2) ?></strong>
        and use your name as the reference.
        Your order will be confirmed within 24 hours.
      </div>

      <form action="checkout.php" method="POST">
        <input type="hidden" name="payment_method"
          value="bank">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="name"
            class="form-input"
            value="<?= htmlspecialchars($pname ?? '') ?>"
            required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone"
            class="form-input"
            value="<?= htmlspecialchars($pphone ?? '') ?>"
            required>
        </div>
        <div class="form-group">
          <label class="form-label">
            Delivery Address
          </label>
          <textarea name="address"
            class="form-input" rows="3"
            required
            style="resize:none;"><?= htmlspecialchars($paddress ?? '') ?></textarea>
        </div>
        <button type="submit" name="place_order"
          class="pay-btn"
          onclick="return confirm(
            'Confirm bank transfer order?')">
          <i class="fa-solid fa-check"></i>
          Confirm Order
        </button>
      </form>

    </div>

    <!-- REFUND POLICY -->
    <div class="pay-card">
      <h3>
        <i class="fa-solid fa-rotate-left"></i>
        Refund Policy
      </h3>

      <div class="refund-box">
        <h4>
          <i class="fa-solid fa-shield-halved"></i>
          Our Refund Guarantee
        </h4>
        <ul>
          <li>
            <i class="fa-solid fa-check"></i>
            Full refund within <strong>7 days</strong>
            if product is defective or damaged.
          </li>
          <li>
            <i class="fa-solid fa-check"></i>
            Refund processed within
            <strong>3-5 business days</strong>
            to original payment method.
          </li>
          <li>
            <i class="fa-solid fa-check"></i>
            Contact us at
            <strong>info@techstore.lk</strong>
            or WhatsApp <strong>+94 77 123 4567</strong>
            to request a refund.
          </li>
          <li>
            <i class="fa-solid fa-check"></i>
            Product must be in
            <strong>original condition</strong>
            with packaging.
          </li>
          <li>
            <i class="fa-solid fa-check"></i>
            Cancelled orders refunded
            <strong>100%</strong> automatically.
          </li>
        </ul>
      </div>

      <!-- STRIPE REFUND NOTE -->
      <div style="background:#f5f3ff;
        border:1px solid #ddd6fe;
        border-radius:12px;padding:14px 16px;
        margin-top:14px;font-size:13px;
        color:#5b21b6;">
        <i class="fa-brands fa-stripe"
          style="font-size:18px;margin-right:8px;"></i>
        <strong>Stripe card payments</strong>
        are refunded directly to your card
        within 5-10 business days automatically.
      </div>

    </div>

  </div>

  <!-- RIGHT ORDER SUMMARY -->
  <div class="order-summary">

    <div class="summary-title">
      <i class="fa-solid fa-receipt"></i>
      Order Summary
    </div>

    <div class="summary-items">
      <?php foreach ($_SESSION['cart'] as $item): ?>
      <div class="summary-item">
        <img
          src="uploads/<?= htmlspecialchars(
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

    <!-- STRIPE BADGE -->
    <div class="stripe-badge">
      <i class="fa-brands fa-stripe"></i>
      Secured by Stripe
    </div>

  </div>

</div>

<script>
// ── PAYMENT METHOD SWITCHER ──
function switchMethod(method) {
  // Hide all sections
  document.getElementById('section-card').style.display = 'none';
  document.getElementById('section-cod').style.display  = 'none';
  document.getElementById('section-bank').style.display = 'none';

  // Remove active from all tabs
  document.getElementById('tab-card').classList.remove('active');
  document.getElementById('tab-cod').classList.remove('active');
  document.getElementById('tab-bank').classList.remove('active');

  // Show selected
  document.getElementById('section-' + method).style.display = 'block';
  document.getElementById('tab-' + method).classList.add('active');
}

// ── STRIPE SETUP ──
const stripe = Stripe('<?= STRIPE_PUBLIC_KEY ?>');
const elements = stripe.elements();

// Card Element style
const cardStyle = {
  style: {
    base: {
      fontFamily: 'Poppins, Segoe UI, sans-serif',
      fontSize: '14px',
      color: '#111',
      '::placeholder': { color: '#9ca3af' }
    },
    invalid: { color: '#dc2626' }
  }
};

const cardElement = elements.create('card', cardStyle);
cardElement.mount('#card-element');

// Focus effect
cardElement.on('focus', function() {
  document.getElementById('card-element')
    .classList.add('focused');
});

cardElement.on('blur', function() {
  document.getElementById('card-element')
    .classList.remove('focused');
});

// Error display
cardElement.on('change', function(event) {
  const errDiv = document.getElementById('card-error');
  if (event.error) {
    errDiv.textContent = event.error.message;
    errDiv.style.display = 'block';
  } else {
    errDiv.style.display = 'none';
  }
});

// Form submit
const stripeForm = document.getElementById('stripe-form');
const payBtn     = document.getElementById('stripe-pay-btn');

stripeForm.addEventListener('submit', async function(e) {
  e.preventDefault();

  // Show loading
  payBtn.disabled = true;
  payBtn.classList.add('loading');

  const cardName = document.getElementById('card-name').value;

  const { paymentMethod, error } =
    await stripe.createPaymentMethod({
      type: 'card',
      card: cardElement,
      billing_details: { name: cardName }
    });

  if (error) {
    const errDiv = document.getElementById('card-error');
    errDiv.textContent = error.message;
    errDiv.style.display = 'block';
    payBtn.disabled = false;
    payBtn.classList.remove('loading');
  } else {
    // Send to server
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'process_payment.php';

    const pmInput = document.createElement('input');
    pmInput.type  = 'hidden';
    pmInput.name  = 'payment_method_id';
    pmInput.value = paymentMethod.id;

    form.appendChild(pmInput);
    document.body.appendChild(form);
    form.submit();
  }
});
</script>

</body>
</html>