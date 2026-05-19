<?php
require '../includes/header.php';
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php"); exit;
}

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$product = null;
$error = $success = "";

if ($edit_id) {
    $s = mysqli_prepare($conn, "SELECT * FROM products WHERE id=?");
    mysqli_stmt_bind_param($s, "i", $edit_id);
    mysqli_stmt_execute($s);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
}

$cats = mysqli_query($conn, "SELECT * FROM categories");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];

    if (empty($name) || $price <= 0) {
        $error = "Name and a valid price are required.";
    } else {
        $image_name = $product['image'] ?? '';
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) { $error = "Only JPG, PNG, GIF, WEBP allowed."; }
            elseif ($_FILES['image']['size'] > 5*1024*1024) { $error = "Max 5MB."; }
            else {
                $image_name = uniqid('img_').'.'.$ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/".$image_name);
            }
        }
        if (empty($error)) {
            // Use NULL if no category selected (avoids foreign key error)
            $category_id = ($category_id > 0) ? $category_id : null;

            if ($edit_id) {
                $s = mysqli_prepare($conn, "UPDATE products SET name=?,description=?,price=?,stock=?,image=?,category_id=? WHERE id=?");
                mysqli_stmt_bind_param($s, "ssdiisi", $name, $description, $price, $stock, $image_name, $category_id, $edit_id);
            } else {
                $s = mysqli_prepare($conn, "INSERT INTO products (name,description,price,stock,image,category_id) VALUES(?,?,?,?,?,?)");
                mysqli_stmt_bind_param($s, "ssdiis", $name, $description, $price, $stock, $image_name, $category_id);
            }
            if (mysqli_stmt_execute($s)) { $success = $edit_id ? "Product updated!" : "Product added!"; if (!$edit_id) { $product=null; $edit_id=0; } }
            else { $error = "Database error."; }
        }
    }
}
?>

<style>
  .page-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.8rem; }
  .page-bar h1 { font-family:'Syne',sans-serif; font-size:1.7rem; font-weight:800; color:var(--dark); }
  .page-bar h1 span { color:var(--purple); }
  .alert { padding:0.75rem 1rem; border-radius:8px; font-size:0.85rem; margin-bottom:1.2rem; }
  .alert-error   { background:#fdecea; color:var(--error);   border-left:3px solid var(--error); }
  .alert-success { background:#edfaf3; color:var(--success); border-left:3px solid var(--success); }
  .alert-success a { color:var(--success); font-weight:600; margin-left:0.4rem; }
  .form-card { background:#fff; border-radius:var(--radius); box-shadow:0 2px 14px rgba(0,0,0,0.06); padding:2rem; }
  .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; }
  .form-group { margin-bottom:1.2rem; }
  .form-group label { display:block; font-size:0.76rem; font-weight:700; color:var(--dark); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem; }
  .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.75rem 1rem; border:2px solid #e5e0f5; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:0.92rem; color:var(--dark); background:var(--bg); outline:none; transition:border-color 0.2s; }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--purple); background:#fff; }
  .form-group textarea { resize:vertical; min-height:95px; }
  .upload-area { border:2px dashed #d0c8f0; border-radius:10px; padding:1.4rem; text-align:center; cursor:pointer; transition:border-color 0.2s,background 0.2s; background:var(--bg); }
  .upload-area:hover { border-color:var(--purple); background:var(--purple-pale); }
  .upload-area input { display:none; }
  .upload-area .icon { font-size:1.8rem; margin-bottom:0.3rem; }
  .upload-area p { font-size:0.8rem; color:var(--muted); }
  .upload-area span { color:var(--purple); font-weight:600; }
  .preview-img { width:75px; height:75px; object-fit:cover; border-radius:8px; margin-top:0.7rem; border:2px solid var(--purple-pale); }
  .btn-submit { width:100%; padding:0.9rem; background:var(--purple); color:#fff; border:none; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:1rem; font-weight:600; cursor:pointer; transition:background 0.2s,transform 0.15s; box-shadow:0 6px 18px rgba(123,47,190,0.3); }
  .btn-submit:hover { background:var(--purple-dark); transform:translateY(-2px); }
  @media(max-width:580px){ .form-row{grid-template-columns:1fr;} }
</style>

<?php require 'sidebar.php'; ?>
<main>
  <div class="page-bar">
    <h1><?= $edit_id?'✏ Edit':'➕ Add' ?> <span>Product</span></h1>
    <a href="products.php" style="font-size:0.85rem;color:var(--muted);">← Back to Products</a>
  </div>

  <?php if ($error): ?><div class="alert alert-error">⚠ <?= $error ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success">✓ <?= $success ?> <a href="products.php">View Products →</a></div><?php endif; ?>

  <div class="form-card">
    <form method="POST" action="" enctype="multipart/form-data">

      <div class="form-group">
        <label>Product Name *</label>
        <input type="text" name="name" placeholder="e.g. Whey Protein 2kg" value="<?= htmlspecialchars($product['name']??'') ?>" required/>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" placeholder="Describe this product..."><?= htmlspecialchars($product['description']??'') ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Price (TND) *</label>
          <input type="number" name="price" step="0.01" min="0" placeholder="0.00" value="<?= $product['price']??'' ?>" required/>
        </div>
        <div class="form-group">
          <label>Stock Quantity</label>
          <input type="number" name="stock" min="0" placeholder="0" value="<?= $product['stock']??0 ?>"/>
        </div>
      </div>

      <div class="form-group">
        <label>Category</label>
        <select name="category_id">
          <option value="">— No Category —</option>
          <?php mysqli_data_seek($cats,0); while ($cat=mysqli_fetch_assoc($cats)): ?>
            <option value="<?= $cat['id'] ?>" <?= (isset($product['category_id'])&&$product['category_id']==$cat['id'])?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Product Image</label>
        <label class="upload-area" for="imgInput">
          <input type="file" id="imgInput" name="image" accept="image/*" onchange="previewImage(this)"/>
          <div class="icon">📷</div>
          <p><span>Click to upload</span> — JPG, PNG, WEBP (max 5MB)</p>
          <?php if (!empty($product['image'])): ?>
            <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" class="preview-img" id="imgPreview"/>
          <?php else: ?>
            <img id="imgPreview" class="preview-img" style="display:none"/>
          <?php endif; ?>
        </label>
      </div>

      <button type="submit" class="btn-submit"><?= $edit_id?'💾 Save Changes':'➕ Add Product' ?></button>
    </form>
  </div>
</main>
  </div>
</div>

<script>
function previewImage(input) {
  const p = document.getElementById('imgPreview');
  if (input.files && input.files[0]) {
    const r = new FileReader();
    r.onload = e => { p.src=e.target.result; p.style.display='block'; };
    r.readAsDataURL(input.files[0]);
  }
}
</script>

<?php require '../includes/footer.php'; ?>
