<?php
// features.php - Feature strip + Why Us section
?>

<style>
.feature-strip {
  background: #111111;
  padding: 60px 0;
}

.feat-grid {
  display: grid;
  grid-template-columns: repeat(4,1fr);
  gap: 20px;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 30px;
}

.feat-item {
  text-align: center;
  padding: 32px 20px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.05);
  transition: all 0.35s ease;
  cursor: default;
}

.feat-item:hover {
  background: rgba(220,38,38,0.1);
  border-color: rgba(220,38,38,0.25);
  transform: translateY(-6px);
}

.feat-icon {
  width: 58px;
  height: 58px;
  background: rgba(220,38,38,0.12);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 18px;
  font-size: 22px;
  color: #dc2626;
  transition: all 0.35s ease;
}

.feat-item:hover .feat-icon {
  background: #dc2626;
  color: white;
  transform: scale(1.12);
}

.feat-title {
  font-size: 14px;
  font-weight: 800;
  color: white;
  margin-bottom: 8px;
}

.feat-desc {
  font-size: 12px;
  color: rgba(255,255,255,0.38);
  line-height: 1.7;
}

/* WHY US */
.why-section {
  background: #f8f8f8;
  padding: 90px 0;
}

.why-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 30px;
}

.why-grid {
  display: grid;
  grid-template-columns: repeat(3,1fr);
  gap: 24px;
}

.why-card {
  background: white;
  border-radius: 20px;
  padding: 38px 30px;
  transition: all 0.4s ease;
  border: 2px solid transparent;
  position: relative;
  overflow: hidden;
}

.why-card::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  background: #dc2626;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.4s ease;
}

.why-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 24px 60px rgba(0,0,0,0.09);
}

.why-card:hover::after { transform: scaleX(1); }

.why-num {
  font-size: 64px;
  font-weight: 900;
  color: rgba(220,38,38,0.07);
  line-height: 1;
  margin-bottom: 16px;
}

.why-title {
  font-size: 19px;
  font-weight: 800;
  color: #111;
  margin-bottom: 10px;
}

.why-desc {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.75;
}

@media (max-width: 900px) {
  .feat-grid { grid-template-columns: repeat(2,1fr); }
  .why-grid  { grid-template-columns: 1fr; }
}

@media (max-width: 560px) {
  .feat-grid { grid-template-columns: 1fr; }
}
</style>

<!-- FEATURE STRIP -->
<div class="feature-strip">
  <div class="feat-grid">
    <div class="feat-item">
      <div class="feat-icon"><i class="fa-solid fa-truck-fast"></i></div>
      <div class="feat-title">Free Delivery</div>
      <div class="feat-desc">Free shipping on orders over Rs 5,000.</div>
    </div>
    <div class="feat-item">
      <div class="feat-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <div class="feat-title">Secure Payment</div>
      <div class="feat-desc">100% protected checkout.</div>
    </div>
    <div class="feat-item">
      <div class="feat-icon"><i class="fa-solid fa-rotate-left"></i></div>
      <div class="feat-title">Easy Returns</div>
      <div class="feat-desc">7-day hassle-free returns.</div>
    </div>
    <div class="feat-item">
      <div class="feat-icon"><i class="fa-solid fa-headset"></i></div>
      <div class="feat-title">24/7 Support</div>
      <div class="feat-desc">We are always here to help.</div>
    </div>
  </div>
</div>

<!-- WHY US -->
<section class="why-section">
  <div class="why-inner">
    <div style="text-align:center;margin-bottom:60px;">
      <p style="font-size:11px;font-weight:700;letter-spacing:3px;
        text-transform:uppercase;color:#dc2626;margin-bottom:10px;">
        Why Choose Us
      </p>
      <h2 style="font-size:clamp(28px,4vw,46px);font-weight:900;
        color:#111;letter-spacing:-1px;">
        Built for the Best
      </h2>
    </div>
    <div class="why-grid">
      <div class="why-card">
        <div class="why-num">01</div>
        <div class="why-title">Premium Quality</div>
        <div class="why-desc">
          Every product is carefully sourced and quality-checked.
        </div>
      </div>
      <div class="why-card">
        <div class="why-num">02</div>
        <div class="why-title">Best Prices</div>
        <div class="why-desc">
          Competitive pricing with regular offers and deals.
        </div>
      </div>
      <div class="why-card">
        <div class="why-num">03</div>
        <div class="why-title">Fast Service</div>
        <div class="why-desc">
          Quick processing and reliable island-wide delivery.
        </div>
      </div>
    </div>
  </div>
</section>