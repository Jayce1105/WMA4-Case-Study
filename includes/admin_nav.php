<?php $activeAdminNav = $activeAdminNav ?? ''; ?>
<nav class="admin-sidebar">
  <a href="products.php" class="<?= $activeAdminNav === 'products' ? 'active' : '' ?>">Products</a>
  <a href="orders.php" class="<?= $activeAdminNav === 'orders' ? 'active' : '' ?>">Orders</a>
  <a href="dashboard.php" class="<?= $activeAdminNav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
</nav>
