<?php
// BrewCart — shared storefront header.
// Every public page sets $pageTitle, then includes this file.
// It prints: <head>, the announcement bar, navigation, and flash messages.
$storeName    = setting('store_name', 'BrewCart');
$tagline      = setting('tagline', '');
$primary      = setting('primary_color', '#6F4E37');
$accent       = setting('accent_color', '#C89B5A');
$logoPath     = setting('logo_path', '');
$announcement = setting('announcement', '');
$cartCount    = cart_count();
$title        = isset($pageTitle) ? $pageTitle . ' — ' . $storeName : $storeName;
$metaDesc     = $tagline !== '' ? $tagline : $storeName . ' — order coffee online for pickup or delivery.';
$brandInitial = str_initial($storeName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?></title>
<meta name="description" content="<?php echo esc($metaDesc); ?>">
<meta name="theme-color" content="<?php echo esc($primary); ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo esc($title); ?>">
<meta property="og:description" content="<?php echo esc($metaDesc); ?>">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cpath d='M24 6c-3 4 3 6 0 10M36 6c-3 4 3 6 0 10' stroke='%23C89B5A' stroke-width='3.5' fill='none' stroke-linecap='round'/%3E%3Cpath d='M12 24h34v9a13 13 0 0 1-13 13h-8a13 13 0 0 1-13-13z' fill='%236F4E37'/%3E%3Cpath d='M46 27h2a8 8 0 0 1 0 16h-3' fill='none' stroke='%236F4E37' stroke-width='4'/%3E%3Crect x='9' y='52' width='40' height='4' rx='2' fill='%236F4E37'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
