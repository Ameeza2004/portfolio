<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

if (empty($_SESSION['cart']) ||
    !isset($_POST['payment_method_id'])) {
    header("Location: cart.php");
    exit();
}

// ── STRIPE SECRET KEY ──
$stripe_secret = 'sk_test_YOUR_SECRET_KEY_HERE';

// Calculate total in cents
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
$delivery    = $subtotal >= 5000 ? 0 : 300;
$grand_total = $subtotal + $delivery;
$amount_cents = (int)($grand_total * 100);

// Create PaymentIntent via Stripe API
$ch = curl_init('https://api.stripe.com/v1/payment_intents');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_USERPWD        => $stripe_secret . ':',
    CURLOPT_POSTFIELDS     => http_build_query([
        'amount'               => $amount_cents,
        'currency'             => 'lkr',
        'payment_method'       => $_POST['payment_method_id'],
        'confirmation_method'  => 'manual',
        'confirm'              => 'true',
    ])
]);

$response = curl_exec($ch);
$intent   = json_decode($response, true);
curl_close($ch);

if (isset($intent['status']) &&
    $intent['status'] === 'succeeded') {

    // Save order to database
    $user_id = $_SESSION['user_id'] ?? null;
    $name    = $_SESSION['checkout_name']    ?? 'Customer';
    $phone   = $_SESSION['checkout_phone']   ?? '';
    $address = $_SESSION['checkout_address'] ?? '';

    if ($user_id) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO orders
             (user_id, name, phone, address, total)
             VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isssd",
            $user_id, $name, $phone,
            $address, $grand_total);
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO orders
             (name, phone, address, total)
             VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssd",
            $name, $phone, $address, $grand_total);
    }

    mysqli_stmt_execute($stmt);
    $order_id = mysqli_insert_id($conn);

    // Insert order items
    foreach ($_SESSION['cart'] as $prod_id => $item) {
        $pname    = $item['name'];
        $price    = (float)$item['price'];
        $qty      = (int)$item['qty'];
        $sub      = $price * $qty;

        $is = mysqli_prepare($conn,
            "INSERT INTO order_items
             (order_id, product_id, product_name,
              price, quantity, subtotal)
             VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($is, "iisdid",
            $order_id, $prod_id, $pname,
            $price, $qty, $sub);
        mysqli_stmt_execute($is);

        // Decrease stock
        $us = mysqli_prepare($conn,
            "UPDATE products
             SET quantity = GREATEST(0, quantity - ?)
             WHERE id = ?");
        mysqli_stmt_bind_param($us, "ii", $qty, $prod_id);
        mysqli_stmt_execute($us);
    }

    // Clear cart
    unset($_SESSION['cart']);

    // Redirect to success
    header("Location: invoice.php?id=" . $order_id);
    exit();

} else {
    // Payment failed
    $error = $intent['error']['message']
        ?? 'Payment failed. Please try again.';
    header("Location: payment.php?error=" .
        urlencode($error));
    exit();
}
?>