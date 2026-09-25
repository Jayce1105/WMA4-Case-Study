<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';

$db = Database::getConnection();
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$order = null;
$items = [];

if ($orderId > 0) {
    $stmt = $db->prepare(
        'SELECT o.*, c.full_name, c.email
         FROM orders o
         JOIN customers c ON o.customer_id = c.customer_id
         WHERE o.order_id = :id'
    );
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $db->prepare(
            'SELECT oi.*, p.product_name
             FROM order_items oi
             JOIN products p ON oi.product_id = p.product_id
             WHERE oi.order_id = :id'
        );
        $itemStmt->execute(['id' => $orderId]);
        $items = $itemStmt->fetchAll();
    }
}

$pageTitle = 'Track order';
$activeNav = 'track';
$rootPath = '';
require __DIR__ . '/includes/header.php';
?>

<h1>Track your order</h1>
<p class="subtitle">Enter the order number you received after checkout.</p>

<form method="get" class="filters">
  <input type="number" name="order_id" placeholder="Order number" value="<?= $orderId ?: '' ?>">
  <button type="submit" class="btn btn-secondary">Check status</button>
</form>

<?php if ($orderId > 0 && !$order): ?>
  <div class="empty-state">No order found with that number.</div>
<?php elseif ($order): ?>
  <p>
    Order <strong>#<?= (int) $order['order_id'] ?></strong> for <?= htmlspecialchars($order['full_name']) ?>
    &nbsp;
    <span class="status-pill status-<?= htmlspecialchars($order['status']) ?>">
      <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['status']))) ?>
    </span>
  </p>
  <table class="order-table">
    <tr><th>Item</th><th>Qty</th><th>Unit price</th><th>Subtotal</th></tr>
    <?php foreach ($items as $it): ?>
    <tr>
      <td><?= htmlspecialchars($it['product_name']) ?></td>
      <td><?= (int) $it['quantity'] ?></td>
      <td>&#8369;<?= number_format((float) $it['unit_price'], 2) ?></td>
      <td>&#8369;<?= number_format((float) $it['quantity'] * (float) $it['unit_price'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <p style="margin-top: 16px;"><strong>Total: &#8369;<?= number_format((float) $order['total_amount'], 2) ?></strong></p>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
