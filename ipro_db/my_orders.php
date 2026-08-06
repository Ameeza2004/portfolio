<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn,
    "SELECT * FROM orders
     WHERE user_id = ?
     ORDER BY order_date DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
$total_orders = mysqli_num_rows($orders);

$counts = [];
$cq = mysqli_prepare($conn,
    "SELECT status, COUNT(*) as cnt
     FROM orders WHERE user_id = ?
     GROUP BY status");
mysqli_stmt_bind_param($cq, "i", $user_id);
mysqli_stmt_execute($cq);
$cr = mysqli_stmt_get_result($cq);
while ($row = mysqli_fetch_assoc($cr)) {
    $counts[$row['status']] = $row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders – Tech Store</title>

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

body { background: #f5f5f5; color: #111; }

.container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 30px;
}

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
  transition: color 0.2s;
}

.breadcrumb a:hover { color: #dc2626; }
.breadcrumb span    { color: rgba(255,255,255,0.2); }
.breadcrumb strong  { color: rgba(255,255,255,0.7); }

.page-header h1 {
  font-size: clamp(28px,5vw,48px);
  font-weight: 900;
  color: white;
  letter-spacing: -1px;
  margin-bottom: 6px;
}

.page-header h1 span { color: #dc2626; }

.page-header p {
  color: rgba(255,255,255,0.4);
  font-size: 14px;
  font-weight: 400;
}

/* STATS ROW */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  padding: 36px 0 0;
}

.stat-box {
  background: white;
  border-radius: 16px;
  padding: 20px 22px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
  transition: all 0.3s ease;
  animation: fadeUp 0.5s ease both;
}

.stat-box:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.stat-icon.all        { background:#f3f4f6; color:#6b7280; }
.stat-icon.pending    { background:#fef3c7; color:#d97706; }
.stat-icon.processing { background:#dbeafe; color:#2563eb; }
.stat-icon.delivered  { background:#dcfce7; color:#16a34a; }

.stat-num {
  font-size: 26px;
  font-weight: 900;
  color: #111;
  line-height: 1;
  margin-bottom: 2px;
}

.stat-label {
  font-size: 11px;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* ORDERS SECTION */
.orders-section {
  padding: 36px 0 80px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 12px;
}

.section-header h2 {
  font-size: 20px;
  font-weight: 800;
  color: #111;
}

.section-header a {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  background: #dc2626;
  color: white;
  border-radius: 50px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 700;
  transition: all 0.3s ease;
}

.section-header a:hover {
  background: #b91c1c;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(220,38,38,0.35);
}

/* ORDER CARD */
.order-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  margin-bottom: 20px;
  box-shadow: 0 2px 14px rgba(0,0,0,0.06);
  border: 1.5px solid #f0f0f0;
  transition: all 0.35s ease;
  animation: fadeUp 0.5s ease both;
}

.order-card:hover {
  box-shadow: 0 12px 40px rgba(0,0,0,0.1);
  border-color: rgba(220,38,38,0.1);
  transform: translateY(-3px);
}

@keyframes fadeUp {
  from { opacity:0; transform:translateY(20px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ORDER CARD HEADER */
.order-card-head {
  background: #111;
  padding: 16px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
}

.order-id {
  font-size: 15px;
  font-weight: 800;
  color: white;
  display: flex;
  align-items: center;
  gap: 10px;
}

.order-id i { color: #dc2626; }

.order-date {
  font-size: 12px;
  color: rgba(255,255,255,0.4);
  font-weight: 400;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* STATUS BADGES */
.status-badge {
  padding: 6px 16px;
  border-radius: 50px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 6px;
}

.badge-pending    { background:#fef3c7; color:#d97706; }
.badge-processing { background:#dbeafe; color:#2563eb; }
.badge-delivered  { background:#dcfce7; color:#16a34a; }
.badge-cancelled  { background:#fee2e2; color:#dc2626; }

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  display: inline-block;
}

.dot-pending    { background:#d97706; animation:blink 1.5s infinite; }
.dot-processing { background:#2563eb; animation:blink 1.5s infinite; }
.dot-delivered  { background:#16a34a; }
.dot-cancelled  { background:#dc2626; }

@keyframes blink {
  0%,100% { opacity:1; }
  50%     { opacity:0.3; }
}

/* ORDER CARD BODY */
.order-card-body {
  padding: 22px 24px;
}

/* ITEMS LIST */
.order-items-list {
  list-style: none;
  margin-bottom: 18px;
  padding: 0;
}

.order-item-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 0;
  border-bottom: 1px solid #f3f4f6;
}

.order-item-row:last-child {
  border-bottom: none;
}

.order-item-img {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  object-fit: cover;
  background: #f5f5f5;
  flex-shrink: 0;
}

.order-item-name {
  flex: 1;
  font-size: 14px;
  font-weight: 600;
  color: #111;
}

.order-item-qty {
  font-size: 12px;
  color: #9ca3af;
  font-weight: 500;
}

.order-item-sub {
  font-size: 14px;
  font-weight: 800;
  color: #111;
  white-space: nowrap;
}

/* ORDER CARD FOOTER */
.order-card-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 16px;
  border-top: 2px solid #f3f4f6;
  flex-wrap: wrap;
  gap: 12px;
}

.order-delivery {
  font-size: 12px;
  color: #9ca3af;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 7px;
  max-width: 300px;
}

.order-delivery i { color: #dc2626; font-size: 13px; }

.order-total {
  font-size: 22px;
  font-weight: 900;
  color: #dc2626;
}

/* ACTION BUTTONS */
.order-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-invoice {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 20px;
  background: #dc2626;
  color: white;
  border-radius: 50px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 700;
  transition: all 0.3s ease;
}

.btn-invoice:hover {
  background: #b91c1c;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(220,38,38,0.35);
  color: white;
}

.btn-reorder {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 20px;
  background: #f3f4f6;
  color: #111;
  border-radius: 50px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 700;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
}

.btn-reorder:hover {
  background: #111;
  color: white;
  transform: translateY(-2px);
}

/* TIMELINE / PROGRESS */
.order-timeline {
  display: flex;
  align-items: center;
  gap: 0;
  margin-bottom: 20px;
  overflow-x: auto;
  padding-bottom: 4px;
}

.timeline-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex: 1;
  min-width: 70px;
}

.timeline-dot {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  border: 2px solid #e5e7eb;
  background: white;
  color: #9ca3af;
  transition: all 0.3s ease;
  z-index: 1;
}

.timeline-dot.done {
  background: #dc2626;
  border-color: #dc2626;
  color: white;
}

.timeline-dot.current {
  background: white;
  border-color: #dc2626;
  color: #dc2626;
  box-shadow: 0 0 0 4px rgba(220,38,38,0.15);
  animation: pulseDot 2s infinite;
}

@keyframes pulseDot {
  0%,100% { box-shadow: 0 0 0 4px rgba(220,38,38,0.15); }
  50%     { box-shadow: 0 0 0 8px rgba(220,38,38,0.08); }
}

.timeline-label {
  font-size: 10px;
  font-weight: 600;
  color: #9ca3af;
  text-align: center;
  letter-spacing: 0.3px;
}

.timeline-label.done    { color: #dc2626; }
.timeline-label.current { color: #dc2626; font-weight: 700; }

.timeline-line {
  flex: 1;
  height: 2px;
  background: #e5e7eb;
  margin-bottom: 18px;
}

.timeline-line.done { background: #dc2626; }

/* EMPTY STATE */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: white;
  border-radius: 24px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.06);
}

.empty-icon {
  font-size: 80px;
  color: #e5e7eb;
  margin-bottom: 24px;
  display: block;
}

.empty-state h2 {
  font-size: 24px;
  font-weight: 800;
  color: #111;
  margin-bottom: 10px;
}

.empty-state p {
  color: #9ca3af;
  font-size: 15px;
  margin-bottom: 28px;
  font-weight: 400;
}

.btn-red {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 32px;
  background: #dc2626;
  color: white;
  border-radius: 50px;
  text-decoration: none;
  font-size: 15px;
  font-weight: 700;
  transition: all 0.3s ease;
}

.btn-red:hover {
  background: #b91c1c;
  transform: translateY(-3px);
  box-shadow: 0 10px 24px rgba(220,38,38,0.4);
  color: white;
}

@media (max-width: 900px) {
  .stats-row { grid-template-columns: repeat(2,1fr); }
}

@media (max-width: 560px) {
  .stats-row        { grid-template-columns: 1fr 1fr; }
  .order-card-head  { flex-direction: column; align-items: flex-start; }
  .order-card-foot  { flex-direction: column; align-items: flex-start; }
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
      <strong>My Orders</strong>
    </div>
    <h1>My <span>Orders</span></h1>
    <p>Track and manage all your orders</p>
  </div>
</div>

<!-- STATS ROW -->
<div class="container">
  <div class="stats-row">

    <div class="stat-box">
      <div class="stat-icon all">
        <i class="fa-solid fa-box-archive"></i>
      </div>
      <div>
        <div class="stat-num"><?= $total_orders ?></div>
        <div class="stat-label">Total Orders</div>
      </div>
    </div>

    <div class="stat-box">
      <div class="stat-icon pending">
        <i class="fa-solid fa-clock"></i>
      </div>
      <div>
        <div class="stat-num">
          <?= $counts['Pending'] ?? 0 ?>
        </div>
        <div class="stat-label">Pending</div>
      </div>
    </div>

    <div class="stat-box">
      <div class="stat-icon processing">
        <i class="fa-solid fa-gear"></i>
      </div>
      <div>
        <div class="stat-num">
          <?= $counts['Processing'] ?? 0 ?>
        </div>
        <div class="stat-label">Processing</div>
      </div>
    </div>

    <div class="stat-box">
      <div class="stat-icon delivered">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <div>
        <div class="stat-num">
          <?= $counts['Delivered'] ?? 0 ?>
        </div>
        <div class="stat-label">Delivered</div>
      </div>
    </div>

  </div>
</div>

<!-- ORDERS LIST -->
<div class="orders-section">
  <div class="container">

    <div class="section-header">
      <h2>Order History</h2>
      <a href="product.php">
        <i class="fa-solid fa-plus"></i>
        Shop More
      </a>
    </div>

    <?php if ($total_orders === 0): ?>

    <!-- EMPTY STATE -->
    <div class="empty-state">
      <span class="empty-icon">
        <i class="fa-solid fa-box-open"></i>
      </span>
      <h2>No orders yet</h2>
      <p>
        You haven't placed any orders yet.<br>
        Start shopping and your orders will appear here.
      </p>
      <a href="product.php" class="btn-red">
        <i class="fa-solid fa-bag-shopping"></i>
        Start Shopping
      </a>
    </div>

    <?php else: ?>

    <?php
    mysqli_data_seek($orders, 0);
    $delay = 0;
    while ($order = mysqli_fetch_assoc($orders)):
      $delay += 100;
      $status = $order['status'];

      $istmt = mysqli_prepare($conn,
        "SELECT oi.*, p.image
         FROM order_items oi
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = ?");
      mysqli_stmt_bind_param($istmt, "i", $order['id']);
      mysqli_stmt_execute($istmt);
      $items = mysqli_stmt_get_result($istmt);

      $steps = ['Pending','Processing','Delivered'];
      $current_step = array_search($status, $steps);
      $cancelled = $status === 'Cancelled';
    ?>

    <div class="order-card"
      style="animation-delay:<?= $delay ?>ms">

      <!-- CARD HEADER -->
      <div class="order-card-head">
        <div class="order-id">
          <i class="fa-solid fa-receipt"></i>
          Order #<?= str_pad($order['id'],5,'0',STR_PAD_LEFT) ?>
        </div>

        <div class="order-date">
          <i class="fa-regular fa-calendar"></i>
          <?= date('d M Y, h:i A',
            strtotime($order['order_date'])) ?>
        </div>

        <?php
        $badge_class = strtolower($status);
        $dot_class   = strtolower($status);
        $icons = [
          'Pending'    => 'fa-clock',
          'Processing' => 'fa-gear fa-spin',
          'Delivered'  => 'fa-circle-check',
          'Cancelled'  => 'fa-circle-xmark',
        ];
        $icon = $icons[$status] ?? 'fa-circle';
        ?>
        <span class="status-badge badge-<?= $badge_class ?>">
          <span class="status-dot dot-<?= $dot_class ?>"></span>
          <i class="fa-solid <?= $icon ?>"></i>
          <?= $status ?>
        </span>
      </div>

      <!-- CARD BODY -->
      <div class="order-card-body">

        <!-- ORDER PROGRESS TIMELINE -->
        <?php if (!$cancelled): ?>
        <div class="order-timeline">
          <?php foreach ($steps as $i => $step):
            $is_done    = $current_step !== false && $i < $current_step;
            $is_current = $current_step !== false && $i === $current_step;
          ?>
          <div class="timeline-step">
            <div class="timeline-dot
              <?= $is_done ? 'done' : ($is_current ? 'current' : '') ?>">
              <?php if ($is_done): ?>
                <i class="fa-solid fa-check" style="font-size:11px;"></i>
              <?php elseif ($is_current): ?>
                <i class="fa-solid fa-circle-dot" style="font-size:11px;"></i>
              <?php else: ?>
                <?= $i + 1 ?>
              <?php endif; ?>
            </div>
            <div class="timeline-label
              <?= $is_done ? 'done' : ($is_current ? 'current' : '') ?>">
              <?= $step ?>
            </div>
          </div>
          <?php if ($i < count($steps)-1): ?>
          <div class="timeline-line <?= $is_done?'done':'' ?>"></div>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="background:#fee2e2;border-radius:10px;
          padding:12px 16px;margin-bottom:16px;
          font-size:13px;color:#991b1b;font-weight:600;
          display:flex;align-items:center;gap:8px;">
          <i class="fa-solid fa-circle-xmark"></i>
          This order has been cancelled.
        </div>
        <?php endif; ?>

        <!-- ITEMS -->
        <ul class="order-items-list">
          <?php while ($item = mysqli_fetch_assoc($items)): ?>
          <li class="order-item-row">
            <img
              src="uploads/<?= htmlspecialchars($item['image'] ?? '') ?>"
              alt="<?= htmlspecialchars($item['product_name']) ?>"
              class="order-item-img"
              onerror="this.src='images/logo.png'">
            <div class="order-item-name">
              <?= htmlspecialchars($item['product_name']) ?>
            </div>
            <div class="order-item-qty">
              × <?= $item['quantity'] ?>
            </div>
            <div class="order-item-sub">
              Rs <?= number_format($item['subtotal'], 2) ?>
            </div>
          </li>
          <?php endwhile; ?>
        </ul>

        <!-- FOOTER -->
        <div class="order-card-foot">

          <div class="order-delivery">
            <i class="fa-solid fa-location-dot"></i>
            <?= htmlspecialchars($order['address']) ?>
          </div>

          <div style="display:flex;align-items:center;
            gap:20px;flex-wrap:wrap;">
            <div class="order-total">
              Rs <?= number_format($order['total'], 2) ?>
            </div>
            <div class="order-actions">
              <a href="invoice.php?id=<?= $order['id'] ?>"
                 class="btn-invoice">
                <i class="fa-solid fa-receipt"></i>
                Invoice
              </a>
              <a href="product.php" class="btn-reorder">
                <i class="fa-solid fa-rotate-right"></i>
                Shop Again
              </a>
            </div>
          </div>

        </div>

      </div>
    </div>

    <?php endwhile; ?>
    <?php endif; ?>

  </div>
</div>

</body>
</html>