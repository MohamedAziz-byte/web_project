<?php
require '../includes/header.php';
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

$total_users    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='user'"))[0];
$total_products = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM products"))[0];
$total_orders   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders"))[0];
$total_revenue  = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered'"))[0];
$pending_orders = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders WHERE status='pending'"))[0];
$low_stock      = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM products WHERE stock <= 5"))[0];
$recent         = mysqli_query($conn, "SELECT o.*, u.name AS customer FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 6");
?>

<style>
  .page-title { font-family:'Syne',sans-serif; font-size:1.7rem; font-weight:800; color:var(--dark); margin-bottom:0.3rem; }
  .page-title span { color:var(--purple); }
  .page-sub { font-size:0.85rem; color:var(--muted); margin-bottom:2rem; }

  .stats-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:1.1rem; margin-bottom:2rem; }

  .stat-card {
    background:#fff; border-radius:var(--radius); padding:1.3rem 1.4rem;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
    border-left:4px solid var(--purple);
    transition:transform 0.2s, box-shadow 0.2s;
  }
  .stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 22px rgba(123,47,190,0.12); }
  .stat-card:nth-child(2) { border-color:#06b6d4; }
  .stat-card:nth-child(3) { border-color:#10b981; }
  .stat-card:nth-child(4) { border-color:#f59e0b; }
  .stat-card:nth-child(5) { border-color:#ef4444; }
  .stat-card:nth-child(6) { border-color:#8b5cf6; }
  .stat-icon { font-size:1.4rem; margin-bottom:0.4rem; }
  .stat-value { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; color:var(--dark); }
  .stat-label { font-size:0.76rem; color:var(--muted); font-weight:500; margin-top:0.1rem; }

  .card-box { background:#fff; border-radius:var(--radius); box-shadow:0 2px 10px rgba(0,0,0,0.05); padding:1.5rem; margin-bottom:1.5rem; }
  .card-box-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; padding-bottom:0.6rem; border-bottom:2px solid var(--purple-pale); }
  .card-box-header h2 { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; color:var(--dark); }
  .card-box-header a { font-size:0.8rem; color:var(--purple); font-weight:600; }

  table { width:100%; border-collapse:collapse; font-size:0.86rem; }
  th { text-align:left; padding:0.5rem 0.8rem; background:var(--purple-pale); color:var(--purple); font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; }
  td { padding:0.7rem 0.8rem; border-bottom:1px solid #f0eef5; }
  tr:last-child td { border-bottom:none; }
  tr:hover td { background:#faf9fd; }

  .badge { display:inline-block; padding:0.18rem 0.65rem; border-radius:20px; font-size:0.72rem; font-weight:600; text-transform:capitalize; }
  .badge-pending   { background:#fff8e1; color:#b8860b; }
  .badge-confirmed { background:#e8f5e9; color:#2e7d32; }
  .badge-delivered { background:#e3f2fd; color:#1565c0; }
  .badge-cancelled { background:#fdecea; color:var(--error); }
</style>

<?php require 'sidebar.php'; ?>
<main>
  <div class="page-title">Admin <span>Dashboard</span></div>
  <div class="page-sub">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?> 👋</div>

  <div class="stats-grid">
    <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-value"><?= $total_users ?></div><div class="stat-label">Customers</div></div>
    <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-value"><?= $total_products ?></div><div class="stat-label">Products</div></div>
    <div class="stat-card"><div class="stat-icon">🧾</div><div class="stat-value"><?= $total_orders ?></div><div class="stat-label">Total Orders</div></div>
    <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value"><?= number_format($total_revenue,0) ?></div><div class="stat-label">Revenue (TND)</div></div>
    <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value"><?= $pending_orders ?></div><div class="stat-label">Pending Orders</div></div>
    <div class="stat-card"><div class="stat-icon">⚠️</div><div class="stat-value"><?= $low_stock ?></div><div class="stat-label">Low Stock</div></div>
  </div>

  <div class="card-box">
    <div class="card-box-header">
      <h2>🧾 Recent Orders</h2>
      <a href="orders.php">View All →</a>
    </div>
    <?php if (mysqli_num_rows($recent) === 0): ?>
      <p style="color:var(--muted);text-align:center;padding:2rem;">No orders yet.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php while ($o = mysqli_fetch_assoc($recent)): ?>
            <tr>
              <td>#<?= $o['id'] ?></td>
              <td><?= htmlspecialchars($o['customer']) ?></td>
              <td><strong><?= number_format($o['total'],2) ?> TND</strong></td>
              <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
              <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>
  </div><!-- close admin-content -->
</div><!-- close admin-layout -->

<?php require '../includes/footer.php'; ?>
