<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Product.php';

$db = Database::getConnection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];
$saved = false;

$categories = $db->query('SELECT * FROM categories ORDER BY category_name ASC')->fetchAll();

$product = new Product($isEdit ? $id : null);
if ($isEdit && $product->getId() === null) {
    $errors[] = 'Product not found.';
    $isEdit = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['product_name'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock_quantity'] ?? 0);
    $isAvailable = isset($_POST['is_available']);

    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if ($categoryId <= 0) {
        $errors[] = 'Choose a category.';
    }
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero.';
    }
    if ($stock < 0) {
        $errors[] = 'Stock cannot be negative.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $product->update($id, [
                'category_id' => $categoryId,
                'product_name' => $name,
                'description' => $description,
                'price' => $price,
                'stock_quantity' => $stock,
                'is_available' => $isAvailable ? 1 : 0,
            ]);
            $saved = true;
        } else {
            $newProduct = new Product();
            $newProduct->setCategoryId($categoryId);
            $newProduct->setName($name);
            $newProduct->setDescription($description);
            $newProduct->setPrice($price);
            $newProduct->setStock($stock);
            $newProduct->setIsAvailable($isAvailable);
            if ($newProduct->create()) {
                $saved = true;
                $isEdit = true;
                $product = $newProduct;
            } else {
                $errors[] = 'Could not save the product. Please check the values and try again.';
            }
        }
    }

    if (!empty($errors)) {

        $product->setCategoryId($categoryId);
        $product->setName($name);
        $product->setDescription($description);
        $product->setPrice($price);
        $product->setStock($stock);
        $product->setIsAvailable($isAvailable);
    }
}

$pageTitle = $isEdit ? 'Edit product' : 'Add product';
$activeNav = 'admin';
$rootPath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="admin-shell">
  <?php $activeAdminNav = 'products'; require __DIR__ . '/../includes/admin_nav.php'; ?>
  <div class="admin-content">
    <h1><?= $isEdit ? 'Edit product' : 'Add product' ?></h1>

    <?php if ($saved): ?>
      <div class="alert alert-success">Saved. <a href="products.php">Back to products</a></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <strong>Please fix the following:</strong>
        <ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" class="form-card">
      <label for="product_name">Name</label>
      <input type="text" id="product_name" name="product_name" value="<?= htmlspecialchars($product->getName()) ?>" required>

      <label for="category_id">Category</label>
      <select id="category_id" name="category_id" required>
        <option value="">Choose a category</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['category_id'] ?>" <?= $product->getCategoryId() === (int) $c['category_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="description">Description</label>
      <textarea id="description" name="description" rows="2"><?= htmlspecialchars($product->getDescription()) ?></textarea>

      <label for="price">Price (&#8369;)</label>
      <input type="number" id="price" name="price" min="0" step="0.01" value="<?= htmlspecialchars((string) $product->getPrice()) ?>" required>

      <label for="stock_quantity">Stock quantity</label>
      <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?= htmlspecialchars((string) $product->getStock()) ?>" required>

      <div class="checkbox-row">
        <input type="checkbox" id="is_available" name="is_available" <?= $product->isAvailable() ? 'checked' : '' ?>>
        <label for="is_available">Visible on the customer menu</label>
      </div>

      <p style="margin-top: 22px;">
        <button type="submit" class="btn"><?= $isEdit ? 'Save changes' : 'Add product' ?></button>
        <a href="products.php" class="btn btn-secondary" style="margin-left: 8px;">Cancel</a>
      </p>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
