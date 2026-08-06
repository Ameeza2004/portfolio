<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'new.php'));
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? AND password = ?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $password);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: new.php");
            }
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – Tech Store</title>

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
  0%, 100% { transform: scale(1); opacity: 0.6; }
  50%       { transform: scale(1.3); opacity: 1; }
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* WRAPPER */
.wrapper {
  width: 100%;
  max-width: 420px;
  animation: fadeUp 0.6s ease;
  position: relative;
  z-index: 10;
}

/* LOGO SECTION */
.logo-section {
  text-align: center;
  margin-bottom: 30px;
}

.logo-box {
  width: 100px;
  height: 100px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
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
  box-shadow: 0 30px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.05);
}

/* TABS */
.tabs {
  display: flex;
  background: #f1f5f9;
  border-radius: 12px;
  padding: 5px;
  margin-bottom: 28px;
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
  margin-bottom: 24px;
}

/* INPUT */
.field {
  margin-bottom: 18px;
}

.field label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #6b7280;
  margin-bottom: 8px;
}

.input-box {
  position: relative;
}

.input-box span {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 18px;
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
.btn-login {
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
  text-align: center;
  text-decoration: none;
  position: relative;
  overflow: hidden;
}

.btn-login:hover {
  background: #b91c1c;
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(220,38,38,0.5);
  color: white;
}

.btn-login:active {
  transform: translateY(0px);
  box-shadow: none;
}

/* SHINE EFFECT ON BUTTON */
.btn-login::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 50%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: none;
}

.btn-login:hover::after {
  left: 160%;
  transition: left 0.5s ease;
}

/* REGISTER LINK */
.reg-link {
  text-align: center;
  margin-top: 20px;
  font-size: 13px;
  color: #6b7280;
}

.reg-link a {
  color: #dc2626;
  font-weight: 700;
  text-decoration: none;
}

.reg-link a:hover {
  text-decoration: underline;
  color: #991b1b;
}

/* DIVIDER */
.divider {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 18px 0;
  color: #d1d5db;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e5e7eb;
}

/* HINT */
.hint {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  padding: 12px;
  text-align: center;
  font-size: 12px;
  color: #6b7280;
}

.hint strong {
  color: #dc2626;
}

</style>
</head>
<body>

<!-- GLOW EFFECTS -->
<div class="glow-top"></div>
<div class="glow-bottom"></div>

<div class="wrapper">

  <!-- LOGO -->
  <div class="logo-section">
    <div class="logo-box">
      <img src="images/logo.png" alt="iPro"
        onerror="this.parentElement.style.background='white';this.style.display='none';this.parentElement.innerHTML='<span style=\'font-size:32px;font-weight:900;color:#dc2626;\'>iP</span>'">
    </div>
    <h1>TECH STORE</h1>
    <p>Premium Tech Accessories</p>
  </div>

  <!-- CARD -->
  <div class="card">

    <!-- TABS -->
    <div class="tabs">
      <a href="login.php" class="active">🔑 Login</a>
      <a href="register.php">📝 Register</a>
    </div>

    <!-- ERROR -->
    <?php if ($error): ?>
    <div class="error-msg">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card-title">Welcome Back 👋</div>
    <div class="card-sub">Sign in to continue to your account</div>

    <!-- FORM -->
    <form method="POST" autocomplete="off">

      <!-- USERNAME -->
      <div class="field">
        <label>Username</label>
        <div class="input-box">
          <span>👤</span>
          <input
            type="text"
            name="username"
            placeholder="Enter your username"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            autocomplete="off"
            required
          >
        </div>
      </div>

      <!-- PASSWORD -->
      <div class="field">
        <label>Password</label>
        <div class="input-box">
          <span>🔒</span>
          <input
            type="password"
            name="password"
            placeholder="Enter your password"
            autocomplete="new-password"
            required
          >
        </div>
      </div>

      <!-- BUTTON -->
      <button type="submit" name="login" class="btn-login">
        🔓 Login Now
      </button>

    </form>

    <!-- REGISTER -->
    <div class="reg-link">
      Don't have an account?
      <a href="register.php">Create one here</a>
    </div>

    <!-- DIVIDER -->
    <div class="divider">demo credentials</div>

    <!-- HINT -->
    <div class="hint">
      👑 Admin: <strong>admin</strong> / <strong>....</strong>
      &nbsp;&nbsp;|&nbsp;&nbsp;
      🛒 Customer: <strong>user</strong> / <strong>....</strong>
    </div>

  </div>
</div>

</body>
</html>