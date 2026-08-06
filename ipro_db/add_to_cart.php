<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Check product ID exists
if (!isset($_GET['id'])) {
    header("Location: product.php");
    exit();
}

$id  = (int)$_GET['id'];
$qty = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

// Make sure qty is at least 1
if ($qty < 1) $qty = 1;

// Get product from database
$stmt = mysqli_prepare($conn,
    "SELECT * FROM products
     WHERE id = ? AND quantity > 0");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

// Product not found or out of stock
if (!$product) {
    header("Location: product.php");
    exit();
}

// Make sure qty does not exceed stock
if ($qty > $product['quantity']) {
    $qty = $product['quantity'];
}

// Initialize cart if empty
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// If product already in cart increase qty
if (isset($_SESSION['cart'][$id])) {
    $new_qty = $_SESSION['cart'][$id]['qty'] + $qty;
    // Do not exceed stock
    if ($new_qty > $product['quantity']) {
        $new_qty = $product['quantity'];
    }
    $_SESSION['cart'][$id]['qty'] = $new_qty;
} else {
    // Add new item to cart
    $_SESSION['cart'][$id] = [
        'name'  => $product['product_name'],
        'price' => $product['price'],
        'image' => $product['image'],
        'qty'   => $qty,
    ];
}

// Go back to where user came from
// or go to cart if no referrer
$back = isset($_SERVER['HTTP_REFERER'])
    ? $_SERVER['HTTP_REFERER']
    : 'cart.php';

header("Location: " . $back);
exit();
?>