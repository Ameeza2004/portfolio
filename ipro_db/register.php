<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error   = '';
$success = '';

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);
    $phone    = trim($_POST['phone']);

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters.';
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = 'Username already taken. Choose another.';
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
            mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $password, $phone);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Account created successfully! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register – Tech Store</title>

<style>

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Segoe UI', Arial, sans-serif;
}

body {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #7f1d1d 100%);
  padding: 20px;
}

/* GLOW CIRCLES */
.glow-top {
  position: fixed;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(220,38,38,0.2) 0%, transparent 70%);
  top: -100px;
  right: -100px;
  border-radius: 50%;
  pointer-events: none;
  animation: glowPulse 4s ease-in-out infinite;
}

.glow-bottom {
  position: fixed;
  width: 350px;
  height: 350px;
  background: radial-gradient(circle, rgba(220,38,38,0.15) 0%, transparent 70%);
  bottom: -80px;
  left: -80px;
  border-radius: 50%;
  pointer-events: none;
  animation: glowPulse 4s ease-in-out infinite reverse;
}

@keyframes glowPulse {
  0%, 100% { transform: scale(1);   opacity: 0.6; }
  50%       { transform: scale(1.3); opacity: 1;   }
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0);    }
}

/* WRAPPER */
.wrapper {
  width: 100%;
  max-width: 440px;
  animation: fadeUp 0.6s ease;
  position: relative;
  z-index: 10;
}

/* LOGO */
.logo-section {
  text-align: center;
  margin-bottom: 28px;
}

.logo-box {
  width: 100px;
  height: 100px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
  box-shadow: 0 0 0 4px rgba(220,38,38,0.3), 0 10px 30px rgba(0,0,0,0.4);
  transition: all 0.4s ease;
}

.logo-box:hover {
  box-shadow: 0 0 0 6px rgba(220,38,38,0.6), 0 15px 40px rgba(220,38,38,0.3);
  transform: scale(1.08);
}

.logo-box img {
  width: 65px;
  height: 65px;
  object-fit: contain;
}

.logo-section h1 {
  color: white;
  font-size: 26px;
  font-weight: 900;
  letter-spacing: 5px;
  text-shadow: 0 2px 20px rgba(220,38,38,0.5);
  margin-bottom: 6px;
}

.logo-section p {
  color: rgba(255,255,255,0.45);
  font-size: 13px;
  letter-spacing: 2px;
  text-transform: uppercase;
}

/* CARD */
.card {
  background: white;
  border-radius: 24px;
  padding: 36px;
  box-shadow: 0 30px 80px rgba(0,0,0,0.6);
}

/* TABS */
.tabs {
  display: flex;
  background: #f1f5f9;
  border-radius: 12px;
  padding: 5px;
  margin-bottom: 26px;
  gap: 4px;
}

.tabs a {
  flex: 1;
  text-align: center;
  padding: 11px;
  border-radius: 9px;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  color: #9ca3af;
  transition: all 0.3s ease;
}

.tabs a.active {
  background: #dc2626;
  color: white;
  box-shadow: 0 4px 14px rgba(220,38,38,0.45);
}

.tabs a:not(.active):hover {
  color: #dc2626;
  background: rgba(220,38,38,0.06);
}

/* SUCCESS */
.success-msg {
  background: #dcfce7;
  color: #166534;
  border-left: 4px solid #16a34a;
  padding: 12px 16px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 13px;
  font-weight: 600;
}

.success-msg a {
  color: #dc2626;
  font-weight: 700;
  text-decoration: none;
  margin-left: 6px;
}

/* ERROR */
.error-msg {
  background: #fee2e2;
  color: #991b1b;
  border-left: 4px solid #dc2626;
  padding: 12px 16px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 13px;
  font-weight: 600;
}

/* HEADING */
.card-title {
  font-size: 22px;
  font-weight: 800;
  color: #111;
  margin-bottom: 4px;
}

.card-sub {
  font-size: 13px;
  color: #9ca3af;
  margin-bottom: 22px;
}

/* FIELDS */
.field {
  margin-bottom: 16px;
}

.field label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #6b7280;
  margin-bottom: 7px;
}

.input-box {
  position: relative;
}

.input-box span {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 17px;
  pointer-events: none;
}

.input-box input {
  width: 100%;
  padding: 13px 16px 13px 46px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 14px;
  color: #111;
  background: #f8fafc;
  outline: none;
  transition: all 0.3s ease;
}

.input-box input:hover {
  border-color: #fca5a5;
}

.input-box input:focus {
  border-color: #dc2626;
  background: white;
  box-shadow: 0 0 0 4px rgba(220,38,38,0.1);
}

/* BUTTON */
.btn-register {
  display: block;
  width: 100%;
  padding: 15px;
  margin-top: 8px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 1px;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.btn-register:hover {
  background: #b91c1c;
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(220,38,38,0.5);
  color: white;
}

.btn-register:active {
  transform: translateY(0);
  box-shadow: none;
}

.btn-register::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 50%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
}

.btn-register:hover::after {
  left: 160%;
  transition: left 0.5s ease;
}

/* LOGIN LINK */
.login-link {
  text-align: center;
  margin-top: 20px;
  font-size: 13px;
  color: #6b7280;
}

.login-link a {
  color: #dc2626;
  font-weight: 700;
  text-decoration: none;
}

.login-link a:hover {
  text-decoration: underline;
  color: #991b1b;
}

</style>
</head>
<body>

<!-- GLOW -->
<div class="glow-top"></div>
<div class="glow-bottom"></div>

<div class="wrapper">

  <!-- LOGO -->
  <div class="logo-section">
    <div class="logo-box">
      <img src="images/logo.png" alt="iPro"
        onerror="this.style.display='none';this.parentElement.innerHTML='<span style=\'font-size:32px;font-weight:900;color:#dc2626;\'>iP</span>'">
    </div>
    <h1>TECH STORE</h1>
    <p>Create Your Account</p>
  </div>

  <!-- CARD -->
  <div class="card">

    <!-- TABS -->
    <div class="tabs">
      <a href="login.php">🔑 Login</a>
      <a href="register.php" class="active">📝 Register</a>
    </div>

    <!-- MESSAGES -->
    <?php if ($success): ?>
    <div class="success-msg">
      ✅ <?= htmlspecialchars($success) ?>
      <a href="login.php">Login now →</a>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="error-msg">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card-title">Create Account 🚀</div>
    <div class="card-sub">Fill in your details to get started</div>

    <!-- FORM -->
    <form method="POST" autocomplete="off">

      <!-- USERNAME -->
      <div class="field">
        <label>Username *</label>
        <div class="input-box">
          <span>👤</span>
          <input
            type="text"
            name="username"
            placeholder="Choose a username"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            autocomplete="off"
            required
          >
        </div>
      </div>

      <!-- EMAIL -->
      <div class="field">
        <label>Email Address *</label>
        <div class="input-box">
          <span>✉️</span>
          <input
            type="email"
            name="email"
            placeholder="Enter your email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            autocomplete="off"
            required
          >
        </div>
      </div>

      <!-- PHONE -->
      <div class="field">
        <label>Phone Number</label>
        <div class="input-box">
          <span>📱</span>
          <input
            type="text"
            name="phone"
            placeholder="e.g. 077 123 4567"
            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
            autocomplete="off"
          >
        </div>
      </div>

      <!-- PASSWORD -->
      <div class="field">
        <label>Password *</label>
        <div class="input-box">
          <span>🔒</span>
          <input
            type="password"
            name="password"
            placeholder="Create a password"
            autocomplete="new-password"
            required
          >
        </div>
      </div>

      <!-- CONFIRM PASSWORD -->
      <div class="field">
        <label>Confirm Password *</label>
        <div class="input-box">
          <span>🔐</span>
          <input
            type="password"
            name="confirm"
            placeholder="Repeat your password"
            autocomplete="new-password"
            required
          >
        </div>
      </div>

      <!-- BUTTON -->
      <button type="submit" name="register" class="btn-register">
        🚀 Create Account
      </button>

    </form>

    <!-- LOGIN LINK -->
    <div class="login-link">
      Already have an account?
      <a href="login.php">Login here</a>
    </div>

  </div>
</div>

</body>
</html>