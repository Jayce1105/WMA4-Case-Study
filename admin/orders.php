<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Order.php';

$db = Database::getConnection();
$flashSuccess = null;
$flashError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $validStatuses = ['pending', 'preparing', 'out_for_delivery', 'completed', 'cancelled'];
    if (in_array($newStatus, $validStatuses, true)) {
        (new Order())->update($orderId, ['status' => $newStatus]);
        $flashSuccess = 'Order #' . $orderId . ' updated to ' . str_replace('_', ' ', $newStatus) . '.';
    } else {
        $flashError = 'Invalid status.';
    }
}

$status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'date_desc';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$orders = Order::findAll($db, $status, $search, $sort, $perPage, $offset);
$totalOrders = Order::countAll($db, $status, $search);
$totalPages = max(1, (int) ceil($totalOrders / $perPage));

function buildUrl(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

$statusLabels = [
    'pending' => 'Pending',
    'preparing' => 'Preparing',
    'out_for_delivery' => 'Out for delivery',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];

$pageTitle = 'Orders';
$activeNav = 'admin';
$rootPath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="admin-shell">
  <?php $activeAdminNav = 'orders'; require __DIR__ . '/../includes/admin_nav.php'; ?>
  <div class="admin-content">
    <h1>Orders</h1>

    <?php if ($flashSuccess): ?><div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div><?php endif; ?>
    <?php if ($flashError): ?><div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>

    <form method="get" class="filters">
      <input type="text" name="q" placeholder="Search by customer or order #" value="<?= htmlspecialchars($search) ?>">
      <select name="status">
        <option value="">All statuses</option>
        <?php foreach ($statusLabels as $key => $label): ?>
          <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <select name="sort">
        <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest first</option>
        <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oldest first</option>
        <option value="total_desc" <?= $sort === 'total_desc' ? 'selected' : '' ?>>Highest total</option>
        <option value="total_asc" <?= $sort === 'total_asc' ? 'selected' : '' ?>>Lowest total</option>
      </select>
      <button type="submit" class="btn btn-secondary">Apply</button>
    </form>

    <?php if (empty($orders)): ?>
      <div class="empty-state">No orders match those filters.</div>
    <?php else: ?>
    <table class="admin-table">
      <tr><th>#</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Update</th></tr>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= (int) $o['order_id'] ?></td>
        <td><?= htmlspecialchars($o['full_name']) ?></td>
        <td><?= htmlspecialchars(date('M j, Y g:ia', strtotime($o['order_date']))) ?></td>
        <td>&#8369;<?= number_format((float) $o['total_amount'], 2) ?></td>
        <td><span class="status-pill status-<?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars($statusLabels[$o['status']] ?? $o['status']) ?></span></td>
        <td>
          <form method="post" class="status-select-form">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?= (int) $o['order_id'] ?>">
            <select name="status">
              <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= $o['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-small btn-secondary">Update</button>
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
