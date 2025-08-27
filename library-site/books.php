<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$role = $_SESSION['role'] ?? 'staff';

/* Add book (admin only) */
if ($role === 'admin' && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_book'])) {
  $title = trim($_POST['title'] ?? '');
  $author = trim($_POST['author'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $qty = (int)($_POST['quantity'] ?? 1);
  if ($title && $author && $qty > 0) {
    $stmt = $conn->prepare("INSERT INTO books (title, author, category, quantity) VALUES (?,?,?,?)");
    $stmt->bind_param('sssi', $title, $author, $category, $qty);
    $stmt->execute();
  }
  header("Location: books.php"); exit;
}

/* Delete book (admin only) */
if ($role === 'admin' && isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $stmt = $conn->prepare("DELETE FROM books WHERE id=?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  header("Location: books.php"); exit;
}

/* Adjust quantity (admin only) */
if ($role === 'admin' && isset($_POST['adjust_qty'])) {
  $id = (int)$_POST['book_id'];
  $delta = (int)$_POST['delta']; // +1 or -1
  $stmt = $conn->prepare("UPDATE books SET quantity = GREATEST(quantity + ?, 0) WHERE id=?");
  $stmt->bind_param('ii', $delta, $id);
  $stmt->execute();
  header("Location: books.php"); exit;
}

/* List books (search) */
$q = trim($_GET['q'] ?? '');
$like = "%$q%";
if ($q !== '') {
  $stmt = $conn->prepare("SELECT * FROM books
    WHERE title LIKE ? OR author LIKE ? OR category LIKE ?
    ORDER BY id DESC");
  $stmt->bind_param('sss', $like,$like,$like);
} else {
  $stmt = $conn->prepare("SELECT * FROM books ORDER BY id DESC");
}
$stmt->execute();
$books = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Books</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="topbar-left"><h1>📚 Books</h1></div>
  <div class="topbar-right">
    <a class="btn ghost" href="dashboard.php">Dashboard</a>
    <a class="btn ghost" href="logout.php">Logout</a>
  </div>
</header>

<main class="container">
  <?php if ($role === 'admin'): ?>
  <section class="card">
    <h2>Add Book</h2>
    <form method="post" class="grid-3">
      <input name="title" placeholder="Title" required>
      <input name="author" placeholder="Author" required>
      <input name="category" placeholder="Category">
      <input name="quantity" type="number" min="1" value="1" placeholder="Quantity" required>
      <button class="btn" type="submit" name="add_book">Add</button>
    </form>
  </section>
  <?php endif; ?>

  <section class="card">
    <div class="between">
      <h2>Books</h2>
      <form method="get" class="inline">
        <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search title/author/category">
        <button class="btn outline">Search</button>
      </form>
    </div>
    <table class="table">
      <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>Category</th><th>Quantity</th><?php if($role==='admin'):?><th>Action</th><?php endif;?></tr></thead>
      <tbody>
      <?php while($b=$books->fetch_assoc()): ?>
        <tr>
          <td><?= $b['id'] ?></td>
          <td><?= htmlspecialchars($b['title']) ?></td>
          <td><?= htmlspecialchars($b['author']) ?></td>
          <td><?= htmlspecialchars($b['category'] ?? '') ?></td>
          <td><?= (int)$b['quantity'] ?></td>
          <?php if ($role==='admin'): ?>
          <td class="actions">
            <form method="post" class="inline">
              <input type="hidden" name="book_id" value="<?= $b['id'] ?>">
              <button class="btn small" name="adjust_qty" value="1" onclick="this.form.delta.value=1">+1</button>
              <button class="btn small ghost" name="adjust_qty" value="-1" onclick="this.form.delta.value=-1">-1</button>
              <input type="hidden" name="delta" value="0">
            </form>
            <a class="btn danger small" href="?delete=<?= $b['id'] ?>" onclick="return confirm('Delete this book?')">Delete</a>
          </td>
          <?php endif; ?>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </section>
</main>
<script src="assets/js/script.js"></script>
</body>
</html>
