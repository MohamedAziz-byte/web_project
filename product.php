<?php
require 'includes/header.php';
require 'config/db.php';

// Get product ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch product + category name
$stmt = mysqli_prepare($conn, "
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$p) {
    header("Location: index.php");
    exit;
}

// Fetch related products (same category, exclude current)
$related = [];
if ($p['category_id']) {
    $rs = mysqli_prepare($conn, "SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4");
    mysqli_stmt_bind_param($rs, "ii", $p['category_id'], $id);
    mysqli_stmt_execute($rs);
    $related = mysqli_fetch_all(mysqli_stmt_get_result($rs), MYSQLI_ASSOC);
}

// Handle order submission
$order_error   = "";
$order_success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantity'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: user/login.php");
        exit;
    }

    $qty     = max(1, (int)$_POST['quantity']);
    $user_id = $_SESSION['user_id'];

    if ($qty > $p['stock']) {
        $order_error = "Only {$p['stock']} unit(s) available.";
    } else {
        $total = $qty * $p['price'];

        $o = mysqli_prepare($conn, "INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')");
        mysqli_stmt_bind_param($o, "id", $user_id, $total);
        mysqli_stmt_execute($o);
        $order_id = mysqli_insert_id($conn);

        $oi = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($oi, "iiid", $order_id, $id, $qty, $p['price']);
        mysqli_stmt_execute($oi);

        $us = mysqli_prepare($conn, "UPDATE products SET stock = stock - ? WHERE id = ?");
        mysqli_stmt_bind_param($us, "ii", $qty, $id);
        mysqli_stmt_execute($us);

        // Refresh stock count
        $p['stock'] = $p['stock'] - $qty;

        $order_success = "Order #$order_id placed! Total: " . number_format($total, 2) . " TND";
    }
}
?>

<style>
  /* ── PAGE LAYOUT ── */
  .product-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2.5rem 1.5rem 5rem;
  }

  /* Breadcrumb */
  .breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.82rem;
    color: var(--muted);
    margin-bottom: 2rem;
    flex-wrap: wrap;
  }

  .breadcrumb a { color: var(--purple); text-decoration: none; }
  .breadcrumb a:hover { text-decoration: underline; }
  .breadcrumb span { color: #ccc; }

  /* ── MAIN PRODUCT SECTION ── */
  .product-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-bottom: 4rem;
    align-items: start;
  }

  /* Image side */
  .product-gallery {
    position: sticky;
    top: 80px;
  }

  .main-image {
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 20px;
    overflow: hidden;
    background: var(--purple-pale);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 6rem;
    box-shadow: 0 8px 40px rgba(123,47,190,0.12);
    margin-bottom: 1rem;
  }

  .main-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  /* Info side */
  .product-info {}

  .product-cat-badge {
    display: inline-block;
    background: var(--purple-pale);
    color: var(--purple);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 0.3rem 0.9rem;
    border-radius: 20px;
    margin-bottom: 0.9rem;
  }

  .product-title {
    font-family: 'Syne', sans-serif;
    font-size: 2.1rem;
    font-weight: 800;
    color: var(--dark);
    line-height: 1.2;
    margin-bottom: 1rem;
  }

  .product-price-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.2rem;
  }

  .big-price {
    font-family: 'Syne', sans-serif;
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--purple);
  }

  .big-price .currency {
    font-size: 1rem;
    font-weight: 600;
    vertical-align: super;
  }

  .stock-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.9rem;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
  }

  .stock-pill.in  { background: #edfaf3; color: #1e7e4a; }
  .stock-pill.out { background: #fdecea; color: var(--error); }
  .stock-pill::before { content: '●'; font-size: 0.55rem; }

  /* Divider */
  .divider { height: 1px; background: #ede9f8; margin: 1.2rem 0; }

  .product-desc-text {
    font-size: 0.93rem;
    color: #555;
    line-height: 1.8;
    margin-bottom: 1.5rem;
  }

  /* Quantity + order */
  .order-box {
    background: var(--purple-pale);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1rem;
  }

  .order-box label {
    display: block;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--dark);
    margin-bottom: 0.5rem;
  }

  .qty-row {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 1.2rem;
  }

  .qty-btn {
    width: 38px; height: 38px;
    border-radius: 9px;
    border: 2px solid #d5caf0;
    background: #fff;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--purple);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s, border-color 0.2s;
    flex-shrink: 0;
  }

  .qty-btn:hover { background: var(--purple); color: #fff; border-color: var(--purple); }

  .qty-input {
    width: 70px;
    text-align: center;
    padding: 0.5rem;
    border: 2px solid #d5caf0;
    border-radius: 9px;
    font-family: 'Syne', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    background: #fff;
    outline: none;
  }

  .qty-input:focus { border-color: var(--purple); }

  .total-preview {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    border-radius: 10px;
    padding: 0.7rem 1rem;
    margin-bottom: 1.2rem;
  }

  .total-preview span { font-size: 0.82rem; color: var(--muted); }
  .total-preview strong {
    font-family: 'Syne', sans-serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--purple);
  }

  .btn-order {
    width: 100%;
    padding: 0.9rem;
    background: var(--purple);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    box-shadow: 0 6px 22px rgba(123,47,190,0.35);
    letter-spacing: 0.02em;
  }

  .btn-order:hover { background: var(--purple-dark); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(123,47,190,0.45); }
  .btn-order:active { transform: scale(0.98); }
  .btn-order:disabled { background: #ccc; box-shadow: none; cursor: not-allowed; transform: none; }

  /* Login prompt */
  .login-prompt {
    text-align: center;
    padding: 1.2rem;
    background: #fff;
    border-radius: 12px;
    border: 2px dashed #d5caf0;
    font-size: 0.88rem;
    color: var(--muted);
  }

  .login-prompt a { color: var(--purple); font-weight: 600; }

  /* Alerts */
  .alert {
    padding: 0.8rem 1rem;
    border-radius: 10px;
    font-size: 0.85rem;
    margin-bottom: 1rem;
  }

  .alert-error   { background: #fdecea; color: var(--error);   border-left: 3px solid var(--error); }
  .alert-success { background: #edfaf3; color: var(--success); border-left: 3px solid var(--success); }
  .alert-success a { color: var(--success); font-weight: 600; margin-left: 0.4rem; }

  /* Features row */
  .features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.8rem;
    margin-top: 1.2rem;
  }

  .feature-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0.8rem 0.5rem;
    background: #fff;
    border-radius: 12px;
    border: 1.5px solid #ede9f8;
    gap: 0.3rem;
  }

  .feature-item .f-icon { font-size: 1.3rem; }
  .feature-item p { font-size: 0.72rem; font-weight: 600; color: var(--dark); }
  .feature-item span { font-size: 0.68rem; color: var(--muted); }

  /* ── RELATED PRODUCTS ── */
  .related-section { margin-top: 1rem; }

  .section-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--dark);
    margin-bottom: 1.5rem;
  }

  .section-title span { color: var(--purple); }

  .related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.2rem;
  }

  .related-card {
    background: #fff;
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    color: inherit;
    display: block;
  }

  .related-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(123,47,190,0.14);
  }

  .related-img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    background: var(--purple-pale);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
  }

  .related-img img { width: 100%; height: 100%; object-fit: cover; }

  .related-body { padding: 0.9rem 1rem; }

  .related-name {
    font-family: 'Syne', sans-serif;
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.3rem;
  }

  .related-price {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--purple);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .product-main { grid-template-columns: 1fr; gap: 1.5rem; }
    .product-gallery { position: static; }
    .product-title { font-size: 1.6rem; }
    .big-price { font-size: 1.8rem; }
    .features { grid-template-columns: repeat(3, 1fr); }
  }

  @media (max-width: 480px) {
    .features { grid-template-columns: 1fr 1fr; }
  }
</style>

<main>
<div class="product-page">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="index.php">Home</a>
    <span>›</span>
    <a href="index.php#products">Products</a>
    <span>›</span>
    <?php if ($p['category_name']): ?>
      <a href="index.php#products"><?= htmlspecialchars($p['category_name']) ?></a>
      <span>›</span>
    <?php endif; ?>
    <span style="color:var(--dark);"><?= htmlspecialchars($p['name']) ?></span>
  </div>

  <!-- Main product section -->
  <div class="product-main">

    <!-- LEFT: Image -->
    <div class="product-gallery">
      <div class="main-image">
        <?php if (!empty($p['image'])): ?>
          <img src="uploads/<?= htmlspecialchars($p['image']) ?>"
               alt="<?= htmlspecialchars($p['name']) ?>"
               onerror="this.style.display='none'; this.parentElement.innerHTML='🛒';"/>
        <?php else: ?>
          🛒
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT: Info + Order -->
    <div class="product-info">

      <?php if ($p['category_name']): ?>
        <div class="product-cat-badge"><?= htmlspecialchars($p['category_name']) ?></div>
      <?php endif; ?>

      <h1 class="product-title"><?= htmlspecialchars($p['name']) ?></h1>

      <div class="product-price-row">
        <div class="big-price">
          <span class="currency">TND </span><?= number_format($p['price'], 2) ?>
        </div>
        <?php if ($p['stock'] > 0): ?>
          <span class="stock-pill in">In Stock (<?= $p['stock'] ?> left)</span>
        <?php else: ?>
          <span class="stock-pill out">Out of Stock</span>
        <?php endif; ?>
      </div>

      <div class="divider"></div>

      <p class="product-desc-text">
        <?= nl2br(htmlspecialchars($p['description'] ?: 'No description available for this product.')) ?>
      </p>

      <div class="divider"></div>

      <!-- Alerts -->
      <?php if ($order_error): ?>
        <div class="alert alert-error">⚠ <?= $order_error ?></div>
      <?php endif; ?>

      <?php if ($order_success): ?>
        <div class="alert alert-success">
          ✓ <?= $order_success ?>
          <a href="user/account.php">View My Orders →</a>
        </div>
      <?php endif; ?>

      <!-- Order box -->
      <?php if ($p['stock'] > 0 && empty($order_success)): ?>

        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="order-box">
            <form method="POST" action="" id="orderForm">
              <label>Quantity</label>
              <div class="qty-row">
                <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                <input type="number" name="quantity" id="qtyInput" class="qty-input"
                       value="1" min="1" max="<?= $p['stock'] ?>"
                       oninput="updateTotal()" readonly/>
                <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                <span style="font-size:0.8rem;color:var(--muted);">max <?= $p['stock'] ?></span>
              </div>

              <div class="total-preview">
                <span>Total</span>
                <strong id="totalDisplay">TND <?= number_format($p['price'], 2) ?></strong>
              </div>

              <button type="submit" class="btn-order">🛒 Place Order Now</button>
            </form>
          </div>
        <?php else: ?>
          <div class="login-prompt">
            <p>Please <a href="user/login.php">login</a> or <a href="user/signup.php">create an account</a> to place an order.</p>
          </div>
        <?php endif; ?>

      <?php elseif ($p['stock'] == 0): ?>
        <button class="btn-order" disabled>Out of Stock</button>
      <?php endif; ?>

      <!-- Trust features -->
      <div class="features">
        <div class="feature-item">
          <div class="f-icon">🚚</div>
          <p>Fast Delivery</p>
          <span>2–5 days</span>
        </div>
        <div class="feature-item">
          <div class="f-icon">🔒</div>
          <p>Secure Payment</p>
          <span>100% safe</span>
        </div>
        <div class="feature-item">
          <div class="f-icon">↩️</div>
          <p>Easy Returns</p>
          <span>7-day policy</span>
        </div>
      </div>

    </div>
  </div>

  <!-- Related products -->
  <?php if (!empty($related)): ?>
    <div class="related-section">
      <div class="section-title">Related <span>Products</span></div>
      <div class="related-grid">
        <?php foreach ($related as $r): ?>
          <a href="product.php?id=<?= $r['id'] ?>" class="related-card">
            <div class="related-img">
              <?php if (!empty($r['image'])): ?>
                <img src="uploads/<?= htmlspecialchars($r['image']) ?>"
                     alt="<?= htmlspecialchars($r['name']) ?>"
                     onerror="this.style.display='none'; this.parentElement.innerHTML='🛒';"/>
              <?php else: ?>
                🛒
              <?php endif; ?>
            </div>
            <div class="related-body">
              <div class="related-name"><?= htmlspecialchars($r['name']) ?></div>
              <div class="related-price">TND <?= number_format($r['price'], 2) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</div>
</main>

<script>
  const unitPrice = <?= (float)$p['price'] ?>;
  const maxStock  = <?= (int)$p['stock'] ?>;

  function changeQty(delta) {
    const input = document.getElementById('qtyInput');
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(maxStock, val));
    input.value = val;
    updateTotal();
  }

  function updateTotal() {
    const qty   = Math.max(1, Math.min(maxStock, parseInt(document.getElementById('qtyInput').value) || 1));
    const total = (unitPrice * qty).toFixed(2);
    document.getElementById('totalDisplay').textContent = 'TND ' + total;
  }
</script>

<?php require 'includes/footer.php'; ?>
