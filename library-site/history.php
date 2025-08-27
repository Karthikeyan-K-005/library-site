<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$sql = "
  SELECT bh.id, b.title, b.author,
         bh.borrow_date, bh.due_date, bh.return_date,
         bh.issuer_name, bh.issuer_phone, bh.issuer_aadhaar,
         bh.managed_by_username, bh.managed_by_role,
         bh.quantity,
         CASE
           WHEN bh.return_date IS NOT NULL THEN 'returned'
           WHEN CURDATE() > bh.due_date THEN 'overdue'
           ELSE 'borrowed'
         END AS display_status
  FROM borrow_history bh
  JOIN books b ON bh.book_id = b.id
  ORDER BY bh.borrow_date DESC
";
$rows = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Borrow History</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="topbar-left"><h1>📜 Borrow History</h1></div>
  <div class="topbar-right">
    <a class="btn ghost" href="dashboard.php">Dashboard</a>
    <a class="btn ghost" href="logout.php">Logout</a>
  </div>
</header>

<main class="container">
  <section class="card">
    <div class="between">
      <h2>All Records</h2>
      <input id="historySearch" class="w-300" type="search" placeholder="Filter by book or issuer">
    </div>
    <table class="table" id="historyTable">
      <thead>
        <tr>
          <th>Book</th><th>Issuer</th><th>Phone</th><th>Aadhaar</th>
          <th>Processed By</th><th>Role</th>
          <th>Qty</th>
          <th>Borrowed</th><th>Due</th><th>Returned</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php while($r=$rows->fetch_assoc()): ?>
        <?php $cls = ['borrowed'=>'badge borrowed','overdue'=>'badge overdue','returned'=>'badge returned'][$r['display_status']]; ?>
        <tr>
          <td><?= htmlspecialchars($r['title']) ?> <span class="muted small">by <?= htmlspecialchars($r['author']) ?></span></td>
          <td><?= htmlspecialchars($r['issuer_name']) ?></td>
          <td><?= htmlspecialchars($r['issuer_phone']) ?></td>
          <td><?= htmlspecialchars($r['issuer_aadhaar']) ?></td>
          <td><?= htmlspecialchars($r['managed_by_username']) ?></td>
          <td><?= htmlspecialchars(ucfirst($r['managed_by_role'])) ?></td>
          <td><?= (int)$r['quantity'] ?></td>
          <td><?= htmlspecialchars($r['borrow_date']) ?></td>
          <td><?= htmlspecialchars($r['due_date']) ?></td>
          <td><?= $r['return_date'] ? htmlspecialchars($r['return_date']) : '—' ?></td>
          <td><span class="<?= $cls ?>"><?= $r['display_status'] ?></span></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </section>
</main>
<script src="assets/js/script.js"></script>
</body>
</html>
