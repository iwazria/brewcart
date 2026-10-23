<?php
// BrewCart admin — shared layout header.
// Pages include inc/auth.php, includes/db.php and includes/helpers.php first,
// then set $adminTitle before including this file.
$storeName = setting('store_name', 'BrewCart');
$primary   = setting('primary_color', '#6F4E37');
$accent    = setting('accent_color', '#C89B5A');
$title     = isset($adminTitle) ? $adminTitle . ' — Admin — ' . $storeName : 'Admin — ' . $storeName;
$current   = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?></title>
<link rel="stylesheet" href="../assets/style.css">
<style>
  :root {
    --primary: <?php echo esc($primary); ?>;
    --accent: <?php echo esc($accent); ?>;
  }
</style>
</head>
<body class="admin">
<header class="admin-header">
  <div class="wrap header-inner">
    <a class="brand" href="index.php"><span class="brand-mark"><?php echo esc(str_initial($storeName)); ?></span><span class="brand-name"><?php echo esc($storeName); ?> <small>admin</small></span></a>
    <button class="nav-toggle" type="button" data-nav-toggle aria-label="Open menu">&#9776;</button>
    <nav class="site-nav admin-nav" data-nav>
      <a href="index.php" class="<?php echo $current === 'index.php' ? 'active' : ''; ?>">Dashboard</a>
      <a href="orders.php" class="<?php echo $current === 'orders.php' ? 'active' : ''; ?>">Orders</a>
      <a href="items.php" class="<?php echo $current === 'items.php' ? 'active' : ''; ?>">Menu items</a>
      <a href="categories.php" class="<?php echo $current === 'categories.php' ? 'active' : ''; ?>">Categories</a>
      <a href="settings.php" class="<?php echo $current === 'settings.php' ? 'active' : ''; ?>">Settings</a>
      <a href="../index.php">View store</a>
      <a href="logout.php">Logout</a>
    </nav>
  </div>
</header>
<main class="wrap">
<?php foreach (take_flashes() as $f): ?>
  <div class="flash flash-<?php echo esc($f['type']); ?>">
    <span><?php echo esc($f['message']); ?></span>
    <button type="button" data-flash-close aria-label="Dismiss">&times;</button>
  </div>
<?php endforeach; ?>
