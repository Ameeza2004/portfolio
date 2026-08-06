<?php
// footer.php - Include this at bottom of every page
?>

<!-- FONT AWESOME + GOOGLE FONTS -->
<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
  rel="stylesheet">

<style>

footer {
  background: #080808;
  position: relative;
  z-index: 5;
  font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
}

.footer-top {
  padding: 70px 0 50px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 50px;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 30px;
}

/* BRAND COL */
.foot-logo-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 18px;
}

.foot-logo-circle {
  width: 50px;
  height: 50px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 14px rgba(220,38,38,0.3);
}

.foot-logo-circle img {
  width: 34px;
  height: 34px;
  object-fit: contain;
}

.foot-brand-name {
  font-size: 18px;
  font-weight: 900;
  color: white;
  letter-spacing: 4px;
  font-family: 'Poppins', sans-serif;
}

.foot-brand-tagline {
  font-size: 11px;
  font-weight: 500;
  color: rgba(255,255,255,0.25);
  letter-spacing: 2px;
  text-transform: uppercase;
  margin-top: 2px;
}

.foot-brand p {
  font-size: 13px;
  font-weight: 400;
  color: rgba(255,255,255,0.38);
  line-height: 1.9;
  max-width: 260px;
  margin-bottom: 22px;
  font-family: 'Poppins', sans-serif;
}

/* SOCIAL ICONS */
.social-row {
  display: flex;
  gap: 10px;
  margin-bottom: 22px;
  flex-wrap: wrap;
}

.social-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  text-decoration: none;
  transition: all 0.35s ease;
  border: none;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}

/* FACEBOOK */
.si-fb {
  background: #1877F2;
  color: white;
  box-shadow: 0 4px 14px rgba(24,119,242,0.35);
}

.si-fb:hover {
  background: #0d5fcf;
  transform: translateY(-5px) scale(1.05);
  box-shadow: 0 10px 24px rgba(24,119,242,0.5);
  color: white;
}

/* INSTAGRAM */
.si-ig {
  background: linear-gradient(
    135deg,
    #f09433 0%,
    #e6683c 25%,
    #dc2743 50%,
    #cc2366 75%,
    #bc1888 100%
  );
  color: white;
  box-shadow: 0 4px 14px rgba(225,48,108,0.35);
}

.si-ig:hover {
  transform: translateY(-5px) scale(1.05);
  box-shadow: 0 10px 24px rgba(225,48,108,0.5);
  color: white;
}

/* WHATSAPP */
.si-wa {
  background: #25D366;
  color: white;
  box-shadow: 0 4px 14px rgba(37,211,102,0.35);
}

.si-wa:hover {
  background: #1aaf52;
  transform: translateY(-5px) scale(1.05);
  box-shadow: 0 10px 24px rgba(37,211,102,0.5);
  color: white;
}

/* LOCATION */
.si-loc {
  background: #dc2626;
  color: white;
  box-shadow: 0 4px 14px rgba(220,38,38,0.35);
}

.si-loc:hover {
  background: #b91c1c;
  transform: translateY(-5px) scale(1.05);
  box-shadow: 0 10px 24px rgba(220,38,38,0.5);
  color: white;
}

/* TOOLTIP ON HOVER */
.social-icon::after {
  content: attr(title);
  position: absolute;
  bottom: -32px;
  left: 50%;
  transform: translateX(-50%);
  background: #1a1a1a;
  color: white;
  font-size: 10px;
  padding: 3px 10px;
  border-radius: 6px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
  font-family: 'Poppins', sans-serif;
}

.social-icon:hover::after {
  opacity: 1;
}

/* MAP */
.map-box {
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.07);
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

/* FOOTER COLUMNS */
.foot-col {
  font-family: 'Poppins', sans-serif;
}

.foot-col h4 {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 3px;
  color: white;
  margin-bottom: 24px;
  position: relative;
  padding-bottom: 12px;
  font-family: 'Poppins', sans-serif;
}

.foot-col h4::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0;
  width: 28px;
  height: 2px;
  background: #dc2626;
  border-radius: 2px;
}

.foot-col ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.foot-col ul li {
  margin-bottom: 14px;
}

.foot-col ul li a {
  color: rgba(255,255,255,0.38);
  text-decoration: none;
  font-size: 13px;
  font-weight: 400;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: 'Poppins', sans-serif;
}

.foot-col ul li a i {
  font-size: 13px;
  width: 16px;
  color: rgba(220,38,38,0.6);
  transition: color 0.3s ease;
}

.foot-col ul li a:hover {
  color: red;
  padding-left: 6px;
}

.foot-col ul li a:hover i {
  color: #dc2626;
}

/* FOOTER BOTTOM */
.footer-bottom-bar {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.footer-bottom-bar .left-text {
  font-size: 12px;
  font-weight: 400;
  color: rgba(255,255,255,0.2);
  font-family: 'Poppins', sans-serif;
}

.footer-bottom-bar .left-text span {
  color: #dc2626;
  font-weight: 700;
}

.footer-bottom-bar .right-text {
  font-size: 12px;
  font-weight: 400;
  color: rgba(255,255,255,0.2);
  text-align: right;
  font-family: 'Poppins', sans-serif;
}

.footer-bottom-bar .right-text span {
  color: #dc2626;
  font-weight: 700;
}

/* RESPONSIVE */
@media (max-width: 900px) {
  .footer-grid {
    grid-template-columns: 1fr 1fr;
    gap: 36px;
  }
}

@media (max-width: 560px) {
  .footer-grid {
    grid-template-columns: 1fr;
  }
  .footer-bottom-bar {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }
  .footer-bottom-bar .right-text {
    text-align: left;
  }
}

</style>

<footer>
  <div class="footer-top">
    <div class="footer-grid">

      <!-- ── BRAND COL ── -->
      <div class="foot-brand">

        <div class="foot-logo-wrap">
          <div class="foot-logo-circle">
            <img src="images/logo.png" alt="Logo"
              onerror="this.parentElement.innerHTML=
              '<b style=\'color:#dc2626;font-size:15px;
              font-family:Poppins,sans-serif;\'>iP</b>'">
          </div>
          <div>
            <div class="foot-brand-name">TECH STORE</div>
            <div class="foot-brand-tagline">Premium Accessories</div>
          </div>
        </div>

        <p>
          Sri Lanka's premium destination for tech accessories.
          Quality products, competitive prices, fast delivery.
        </p>

        <!-- SOCIAL ICONS -->
        <div class="social-row">

          <a href="https://facebook.com"
             target="_blank"
             class="social-icon si-fb"
             title="Facebook">
            <i class="fa-brands fa-facebook-f"></i>
          </a>

          <a href="https://instagram.com"
             target="_blank"
             class="social-icon si-ig"
             title="Instagram">
            <i class="fa-brands fa-instagram"></i>
          </a>

          <a href="https://wa.me/94725764060"
             target="_blank"
             class="social-icon si-wa"
             title="WhatsApp">
            <i class="fa-brands fa-whatsapp"></i>
          </a>

          <a href="https://www.google.com/maps?q=90+Penithudumulla,+Nawalapitiya,+Sri+Lanka&output=embed"
             target="_blank"
             class="social-icon si-loc"
             title="Our Location">
            <i class="fa-solid fa-location-dot"></i>
          </a>

        </div>

        <!-- GOOGLE MAP -->
        <div class="map-box">
        <div class="map-box">
  <iframe
    width="100%"
    height="160"
    style="border:0;display:block;border-radius:12px;"
    loading="lazy"
    allowfullscreen
    src="https://maps.google.com/maps?q=Nawalapitiya,Sri+Lanka&output=embed">
  </iframe>
</div>
        </div>

      </div>

      <!-- ── SHOP COL ── -->
      <div class="foot-col">
        <h4>Shop</h4>
        <ul>
          <li>
            <a href="product.php">
              <i class="fa-solid fa-chevron-right"></i>
              All Products
            </a>
          </li>
          <li>
            <a href="product.php?category=Earphone">
              <i class="fa-solid fa-chevron-right"></i>
              Earphones
            </a>
          </li>
          <li>
            <a href="product.php?category=Headphone">
              <i class="fa-solid fa-chevron-right"></i>
              Headphones
            </a>
          </li>
          <li>
            <a href="product.php?category=Earbud">
              <i class="fa-solid fa-chevron-right"></i>
              Earbuds
            </a>
          </li>
        </ul>
      </div>

      <!-- ── ACCOUNT COL ── -->
      <div class="foot-col">
        <h4>Account</h4>
        <ul>
          <li>
            <a href="login.php">
              <i class="fa-solid fa-chevron-right"></i>
              Login
            </a>
          </li>
          <li>
            <a href="register.php">
              <i class="fa-solid fa-chevron-right"></i>
              Register
            </a>
          </li>
          <li>
            <a href="my_orders.php">
              <i class="fa-solid fa-chevron-right"></i>
              My Orders
            </a>
          </li>
          <li>
            <a href="cart.php">
              <i class="fa-solid fa-chevron-right"></i>
              Cart
            </a>
          </li>
        </ul>
      </div>

      <!-- ── CONTACT COL ── -->
      <div class="foot-col">
        <h4>Contact</h4>
        <ul>
          <li>
            <a href="https://maps.google.com/?q=Colombo,Sri+Lanka"
               target="_blank">
              <i class="fa-solid fa-location-dot"></i>
              Penithudumulla,Nawalapitiya, Sri Lanka
            </a>
          </li>
          <li>
            <a href="tel:+94725764060">
              <i class="fa-solid fa-phone"></i>
              +94 72 576 4060
            </a>
          </li>
          <li>
            <a href="https://wa.me/94725764060" target="_blank">
              <i class="fa-brands fa-whatsapp"></i>
              WhatsApp Us
            </a>
          </li>
          <li>
            <a href="mailto:ipro@gmail.com.">
              <i class="fa-solid fa-envelope"></i>
              ipro@gmail.com
            </a>
          </li>
        </ul>
      </div>

    </div>
  </div>

  <!-- BOTTOM BAR -->
  <div class="footer-bottom-bar">

    <p class="left-text">
      © <?= date('Y') ?>
      <span>Tech Accessories Store</span>.
      All rights reserved.
    </p>

    <p class="right-text">
      Designed with <span>♥</span>
      for University Presentation
    </p>

  </div>

</footer>