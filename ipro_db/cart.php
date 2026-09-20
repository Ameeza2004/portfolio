<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// REMOVE ITEM
if (isset($_GET['remove'])) {
    $key = (int)$_GET['remove'];
    unset($_SESSION['cart'][$key]);
    header("Location: cart.php");
    exit();
}

// CLEAR CART
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    header("Location: cart.php");
    exit();
}

// INCREASE QTY — with stock check
if (isset($_GET['inc'])) {
    $key = (int)$_GET['inc'];

    if (isset($_SESSION['cart'][$key])) {

        // Check stock from database
        $stmt = mysqli_prepare($conn,
            "SELECT quantity FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $key);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);

        if ($product &&
            $_SESSION['cart'][$key]['qty'] < $product['quantity']) {
            // Stock available — increase qty
            $_SESSION['cart'][$key]['qty']++;
            unset($_SESSION['cart_error']);
        } else {
            // Stock limit reached — show error
            $available = $product['quantity'] ?? 0;
            $_SESSION['cart_error'] =
                "Sorry! Only <strong>{$available}</strong>
                 units of <strong>" .
                htmlspecialchars(
                    $_SESSION['cart'][$key]['name']) .
                "</strong> are available in stock.";
        }
    }

    header("Location: cart.php");
    exit();
}

// DECREASE QTY
if (isset($_GET['dec'])) {
    $key = (int)$_GET['dec'];
    if (isset($_SESSION['cart'][$key])) {
        if ($_SESSION['cart'][$key]['qty'] > 1) {
            $_SESSION['cart'][$key]['qty']--;
        } else {
            unset($_SESSION['cart'][$key]);
        }
        // Clear any error when decreasing
        unset($_SESSION['cart_error']);
    }
    header("Location: cart.php");
    exit();
}

$cart     = $_SESSION['cart'] ?? [];
$total    = 0;
$count    = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
    $count += $item['qty'];
}
$delivery = $total >= 5000 ? 0 : 300;
$grand    = $total + $delivery;

// GET ERROR IF ANY
$cart_error = $_SESSION['cart_error'] ?? '';
unset($_SESSION['cart_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart – Tech Store</title>

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

.container {
  max-width:1200px;
  margin:0 auto;
  padding:0 30px;
}

/* PAGE HEADER */
.page-header {
  background:#111;
  padding:50px 0 40px;
  position:relative;
  overflow:hidden;
}
.page-header::before {
  content:'';
  position:absolute;
  width:400px; height:400px;
  background:radial-gradient(circle,
    rgba(220,38,38,0.15) 0%,transparent 70%);
  top:50%; right:-80px;
  transform:translateY(-50%);
  border-radius:50%;
  pointer-events:none;
}
.page-header-inner {
  max-width:1200px;
  margin:0 auto;
  padding:0 30px;
  position:relative;
  z-index:1;
}
.breadcrumb {
  display:flex; align-items:center;
  gap:8px; margin-bottom:16px; font-size:13px;
}
.breadcrumb a {
  color:rgba(255,255,255,0.4);
  text-decoration:none;
  transition:color 0.2s;
}
.breadcrumb a:hover { color:#dc2626; }
.breadcrumb span   { color:rgba(255,255,255,0.2); }
.breadcrumb strong { color:rgba(255,255,255,0.7); }
.page-header h1 {
  font-size:clamp(28px,5vw,48px);
  font-weight:900; color:white;
  letter-spacing:-1px; margin-bottom:6px;
}
.page-header h1 span { color:#dc2626; }
.page-header p {
  color:rgba(255,255,255,0.4);
  font-size:14px; font-weight:400;
}

/* STOCK ERROR MESSAGE */
.stock-error {
  background:#fee2e2;
  border:1px solid #fecaca;
  border-left:4px solid #dc2626;
  border-radius:12px;
  padding:14px 18px;
  margin-bottom:20px;
  display:flex;
  align-items:center;
  gap:12px;
  font-size:14px;
  font-weight:600;
  color:#991b1b;
  animation:slideDown 0.4s ease;
}
@keyframes slideDown {
  from { opacity:0; transform:translateY(-10px); }
  to   { opacity:1; transform:translateY(0); }
}
.stock-error i {
  font-size:20px;
  color:#dc2626;
  flex-shrink:0;
}
.stock-error .close-error {
  margin-left:auto;
  background:none;
  border:none;
  color:#dc2626;
  font-size:18px;
  cursor:pointer;
  padding:0 4px;
  transition:transform 0.2s;
}
.stock-error .close-error:hover {
  transform:scale(1.2);
}

/* MAIN LAYOUT */
.cart-layout {
  display:grid;
  grid-template-columns:1fr 360px;
  gap:28px;
  padding:40px 0 80px;
  align-items:start;
}

/* CART LEFT */
.cart-left h2 {
  font-size:18px; font-weight:800;
  color:#111; margin-bottom:20px;
  display:flex; align-items:center;
  justify-content:space-between;
}
.cart-left h2 a {
  font-size:13px; font-weight:700;
  color:#dc2626; text-decoration:none;
  display:flex; align-items:center;
  gap:6px; transition:all 0.2s;
}
.cart-left h2 a:hover { color:#b91c1c; }

/* CART ITEM */
.cart-item {
  background:white; border-radius:16px;
  padding:20px;
  display:flex; align-items:center;
  gap:18px; margin-bottom:14px;
  box-shadow:0 2px 12px rgba(0,0,0,0.05);
  border:1.5px solid #f0f0f0;
  transition:all 0.3s ease;
  animation:slideIn 0.4s ease both;
}
.cart-item:hover {
  box-shadow:0 8px 30px rgba(0,0,0,0.1);
  border-color:rgba(220,38,38,0.15);
  transform:translateX(4px);
}
@keyframes slideIn {
  from { opacity:0; transform:translateX(-20px); }
  to   { opacity:1; transform:translateX(0); }
}
.cart-item-img {
  width:90px; height:90px;
  border-radius:12px; object-fit:cover;
  flex-shrink:0; background:#f5f5f5;
}
.cart-item-info { flex:1; }
.cart-item-cat {
  font-size:10px; font-weight:700;
  text-transform:uppercase; letter-spacing:1.5px;
  color:#9ca3af; margin-bottom:4px;
}
.cart-item-name {
  font-size:16px; font-weight:800;
  color:#111; margin-bottom:4px;
}
.cart-item-unit {
  font-size:13px; color:#9ca3af; font-weight:500;
}

/* STOCK WARNING ON ITEM */
.stock-warning-inline {
  display:inline-flex; align-items:center;
  gap:5px; font-size:11px; font-weight:700;
  color:#d97706; background:#fef3c7;
  padding:3px 10px; border-radius:50px;
  margin-top:5px;
}

/* QTY CONTROLS */
.cart-qty {
  display:flex; align-items:center;
  border:1.5px solid #e5e7eb;
  border-radius:10px; overflow:hidden;
  flex-shrink:0;
}
.cart-qty-btn {
  width:36px; height:36px;
  background:#f9fafb; border:none;
  font-size:18px; font-weight:700;
  color:#111; cursor:pointer;
  display:flex; align-items:center;
  justify-content:center;
  text-decoration:none;
  transition:all 0.2s ease; flex-shrink:0;
}
.cart-qty-btn:hover {
  background:#dc2626; color:white;
}
.cart-qty-btn.disabled {
  background:#f3f4f6; color:#d1d5db;
  cursor:not-allowed; pointer-events:none;
}
.cart-qty-val {
  width:42px; height:36px;
  display:flex; align-items:center;
  justify-content:center;
  font-size:15px; font-weight:800; color:#111;
  border-left:1.5px solid #e5e7eb;
  border-right:1.5px solid #e5e7eb;
  background:white;
}
.cart-item-price {
  font-size:18px; font-weight:900;
  color:#111; min-width:110px;
  text-align:right; flex-shrink:0;
}
.cart-remove {
  width:36px; height:36px;
  border-radius:10px; background:#fff5f5;
  border:1.5px solid #fecaca; color:#dc2626;
  display:flex; align-items:center;
  justify-content:center; font-size:14px;
  cursor:pointer; text-decoration:none;
  transition:all 0.3s ease; flex-shrink:0;
}
.cart-remove:hover {
  background:#dc2626; color:white;
  border-color:#dc2626; transform:scale(1.1);
}

/* CONTINUE LINK */
.continue-link {
  display:inline-flex; align-items:center;
  gap:8px; color:#6b7280; text-decoration:none;
  font-size:13px; font-weight:600;
  margin-top:16px; transition:all 0.2s;
}
.continue-link:hover { color:#dc2626; gap:10px; }

/* ORDER SUMMARY */
.order-summary {
  background:white; border-radius:20px;
  padding:28px;
  box-shadow:0 2px 16px rgba(0,0,0,0.06);
  border:1.5px solid #f0f0f0;
  position:sticky; top:90px;
  animation:fadeIn 0.5s ease both;
}
@keyframes fadeIn {
  from { opacity:0; transform:translateY(16px); }
  to   { opacity:1; transform:translateY(0); }
}
.summary-title {
  font-size:18px; font-weight:900;
  color:#111; margin-bottom:24px;
  padding-bottom:16px;
  border-bottom:2px solid #f3f4f6;
  display:flex; align-items:center; gap:10px;
}
.summary-title i { color:#dc2626; }
.summary-items {
  margin-bottom:20px; padding-bottom:16px;
  border-bottom:1px solid #f3f4f6;
}
.summary-item {
  display:flex; align-items:center;
  gap:12px; margin-bottom:12px;
}
.summary-item img {
  width:44px; height:44px;
  border-radius:8px; object-fit:cover;
  flex-shrink:0;
}
.summary-item-name {
  flex:1; font-size:13px;
  font-weight:600; color:#111;
}
.summary-item-qty {
  font-size:12px; color:#9ca3af;
}
.summary-item-price {
  font-size:13px; font-weight:800;
  color:#111; white-space:nowrap;
}
.summary-row {
  display:flex; justify-content:space-between;
  align-items:center; margin-bottom:14px;
  font-size:14px;
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
  height:1px; background:#f3f4f6; margin:16px 0;
}
.summary-total {
  display:flex; justify-content:space-between;
  align-items:center; margin-bottom:24px;
}
.summary-total span:first-child {
  font-size:16px; font-weight:800; color:#111;
}
.summary-total span:last-child {
  font-size:24px; font-weight:900; color:#dc2626;
}

/* DELIVERY PROGRESS */
.delivery-progress {
  background:#fef9c3; border:1px solid #fde68a;
  border-radius:12px; padding:14px 16px;
  margin-bottom:20px; font-size:13px;
  color:#92400e; font-weight:600;
}
.delivery-progress i {
  color:#f59e0b; margin-right:6px;
}
.progress-bar-wrap {
  background:#fde68a; border-radius:50px;
  height:6px; margin-top:10px; overflow:hidden;
}
.progress-bar-fill {
  height:100%; background:#f59e0b;
  border-radius:50px; transition:width 0.5s ease;
}
.free-delivery-msg {
  background:#dcfce7; border:1px solid #bbf7d0;
  border-radius:12px; padding:14px 16px;
  margin-bottom:20px; font-size:13px;
  color:#166534; font-weight:600;
}
.free-delivery-msg i {
  color:#16a34a; margin-right:6px;
}

/* CHECKOUT BTN */
.checkout-btn {
  display:flex; align-items:center;
  justify-content:center; gap:10px;
  width:100%; padding:16px;
  background:#dc2626; color:white;
  border:none; border-radius:14px;
  font-size:16px; font-weight:800;
  text-decoration:none; cursor:pointer;
  transition:all 0.3s ease;
  position:relative; overflow:hidden;
  font-family:'Poppins',sans-serif;
}
.checkout-btn::after {
  content:''; position:absolute;
  top:0; left:-100%;
  width:50%; height:100%;
  background:linear-gradient(90deg,
    transparent,rgba(255,255,255,0.25),transparent);
}
.checkout-btn:hover {
  background:#b91c1c; transform:translateY(-3px);
  box-shadow:0 12px 30px rgba(220,38,38,0.45);
  color:white;
}
.checkout-btn:hover::after {
  left:160%; transition:left 0.5s ease;
}
.secure-note {
  text-align:center; margin-top:14px;
  font-size:12px; color:#9ca3af;
  display:flex; align-items:center;
  justify-content:center; gap:6px;
}
.secure-note i { color:#16a34a; }

/* EMPTY CART */
.empty-cart {
  text-align:center; padding:80px 20px;
  background:white; border-radius:24px;
  box-shadow:0 2px 16px rgba(0,0,0,0.06);
  grid-column:1 / -1;
}
.empty-cart-icon {
  font-size:80px; color:#e5e7eb;
  margin-bottom:24px; display:block;
}
.empty-cart h2 {
  font-size:26px; font-weight:900;
  color:#111; margin-bottom:10px;
}
.empty-cart p {
  color:#6b7280; font-size:15px;
  margin-bottom:28px;
}
.btn-red {
  display:inline-flex; align-items:center;
  gap:8px; padding:14px 32px;
  background:#dc2626; color:white;
  border-radius:50px; text-decoration:none;
  font-size:15px; font-weight:700;
  transition:all 0.3s ease;
}
.btn-red:hover {
  background:#b91c1c; transform:translateY(-3px);
  box-shadow:0 10px 24px rgba(220,38,38,0.4);
  color:white;
}

@media (max-width:900px) {
  .cart-layout { grid-template-columns:1fr; }
  .order-summary { position:static; }
}
@media (max-width:560px) {
  .cart-item { flex-wrap:wrap; }
  .cart-item-img { width:70px; height:70px; }
  .cart-item-price { min-width:auto; }
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
      <a href="product.php">Products</a>
      <span>/</span>
      <strong>Cart</strong>
    </div>
    <h1>Shopping <span>Cart</span></h1>
    <p>
      <?php if ($count > 0): ?>
        You have <?= $count ?>
        item<?= $count > 1 ? 's' : '' ?> in your cart
      <?php else: ?>
        Your cart is empty
      <?php endif; ?>
    </p>
  </div>
</div>

<div style="padding:0;">
  <div class="container">

    <!-- STOCK ERROR MESSAGE -->
    <?php if ($cart_error): ?>
    <div class="stock-error" id="stockError"
      style="margin-top:24px;">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span><?= $cart_error ?></span>
      <button class="close-error"
        onclick="document.getElementById(
          'stockError').style.display='none'">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <?php endif; ?>

    <div class="cart-layout">

      <?php if (empty($cart)): ?>

      <!-- EMPTY CART -->
      <div class="empty-cart">
        <span class="empty-cart-icon">
          <i class="fa-solid fa-bag-shopping"></i>
        </span>
        <h2>Your cart is empty</h2>
        <p>
          Looks like you haven't added anything yet.<br>
          Explore our products!
        </p>
        <a href="product.php" class="btn-red">
          <i class="fa-solid fa-arrow-left"></i>
          Browse Products
        </a>
      </div>

      <?php else: ?>

      <!-- LEFT CART ITEMS -->
      <div class="cart-left">
        <h2>
          Cart Items
          <a href="cart.php?clear=1"
            onclick="return confirm(
              'Clear all items?')">
            <i class="fa-solid fa-trash"></i>
            Clear All
          </a>
        </h2>

        <?php
        // Check stock for all items
        foreach ($cart as $key => $item):
          $subtotal = $item['price'] * $item['qty'];

          // Get current stock
          $chk = mysqli_prepare($conn,
            "SELECT quantity FROM products WHERE id = ?");
          mysqli_stmt_bind_param($chk, "i", $key);
          mysqli_stmt_execute($chk);
          $chk_res = mysqli_stmt_get_result($chk);
          $stock = mysqli_fetch_assoc($chk_res);
          $available_stock = $stock['quantity'] ?? 0;
          $at_max = $item['qty'] >= $available_stock;
        ?>

        <div class="cart-item">

          <!-- IMAGE -->
          <img
            src="uploads/<?= htmlspecialchars(
              $item['image']) ?>"
            alt="<?= htmlspecialchars($item['name']) ?>"
            class="cart-item-img"
            onerror="this.src='images/logo.png'">

          <!-- INFO -->
          <div class="cart-item-info">
            <div class="cart-item-cat">
              Tech Accessory
            </div>
            <div class="cart-item-name">
              <?= htmlspecialchars($item['name']) ?>
            </div>
            <div class="cart-item-unit">
              Rs <?= number_format($item['price'],2) ?>
              / unit
            </div>
            <!-- SHOW WARNING IF AT MAX STOCK -->
            <?php if ($at_max): ?>
            <div class="stock-warning-inline">
              <i class="fa-solid fa-triangle-exclamation"></i>
              Max stock reached
              (<?= $available_stock ?> available)
            </div>
            <?php endif; ?>
          </div>

          <!-- QTY CONTROLS -->
          <div class="cart-qty">
            <a href="cart.php?dec=<?= $key ?>"
              class="cart-qty-btn" title="Decrease">
              −
            </a>
            <span class="cart-qty-val">
              <?= $item['qty'] ?>
            </span>
            <!-- DISABLE + BUTTON IF AT MAX STOCK -->
            <a href="<?= $at_max
                ? '#'
                : 'cart.php?inc='.$key ?>"
              class="cart-qty-btn
                <?= $at_max ? 'disabled' : '' ?>"
              title="<?= $at_max
                ? 'Maximum stock reached'
                : 'Increase' ?>">
              +
            </a>
          </div>

          <!-- PRICE -->
          <div class="cart-item-price">
            Rs <?= number_format($subtotal,2) ?>
          </div>

          <!-- REMOVE -->
          <a href="cart.php?remove=<?= $key ?>"
            class="cart-remove"
            onclick="return confirm(
              'Remove this item?')"
            title="Remove">
            <i class="fa-solid fa-xmark"></i>
          </a>

        </div>
        <?php endforeach; ?>

        <a href="product.php" class="continue-link">
          <i class="fa-solid fa-arrow-left"></i>
          Continue Shopping
        </a>
      </div>

      <!-- RIGHT ORDER SUMMARY -->
      <div class="order-summary">

        <div class="summary-title">
          <i class="fa-solid fa-receipt"></i>
          Order Summary
        </div>

        <!-- MINI ITEMS -->
        <div class="summary-items">
          <?php foreach ($cart as $item): ?>
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

        <!-- PRICE ROWS -->
        <div class="summary-row">
          <span>
            Subtotal
            (<?= $count ?> item<?= $count>1?'s':'' ?>)
          </span>
          <span>Rs <?= number_format($total,2) ?></span>
        </div>

        <div class="summary-row
          <?= $delivery==0?'free':'' ?>">
          <span>Delivery</span>
          <span>
            <?= $delivery==0
              ? 'FREE'
              : 'Rs '.number_format($delivery,2) ?>
          </span>
        </div>

        <div class="summary-divider"></div>

        <div class="summary-total">
          <span>Total</span>
          <span>Rs <?= number_format($grand,2) ?></span>
        </div>

        <!-- FREE DELIVERY MSG -->
        <?php if ($delivery > 0): ?>
        <?php $remaining = 5000 - $total; ?>
        <?php $progress = min(100,($total/5000)*100); ?>
        <div class="delivery-progress">
          <i class="fa-solid fa-truck"></i>
          Add Rs <?= number_format($remaining,2) ?>
          more for <strong>FREE delivery!</strong>
          <div class="progress-bar-wrap">
            <div class="progress-bar-fill"
              style="width:<?= $progress ?>%">
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="free-delivery-msg">
          <i class="fa-solid fa-circle-check"></i>
          You got <strong>FREE delivery!</strong>
        </div>
        <?php endif; ?>

        <!-- CHECKOUT BTN -->
        <a href="checkout.php" class="checkout-btn">
          <i class="fa-solid fa-lock"></i>
          Proceed to Checkout
        </a>

        <div class="secure-note">
          <i class="fa-solid fa-shield-halved"></i>
          Secure and encrypted checkout
        </div>

      </div>

      <?php endif; ?>

    </div>
  </div>
</div>

</body>
</html>