<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Product.php';

$db = Database::getConnection();
$flashSuccess = null;
$flashError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = (int) ($_POST['product_id'] ?? 0);
    try {
        (new Product())->delete($deleteId);
        $flashSuccess = 'Product deleted.';
    } catch (PDOException $e) {
        $flashError = "This product can't be deleted because it has existing orders. Mark it unavailable instead.";
    }
}

$search = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
$sort = $_GET['sort'] ?? 'name_asc';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

$categories = $db->query('SELECT * FROM categories ORDER BY category_name ASC')->fetchAll();
$products = Product::findAllForAdmin($db, $search, $categoryId, $sort, $perPage, $offset);
$totalProducts = Product::countAllForAdmin($db, $search, $categoryId);
$totalPages = max(1, (int) ceil($totalProducts / $perPage));

function buildUrl(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

$pageTitle = 'Products';
$activeNav = 'admin';
$rootPath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="admin-shell">
  <?php $activeAdminNav = 'products'; require __DIR__ . '/../includes/admin_nav.php'; ?>
  <div class="admin-content">
    <div class="admin-toolbar">
      <h1 style="margin:0;">Products</h1>
      <a href="product_form.php" class="btn">Add product</a>
    </div>

    <?php if ($flashSuccess): ?><div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div><?php endif; ?>
    <?php if ($flashError): ?><div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>

    <form method="get" class="filters">
      <input type="text" name="q" placeholder="Search products" value="<?= htmlspecialchars($search) ?>">
      <select name="cat">
        <option value="0">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['category_id'] ?>" <?= $categoryId === (int) $c['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="sort">
        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (low to high)</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (high to low)</option>
        <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>Stock (low to high)</option>
        <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>Stock (high to low)</option>
      </select>
      <button type="submit" class="btn btn-secondary">Apply</button>
    </form>

    <?php if (empty($products)): ?>
      <div class="empty-state">No products match those filters.</div>
    <?php else: ?>
    <table class="admin-table">
      <tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr>
      <?php foreach ($products as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['product_name']) ?></td>
        <td><?= htmlspecialchars($p['category_name']) ?></td>
        <td>&#8369;<?= number_format((float) $p['price'], 2) ?></td>
        <td>
          <?= (int) $p['stock_quantity'] ?>
          <?php if ((int) $p['stock_quantity'] === 0): ?>
            <span class="badge badge-out">Out</span>
          <?php elseif ((int) $p['stock_quantity'] <= Product::lowStockThreshold()): ?>
            <span class="badge badge-low">Low</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ((int) $p['is_available'] === 1): ?>
            <span class="badge badge-ok">Visible</span>
          <?php else: ?>
            <span class="badge badge-out">Hidden</span>
          <?php endif; ?>
        </td>
        <td class="row-actions">
          <a href="product_form.php?id=<?= (int) $p['product_id'] ?>" class="btn btn-small btn-secondary">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this product?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" value="<?= (int) $p['product_id'] ?>">
            <button type="submit" class="btn btn-small btn-danger">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>

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
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
