<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Product.php';

$db = Database::getConnection();

$search = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
$sort = $_GET['sort'] ?? 'name_asc';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

$categories = $db->query('SELECT * FROM categories ORDER BY category_name ASC')->fetchAll();
$products = Product::findAvailable($db, $search, $categoryId, $sort, $perPage, $offset);
$totalProducts = Product::countAvailable($db, $search, $categoryId);
$totalPages = max(1, (int) ceil($totalProducts / $perPage));

function buildUrl(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

$pageTitle = 'Menu';
$activeNav = 'menu';
$rootPath = '';
require __DIR__ . '/includes/header.php';
?>

<h1>Today's menu</h1>
<p class="subtitle">Search, filter by category, or sort by price — everything here updates live from the kitchen's stock.</p>

<form method="get" class="filters">
  <input type="text" name="q" placeholder="Search the menu" value="<?= htmlspecialchars($search) ?>">
  <select name="cat">
    <option value="0">All categories</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= (int) $c['category_id'] ?>" <?= $categoryId === (int) $c['category_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($c['category_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select name="sort">
    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (low to high)</option>
    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (high to low)</option>
  </select>
  <button type="submit" class="btn btn-secondary">Apply</button>
</form>

<?php if (empty($products)): ?>
  <div class="empty-state">No items match your search. Try clearing a filter.</div>
<?php else: ?>
<ul class="menu-list">
  <?php foreach ($products as $p): ?>
  <li>
    <div class="item-info">
      <span class="item-name"><?= htmlspecialchars($p['product_name']) ?></span>
      <span class="item-category"><?= htmlspecialchars($p['category_name']) ?></span>
    </div>
    <span class="leader"></span>
    <span class="item-price">&#8369;<?= number_format((float) $p['price'], 2) ?></span>
    <span class="item-stock">
      <?php if ((int) $p['stock_quantity'] === 0): ?>
        <span class="badge badge-out">Out of stock</span>
      <?php elseif ((int) $p['stock_quantity'] <= Product::lowStockThreshold()): ?>
        <span class="badge badge-low"><?= (int) $p['stock_quantity'] ?> left</span>
      <?php else: ?>
        <span class="badge badge-ok">In stock</span>
      <?php endif; ?>
    </span>
  </li>
  <?php endforeach; ?>
</ul>

<?php if ($totalPages > 1): ?>
<div class="pagination">
  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <?php if ($i === $page): ?>
      <span class="current"><?= $i ?></span>
    <?php else: ?>
      <a href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<p style="margin-top: 28px;">
  <a href="place_order.php" class="btn">Place an order</a>
</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
