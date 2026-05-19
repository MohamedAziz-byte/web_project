<?php
require '../includes/header.php';
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM orders WHERE user_id=$did");
    mysqli_query($conn, "DELETE FROM users WHERE id=$did AND role='user'");
    header("Location: customers.php?msg=deleted"); exit;
}

$msg       = $_GET['msg'] ?? '';
$customers = mysqli_query($conn, "
    SELECT u.*, COUNT(o.id) AS total_orders, COALESCE(SUM(o.total),0) AS total_spent
    FROM users u LEFT JOIN orders o ON o.user_id=u.id
    WHERE u.role='user' GROUP BY u.id ORDER BY u.created_at DESC
");
?>

<style>
  .page-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.8rem; }
  .page-bar h1 { font-family:'Syne',sans-serif; font-size:1.7rem; font-weight:800; color:var(--dark); }
  .page-bar h1 span { color:var(--purple); }
  .alert-success { background:#edfaf3; color:var(--success); border-left:3px solid var(--success); padding:0.7rem 1rem; border-radius:8px; font-size:0.85rem; margin-bottom:1.2rem; }
  .card-box { background:#fff; border-radius:var(--radius); box-shadow:0 2px 10px rgba(0,0,0,0.05); overflow:hidden; }
  table { width:100%; border-collapse:collapse; font-size:0.86rem; }
  th { text-align:left; padding:0.7rem 1rem; background:var(--purple-pale); color:var(--purple); font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; }
  td { padding:0.75rem 1rem; border-bottom:1px solid #f0eef5; vertical-align:middle; }
  tr:last-child td { border-bottom:none; }
  tr:hover td { background:#faf9fd; }
  .cust-info { display:flex; align-items:center; gap:0.7rem; }
  .avatar { width:34px; height:34px; border-radius:50%; background:var(--purple); color:#fff; font-weight:700; font-size:0.85rem; display:flex; align-items:center; justify-content:center; }
  .btn-del { padding:0.28rem 0.8rem; border-radius:6px; font-size:0.77rem; font-weight:600; background:#fdecea; color:var(--error); border:none; cursor:pointer; transition:background 0.2s; }
  .btn-del:hover { background:var(--error); color:#fff; }
  .empty { text-align:center; padding:3rem; color:var(--muted); }
</style>

<?php require 'sidebar.php'; ?>
<main>
  <div class="page-bar"><h1>Manage <span>Customers</span></h1></div>
  <?php if ($msg === 'deleted'): ?><div class="alert-success">✓ Customer removed.</div><?php endif; ?>
  <div class="card-box">
    <?php if (mysqli_num_rows($customers) === 0): ?>
      <div class="empty">No customers yet.</div>
    <?php else: ?>
      <table>
        <thead><tr><th>#</th><th>Customer</th><th>Email</th><th>Orders</th><th>Spent</th><th>Joined</th><th>Action</th></tr></thead>
        <tbody>
          <?php while ($c = mysqli_fetch_assoc($customers)): ?>
            <tr>
              <td><?= $c['id'] ?></td>
              <td><div class="cust-info"><div class="avatar"><?= strtoupper(substr($c['name'],0,1)) ?></div><strong><?= htmlspecialchars($c['name']) ?></strong></div></td>
              <td style="color:var(--muted);font-size:0.81rem;"><?= htmlspecialchars($c['email']) ?></td>
              <td><?= $c['total_orders'] ?></td>
              <td><strong><?= number_format($c['total_spent'],2) ?> TND</strong></td>
              <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
              <td><button class="btn-del" onclick="if(confirm('Delete customer and all their orders?')) window.location='customers.php?delete=<?= $c['id'] ?>'">🗑 Delete</button></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>
  </div>
</div>
<?php require '../includes/footer.php'; ?>
