<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

$featured = mysqli_query($conn,
    "SELECT * FROM products
     WHERE quantity > 0
     ORDER BY created_at DESC LIMIT 6");

$cats = mysqli_query($conn,
    "SELECT DISTINCT category FROM products");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tech Store – Premium Tech Accessories</title>
<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap"
  rel="stylesheet">

<style>
* {
  margin: 0; padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
}

body { background: #fff; color: #111; overflow-x: hidden; }

/* CATEGORY STRIP */
.cat-strip {
  background: #111;
  padding: 20px 0;
  position: sticky;
  top: 70px;
  z-index: 100;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.cat-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 30px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
}

.cat-pill {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 22px;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.3s ease;
  border: 1px solid rgba(255,255,255,0.12);
  color: rgba(255,255,255,0.65);
  background: transparent;
}

.cat-pill:hover,
.cat-pill.active {
  background: #dc2626;
  color: white;
  border-color: #dc2626;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(220,38,38,0.35);
}

/* PRODUCTS SECTION */
.products-grid {
  display: grid;
  grid-template-columns: repeat(3,1fr);
  gap: 28px;
  padding: 60px 0;
}

.prod-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  transition: all 0.4s ease;
  box-shadow: 0 2px 16px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
  position: relative;
}

.prod-card:hover {
  transform: translateY(-12px);
  box-shadow: 0 24px 60px rgba(0,0,0,0.13);
  border-color: transparent;
}

.prod-img-box {
  position: relative;
  height: 230px;
  overflow: hidden;
  background: #f5f5f5;
}

.prod-img-box img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.prod-card:hover .prod-img-box img {
  transform: scale(1.08);
}

.prod-tag {
  position: absolute;
  top: 14px; left: 14px;
  background: #111;
  color: white;
  font-size: 10px;
  font-weight: 800;
  padding: 5px 12px;
  border-radius: 50px;
  letter-spacing: 1px;
  text-transform: uppercase;
}

.prod-stock-tag {
  position: absolute;
  top: 14px; right: 14px;
  font-size: 10px;
  font-weight: 800;
  padding: 5px 12px;
  border-radius: 50px;
}

.tag-in  { background:#dcfce7; color:#166534; }
.tag-low { background:#fef9c3; color:#854d0e; }
.tag-out { background:#fee2e2; color:#991b1b; }

.prod-body { padding: 18px 20px 22px; }

.prod-cat {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  color: #6b7280;
  margin-bottom: 6px;
}

.prod-name {
  font-size: 17px;
  font-weight: 800;
  color: #111;
  margin-bottom: 6px;
  line-height: 1.3;
}

.prod-desc {
  font-size: 13px;
  color: #6b7280;
  line-height: 1.6;
  margin-bottom: 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.prod-stock-count {
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.prod-stock-count i { color: #dc2626; font-size: 11px; }

.prod-price {
  font-size: 22px;
  font-weight: 900;
  color: #111;
  display: block;
  margin-bottom: 16px;
}

/* QTY CONTROLS */
.qty-wrap {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.qty-label {
  font-size: 11px;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.qty-row {
  display: flex;
  align-items: center;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}

.qty-btn {
  width: 34px; height: 34px;
  background: #f5f5f5;
  border: none;
  font-size: 18px;
  font-weight: 700;
  color: #111;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.qty-btn:hover {
  background: #dc2626;
  color: white;
}

.qty-input {
  width: 38px; height: 34px;
  border: none;
  border-left: 1.5px solid #e5e7eb;
  border-right: 1.5px solid #e5e7eb;
  text-align: center;
  font-size: 14px;
  font-weight: 800;
  color: #111;
  outline: none;
  background: white;
  font-family: 'Poppins', sans-serif;
}

/* ADD TO CART BTN */
.add-cart-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 13px;
  background: #111;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  font-family: 'Poppins', sans-serif;
  margin-top: 4px;
}

.add-cart-btn:hover {
  background: #dc2626;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(220,38,38,0.35);
}

.add-cart-btn.disabled {
  background: #e5e7eb;
  color: #9ca3af;
  cursor: not-allowed;
  pointer-events: none;
}

/* (Call To Action) SECTION */
.cta-section {
  background: #0a0a0a;
  padding: 100px 0;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.cta-glow {
  position: absolute;
  width: 600px; height: 600px;
  background: radial-gradient(circle,
    rgba(220,38,38,0.14) 0%, transparent 70%);
  top: 50%; left: 50%;
  transform: translate(-50%,-50%);
  border-radius: 50%;
  animation: glowPulse 5s ease-in-out infinite;
  pointer-events: none;
}

@keyframes glowPulse {
  0%,100% { transform:translate(-50%,-50%) scale(1); }
  50%      { transform:translate(-50%,-50%) scale(1.3); }
}

.btn-red {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 16px 42px;
  border-radius: 50px;
  font-size: 15px;
  font-weight: 800;
  text-decoration: none;
  background: #dc2626;
  color: white;
  box-shadow: 0 8px 30px rgba(220,38,38,0.45);
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  position: relative;
  z-index: 1;
}

.btn-red:hover {
  background: #b91c1c;
  transform: translateY(-4px);
  box-shadow: 0 16px 40px rgba(220,38,38,0.55);
  color: white;
}

/* REVEAL ANIMATION */
.reveal {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.7s ease, transform 0.7s ease;
}

.reveal.visible {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 900px) {
  .products-grid { grid-template-columns: repeat(2,1fr); }
}

@media (max-width: 560px) {
  .products-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<?php include 'navbar.php'; ?>

<!-- HERO -->
<?php include 'hero.php'; ?>

<!-- CATEGORY STRIP -->
<div class="cat-strip">
  <div class="cat-inner">
    <span style="font-size:10px;font-weight:700;
      letter-spacing:2px;text-transform:uppercase;
      color:rgba(255,255,255,0.25);margin-right:6px;">
      Browse:
    </span>
    <a href="product.php" class="cat-pill active">
  <i class="fa-solid fa-th-large"></i> All
</a>
<?php while ($cat = mysqli_fetch_assoc($cats)):
  // Choose icon based on category
  $cat_name = $cat['category'];
  if ($cat_name === 'Earphone') {
      $icon = 'fa-headphones';
  } elseif ($cat_name === 'Headphone') {
      $icon = 'fa-headphones-simple';
  } elseif ($cat_name === 'Earbud') {
      $icon = 'fa-music';
  } elseif ($cat_name === 'Power Bank') {
      $icon = 'fa-battery-full';
  } elseif ($cat_name === 'Smart Watch') {
      $icon = 'fa-clock';
  } else {
      $icon = 'fa-tag';
  }
?>
<a href="product.php?category=<?= urlencode($cat_name) ?>"
   class="cat-pill">
  <i class="fa-solid <?= $icon ?>"></i>
  <?= htmlspecialchars($cat_name) ?>
</a>
<?php endwhile; ?>
  </div>
</div>

<!-- PRODUCTS -->
<div style="background:white;padding:0 0 60px;">
  <div style="max-width:1200px;margin:0 auto;padding:0 30px;">

    <div style="display:flex;justify-content:space-between;
      align-items:flex-end;padding-top:60px;
      margin-bottom:40px;flex-wrap:wrap;gap:16px;">
      <div>
        <p style="font-size:11px;font-weight:700;
          letter-spacing:3px;text-transform:uppercase;
          color:#dc2626;margin-bottom:8px;">
          <i class="fa-solid fa-star"></i> Featured
        </p>
        <h2 style="font-size:clamp(28px,4vw,46px);
          font-weight:900;color:#111;letter-spacing:-1px;">
          Top Products
        </h2>
      </div>
      <a href="product.php"
        style="display:inline-flex;align-items:center;
          gap:8px;color:#111;text-decoration:none;
          font-size:14px;font-weight:700;padding:12px 26px;
          border-radius:50px;border:2px solid #111;
          transition:all 0.3s ease;"
        onmouseover="this.style.background='#111';
          this.style.color='white'"
        onmouseout="this.style.background='transparent';
          this.style.color='#111'">
        View All
        <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <!-- PRODUCT CARDS -->
    <div class="products-grid">
    <?php while ($row = mysqli_fetch_assoc($featured)):
      $in_stock = $row['quantity'] > 0;
    ?>
      <div class="prod-card reveal">
        <div class="prod-img-box">
          <img
            src="uploads/<?= htmlspecialchars($row['image']) ?>"
            alt="<?= htmlspecialchars($row['product_name']) ?>"
            onerror="this.src='images/logo.png'">
          <span class="prod-tag">New</span>
          <?php if ($row['quantity'] <= 0): ?>
            <span class="prod-stock-tag tag-out">
              Out of Stock
            </span>
          <?php elseif ($row['quantity'] <= 3): ?>
            <span class="prod-stock-tag tag-low">
              Only <?= $row['quantity'] ?> left
            </span>
          <?php else: ?>
            <span class="prod-stock-tag tag-in">
              In Stock
            </span>
          <?php endif; ?>
        </div>

        <div class="prod-body">
          <div class="prod-cat">
            <?= htmlspecialchars($row['category']) ?>
          </div>
          <div class="prod-name">
            <?= htmlspecialchars($row['product_name']) ?>
          </div>
          <?php if ($row['description']): ?>
          <div class="prod-desc">
            <?= htmlspecialchars($row['description']) ?>
          </div>
          <?php endif; ?>

          <div class="prod-stock-count">
            <i class="fa-solid fa-box-archive"></i>
            <?= $row['quantity'] ?> units available
          </div>

          <span class="prod-price">
            Rs <?= number_format($row['price'],2) ?>
          </span>

          <?php if ($in_stock): ?>
          <form action="add_to_cart.php" method="GET">
            <input type="hidden"
              name="id" value="<?= $row['id'] ?>">
            <div class="qty-wrap">
              <span class="qty-label">Quantity</span>
              <div class="qty-row">
                <button type="button" class="qty-btn"
                  onclick="changeQty(this,-1)">−</button>
                <input type="number" name="qty"
                  class="qty-input"
                  value="1" min="1"
                  max="<?= $row['quantity'] ?>"
                  readonly>
                <button type="button" class="qty-btn"
                  onclick="changeQty(this,1,
                  <?= $row['quantity'] ?>)">+</button>
              </div>
            </div>
            <button type="submit" class="add-cart-btn">
              <i class="fa-solid fa-bag-shopping"></i>
              Add to Cart
            </button>
          </form>
          <?php else: ?>
          <div class="add-cart-btn disabled">
            <i class="fa-solid fa-ban"></i>
            Out of Stock
          </div>
          <?php endif; ?>

        </div>
      </div>
    <?php endwhile; ?>
    </div>

  </div>
</div>

<!-- FEATURES -->
<?php include 'features.php'; ?>

<!-- Call To Action -->
<section class="cta-section">
  <div class="cta-glow"></div>
  <div style="position:relative;z-index:1;
    max-width:1200px;margin:0 auto;padding:0 30px;">
    <p style="font-size:11px;font-weight:700;
      letter-spacing:3px;text-transform:uppercase;
      color:#dc2626;margin-bottom:20px;">
      Limited Time
    </p>
    <h2 style="font-size:clamp(34px,5vw,66px);
      font-weight:900;color:white;letter-spacing:-2px;
      line-height:1.1;margin-bottom:18px;">
      Ready to Upgrade<br>Your Tech?
    </h2>
    <p style="font-size:16px;
      color:rgba(255,255,255,0.45);
      margin-bottom:44px;">
      Browse our full collection and find your perfect gear.
    </p>
    <a href="product.php" class="btn-red">
      Shop Collection
      <i class="fa-solid fa-arrow-right"></i>
    </a>
  </div>
</section>

<!-- FOOTER -->
<?php include 'footer.php'; ?>

<script>
// Scroll Reveal
const reveals = document.querySelectorAll('.reveal');
const obs = new IntersectionObserver(function(entries) {
  entries.forEach(function(e) {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
    }
  });
}, { threshold: 0.12 });
reveals.forEach(function(el) { obs.observe(el); });

// Quantity Controls
function changeQty(btn, delta, max) {
  const input = btn.parentElement
    .querySelector('.qty-input');
  let val = parseInt(input.value) + delta;
  if (val < 1) val = 1;
  if (max && val > max) val = max;
  input.value = val;
}
</script>

</body>
</html>