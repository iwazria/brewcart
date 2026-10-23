<?php
// BrewCart — home page: hero, featured items, hours/location teaser.
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Home';

// Featured items for the home page (hand-picked by the owner in admin).
$stmt = db()->prepare(
    'SELECT mi.*, c.name AS category_name
     FROM menu_items mi
     LEFT JOIN categories c ON c.id = mi.category_id
     WHERE mi.is_featured = 1 AND mi.is_available = 1
     ORDER BY mi.sort_order, mi.id
     LIMIT 6'
);
$stmt->execute();
$featured = $stmt->fetchAll();

// Attach size/add-on options to each featured item (one query).
if ($featured) {
    $ids = array_column($featured, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $ostmt = db()->prepare("SELECT * FROM item_options WHERE item_id IN ($ph) ORDER BY sort_order, id");
    $ostmt->execute($ids);
    $byItem = [];
    foreach ($ostmt->fetchAll() as $opt) {
        $byItem[$opt['item_id']][] = $opt;
    }
    foreach ($featured as &$it) {
        $it['options'] = $byItem[$it['id']] ?? [];
    }
    unset($it);
}

require __DIR__ . '/includes/header.php';
$returnTo = 'index.php';
?>

<section class="hero">
  <div class="hero-inner">
    <p class="hero-kicker"><?php echo esc(setting('tagline', '')); ?></p>
    <h1><?php echo esc(setting('store_name', 'BrewCart')); ?></h1>
    <p class="hero-sub">Order your favorite coffee online — pickup or delivery, ready when you are.</p>
    <div class="hero-actions">
      <a class="btn btn-light" href="menu.php">View menu</a>
      <a class="btn btn-ghost-light" href="track.php">Track my order</a>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section">
  <div class="section-head">
    <h2>Customer favorites</h2>
    <a href="menu.php">See full menu &rarr;</a>
  </div>
  <div class="item-grid">
    <?php foreach ($featured as $item): ?>
      <?php require __DIR__ . '/includes/item-card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section info-cards">
  <div class="info-card">
    <h3>Opening hours</h3>
    <p><?php echo esc(setting('hours', '')); ?></p>
  </div>
  <div class="info-card">
    <h3>Find us</h3>
    <p><?php echo esc(setting('address', '')); ?><br><?php echo esc(setting('phone', '')); ?></p>
  </div>
  <div class="info-card">
    <h3>How ordering works</h3>
    <p>Pick your drinks, check out in a minute, and track your order live until it is ready.</p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
