<?php
/**
 * Shared page header. Including pages should set, before requiring this file:
 *   $pageTitle  - string shown in the browser tab
 *   $activeNav  - one of 'menu' | 'order' | 'track' | 'admin' (for the active nav underline)
 *   $rootPath   - '' for pages at the project root, '../' for pages inside admin/
 */
require_once __DIR__ . '/../config/app.php';

$pageTitle = $pageTitle ?? SITE_NAME;
$activeNav = $activeNav ?? '';
$rootPath = $rootPath ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $rootPath ?>assets/css/style.css">
</head>
<body>
<header class="site-header">
  <a class="wordmark" href="<?= $rootPath ?>index.php"><?= htmlspecialchars(SITE_NAME) ?></a>
  <nav class="site-nav">
    <a href="<?= $rootPath ?>index.php" class="<?= $activeNav === 'menu' ? 'active' : '' ?>">Menu</a>
    <a href="<?= $rootPath ?>place_order.php" class="<?= $activeNav === 'order' ? 'active' : '' ?>">Order</a>
    <a href="<?= $rootPath ?>order_status.php" class="<?= $activeNav === 'track' ? 'active' : '' ?>">Track order</a>
    <a href="<?= $rootPath ?>admin/dashboard.php" class="nav-business <?= $activeNav === 'admin' ? 'active' : '' ?>">Business side</a>
  </nav>
</header>
<main class="site-main">
