<?php
// BrewCart — shared storefront header.
// Every public page sets $pageTitle, then includes this file.
// It prints: <head>, the announcement bar, navigation, and flash messages.
$storeName    = setting('store_name', 'BrewCart');
$primary      = setting('primary_color', '#6F4E37');
$accent       = setting('accent_color', '#C89B5A');
$logoPath     = setting('logo_path', '');
$announcement = setting('announcement', '');
$cartCount    = cart_count();
$title        = isset($pageTitle) ? $pageTitle . ' — ' . $storeName : $storeName;
$brandInitial = str_initial($storeName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?></title>
<link rel="stylesheet" href="assets/style.css">
<style>
  /* Brand colors come from the admin Settings page. */
  :root {
    --primary: <?php echo esc($primary); ?>;
    --accent: <?php echo esc($accent); ?>;
  }
</style>
</head>
<body>
<?php if ($announcement !== ''): ?>
<div class="announcement"><?php echo esc($announcement); ?></div>
<?php endif; ?>
<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="index.php">
      <?php if ($logoPath !== ''): ?>
        <img class="brand-logo" src="<?php echo esc($logoPath); ?>" alt="<?php echo esc($storeName); ?> logo">
      <?php else: ?>
        <span class="brand-mark"><?php echo esc($brandInitial); ?></span>
      <?php endif; ?>
      <span class="brand-name"><?php echo esc($storeName); ?></span>
    </a>
    <button class="nav-toggle" type="button" data-nav-toggle aria-label="Open menu">&#9776;</button>
    <nav class="site-nav" data-nav>
      <a href="index.php">Home</a>
      <a href="menu.php">Menu</a>
      <a href="track.php">Track Order</a>
      <a href="contact.php">Contact</a>
      <a href="cart.php" class="cart-link">Cart<?php if ($cartCount > 0): ?> <span class="cart-badge"><?php echo (int) $cartCount; ?></span><?php endif; ?></a>
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
