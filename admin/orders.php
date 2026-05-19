<?php
require '../includes/header.php';
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $oid = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowed = ['pending','confirmed','delivered','cancelled'];
    if (in_array($status, $allowed)) {
        $s = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE id=?");
        mysqli_stmt_bind_param($s, "si", $status, $oid);
        mysqli_stmt_execute($s);
    }
    header("Location: orders.php?msg=updated"); exit;
}

$msg    = $_GET['msg'] ?? '';
$orders = mysqli_query($conn, "SELECT o.*, u.name AS customer, u.email FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC");
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
  .badge { display:inline-block; padding:0.18rem 0.65rem; border-radius:20px; font-size:0.72rem; font-weight:600; text-transform:capitalize; }
  .badge-pending   { background:#fff8e1; color:#b8860b; }
  .badge-confirmed { background:#e8f5e9; color:#2e7d32; }
  .badge-delivered { background:#e3f2fd; color:#1565c0; }
  .badge-cancelled { background:#fdecea; color:var(--error); }
  .status-form { display:flex; gap:0.5rem; align-items:center; }
  .status-form select { padding:0.3rem 0.6rem; border-radius:6px; font-size:0.8rem; border:1.5px solid #e0d9f5; outline:none; background:var(--bg); color:var(--text); }
  .status-form select:focus { border-color:var(--purple); }
  .btn-update { padding:0.3rem 0.75rem; border-radius:6px; font-size:0.78rem; font-weight:600; background:var(--purple); color:#fff; border:none; cursor:pointer; transition:background 0.2s; }
  .btn-update:hover { background:var(--purple-dark); }
  .empty { text-align:center; padding:3rem; color:var(--muted); }
</style>

<?php require 'sidebar.php'; ?>
<main>
  <div class="page-bar"><h1>Manage <span>Orders</span></h1></div>
  <?php if ($msg === 'updated'): ?><div class="alert-success">✓ Order status updated.</div><?php endif; ?>
  <div class="card-box">
    <?php if (mysqli_num_rows($orders) === 0): ?>
      <div class="empty">No orders yet.</div>
    <?php else: ?>
      <table>
        <thead><tr><th>#</th><th>Customer</th><th>Email</th><th>Total</th><th>Date</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
          <?php while ($o = mysqli_fetch_assoc($orders)): ?>
            <tr>
              <td>#<?= $o['id'] ?></td>
              <td><strong><?= htmlspecialchars($o['customer']) ?></strong></td>
              <td style="color:var(--muted);font-size:0.81rem;"><?= htmlspecialchars($o['email']) ?></td>
              <td><strong><?= number_format($o['total'],2) ?> TND</strong></td>
              <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
              <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
              <td>
                <form method="POST" class="status-form">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>"/>
                  <select name="status">
                    <?php foreach (['pending','confirmed','delivered','cancelled'] as $s): ?>
                      <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn-update">Save</button>
                </form>
              </td>
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
