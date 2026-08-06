
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

$search   = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search) {
    $where   .= " AND product_name LIKE ?";
    $params[] = "%$search%";
    $types   .= "s";
}
if ($category) {
    $where   .= " AND category = ?";
    $params[] = $category;
    $types   .= "s";
}

$stmt = mysqli_prepare($conn,
    "SELECT * FROM products $where ORDER BY id DESC");
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$cats = mysqli_query($conn,
    "SELECT DISTINCT category FROM products ORDER BY category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products – Tech Store</title>
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

body { background: #f5f5f5; color: #111; }

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 30px;
}

/* PAGE HEADER */
.page-header {
  background: #111;
  padding: 60px 0 50px;
  position: relative;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  width: 500px; height: 500px;
  background: radial-gradient(circle,
    rgba(220,38,38,0.15) 0%, transparent 70%);
  top: 50%; right: -100px;
  transform: translateY(-50%);
  border-radius: 50%;
  pointer-events: none;
}

.page-header-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 30px;
  position: relative;
  z-index: 1;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 20px;
  font-size: 13px;
}

.breadcrumb a {
  color: rgba(255,255,255,0.4);
  text-decoration: none;
  transition: color 0.2s;
}

.breadcrumb a:hover { color: #dc2626; }
.breadcrumb span    { color: rgba(255,255,255,0.2); }
.breadcrumb strong  { color: rgba(255,255,255,0.7); }

.page-header h1 {
  font-size: clamp(32px,5vw,52px);
  font-weight: 900;
  color: white;
  letter-spacing: -1px;
  margin-bottom: 8px;
}

.page-header h1 span { color: #dc2626; }

.page-header p {
  color: rgba(255,255,255,0.45);
  font-size: 15px;
  font-weight: 400;
}

/* FILTER BAR */
.filter-bar {
  background: white;
  padding: 18px 0;
  position: sticky;
  top: 70px;
  z-index: 100;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  border-bottom: 1px solid #f0f0f0;
}

.filter-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 30px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 200px;
  max-width: 340px;
}

.search-box i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 14px;
}

.search-box input {
  width: 100%;
  padding: 11px 16px 11px 40px;
  border: 2px solid #e5e7eb;
  border-radius: 50px;
  font-size: 14px;
  outline: none;
  transition: all 0.3s ease;
  background: #f9fafb;
  color: #111;
  font-family: 'Poppins', sans-serif;
}

.search-box input:focus {
  border-color: #dc2626;
  background: white;
  box-shadow: 0 0 0 4px rgba(220,38,38,0.08);
}

.search-btn {
  padding: 11px 24px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 50px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: 'Poppins', sans-serif;
}

.search-btn:hover {
  background: #b91c1c;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(220,38,38,0.35);
}

.clear-btn {
  padding: 11px 20px;
  background: #f3f4f6;
  color: #6b7280;
  border: none;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: 'Poppins', sans-serif;
}

.clear-btn:hover {
  background: #e5e7eb;
  color: #111;
}

.cat-pills {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: center;
}

.cat-pill {
  padding: 8px 18px;
  border-radius: 50px;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  border: 1.5px solid #e5e7eb;
  color: #6b7280;
  background: white;
  transition: all 0.3s ease;
  font-family: 'Poppins', sans-serif;
}

.cat-pill:hover,
.cat-pill.active {
  background: #dc2626;
  color: white;
  border-color: #dc2626;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(220,38,38,0.3);
}

/* PRODUCTS AREA */
.products-area {
  padding: 40px 0 80px;
}

.results-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
  flex-wrap: wrap;
  gap: 12px;
}

.results-count {
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.results-count strong {
  color: #111;
  font-weight: 800;
}

/* PRODUCT GRID */
.products-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

/* PRODUCT CARD */
.prod-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  transition: all 0.4s ease;
  box-shadow: 0 2px 16px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
  position: relative;
  animation: fadeUp 0.5s ease both;
}

.prod-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 24px 60px rgba(0,0,0,0.13);
  border-color: transparent;
}

@keyframes fadeUp {
  from { opacity:0; transform:translateY(24px); }
  to   { opacity:1; transform:translateY(0); }
}

/* IMAGE */
.prod-img-box {
  position: relative;
  height: 240px;
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

.prod-new-tag {
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

/* CARD BODY */
.prod-body {
  padding: 18px 20px 22px;
}

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
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
  margin-bottom: 12px;
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

/* ADD TO CART */
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

/* NO RESULTS */
.no-results {
  text-align: center;
  padding: 80px 20px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.06);
}

.no-results-icon {
  font-size: 72px;
  margin-bottom: 20px;
  display: block;
  color: #e5e7eb;
}

.no-results h3 {
  font-size: 22px;
  font-weight: 800;
  color: #111;
  margin-bottom: 10px;
}

.no-results p {
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 24px;
}

.btn-red {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 13px 28px;
  background: #dc2626;
  color: white;
  border-radius: 50px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 700;
  transition: all 0.3s ease;
}

.btn-red:hover {
  background: #b91c1c;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(220,38,38,0.4);
  color: white;
}

@media (max-width: 900px) {
  .products-grid { grid-template-columns: repeat(2,1fr); }
}

@media (max-width: 560px) {
  .products-grid { grid-template-columns: 1fr; }
  .filter-inner  { flex-direction: column; align-items: stretch; }
  .search-box    { max-width: 100%; }
}
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-inner">
    <div class="breadcrumb">
      <a href="index.php">
        <i class="fa-solid fa-house"></i> Home
      </a>
      <span>/</span>
      <strong>Products</strong>
    </div>
    <h1>Our <span>Products</span></h1>
    <p>Browse our full collection of premium tech accessories</p>
  </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar">
  <div class="filter-inner">

    <form method="GET"
      style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search"
          placeholder="Search products..."
          value="<?= htmlspecialchars($search) ?>">
        <?php if ($category): ?>
        <input type="hidden" name="category"
          value="<?= htmlspecialchars($category) ?>">
        <?php endif; ?>
      </div>
      <button type="submit" class="search-btn">
        Search
      </button>
      <?php if ($search || $category): ?>
      <a href="product.php" class="clear-btn">
        <i class="fa-solid fa-xmark"></i> Clear
      </a>
      <?php endif; ?>
    </form>

    <div class="cat-pills">
      <a href="product.php"
        class="cat-pill <?= !$category?'active':'' ?>">
        All
      </a>
     <?php while ($cat = mysqli_fetch_assoc($cats)):
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
<a href="product.php?category=<?= urlencode($cat_name) ?>
    <?= $search ? '&search='.urlencode($search) : '' ?>"
  class="cat-pill
    <?= $category===$cat_name?'active':'' ?>">
  <i class="fa-solid <?= $icon ?>"></i>
  <?= htmlspecialchars($cat_name) ?>
</a>
<?php endwhile; ?>
    </div>

  </div>
</div>

<!-- PRODUCTS AREA -->
<div class="products-area">
  <div class="container">

    <div class="results-bar">
      <div class="results-count">
        Showing
        <strong><?= mysqli_num_rows($result) ?></strong>
        products
        <?php if ($category): ?>
          in <strong><?= htmlspecialchars($category) ?></strong>
        <?php endif; ?>
        <?php if ($search): ?>
          for "<strong><?= htmlspecialchars($search) ?></strong>"
        <?php endif; ?>
      </div>
    </div>

    <?php if (mysqli_num_rows($result) === 0): ?>
    <div class="no-results">
      <span class="no-results-icon">
        <i class="fa-solid fa-box-open"></i>
      </span>
      <h3>No products found</h3>
      <p>Try a different search or browse all products.</p>
      <a href="product.php" class="btn-red">
        <i class="fa-solid fa-arrow-left"></i>
        View All Products
      </a>
    </div>

    <?php else: ?>
    <div class="products-grid">
    <?php while ($row = mysqli_fetch_assoc($result)):
      $in_stock = $row['quantity'] > 0;
    ?>
      <div class="prod-card">

        <div class="prod-img-box">
          <img
            src="uploads/<?= htmlspecialchars($row['image']) ?>"
            alt="<?= htmlspecialchars($row['product_name']) ?>"
            onerror="this.src='images/logo.png'">

          <span class="prod-new-tag">New</span>

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
            Rs <?= number_format($row['price'], 2) ?>
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
    <?php endif; ?>

  </div>
</div>

<?php include 'footer.php'; ?>

<script>
function changeQty(btn, delta, max) {
    const input = btn.parentElement.querySelector('.qty-input');
    let val = parseInt(input.value);

    if (delta > 0 && val >= max) {
        alert("Only " + max + " products are available in stock.");
        return;
    }

    val += delta;

    if (val < 1) val = 1;

    input.value = val;
}
</script>

</body>
</html>