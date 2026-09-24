<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Product.php';
require_once __DIR__ . '/classes/Customer.php';
require_once __DIR__ . '/classes/OrderItem.php';
require_once __DIR__ . '/classes/Order.php';

$db = Database::getConnection();
$errors = [];
$successOrderId = null;

$products = Product::findAvailable($db, '', 0, 'name_asc', 100, 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $quantities = $_POST['quantity'] ?? [];

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($address === '') {
        $errors[] = 'Delivery address is required.';
    }

    $items = [];
    foreach ($quantities as $productId => $qty) {
        $qty = (int) $qty;
        if ($qty <= 0) {
            continue;
        }
        foreach ($products as $p) {
            if ((int) $p['product_id'] === (int) $productId) {
                $items[] = [
                    'product_id' => (int) $productId,
                    'name' => $p['product_name'],
                    'qty' => $qty,
                    'price' => (float) $p['price'],
                ];
                break;
            }
        }
    }
    if (count($items) === 0) {
        $errors[] = 'Select at least one item and a quantity greater than zero.';
    }

    if (empty($errors)) {
        try {
            $customer = new Customer();
            $customer->setFullName($fullName);
            $customer->setEmail($email);
            $customer->setPhone($phone);
            $customer->setAddress($address);
            $customerId = $customer->findOrCreate();

            $order = new Order();
            $order->setCustomerId($customerId);
            $order->setDeliveryAddress($address);
            foreach ($items as $it) {
                $order->addItem(new OrderItem($it['product_id'], $it['name'], $it['qty'], $it['price']));
            }
            $successOrderId = $order->placeOrder();
        } catch (InsufficientStockException $e) {
            $errors[] = $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'Something went wrong while placing your order. Please try again.';
        }
    }
}

$pageTitle = 'Place an order';
$activeNav = 'order';
$rootPath = '';
require __DIR__ . '/includes/header.php';
?>

<h1>Place an order</h1>

<?php if (!empty($errors)): ?>
  <div class="alert alert-error">
    <strong>Please fix the following:</strong>
    <ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<?php if ($successOrderId): ?>
  <div class="alert alert-success">
    Order placed! Your order number is <strong>#<?= $successOrderId ?></strong>.
    <a href="order_status.php?order_id=<?= $successOrderId ?>">Track this order</a>.
  </div>
<?php else: ?>

<form method="post" action="place_order.php">
  <label for="full_name">Full name</label>
  <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>

  <label for="email">Email</label>
  <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

  <label for="phone">Phone</label>
  <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

  <label for="address">Delivery address</label>
  <textarea id="address" name="address" rows="2" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

  <h2 style="margin-top: 28px;">Menu</h2>
  <table class="order-table">
    <tr><th>Item</th><th>Price</th><th>In stock</th><th>Qty</th></tr>
    <?php foreach ($products as $p): ?>
    <tr>
      <td><?= htmlspecialchars($p['product_name']) ?></td>
      <td>&#8369;<?= number_format((float) $p['price'], 2) ?></td>
      <td><?= (int) $p['stock_quantity'] ?></td>
      <td><input type="number" name="quantity[<?= (int) $p['product_id'] ?>]" min="0" max="<?= (int) $p['stock_quantity'] ?>" value="0"></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <p style="margin-top: 24px;"><button type="submit" class="btn">Place order</button></p>
</form>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
