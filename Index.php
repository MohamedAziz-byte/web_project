<?php
session_start();
require 'config/db.php';

// Fetch all products
$result   = mysqli_query($conn, "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

// Fetch categories for filter
$cat_result = mysqli_query($conn, "SELECT * FROM categories");
$categories = [];
while ($row = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ShopZone — Home</title>
  <link rel="stylesheet" href="mystyle.css"/>
  
</head>
<body>

<!-- ── NAVBAR ── -->
<nav>
  <div class="nav-brand">Shop<span>Zone</span></div>
  <div class="nav-links">
    <?php if (isset($_SESSION['user_id'])): ?>
      <div class="nav-user">
        👋 <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="admin/dashboard.php" style="color:var(--purple);font-weight:600;">Admin Panel</a>
        <?php else: ?>
          <a href="user/account.php">My Account</a>
        <?php endif; ?>
        <a href="user/logout.php" class="logout">Logout</a>
      </div>
    <?php else: ?>
      <a href="user/login.php">Login</a>
      <a href="user/signup.php" class="btn-nav">Sign Up</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ── HERO ── -->
<section class="hero">
  <div class="hero-tag">✦ New Arrivals Every Week</div>
  <h1>Shop the Best.<br/><span>Feel the Difference.</span></h1>
  <p>Discover thousands of quality products at unbeatable prices, delivered right to your door.</p>
  <div class="hero-btns">
    <a href="#products" class="btn-hero-primary">Browse Products</a>
    <?php if (!isset($_SESSION['user_id'])): ?>
      <a href="user/signup.php" class="btn-hero-outline">Create Free Account</a>
    <?php endif; ?>
  </div>
</section>

<!-- ── STATS ── -->
<div class="stats-bar">
  <div class="stat-item">
    <strong><?= count($products) ?>+</strong>
    <span>Products</span>
  </div>
  <div class="stat-item">
    <strong>100%</strong>
    <span>Secure Checkout</span>
  </div>
  <div class="stat-item">
    <strong>24/7</strong>
    <span>Customer Support</span>
  </div>
  <div class="stat-item">
    <strong>Fast</strong>
    <span>Delivery</span>
  </div>
</div>

<!-- ── PRODUCTS ── -->
<main class="main" id="products">
  <div class="section-header">
    <h2>Our <span>Products</span></h2>
    <div class="filter-pills">
      <span class="pill active" onclick="filterProducts('all', this)">All</span>
      <?php foreach ($categories as $cat): ?>
        <span class="pill" onclick="filterProducts('<?= $cat['id'] ?>', this)"><?= htmlspecialchars($cat['name']) ?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="product-grid" id="productGrid">
    <?php if (empty($products)): ?>
      <div class="empty-state">
        <div class="icon">🛍️</div>
        <h3>No products yet</h3>
        <p>Check back soon — new items are being added!</p>
      </div>
    <?php else: ?>
      <?php foreach ($products as $p): ?>
        <div class="product-card" data-cat="<?= $p['category_id'] ?>">

          <!-- Image or placeholder -->
          <div class="product-img">
            <?php if (!empty($p['image']) && file_exists("uploads/" . $p['image'])): ?>
              <img src="uploads/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"/>
            <?php else: ?>
              🛒
            <?php endif; ?>
          </div>

          <div class="product-body">
            <?php if ($p['category_name']): ?>
              <div class="product-cat"><?= htmlspecialchars($p['category_name']) ?></div>
            <?php endif; ?>
            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="product-desc"><?= htmlspecialchars($p['description']) ?></div>

            <div class="product-footer">
              <div class="product-price">
                <span class="currency">TND </span><?= number_format($p['price'], 2) ?>
              </div>
              <?php if ($p['stock'] > 0): ?>
                <span class="stock-badge in-stock">In Stock</span>
              <?php else: ?>
                <span class="stock-badge out-stock">Out of Stock</span>
              <?php endif; ?>
            </div>

            <?php if ($p['stock'] > 0): ?>
              <a href="product.php?id=<?= $p['id'] ?>" class="btn-add">View & Order →</a>
            <?php else: ?>
              <span class="btn-add" style="background:#ccc;cursor:not-allowed;">Out of Stock</span>
            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<!-- ── FOOTER ── -->
<footer>
  © 2024 <span>ShopZone</span> — All rights reserved.
</footer>

<script>
  function filterProducts(catId, el) {
    // Update active pill
    document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');

    // Show/hide cards
    document.querySelectorAll('.product-card').forEach(card => {
      if (catId === 'all' || card.dataset.cat === catId) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    });
  }
</script>

</body>
</html>