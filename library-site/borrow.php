<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$role = $_SESSION['role'] ?? 'staff';
$user = $_SESSION['username'] ?? '';

/* BORROW */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['borrow'])) {
    $bookId = (int)($_POST['book_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 1);
    $due = trim($_POST['due_date'] ?? '');
    $issuer_name = trim($_POST['issuer_name'] ?? '');
    $issuer_phone = trim($_POST['issuer_phone'] ?? '');
    $issuer_aadhaar = trim($_POST['issuer_aadhaar'] ?? '');

    if (!$bookId || $qty<=0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) || !$issuer_name || !$issuer_phone || !$issuer_aadhaar) {
        $err = "All fields are required, quantity must be positive, and due date must be YYYY-MM-DD.";
    } else {
        $chk = $conn->prepare("SELECT quantity FROM books WHERE id=?");
        $chk->bind_param('i',$bookId); 
        $chk->execute();
        $bk = $chk->get_result()->fetch_assoc();

        if (!$bk || (int)$bk['quantity'] < $qty) {
            $err = "Requested quantity exceeds available stock.";
        } else {
            $conn->begin_transaction();
            try {
                $u = $conn->prepare("UPDATE books SET quantity = quantity - ? WHERE id=? AND quantity >= ?");
                $u->bind_param('iii',$qty,$bookId,$qty);
                $u->execute();

                $i = $conn->prepare("INSERT INTO borrow_history
                  (book_id, issuer_name, issuer_phone, issuer_aadhaar, managed_by_username, managed_by_role, quantity, due_date)
                  VALUES (?,?,?,?,?,?,?,?)");
                $i->bind_param('isssssis', $bookId, $issuer_name, $issuer_phone, $issuer_aadhaar, $user, $role, $qty, $due);
                $i->execute();

                $conn->commit();
            } catch (Throwable $e) {
                $conn->rollback();
                $err = "Borrow failed.";
            }
        }
    }
}

/* RETURN */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['return'])) {
    $histId = (int)($_POST['history_id'] ?? 0);
    if ($histId) {
        $conn->begin_transaction();
        try {
            $h = $conn->prepare("SELECT book_id, quantity FROM borrow_history WHERE id=? AND return_date IS NULL");
            $h->bind_param('i',$histId);
            $h->execute();
            $row = $h->get_result()->fetch_assoc();
            if ($row) {
              $bookId = (int)$row['book_id'];
              $qty = (int)$row['quantity'];

              $up = $conn->prepare("UPDATE borrow_history SET return_date = CURDATE(), managed_by_username=?, managed_by_role=? WHERE id=?");
              $up->bind_param('ssi',$user,$role,$histId);
              $up->execute();

              $b = $conn->prepare("UPDATE books SET quantity = quantity + ? WHERE id=?");
              $b->bind_param('ii',$qty,$bookId);
              $b->execute();
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            $err = "Return failed.";
        }
    }
}

/* Books list */
$books = $conn->query("SELECT * FROM books ORDER BY title");

/* Active borrows */
$actives = $conn->query("
  SELECT h.id as history_id, b.title, b.author, h.issuer_name, h.due_date, h.quantity
  FROM borrow_history h
  JOIN books b ON b.id=h.book_id
  WHERE h.return_date IS NULL
  ORDER BY h.borrow_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Borrow / Return</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="topbar-left"><h1>📖 Borrow / Return</h1></div>
  <div class="topbar-right">
    <a class="btn ghost" href="dashboard.php">Dashboard</a>
    <a class="btn ghost" href="logout.php">Logout</a>
  </div>
</header>

<main class="container">
  <?php if (!empty($err)): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <section class="card">
    <div class="between">
      <h2>Borrow a Book</h2>
      <span class="muted small">Processed by: <b><?= htmlspecialchars($user) ?></b> (<?= htmlspecialchars($role) ?>)</span>
    </div>

    <!-- NEW structured form -->
    <form method="post" class="borrow-form">
      <div class="form-group">
        <label for="book_id">Select Book</label>
        <select name="book_id" id="book_id" required>
          <option value="">Select a book (available qty)</option>
          <?php while($b=$books->fetch_assoc()): ?>
            <option value="<?= $b['id'] ?>" <?= $b['quantity']<=0?'disabled':'' ?>>
              <?= htmlspecialchars($b['title']) ?> — <?= htmlspecialchars($b['author']) ?> (<?= (int)$b['quantity'] ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="quantity">Quantity</label>
        <input type="number" name="quantity" id="quantity" min="1" placeholder="Qty" required>
      </div>

      <div class="form-group">
        <label for="issuer_name">Issuer Name</label>
        <input type="text" name="issuer_name" id="issuer_name" placeholder="Issuer Name" required>
      </div>

      <div class="form-group">
        <label for="issuer_phone">Phone</label>
        <input type="tel" name="issuer_phone" id="issuer_phone" placeholder="Phone" pattern="[\d\s+\-]{7,}" required>
      </div>

      <div class="form-group">
        <label for="issuer_aadhaar">Aadhaar</label>
        <input type="text" name="issuer_aadhaar" id="issuer_aadhaar" placeholder="Aadhaar (12 digits)" pattern="\d{12}" required>
      </div>

      <div class="form-group">
        <label for="due_date">Due Date</label>
        <input type="date" name="due_date" id="due_date" required>
      </div>

      <div class="form-actions">
        <button class="btn" name="borrow" type="submit">Borrow</button>
      </div>
    </form>
  </section>

  <section class="card">
    <div class="between">
      <h2>Active Borrows</h2>
      <input id="bookSearch" class="w-300" type="search" placeholder="Quick filter by book/issuer">
    </div>
    <table class="table" id="borrowTable">
      <thead><tr><th>Book</th><th>Issuer</th><th>Due</th><th>Qty</th><th>Action</th></tr></thead>
      <tbody>
        <?php while($r=$actives->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['title']) ?> <span class="muted small">by <?= htmlspecialchars($r['author']) ?></span></td>
            <td><?= htmlspecialchars($r['issuer_name']) ?></td>
            <td><?= htmlspecialchars($r['due_date']) ?></td>
            <td><?= (int)$r['quantity'] ?></td>
            <td class="actions">
              <form method="post" class="inline">
                <input type="hidden" name="history_id" value="<?= $r['history_id'] ?>">
                <button class="btn danger" name="return" type="submit">Return</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </section>
</main>
<script src="assets/js/script.js"></script>
</body>
</html>
