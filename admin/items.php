<?php
// BrewCart admin — menu item CRUD, including size/add-on options per item.
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$adminTitle = 'Menu items';

$categories = db()->query('SELECT id, name FROM categories ORDER BY sort_order, id')->fetchAll();
$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);

// ---------------- POST handlers ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? null);
    $form = $_POST['form'] ?? '';

    if ($form === 'save') {
        $itemId      = (int) ($_POST['item_id'] ?? 0);
        $name        = trim((string) ($_POST['name'] ?? ''));
        $categoryId  = (int) ($_POST['category_id'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        $price       = $_POST['price'] ?? '';
        $image       = trim((string) ($_POST['image'] ?? ''));
        $sortOrder   = (int) ($_POST['sort_order'] ?? 0);
        $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;

        $errors = [];
        if (strlen($name) < 2 || strlen($name) > 120) {
            $errors[] = 'Name must be 2–120 characters.';
        }
        if (!is_numeric($price) || (float) $price < 0) {
            $errors[] = 'Price must be a number, 0 or more.';
        }
        if (strlen($image) > 255) {
            $errors[] = 'Image URL is too long (max 255 characters).';
        }
        $catOk = ($categoryId === 0);
        foreach ($categories as $c) {
            if ((int) $c['id'] === $categoryId) {
                $catOk = true;
            }
        }
        if (!$catOk) {
            $errors[] = 'Unknown category.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('items.php?action=' . ($itemId > 0 ? 'edit&id=' . $itemId : 'add'));
        }

        $catValue = $categoryId > 0 ? $categoryId : null;
        if ($itemId > 0) {
            $stmt = db()->prepare(
                'UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, image = ?,
                 is_featured = ?, is_available = ?, sort_order = ? WHERE id = ?'
            );
            $stmt->execute([$catValue, $name, $description, (float) $price, $image, $isFeatured, $isAvailable, $sortOrder, $itemId]);
            flash('success', 'Item updated.');
            redirect('items.php?action=edit&id=' . $itemId);
        } else {
            $stmt = db()->prepare(
                'INSERT INTO menu_items (category_id, name, description, price, image, is_featured, is_available, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$catValue, $name, $description, (float) $price, $image, $isFeatured, $isAvailable, $sortOrder]);
            flash('success', 'Item added.');
            redirect('items.php');
        }
    }

    if ($form === 'delete') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        // Options are removed automatically (ON DELETE CASCADE).
        $stmt = db()->prepare('DELETE FROM menu_items WHERE id = ?');
        $stmt->execute([$itemId]);
        flash('success', 'Item deleted.');
        redirect('items.php');
    }

    if ($form === 'option_add') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $optName = trim((string) ($_POST['option_name'] ?? ''));
        $delta = $_POST['price_delta'] ?? '0';
        if ($itemId > 0 && strlen($optName) >= 2 && is_numeric($delta)) {
            $stmt = db()->prepare('INSERT INTO item_options (item_id, name, price_delta) VALUES (?, ?, ?)');
            $stmt->execute([$itemId, $optName, (float) $delta]);
            flash('success', 'Option added.');
        } else {
            flash('error', 'Option needs a name and a numeric price change.');
        }
        redirect('items.php?action=edit&id=' . $itemId);
    }

    if ($form === 'option_delete') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $optId = (int) ($_POST['option_id'] ?? 0);
        $stmt = db()->prepare('DELETE FROM item_options WHERE id = ? AND item_id = ?');
        $stmt->execute([$optId, $itemId]);
        flash('success', 'Option deleted.');
        redirect('items.php?action=edit&id=' . $itemId);
    }
}

// ---------------- data for the views ----------------
$item = null;
$options = [];
if (($action === 'edit') && $id > 0) {
    $stmt = db()->prepare('SELECT * FROM menu_items WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        flash('error', 'Item not found.');
        redirect('items.php');
    }
    $ostmt = db()->prepare('SELECT * FROM item_options WHERE item_id = ? ORDER BY sort_order, id');
    $ostmt->execute([$id]);
    $options = $ostmt->fetchAll();
}

$items = db()->query(
    'SELECT mi.*, c.name AS category_name
     FROM menu_items mi
     LEFT JOIN categories c ON c.id = mi.category_id
     ORDER BY c.sort_order, c.id, mi.sort_order, mi.id'
)->fetchAll();

require __DIR__ . '/inc/header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
  <p><a href="items.php">&larr; Back to items</a></p>
  <h1 class="page-title"><?php echo $item ? 'Edit item' : 'Add item'; ?></h1>

  <form method="post" action="items.php" class="form">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="form" value="save">
    <input type="hidden" name="item_id" value="<?php echo $item ? (int) $item['id'] : 0; ?>">
    <div class="field-row">
      <div class="field">
        <label for="i-name">Name *</label>
        <input id="i-name" type="text" name="name" value="<?php echo esc($item['name'] ?? ''); ?>" maxlength="120" required>
      </div>
      <div class="field">
        <label for="i-cat">Category</label>
        <select id="i-cat" name="category_id">
          <option value="0">— No category —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo (int) $c['id']; ?>" <?php echo ($item && (int) $item['category_id'] === (int) $c['id']) ? 'selected' : ''; ?>><?php echo esc($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label for="i-desc">Description</label>
      <textarea id="i-desc" name="description" rows="3"><?php echo esc($item['description'] ?? ''); ?></textarea>
    </div>
    <div class="field">
      <label for="i-image">Image URL</label>
      <input id="i-image" type="text" name="image" value="<?php echo esc($item['image'] ?? ''); ?>" maxlength="255" placeholder="https://… (leave empty for a letter tile)">
    </div>
    <div class="field-row">
      <div class="field">
        <label for="i-price">Price *</label>
        <input id="i-price" type="number" name="price" step="0.01" min="0" value="<?php echo esc($item['price'] ?? ''); ?>" required>
      </div>
      <div class="field">
        <label for="i-sort">Sort order</label>
        <input id="i-sort" type="number" name="sort_order" value="<?php echo (int) ($item['sort_order'] ?? 0); ?>">
      </div>
    </div>
    <div class="field-row">
      <label class="check"><input type="checkbox" name="is_featured" value="1" <?php echo ($item && $item['is_featured']) ? 'checked' : ''; ?>> Featured on home page</label>
      <label class="check"><input type="checkbox" name="is_available" value="1" <?php echo (!$item || $item['is_available']) ? 'checked' : ''; ?>> Available for ordering</label>
    </div>
    <button type="submit" class="btn btn-primary">Save item</button>
  </form>

  <?php if ($item): ?>
    <h2>Size / add-on options</h2>
    <p class="muted small">Example: "Regular" (+0) and "Large" (+80). Shoppers pick one when adding the item.</p>
    <?php if ($options): ?>
    <table class="admin-table">
      <thead><tr><th>Option</th><th>Price change</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($options as $opt): ?>
        <tr>
          <td><?php echo esc($opt['name']); ?></td>
          <td><?php echo ((float) $opt['price_delta'] >= 0 ? '+' : '') . money($opt['price_delta']); ?></td>
          <td>
            <form method="post" action="items.php" data-confirm="Delete this option?">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="form" value="option_delete">
              <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
              <input type="hidden" name="option_id" value="<?php echo (int) $opt['id']; ?>">
              <button type="submit" class="link-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
    <form method="post" action="items.php" class="form inline-search">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="form" value="option_add">
      <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
      <div class="field"><label>Option name</label><input type="text" name="option_name" placeholder="e.g. Large" required></div>
      <div class="field"><label>Price change</label><input type="number" name="price_delta" step="0.01" value="0" required></div>
      <button type="submit" class="btn btn-ghost">Add option</button>
    </form>
  <?php endif; ?>

<?php else: ?>
  <div class="page-head">
    <h1 class="page-title">Menu items</h1>
    <a class="btn btn-primary" href="items.php?action=add">Add item</a>
  </div>
  <?php if ($items): ?>
  <table class="admin-table">
    <thead><tr><th>Item</th><th>Category</th><th>Price</th><th>Flags</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><strong><?php echo esc($it['name']); ?></strong></td>
        <td class="muted"><?php echo esc($it['category_name'] ?? '—'); ?></td>
        <td><?php echo money($it['price']); ?></td>
        <td>
          <?php if ($it['is_featured']): ?><span class="status-pill status-confirmed">Featured</span><?php endif; ?>
          <?php if (!$it['is_available']): ?><span class="status-pill status-cancelled">Hidden</span><?php endif; ?>
        </td>
        <td class="row-actions">
          <a href="items.php?action=edit&id=<?php echo (int) $it['id']; ?>">Edit</a>
          <form method="post" action="items.php" class="inline-form" data-confirm="Delete &quot;<?php echo esc($it['name']); ?>&quot; and its options?">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="form" value="delete">
            <input type="hidden" name="item_id" value="<?php echo (int) $it['id']; ?>">
            <button type="submit" class="link-danger">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p class="muted">No items yet. <a href="items.php?action=add">Add the first one</a>.</p>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
