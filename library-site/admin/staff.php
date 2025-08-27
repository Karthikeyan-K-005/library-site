<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

if (($_SESSION['role'] ?? '') !== 'admin') { header("Location: ../dashboard.php"); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add'])) {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($username && $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?,?)");
    $stmt->bind_param('ss', $username, $hash);
    if (!$stmt->execute()) {
      if ($conn->errno === 1062) $msg = 'Username already exists.';
      else $msg = 'Failed to add staff.';
    }
  } else {
    $msg = 'Username and password are required.';
  }
}

if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  // prevent accidental delete of yourself
  if (($id ?? 0) !== (int)($_SESSION['id'] ?? 0)) {
    $del = $conn->prepare("DELETE FROM users WHERE id=?");
    $del->bind_param('i',$id);
    $del->execute();
  }
  header("Location: staff.php"); exit;
}

$staff = $conn->query("SELECT id, username, created_at FROM users ORDER BY username");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Staff</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="topbar-left"><h1>👥 Staff</h1></div>
  <div class="topbar-right">
    <a class="btn ghost" href="../dashboard.php">Dashboard</a>
    <a class="btn ghost" href="../logout.php">Logout</a>
  </div>
</header>

<main class="container">
  <section class="card">
    <h2>Add Staff</h2>
    <?php if ($msg): ?><div class="alert"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="grid-3">
      <input name="username" placeholder="Username" required>
      <input name="password" placeholder="Temp password" required>
      <button class="btn" name="add" type="submit">Create</button>
    </form>
  </section>

  <section class="card">
    <h2>Staff List</h2>
    <table class="table compact">
      <thead><tr><th>ID</th><th>Username</th><th>Created</th><th>Action</th></tr></thead>
      <tbody>
      <?php while($r=$staff->fetch_assoc()): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['username']) ?></td>
          <td><?= htmlspecialchars($r['created_at']) ?></td>
          <td class="actions">
            <a class="btn danger small" href="?delete=<?= $r['id'] ?>" onclick="return confirm('Delete this staff?')">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </section>
</main>
</body>
</html>
