<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard | Library Pro</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="topbar-left"><h1>📚 Library Pro</h1><span class="chip"><?= strtoupper($role) ?></span></div>
  <div class="topbar-right">
    <span class="muted">Hello, <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
    <a class="btn ghost" href="logout.php">Logout</a>
  </div>
</header>

<main class="container grid-cards">
  <a class="card nav" href="books.php">
    <h3>Manage Books</h3>
    <p class="muted small">View books. <?= $role==='admin' ? 'Add, delete, adjust quantity.' : 'Quantities shown only.' ?></p>
  </a>
  <a class="card nav" href="borrow.php">
    <h3>Borrow / Return</h3>
    <p class="muted small">Both Admin & Staff can issue and accept returns.</p>
  </a>
  <a class="card nav" href="history.php">
    <h3>Borrow History</h3>
    <p class="muted small">Issuer details + who managed (Admin/Staff).</p>
  </a>
  <?php if ($role === 'admin'): ?>
  <a class="card nav" href="admin/staff.php">
    <h3>Manage Staff</h3>
    <p class="muted small">Create / delete staff users (passwords are hashed).</p>
  </a>
  <?php endif; ?>
</main>
</body>
</html>
