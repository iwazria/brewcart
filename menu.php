<?php
// BrewCart — menu page with category filter (?cat=id).
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Menu';

// All categories for the filter pills.
$categories = db()->query('SELECT id, name FROM categories ORDER BY sort_order, id')->fetchAll();
$catIds = array_map('intval', array_column($categories, 'id')); // PDO returns strings; cast for strict compare

// Read the requested category; fall back to "all" when it is invalid.
$catId = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
if ($catId !== 0 && !in_array($catId, $catIds, true)) {
    $catId = 0;
}

// Fetch the visible items (only ones marked available).
if ($catId > 0) {
    $stmt = db()->prepare(
        'SELECT mi.*, c.name AS category_name
         FROM menu_items mi
         LEFT JOIN categories c ON c.id = mi.category_id
         WHERE mi.is_available = 1 AND mi.category_id = ?
         ORDER BY mi.sort_order, mi.id'
    );
    $stmt->execute([$catId]);
} else {
    $stmt = db()->query(
        'SELECT mi.*, c.name AS category_name
         FROM menu_items mi
         LEFT JOIN categories c ON c.id = mi.category_id
         WHERE mi.is_available = 1
         ORDER BY c.sort_order, c.id, mi.sort_order, mi.id'
    );
}
$items = $stmt->fetchAll();

// Attach options in one extra query.
if ($items) {
    $ids = array_column($items, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $ostmt = db()->prepare("SELECT * FROM item_options WHERE item_id IN ($ph) ORDER BY sort_order, id");
    $ostmt->execute($ids);
    $byItem = [];
    foreach ($ostmt->fetchAll() as $opt) {
        $byItem[$opt['item_id']][] = $opt;
    }
    foreach ($items as &$it) {
        $it['options'] = $byItem[$it['id']] ?? [];
    }
    unset($it);
}

require __DIR__ . '/includes/header.php';
$returnTo = 'menu.php' . ($catId > 0 ? '?cat=' . $catId : '');
?>

<section class="section">
  <h1 class="page-title">Our menu</h1>

  <?php if ($categories): ?>
  <div class="cat-pills">
    <a href="menu.php" class="<?php echo $catId === 0 ? 'active' : ''; ?>">All</a>
    <?php foreach ($categories as $cat): ?>
      <a href="menu.php?cat=<?php echo (int) $cat['id']; ?>" class="<?php echo $catId === (int) $cat['id'] ? 'active' : ''; ?>"><?php echo esc($cat['name']); ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($items): ?>
    <div class="item-grid">
      <?php foreach ($items as $item): ?>
        <?php require __DIR__ . '/includes/item-card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted">No items here yet — check back soon.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
