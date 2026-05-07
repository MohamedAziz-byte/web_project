<?php
require '../includes/header.php';
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    // Get image to remove file
    $img = mysqli_fetch_row(mysqli_query($conn, "SELECT image FROM products WHERE id=$did"));
    if ($img && $img[0] && file_exists("../uploads/" . $img[0])) {
        unlink("../uploads/" . $img[0]);
    }
    mysqli_query($conn, "DELETE FROM products WHERE id=$did");
    header("Location: products.php?msg=deleted");
    exit;
}

$msg = $_GET['msg'] ?? '';
$products = mysqli_query($conn, "SELECT p.*, c.name AS cat FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC");
?>

<style>
  .admin-wrap { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem 5rem; }

  .page-bar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;
  }

  .page-bar h1 {
    font-family: 'Syne', sans-serif; font-size: 1.7rem; font-weight: 800; color: var(--dark);
  }

  .page-bar h1 span { color: var(--purple); }

  .admin-nav {
    display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 2rem;
  }

  .admin-nav a {
    padding: 0.42rem 1rem; border-radius: 8px; font-size: 0.84rem; font-weight: 600;
    border: 1.5px solid #e0d9f5; color: var(--text); transition: all 0.2s;
  }

  .admin-nav a:hover, .admin-nav a.active {
    background: var(--purple); border-color: var(--purple); color: #fff;
  }

  .btn-add {
    background: var(--purple); color: #fff; padding: 0.55rem 1.3rem;
    border-radius: 9px; font-weight: 600; font-size: 0.88rem;
    transition: background 0.2s, transform 0.15s;
    box-shadow: 0 4px 14px rgba(123,47,190,0.3);
  }

  .btn-add:hover { background: var(--purple-dark); transform: translateY(-1px); }

  .alert-success {
    background: #edfaf3; color: var(--success); border-left: 3px solid var(--success);
    padding: 0.7rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.2rem;
  }

  .card-box {
    background: #fff; border-radius: var(--radius);
    box-shadow: 0 2px 12px rgba(0,0,0,0.05); overflow: hidden;
  }

  table { width: 100%; border-collapse: collapse; font-size: 0.87rem; }

  th {
    text-align: left; padding: 0.7rem 1rem;
    background: var(--purple-pale); color: var(--purple);
    font-size: 0.73rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
  }

  td { padding: 0.8rem 1rem; border-bottom: 1px solid #f0eef5; vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #faf9fd; }

  .product-thumb {
    width: 48px; height: 48px; border-radius: 8px;
    object-fit: cover; background: var(--purple-pale);
    display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
  }

  .product-thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }

  .badge-stock {
    display: inline-block; padding: 0.18rem 0.65rem; border-radius: 20px;
    font-size: 0.72rem; font-weight: 600;
  }

  .in-stock  { background: #edfaf3; color: var(--success); }
  .low-stock { background: #fff8e1; color: #b8860b; }
  .out-stock { background: #fdecea; color: var(--error); }

  .actions { display: flex; gap: 0.5rem; }

  .btn-edit {
    padding: 0.3rem 0.8rem; border-radius: 6px; font-size: 0.78rem; font-weight: 600;
    background: var(--purple-pale); color: var(--purple); transition: background 0.2s;
  }

  .btn-edit:hover { background: var(--purple); color: #fff; }

  .btn-del {
    padding: 0.3rem 0.8rem; border-radius: 6px; font-size: 0.78rem; font-weight: 600;
    background: #fdecea; color: var(--error); transition: background 0.2s;
    border: none; cursor: pointer;
  }

  .btn-del:hover { background: var(--error); color: #fff; }

  .empty { text-align: center; padding: 3rem; color: var(--muted); }
  .empty .icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
</style>

<main>
<div class="admin-wrap">

  <div class="page-bar">
    <h1>Manage <span>Products</span></h1>
    <a href="add_product.php" class="btn-add">➕ Add Product</a>
  </div>

  <div class="admin-nav">
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="products.php" class="active">📦 Products</a>
    <a href="add_product.php">➕ Add Product</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="customers.php">👥 Customers</a>
  </div>

  <?php if ($msg === 'deleted'): ?>
    <div class="alert-success">✓ Product deleted successfully.</div>
  <?php endif; ?>

  <div class="card-box">
    <?php if (mysqli_num_rows($products) === 0): ?>
      <div class="empty">
        <div class="icon">📦</div>
        <p>No products yet. <a href="add_product.php" style="color:var(--purple);font-weight:600;">Add one →</a></p>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($p = mysqli_fetch_assoc($products)): ?>
            <tr>
              <td><?= $p['id'] ?></td>
              <td>
                <div class="product-thumb">
                  <?php if (!empty($p['image']) && file_exists("../uploads/" . $p['image'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($p['image']) ?>" alt=""/>
                  <?php else: ?>
                    🛒
                  <?php endif; ?>
                </div>
              </td>
              <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
              <td><?= htmlspecialchars($p['cat'] ?? '—') ?></td>
              <td><?= number_format($p['price'], 2) ?> TND</td>
              <td>
                <?php if ($p['stock'] == 0): ?>
                  <span class="badge-stock out-stock">Out of Stock</span>
                <?php elseif ($p['stock'] <= 5): ?>
                  <span class="badge-stock low-stock">Low (<?= $p['stock'] ?>)</span>
                <?php else: ?>
                  <span class="badge-stock in-stock"><?= $p['stock'] ?> units</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="actions">
                  <a href="add_product.php?edit=<?= $p['id'] ?>" class="btn-edit">✏ Edit</a>
                  <button class="btn-del"
                    onclick="if(confirm('Delete this product?')) window.location='products.php?delete=<?= $p['id'] ?>'">
                    🗑 Delete
                  </button>
                </div>
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
