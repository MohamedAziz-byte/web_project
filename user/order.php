<?php
require '../includes/header.php';
require '../config/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=order");
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Fetch product
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    echo "<main><div style='text-align:center;padding:4rem;color:#888;'>Product not found. <a href='../index.php' style='color:#7b2fbe;'>Go back</a></div></main>";
    require '../includes/footer.php';
    exit;
}

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = max(1, (int)$_POST['quantity']);

    if ($qty > $product['stock']) {
        $error = "Only {$product['stock']} unit(s) available in stock.";
    } else {
        $total = $qty * $product['price'];

        // Insert order
        $o = mysqli_prepare($conn, "INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')");
        mysqli_stmt_bind_param($o, "id", $user_id, $total);
        mysqli_stmt_execute($o);
        $order_id = mysqli_insert_id($conn);

        // Insert order item
        $oi = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($oi, "iiid", $order_id, $product_id, $qty, $product['price']);
        mysqli_stmt_execute($oi);

        // Reduce stock
        $us = mysqli_prepare($conn, "UPDATE products SET stock = stock - ? WHERE id = ?");
        mysqli_stmt_bind_param($us, "ii", $qty, $product_id);
        mysqli_stmt_execute($us);

        $success = "✓ Order #$order_id placed successfully! Total: " . number_format($total, 2) . " TND";
    }
}
?>

<style>
  .order-wrap {
    max-width: 640px;
    margin: 3rem auto;
    padding: 0 1.5rem 5rem;
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
    color: var(--muted);
    margin-bottom: 1.5rem;
    transition: color 0.2s;
  }

  .back-link:hover { color: var(--purple); }

  .order-card {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: 0 4px 24px rgba(0,0,0,0.07);
    overflow: hidden;
  }

  .product-preview {
    display: flex;
    gap: 1.2rem;
    align-items: center;
    padding: 1.5rem;
    background: var(--purple-pale);
    border-bottom: 1px solid #e8e0f5;
  }

  .product-preview img,
  .product-preview .img-placeholder {
    width: 80px; height: 80px;
    border-radius: 10px;
    object-fit: cover;
    background: var(--purple);
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem;
    flex-shrink: 0;
  }

  .product-preview-info h3 {
    font-family: 'Syne', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.3rem;
  }

  .product-preview-info p {
    font-size: 0.85rem;
    color: var(--muted);
  }

  .product-preview-info .price {
    font-size: 1rem;
    font-weight: 700;
    color: var(--purple);
    margin-top: 0.2rem;
  }

  .order-form { padding: 1.8rem; }

  .order-form h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: var(--dark);
  }

  .form-group { margin-bottom: 1.2rem; }

  .form-group label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--dark);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.4rem;
  }

  .form-group input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e0f5;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.95rem;
    color: var(--dark);
    background: var(--bg);
    outline: none;
    transition: border-color 0.2s;
  }

  .form-group input:focus { border-color: var(--purple); background: #fff; }

  .order-summary {
    background: var(--purple-pale);
    border-radius: 10px;
    padding: 1rem 1.2rem;
    margin-bottom: 1.2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .order-summary span { font-size: 0.88rem; color: var(--muted); }
  .order-summary strong { font-family: 'Syne', sans-serif; font-size: 1.3rem; color: var(--purple); }

  .btn-order {
    width: 100%;
    padding: 0.9rem;
    background: var(--purple);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    box-shadow: 0 6px 20px rgba(123,47,190,0.3);
  }

  .btn-order:hover { background: var(--purple-dark); transform: translateY(-2px); }

  .alert {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 1rem;
  }

  .alert-error   { background: #fdecea; color: var(--error);   border-left: 3px solid var(--error); }
  .alert-success { background: #edfaf3; color: var(--success); border-left: 3px solid var(--success); }
  .alert-success a { color: var(--success); font-weight: 600; margin-left: 0.5rem; }
</style>

<main>
<div class="order-wrap">
  <a href="../index.php" class="back-link">← Back to products</a>

  <div class="order-card">
    <!-- Product Preview -->
    <div class="product-preview">
      <?php if (!empty($product['image']) && file_exists("../uploads/" . $product['image'])): ?>
        <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>"/>
      <?php else: ?>
        <div class="img-placeholder">🛒</div>
      <?php endif; ?>
      <div class="product-preview-info">
        <h3><?= htmlspecialchars($product['name']) ?></h3>
        <p><?= htmlspecialchars(substr($product['description'], 0, 80)) ?>...</p>
        <div class="price"><?= number_format($product['price'], 2) ?> TND / unit</div>
      </div>
    </div>

    <!-- Order Form -->
    <div class="order-form">
      <h2>Place Your Order</h2>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠ <?= $error ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <?= $success ?>
          <a href="account.php">View My Orders →</a>
        </div>
      <?php else: ?>

        <form method="POST" action="">
          <div class="form-group">
            <label>Quantity <small style="color:var(--muted);text-transform:none;">(<?= $product['stock'] ?> available)</small></label>
            <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" required/>
          </div>

          <!-- Live total preview -->
          <div class="order-summary">
            <span>Estimated Total</span>
            <strong id="totalDisplay"><?= number_format($product['price'], 2) ?> TND</strong>
          </div>

          <button type="submit" class="btn-order">✓ Confirm Order</button>
        </form>

        <script>
          const price = <?= $product['price'] ?>;
          document.querySelector('input[name="quantity"]').addEventListener('input', function() {
            const qty = Math.max(1, parseInt(this.value) || 1);
            document.getElementById('totalDisplay').textContent = (price * qty).toFixed(2) + ' TND';
          });
        </script>

      <?php endif; ?>
    </div>
  </div>
</div>
</main>

<?php require '../includes/footer.php'; ?>
