<?php
require '../includes/header.php';
require '../config/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Fetch user orders
$ostmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($ostmt, "i", $user_id);
mysqli_stmt_execute($ostmt);
$orders = mysqli_stmt_get_result($ostmt);
?>

<style>
  .page-wrap { max-width: 900px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

  .page-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--dark);
    margin-bottom: 2rem;
  }

  .page-title span { color: var(--purple); }

  .card-box {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: 0 2px 16px rgba(0,0,0,0.06);
    padding: 1.8rem;
    margin-bottom: 2rem;
  }

  .card-box h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 1.2rem;
    padding-bottom: 0.6rem;
    border-bottom: 2px solid var(--purple-pale);
  }

  .info-row {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
  }

  .info-item { flex: 1; min-width: 180px; }

  .info-item label {
    display: block;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.3rem;
  }

  .info-item p {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--dark);
  }

  /* Orders table */
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
  }

  th {
    text-align: left;
    padding: 0.6rem 0.8rem;
    background: var(--purple-pale);
    color: var(--purple);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }

  td {
    padding: 0.75rem 0.8rem;
    border-bottom: 1px solid #f0eef5;
    color: var(--text);
  }

  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #faf9fd; }

  .badge {
    display: inline-block;
    padding: 0.2rem 0.7rem;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: capitalize;
  }

  .badge-pending   { background: #fff8e1; color: #b8860b; }
  .badge-confirmed { background: #e8f5e9; color: #2e7d32; }
  .badge-delivered { background: #e3f2fd; color: #1565c0; }
  .badge-cancelled { background: #fdecea; color: var(--error); }

  .empty-orders {
    text-align: center;
    padding: 2.5rem;
    color: var(--muted);
  }

  .empty-orders .icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
</style>

<main>
<div class="page-wrap">
  <div class="page-title">My <span>Account</span></div>

  <!-- Profile info -->
  <div class="card-box">
    <h2>👤 Profile Information</h2>
    <div class="info-row">
      <div class="info-item">
        <label>Full Name</label>
        <p><?= htmlspecialchars($user['name']) ?></p>
      </div>
      <div class="info-item">
        <label>Email Address</label>
        <p><?= htmlspecialchars($user['email']) ?></p>
      </div>
      <div class="info-item">
        <label>Account Type</label>
        <p><?= ucfirst($user['role']) ?></p>
      </div>
      <div class="info-item">
        <label>Member Since</label>
        <p><?= date('d M Y', strtotime($user['created_at'])) ?></p>
      </div>
    </div>
  </div>

  <!-- Order history -->
  <div class="card-box">
    <h2>📦 My Orders</h2>
    <?php if (mysqli_num_rows($orders) === 0): ?>
      <div class="empty-orders">
        <div class="icon">🛒</div>
        <p>You haven't placed any orders yet.</p>
        <a href="../index.php" style="color:var(--purple);font-weight:600;">Browse Products →</a>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Total</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($order = mysqli_fetch_assoc($orders)): ?>
            <tr>
              <td>#<?= $order['id'] ?></td>
              <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
              <td><strong><?= number_format($order['total'], 2) ?> TND</strong></td>
              <td>
                <span class="badge badge-<?= $order['status'] ?>">
                  <?= ucfirst($order['status']) ?>
                </span>
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
