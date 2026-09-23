<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Product.php';

$db = Database::getConnection();

$pendingCount = (int) $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

$todayRevenue = (float) $db->query(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(order_date) = CURDATE() AND status != 'cancelled'"
)->fetchColumn();

$totalProducts = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();

$lowStockThreshold = Product::lowStockThreshold();
$lowStockStmt = $db->prepare(
    'SELECT product_name, stock_quantity FROM products
     WHERE stock_quantity <= :threshold AND is_available = 1
     ORDER BY stock_quantity ASC
     LIMIT 5'
);
$lowStockStmt->execute(['threshold' => $lowStockThreshold]);
$lowStockItems = $lowStockStmt->fetchAll();
$lowStockCount = count($lowStockItems);

$topSellers = $db->query(
    'SELECT p.product_name, SUM(oi.quantity) AS total_sold
     FROM order_items oi
     JOIN products p ON oi.product_id = p.product_id
     GROUP BY oi.product_id, p.product_name
     ORDER BY total_sold DESC
     LIMIT 5'
)->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'admin';
$rootPath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="admin-shell">
  <?php $activeAdminNav = 'dashboard'; require __DIR__ . '/../includes/admin_nav.php'; ?>
  <div class="admin-content">
    <h1>Dashboard</h1>
    <p class="subtitle">A quick read on what's happening right now.</p>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-value"><?= $pendingCount ?></div>
        <div class="stat-label">Pending orders</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">&#8369;<?= number_format($todayRevenue, 2) ?></div>
        <div class="stat-label">Revenue today</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $totalProducts ?></div>
        <div class="stat-label">Total products</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $lowStockCount ?></div>
        <div class="stat-label">Low stock items</div>
      </div>
    </div>

    <h2>Top sellers</h2>
    <?php if (empty($topSellers)): ?>
      <div class="empty-state">No sales yet.</div>
    <?php else: ?>
    <ul class="mini-list">
      <?php foreach ($topSellers as $s): ?>
        <li><span><?= htmlspecialchars($s['product_name']) ?></span><span><?= (int) $s['total_sold'] ?> sold</span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <h2 style="margin-top: 28px;">Running low</h2>
    <?php if (empty($lowStockItems)): ?>
      <div class="empty-state">Everything's well stocked.</div>
    <?php else: ?>
    <ul class="mini-list">
      <?php foreach ($lowStockItems as $item): ?>
        <li><span><?= htmlspecialchars($item['product_name']) ?></span><span class="badge badge-low"><?= (int) $item['stock_quantity'] ?> left</span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
