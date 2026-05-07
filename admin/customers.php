<?php
require '../includes/header.php';
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

// Delete customer
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM orders WHERE user_id=$did");
    mysqli_query($conn, "DELETE FROM users WHERE id=$did AND role='user'");
    header("Location: customers.php?msg=deleted");
    exit;
}

$msg = $_GET['msg'] ?? '';
$customers = mysqli_query($conn, "
    SELECT u.*, COUNT(o.id) AS total_orders, COALESCE(SUM(o.total),0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
?>

<style>
  .admin-wrap { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem 5rem; }

  .page-bar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
  .page-bar h1 { font-family: 'Syne', sans-serif; font-size: 1.7rem; font-weight: 800; color: var(--dark); }
  .page-bar h1 span { color: var(--purple); }

  .admin-nav { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 2rem; }

  .admin-nav a {
    padding: 0.42rem 1rem; border-radius: 8px; font-size: 0.84rem; font-weight: 600;
    border: 1.5px solid #e0d9f5; color: var(--text); transition: all 0.2s;
  }

  .admin-nav a:hover, .admin-nav a.active {
    background: var(--purple); border-color: var(--purple); color: #fff;
  }

  .alert-success {
    background: #edfaf3; color: var(--success); border-left: 3px solid var(--success);
    padding: 0.7rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.2rem;
  }

  .card-box { background: #fff; border-radius: var(--radius); box-shadow: 0 2px 12px rgba(0,0,0,0.05); overflow: hidden; }

  table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }

  th {
    text-align: left; padding: 0.7rem 1rem;
    background: var(--purple-pale); color: var(--purple);
    font-size: 0.73rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
  }

  td { padding: 0.8rem 1rem; border-bottom: 1px solid #f0eef5; vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #faf9fd; }

  .avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--purple); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.85rem;
  }

  .customer-info { display: flex; align-items: center; gap: 0.7rem; }

  .btn-del {
    padding: 0.3rem 0.8rem; border-radius: 6px; font-size: 0.78rem; font-weight: 600;
    background: #fdecea; color: var(--error); border: none; cursor: pointer;
    transition: background 0.2s;
  }

  .btn-del:hover { background: var(--error); color: #fff; }

  .empty { text-align: center; padding: 3rem; color: var(--muted); }
</style>

<main>
<div class="admin-wrap">

  <div class="page-bar">
    <h1>Manage <span>Customers</span></h1>
  </div>

  <div class="admin-nav">
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="products.php">📦 Products</a>
    <a href="add_product.php">➕ Add Product</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="customers.php" class="active">👥 Customers</a>
  </div>

  <?php if ($msg === 'deleted'): ?>
    <div class="alert-success">✓ Customer removed successfully.</div>
  <?php endif; ?>

  <div class="card-box">
    <?php if (mysqli_num_rows($customers) === 0): ?>
      <div class="empty">No customers registered yet.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Orders</th>
            <th>Total Spent</th>
            <th>Joined</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($c = mysqli_fetch_assoc($customers)): ?>
            <tr>
              <td><?= $c['id'] ?></td>
              <td>
                <div class="customer-info">
                  <div class="avatar"><?= strtoupper(substr($c['name'], 0, 1)) ?></div>
                  <strong><?= htmlspecialchars($c['name']) ?></strong>
                </div>
              </td>
              <td style="color:var(--muted);font-size:0.82rem;"><?= htmlspecialchars($c['email']) ?></td>
              <td><?= $c['total_orders'] ?> order<?= $c['total_orders'] != 1 ? 's' : '' ?></td>
              <td><strong><?= number_format($c['total_spent'], 2) ?> TND</strong></td>
              <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
              <td>
                <button class="btn-del"
                  onclick="if(confirm('Delete this customer and all their orders?')) window.location='customers.php?delete=<?= $c['id'] ?>'">
                  🗑 Delete
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>
</main>

<?php require '../includes/footer.php'; ?>
