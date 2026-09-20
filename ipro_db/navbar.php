<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// rest of navbar code...



$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['qty'];
    }
}

$current  = basename($_SERVER['PHP_SELF']);
$is_admin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$base     = $is_admin ? '../' : '';
?>

<!-- FONT AWESOME FOR EXACT ICONS -->
<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

@keyframes navDown {
  from { opacity:0; transform:translateY(-100%); }
  to   { opacity:1; transform:translateY(0); }
}

@keyframes logoPulse {
  0%,100% { box-shadow: 0 0 0 3px rgba(220,38,38,0.3); }
  50%      { box-shadow: 0 0 0 8px rgba(220,38,38,0.5); }
}

/* NAVBAR WRAPPER */
.navbar-main {
  width: 100%;
  background: #0a0a0a;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 40px;
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 9999;
  box-sizing: border-box;
  animation: navDown 0.5s ease;
  border-bottom: 1px solid rgba(220,38,38,0.2);
  box-shadow: 0 4px 30px rgba(0,0,0,0.5);
}

/* LOGO */
.nav-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  transition: transform 0.3s ease;
}

.nav-logo:hover {
  transform: scale(1.05);
}

.nav-logo-circle {
  width: 46px;
  height: 46px;
  background: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: logoPulse 3s ease-in-out infinite;
  flex-shrink: 0;
  overflow: hidden;
}

.nav-logo-circle img {
  width: 34px;
  height: 34px;
  object-fit: contain;
}

.nav-logo-text {
  color: #ffffff;
  font-size: 18px;
  font-weight: 900;
  letter-spacing: 4px;
  text-transform: uppercase;
}

/* CENTER LINKS */
.nav-center {
  display: flex;
  align-items: center;
  gap: 2px;
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}

.nav-link {
  color: rgba(255,255,255,0.7);
  text-decoration: none;
  padding: 8px 20px;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.5px;
  border-radius: 6px;
  transition: all 0.3s ease;
  position: relative;
  white-space: nowrap;
}

.nav-link::after {
  content: '';
  position: absolute;
  bottom: 2px;
  left: 20px;
  right: 20px;
  height: 2px;
  background: #dc2626;
  border-radius: 2px;
  transform: scaleX(0);
  transform-origin: center;
  transition: transform 0.3s ease;
}

.nav-link:hover {
  color: #ffffff;
  background: rgba(255,255,255,0.06);
}

.nav-link:hover::after {
  transform: scaleX(1);
}

.nav-link.active {
  color: #ffffff;
}

.nav-link.active::after {
  transform: scaleX(1);
}

/* RIGHT SIDE ICONS */
.nav-right {
  display: flex;
  align-items: center;
  gap: 6px;
}

/* ICON BUTTON BASE */
.nav-icon-btn {
  position: relative;
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  color: rgba(255,255,255,0.75);
  font-size: 18px;
}

.nav-icon-btn:hover {
  background: rgba(220,38,38,0.15);
  color: #dc2626;
  transform: translateY(-2px);
}

/* CART BADGE */
.nav-cart-badge {
  position: absolute;
  top: 4px;
  right: 4px;
  background: #dc2626;
  color: white;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  font-size: 9px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #0a0a0a;
  transition: all 0.3s ease;
}

.nav-icon-btn:hover .nav-cart-badge {
  background: white;
  color: #dc2626;
}

/* DIVIDER */
.nav-divider {
  width: 1px;
  height: 28px;
  background: rgba(255,255,255,0.1);
  margin: 0 6px;
}

/* USER CHIP */
.nav-user-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  color: rgba(255,255,255,0.7);
  font-size: 13px;
  font-weight: 600;
  padding: 7px 14px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 50px;
  letter-spacing: 0.3px;
  transition: all 0.3s ease;
}

.nav-user-chip:hover {
  background: rgba(255,255,255,0.1);
  color: white;
}

/* TOOLTIP */
.nav-icon-btn .tooltip {
  position: absolute;
  bottom: -34px;
  left: 50%;
  transform: translateX(-50%);
  background: #1a1a1a;
  color: white;
  font-size: 11px;
  padding: 4px 10px;
  border-radius: 6px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
  border: 1px solid rgba(255,255,255,0.1);
}

.nav-icon-btn:hover .tooltip {
  opacity: 1;
}

</style>

<!-- ===== NAVBAR ===== -->
<div class="navbar-main">

  <!-- LOGO LEFT -->
  <a href="<?= $base ?>index.php" class="nav-logo">
    <div class="nav-logo-circle">
      <img
        src="<?= $base ?>images/logo.png"
        alt="iPro"
        onerror="this.style.display='none';
          this.parentElement.innerHTML=
          '<span style=\'color:#dc2626;font-weight:900;font-size:16px;\'>iP</span>'"
      >
    </div>
    <span class="nav-logo-text">TECH STORE</span>
  </a>

  <!-- CENTER LINKS -->
  <div class="nav-center">
    <a href="<?= $base ?>new.php"
       class="nav-link <?= $current=='new.php'?'active':'' ?>">
      Home
    </a>
    <a href="<?= $base ?>product.php"
       class="nav-link <?= $current=='product.php'?'active':'' ?>">
      Products
    </a>
    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="<?= $base ?>my_orders.php"
       class="nav-link <?= $current=='my_orders.php'?'active':'' ?>">
      My Orders
    </a>
    <?php endif; ?>
  </div>

  <!-- RIGHT ICONS -->
  <div class="nav-right">

    <?php if (isset($_SESSION['user_id'])): ?>

      <!-- USER NAME CHIP -->
      <div class="nav-user-chip">
        <i class="fa-solid fa-circle-user" style="font-size:16px;color:#dc2626;"></i>
        <?= htmlspecialchars($_SESSION['username']) ?>
      </div>

      <!-- CART ICON -->
      <a href="<?= $base ?>cart.php" class="nav-icon-btn" title="">
        <i class="fa-solid fa-bag-shopping"></i>
        <?php if ($cart_count > 0): ?>
        <span class="nav-cart-badge"><?= $cart_count ?></span>
        <?php endif; ?>
        <span class="tooltip">Cart (<?= $cart_count ?>)</span>
      </a>

      <!-- LOGOUT ICON -->
      <a href="<?= $base ?>logout.php" class="nav-icon-btn" title="">
        <i class="fa-solid fa-arrow-right-from-bracket"></i>
        <span class="tooltip">Logout</span>
      </a>

    <?php else: ?>

      <!-- CART ICON (guest) -->
      <a href="<?= $base ?>cart.php" class="nav-icon-btn">
        <i class="fa-solid fa-bag-shopping"></i>
        <?php if ($cart_count > 0): ?>
        <span class="nav-cart-badge"><?= $cart_count ?></span>
        <?php endif; ?>
        <span class="tooltip">Cart (<?= $cart_count ?>)</span>
      </a>

      <div class="nav-divider"></div>

      <!-- LOGIN ICON -->
      <a href="<?= $base ?>login.php" class="nav-icon-btn">
        <i class="fa-solid fa-user"></i>
        <span class="tooltip">Login</span>
      </a>

    <?php endif; ?>

  </div>

</div>

<!-- SPACER -->
<div style="height:70px;"></div>