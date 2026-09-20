<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Admin guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = '';
$error   = '';

if (isset($_POST['add_product'])) {
    $name        = trim($_POST['product_name']);
    $category    = trim($_POST['category']);
    $price       = trim($_POST['price']);
    $quantity    = trim($_POST['quantity']);
    $description = trim($_POST['description']);

    if (empty($name) || empty($category) || empty($price) || $quantity === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = 'Please enter a valid price.';
    } elseif (!is_numeric($quantity) || $quantity < 0) {
        $error = 'Please enter a valid quantity.';
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
        $error = 'Please upload a product image.';
    } else {
        $allowed = ['jpg','jpeg','png','webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = 'Only JPG, PNG, WEBP images are allowed.';
        } else {
            $newname = uniqid('prod_') . '.' . $ext;

            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }

            $upload_path = 'uploads/' . $newname;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $status = $quantity > 0 ? 'Available' : 'Out of Stock';
                $price_f = (float)$price;
                $qty_i   = (int)$quantity;

                $stmt = mysqli_prepare($conn,
                    "INSERT INTO products
                     (product_name, category, price, quantity, image, product_status, description)
                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssdisss",
                    $name, $category, $price_f, $qty_i, $newname, $status, $description);

                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Product "' . htmlspecialchars($name) . '" added successfully!';
                } else {
                    $error = 'Database error. Please try again.';
                }
            } else {
                $error = 'Failed to upload image. Try again.';
            }
        }
    }
}

// Existing categories for the dropdown / datalist
$cats = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category");
$cat_list = [];
while ($c = mysqli_fetch_assoc($cats)) {
    $cat_list[] = $c['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product – Admin</title>

<link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap"
  rel="stylesheet">

<style>
* {
  margin: 0; padding: 0;
  box-sizing: border-box;
  font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
}

body { background: #f0f2f5; color: #111; }

/* ── SIDEBAR ── */
.admin-wrapper { display: flex; min-height: 100vh; }

.admin-sidebar {
  width: 250px;
  background: #0a0a0a;
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 100;
  overflow-y: auto;
  box-shadow: 4px 0 24px rgba(0,0,0,0.3);
}

.sidebar-brand {
  padding: 26px 22px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  display: flex;
  align-items: center;
  gap: 12px;
}

.sidebar-logo-circle {
  width: 42px;
  height: 42px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.sidebar-logo-circle img {
  width: 28px; height: 28px;
  object-fit: contain;
}

.sidebar-brand span {
  color: white;
  font-size: 14px;
  font-weight: 800;
  letter-spacing: 2px;
}

.sidebar-section {
  padding: 22px 22px 10px;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: rgba(255,255,255,0.25);
  font-weight: 700;
}

.sidebar-nav a {
  display: flex;
  align-items: center;
  gap: 12px;
  color: rgba(255,255,255,0.6);
  text-decoration: none;
  padding: 12px 22px;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.sidebar-nav a:hover,
.sidebar-nav a.active {
  background: rgba(220,38,38,0.12);
  color: white;
  border-left-color: #dc2626;
}

.sidebar-nav a i { width: 18px; font-size: 14px; }

.sidebar-logout {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  padding: 16px;
  border-top: 1px solid rgba(255,255,255,0.08);
}

.sidebar-logout a {
  display: flex;
  align-items: center;
  gap: 10px;
  color: rgba(255,255,255,0.5);
  text-decoration: none;
  padding: 11px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.sidebar-logout a:hover {
  background: rgba(220,38,38,0.15);
  color: #dc2626;
}

/* ── MAIN ── */
.admin-main {
  margin-left: 250px;
  padding: 32px;
  width: 100%;
}

.admin-topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
  flex-wrap: wrap;
  gap: 12px;
}

.admin-topbar h2 {
  font-size: 24px;
  font-weight: 800;
  color: #111;
}

.admin-topbar p {
  font-size: 13px;
  color: #6b7280;
  margin-top: 2px;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #111;
  color: white;
  border-radius: 50px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 700;
  transition: all 0.3s ease;
}

.btn-back:hover {
  background: #333;
  transform: translateY(-2px);
}

/* MESSAGES */
.alert-box {
  padding: 14px 20px;
  border-radius: 12px;
  margin-bottom: 22px;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}

.alert-success {
  background: #dcfce7;
  color: #166534;
  border-left: 4px solid #16a34a;
}

.alert-error {
  background: #fee2e2;
  color: #991b1b;
  border-left: 4px solid #dc2626;
}

/* LAYOUT */
.form-layout {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 24px;
  align-items: start;
}

/* FORM CARD */
.form-card {
  background: white;
  border-radius: 18px;
  padding: 30px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
}

.form-card h3 {
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

.form-card h3 i { color: #dc2626; }

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group { margin-bottom: 20px; }

.form-label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #6b7280;
  margin-bottom: 8px;
}

.form-input-wrap { position: relative; }

.form-input-wrap i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 14px;
}

.form-input {
  width: 100%;
  padding: 12px 16px 12px 42px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 14px;
  color: #111;
  background: #f9fafb;
  outline: none;
  transition: all 0.3s ease;
  font-family: 'Poppins', sans-serif;
}

.form-input:hover { border-color: #fca5a5; }

.form-input:focus {
  border-color: #dc2626;
  background: white;
  box-shadow: 0 0 0 4px rgba(220,38,38,0.08);
}

.form-textarea {
  width: 100%;
  padding: 13px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 14px;
  color: #111;
  background: #f9fafb;
  outline: none;
  transition: all 0.3s ease;
  resize: none;
  min-height: 110px;
  font-family: 'Poppins', sans-serif;
}

.form-textarea:hover { border-color: #fca5a5; }

.form-textarea:focus {
  border-color: #dc2626;
  background: white;
  box-shadow: 0 0 0 4px rgba(220,38,38,0.08);
}

/* FILE UPLOAD AREA */
.upload-area {
  border: 2px dashed #e5e7eb;
  border-radius: 14px;
  padding: 30px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #f9fafb;
  position: relative;
}

.upload-area:hover,
.upload-area.dragover {
  border-color: #dc2626;
  background: #fef2f2;
}

.upload-area input[type="file"] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.upload-icon {
  font-size: 36px;
  color: #dc2626;
  margin-bottom: 12px;
}

.upload-text {
  font-size: 14px;
  font-weight: 700;
  color: #111;
  margin-bottom: 4px;
}

.upload-subtext {
  font-size: 12px;
  color: #9ca3af;
}

/* IMAGE PREVIEW */
.image-preview {
  display: none;
  margin-top: 16px;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  max-width: 220px;
}

.image-preview img {
  width: 100%;
  display: block;
}

/* SUBMIT BTN */
.submit-btn {
  width: 100%;
  padding: 15px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 8px;
}

.submit-btn:hover {
  background: #b91c1c;
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(220,38,38,0.4);
}

/* SIDE TIPS CARD */
.tips-card {
  background: white;
  border-radius: 18px;
  padding: 26px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
}

.tips-card h4 {
  font-size: 15px;
  font-weight: 800;
  color: #111;
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.tips-card h4 i { color: #dc2626; }

.tip-item {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.tip-item:last-child { margin-bottom: 0; }

.tip-num {
  width: 24px;
  height: 24px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 800;
  flex-shrink: 0;
}

.tip-text {
  font-size: 13px;
  color: #6b7280;
  line-height: 1.6;
}

/* DATALIST CATEGORIES HINT */
.cat-hint {
  font-size: 11px;
  color: #9ca3af;
  margin-top: 6px;
}

@media (max-width: 900px) {
  .admin-sidebar { transform: translateX(-100%); }
  .admin-main { margin-left: 0; }
  .form-layout { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="admin-wrapper">

  <!-- SIDEBAR -->
  <div class="admin-sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-logo-circle">
        <img src="images/logo.png" alt="Logo"
          onerror="this.parentElement.innerHTML='<b style=\'color:#dc2626;font-size:14px;\'>iP</b>'">
      </div>
      <span>ADMIN PANEL</span>
    </div>

    <div class="sidebar-section">Main</div>
    <nav class="sidebar-nav">
      <a href="admin/dashboard.php">
        <i class="fa-solid fa-chart-line"></i> Dashboard
      </a>
      <a href="add_product.php" class="active">
        <i class="fa-solid fa-plus"></i> Add Product
      </a>
      <a href="admin/orders.php">
        <i class="fa-solid fa-box"></i> Orders
      </a>
    </nav>

    <div class="sidebar-section">Sales Reports</div>
    <nav class="sidebar-nav">
      <a href="admin/sales/weekly.php">
        <i class="fa-solid fa-calendar-week"></i> Weekly Sales
      </a>
      <a href="admin/sales/monthly.php">
        <i class="fa-solid fa-calendar"></i> Monthly Sales
      </a>
      <a href="admin/sales/yearly.php">
        <i class="fa-solid fa-calendar-days"></i> Yearly Sales
      </a>
    </nav>

    <div class="sidebar-logout">
      <a href="logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout (<?= htmlspecialchars($_SESSION['username']) ?>)
      </a>
    </div>
  </div>

  <!-- MAIN -->
  <div class="admin-main">

    <div class="admin-topbar">
      <div>
        <h2>Add New Product</h2>
        <p>Add a new tech accessory to your store</p>
      </div>
      <a href="admin/dashboard.php" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Dashboard
      </a>
    </div>

    <!-- MESSAGES -->
    <?php if ($success): ?>
    <div class="alert-box alert-success">
      <i class="fa-solid fa-circle-check"></i>
      <?= $success ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-box alert-error">
      <i class="fa-solid fa-circle-exclamation"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="form-layout">

      <!-- FORM CARD -->
      <div class="form-card">
        <h3>
          <i class="fa-solid fa-box-open"></i>
          Product Details
        </h3>

        <form method="POST" enctype="multipart/form-data">

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Product Name *</label>
              <div class="form-input-wrap">
                <i class="fa-solid fa-tag"></i>
                <input type="text" name="product_name"
                  class="form-input"
                  placeholder="e.g. Wireless Earbuds Pro"
                  value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>"
                  required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Category *</label>
              <div class="form-input-wrap">
                <i class="fa-solid fa-layer-group"></i>
                <input type="text" name="category"
                  class="form-input"
                  list="cat-list"
                  placeholder="e.g. Earphone"
                  value="<?= htmlspecialchars($_POST['category'] ?? '') ?>"
                  required>
                <datalist id="cat-list">
                  <?php foreach ($cat_list as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>">
                  <?php endforeach; ?>
                </datalist>
              </div>
              <div class="cat-hint">
                Existing: <?= implode(', ', $cat_list) ?: 'None yet' ?>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Price (Rs) *</label>
              <div class="form-input-wrap">
                <i class="fa-solid fa-money-bill"></i>
                <input type="number" name="price" step="0.01" min="0"
                  class="form-input"
                  placeholder="e.g. 3500.00"
                  value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                  required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Quantity *</label>
              <div class="form-input-wrap">
                <i class="fa-solid fa-box-archive"></i>
                <input type="number" name="quantity" min="0"
                  class="form-input"
                  placeholder="e.g. 10"
                  value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>"
                  required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea"
              placeholder="Brief description of the product..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Product Image *</label>
            <div class="upload-area" id="uploadArea">
              <input type="file" name="image" id="imageInput"
                accept=".jpg,.jpeg,.png,.webp" required>
              <div class="upload-icon">
                <i class="fa-solid fa-cloud-arrow-up"></i>
              </div>
              <div class="upload-text">Click or drag image to upload</div>
              <div class="upload-subtext">JPG, PNG, WEBP — Max 5MB</div>
            </div>
            <div class="image-preview" id="imagePreview">
              <img id="previewImg" src="" alt="Preview">
            </div>
          </div>

          <button type="submit" name="add_product" class="submit-btn">
            <i class="fa-solid fa-plus"></i>
            Add Product
          </button>

        </form>
      </div>

      
    </div>

  </div>
</div>

<script>
const uploadArea = document.getElementById('uploadArea');
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');
const previewImg = document.getElementById('previewImg');

imageInput.addEventListener('change', function() {
  if (this.files && this.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      previewImg.src = e.target.result;
      imagePreview.style.display = 'block';
    };
    reader.readAsDataURL(this.files[0]);
  }
});

uploadArea.addEventListener('dragover', function(e) {
  e.preventDefault();
  this.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', function() {
  this.classList.remove('dragover');
});

uploadArea.addEventListener('drop', function(e) {
  this.classList.remove('dragover');
});
</script>

</body>
</html>