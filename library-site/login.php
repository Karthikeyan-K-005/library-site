<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username'] ?? '');
  $p = trim($_POST['password'] ?? '');

  // Hardcoded admin (as in your original)
  if ($u === 'admin' && $p === 'admin@123') {
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
    header('Location: dashboard.php');
    exit;
  }

  // Staff from DB (hashed)
  $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username=?");
  $stmt->bind_param('s', $u);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  if ($row && password_verify($p, $row['password'])) {
    $_SESSION['role'] = 'staff';
    $_SESSION['username'] = $row['username'];
    $_SESSION['id'] = $row['id'];
    header('Location: dashboard.php');
    exit;
  } else {
    $error = 'Invalid credentials';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | Library Pro</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="centered">
  <form class="card form" method="post" autocomplete="off">
    <h2>Sign in</h2>
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <label>Username<input name="username" required></label>
    <label>Password<input type="password" name="password" required></label>
    <button class="btn full" type="submit">Login</button>
    <!-- <p class="muted small">Admin: admin / admin@123</p> -->
    <p class="muted"><a href="index.php">← Back</a></p>
  </form>
</body>
</html>
