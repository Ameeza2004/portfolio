<?php
// hero.php - Homepage hero section only
?>

<style>
.hero {
  position: relative;
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  overflow: hidden;
}

/* BACKGROUND IMAGE */
.hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url('images/bg.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  z-index: 0;
}

/* DARK OVERLAY */
.hero::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.72);
  z-index: 1;
}

.hero-content {
  position: relative;
  z-index: 2;
  padding: 120px 20px 40px;
  max-width: 820px;
  width: 100%;
}

.hero-badge {
  display: inline-block;
  background: rgba(220,38,38,0.18);
  border: 1px solid rgba(220,38,38,0.45);
  color: #fca5a5;
  padding: 8px 22px;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 3px;
  text-transform: uppercase;
  margin-bottom: 28px;
  animation: fadeDown 0.8s ease both;
}

.hero-title {
  font-size: clamp(52px,10vw,100px);
  font-weight: 900;
  color: white;
  line-height: 1;
  letter-spacing: -3px;
  margin-bottom: 8px;
  text-transform: uppercase;
  animation: fadeDown 0.9s ease 0.1s both;
}

.hero-title-red {
  font-size: clamp(52px,10vw,100px);
  font-weight: 900;
  color: #dc2626;
  display: block;
  line-height: 1;
  letter-spacing: -3px;
  text-transform: uppercase;
  margin-bottom: 28px;
  animation: fadeDown 1s ease 0.15s both;
}

.hero-sub {
  font-size: 17px;
  color: rgba(255,255,255,0.55);
  margin-bottom: 40px;
  line-height: 1.8;
  animation: fadeDown 1s ease 0.2s both;
}

.hero-buttons {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 60px;
  animation: fadeDown 1.1s ease 0.3s both;
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
}

.btn-red:hover {
  background: #b91c1c;
  transform: translateY(-4px);
  box-shadow: 0 16px 40px rgba(220,38,38,0.55);
  color: white;
}

.btn-white {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 16px 42px;
  border-radius: 50px;
  font-size: 15px;
  font-weight: 800;
  text-decoration: none;
  background: transparent;
  color: white;
  border: 2px solid rgba(255,255,255,0.35);
  transition: all 0.3s ease;
}

.btn-white:hover {
  background: white;
  color: #111111;
  border-color: white;
  transform: translateY(-4px);
}

/* STATS */
.hero-stats {
  position: relative;
  z-index: 2;
  display: flex;
  gap: 60px;
  justify-content: center;
  flex-wrap: wrap;
  padding: 28px 20px 50px;
  border-top: 1px solid rgba(255,255,255,0.08);
  width: 100%;
  max-width: 600px;
  animation: fadeUp 1.2s ease 0.5s both;
}

.hero-stat-number {
  font-size: 32px;
  font-weight: 900;
  color: white;
  display: block;
  line-height: 1;
  margin-bottom: 6px;
}

.hero-stat-label {
  font-size: 10px;
  color: rgba(255,255,255,0.35);
  text-transform: uppercase;
  letter-spacing: 2.5px;
  display: block;
}

/* SCROLL HINT */
.scroll-hint {
  position: absolute;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: rgba(255,255,255,0.2);
  font-size: 9px;
  letter-spacing: 2px;
  text-transform: uppercase;
}

.scroll-line {
  width: 1px;
  height: 48px;
  background: linear-gradient(to bottom, #dc2626, transparent);
  animation: scrollAnim 2s ease-in-out infinite;
}

@keyframes scrollAnim {
  0%   { opacity:0; transform:scaleY(0); transform-origin:top; }
  50%  { opacity:1; transform:scaleY(1); transform-origin:top; }
  100% { opacity:0; transform:scaleY(0); transform-origin:bottom; }
}

@keyframes fadeDown {
  from { opacity:0; transform:translateY(-24px); }
  to   { opacity:1; transform:translateY(0); }
}

@keyframes fadeUp {
  from { opacity:0; transform:translateY(24px); }
  to   { opacity:1; transform:translateY(0); }
}
</style>

<section class="hero">
  <div class="hero-content">

    <div class="hero-badge">
      <i class="fa-solid fa-bolt"></i>
      &nbsp; New Collection 2026
    </div>

    <h1 class="hero-title">PREMIUM</h1>
    <span class="hero-title-red">TECH GEAR</span>

    <p class="hero-sub">
      Discover the finest tech accessories.<br>
      Engineered for performance. Designed for style.
    </p>

    <div class="hero-buttons">
      <a href="product.php" class="btn-red">
        Shop Now &nbsp;<i class="fa-solid fa-arrow-right"></i>
      </a>
      <?php if (!isset($_SESSION['user_id'])): ?>
      <a href="register.php" class="btn-white">Create Account</a>
      <?php else: ?>
      <a href="my_orders.php" class="btn-white">My Orders</a>
      <?php endif; ?>
    </div>

    <div class="hero-stats">
      <div style="text-align:center;">
        <span class="hero-stat-number">500+</span>
        <span class="hero-stat-label">Products</span>
      </div>
      <div style="text-align:center;">
        <span class="hero-stat-number">10K+</span>
        <span class="hero-stat-label">Customers</span>
      </div>
      <div style="text-align:center;">
        <span class="hero-stat-number">99%</span>
        <span class="hero-stat-label">Satisfaction</span>
      </div>
    </div>

  </div>

  <div class="scroll-hint">
    <div class="scroll-line"></div>
    <span>Scroll</span>
  </div>
</section>