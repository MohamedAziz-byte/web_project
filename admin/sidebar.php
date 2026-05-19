<?php
// Get current page name to highlight active link
$current = basename($_SERVER['PHP_SELF']);

// Get counts for badges
require_once dirname(__DIR__) . '/config/db.php';
$pending_count   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders WHERE status='pending'"))[0];
$low_stock_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM products WHERE stock <= 5"))[0];
?>

<style>
  .admin-layout {
    display: flex;
    flex: 1;
    min-height: calc(100vh - 64px);
  }

  /* ══ SIDEBAR ══ */
  .sidebar {
    width: 240px;
    min-width: 240px;
    background: #12101a;
    display: flex;
    flex-direction: column;
    padding: 1.5rem 0;
    position: sticky;
    top: 64px;
    height: calc(100vh - 64px);
    overflow-y: auto;
    z-index: 50;
  }

  .sidebar::-webkit-scrollbar { width: 4px; }
  .sidebar::-webkit-scrollbar-thumb { background: rgba(123,47,190,0.4); border-radius: 4px; }

  .sidebar-brand {
    padding: 0 1.4rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    margin-bottom: 1rem;
  }

  .sidebar-brand p {
    font-size: 0.68rem;
    color: rgba(255,255,255,0.3);
    text-transform: uppercase;
    letter-spacing: 0.12em;
    margin-bottom: 0.25rem;
  }

  .sidebar-brand h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #fff;
  }

  .sidebar-brand h2 span { color: #7b2fbe; }

  .sidebar-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: rgba(255,255,255,0.22);
    padding: 0.9rem 1.4rem 0.35rem;
  }

  .sidebar-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.68rem 1.4rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: all 0.18s;
  }

  .sidebar-link:hover {
    background: rgba(123,47,190,0.12);
    color: rgba(255,255,255,0.9);
    border-left-color: rgba(123,47,190,0.5);
  }

  .sidebar-link.active {
    background: rgba(123,47,190,0.22);
    color: #fff;
    border-left-color: #7b2fbe;
    font-weight: 600;
  }

  .sidebar-link .icon {
    font-size: 1rem;
    width: 20px;
    text-align: center;
    flex-shrink: 0;
  }

  .sidebar-badge {
    margin-left: auto;
    background: #7b2fbe;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 0.15rem 0.5rem;
    border-radius: 20px;
    min-width: 20px;
    text-align: center;
  }

  .sidebar-badge.warn { background: #f59e0b; color: #12101a; }

  .sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,0.06);
    margin: 0.8rem 1.4rem;
  }

  .sidebar-footer {
    margin-top: auto;
    padding: 1rem 1.4rem;
    border-top: 1px solid rgba(255,255,255,0.06);
  }

  .s-user {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    margin-bottom: 0.75rem;
  }

  .s-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #7b2fbe;
    color: #fff;
    font-weight: 700;
    font-size: 0.85rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }

  .s-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: rgba(255,255,255,0.8);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
  }

  .s-role {
    font-size: 0.68rem;
    color: rgba(255,255,255,0.28);
  }

  .logout-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: rgba(255,255,255,0.3);
    text-decoration: none;
    transition: color 0.2s;
    padding: 0.25rem 0;
  }

  .logout-btn:hover { color: #ef4444; }

  /* ══ CONTENT ══ */
  .admin-content {
    flex: 1;
    overflow-x: hidden;
    background: #f8f7fc;
  }

  .admin-content main {
    padding: 2.5rem 2rem 5rem;
    max-width: 1060px;
    margin: 0 auto;
  }

  /* ══ MOBILE ══ */
  .sidebar-toggle {
    display: none;
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 200;
    width: 50px; height: 50px;
    border-radius: 50%;
    background: #7b2fbe;
    color: #fff;
    border: none;
    font-size: 1.3rem;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(123,47,190,0.5);
    align-items: center;
    justify-content: center;
  }

  .sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 49;
  }

  @media (max-width: 768px) {
    .sidebar {
      position: fixed;
      top: 0; left: 0;
      height: 100vh;
      transform: translateX(-100%);
      transition: transform 0.28s ease;
      z-index: 100;
    }
    .sidebar.open { transform: translateX(0); }
    .sidebar-toggle { display: flex; }
    .sidebar-overlay.show { display: block; }
  }
</style>

<!-- Mobile overlay + toggle -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>

<div class="admin-layout">

  <aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
      <p>Admin Panel</p>
      <h2>Pure<span>Gain</span></h2>
    </div>

    <!-- MAIN -->
    <div class="sidebar-label">Main</div>
    <a href="dashboard.php" class="sidebar-link <?= $current==='dashboard.php'?'active':'' ?>">
      <span class="icon">📊</span> Dashboard
    </a>

    <!-- CATALOG -->
    <div class="sidebar-label">Catalog</div>
    <a href="products.php" class="sidebar-link <?= $current==='products.php'?'active':'' ?>">
      <span class="icon">📦</span> Products
      <?php if ($low_stock_count > 0): ?>
        <span class="sidebar-badge warn"><?= $low_stock_count ?></span>
      <?php endif; ?>
    </a>
    <a href="add_product.php" class="sidebar-link <?= $current==='add_product.php'?'active':'' ?>">
      <span class="icon">➕</span> Add Product
    </a>

    <!-- SALES -->
    <div class="sidebar-label">Sales</div>
    <a href="orders.php" class="sidebar-link <?= $current==='orders.php'?'active':'' ?>">
      <span class="icon">🧾</span> Orders
      <?php if ($pending_count > 0): ?>
        <span class="sidebar-badge"><?= $pending_count ?></span>
      <?php endif; ?>
    </a>
    <a href="customers.php" class="sidebar-link <?= $current==='customers.php'?'active':'' ?>">
      <span class="icon">👥</span> Customers
    </a>

    <div class="sidebar-divider"></div>

    <a href="../index.php" class="sidebar-link">
      <span class="icon">🌐</span> View Store
    </a>

    <!-- FOOTER -->
    <div class="sidebar-footer">
      <div class="s-user">
        <div class="s-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        <div>
          <div class="s-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
          <div class="s-role">Administrator</div>
        </div>
      </div>
      <a href="../user/logout.php" class="logout-btn">🚪 Logout</a>
    </div>

  </aside>

  <!-- Content starts here — closed by footer.php -->
  <div class="admin-content">

<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>
