<?php
// BrewCart admin — category CRUD + sort order.
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$adminTitle = 'Categories';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? null);
    $form = $_POST['form'] ?? '';

    if ($form === 'save') {
        $catId = (int) ($_POST['category_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if (strlen($name) < 2 || strlen($name) > 80) {
            flash('error', 'Category name must be 2–80 characters.');
        } elseif ($catId > 0) {
            $stmt = db()->prepare('UPDATE categories SET name = ?, sort_order = ? WHERE id = ?');
            $stmt->execute([$name, $sortOrder, $catId]);
            flash('success', 'Category updated.');
        } else {
            $stmt = db()->prepare('INSERT INTO categories (name, sort_order) VALUES (?, ?)');
            $stmt->execute([$name, $sortOrder]);
            flash('success', 'Category added.');
        }
        redirect('categories.php');
    }

    if ($form === 'delete') {
        $catId = (int) ($_POST['category_id'] ?? 0);
        // Items keep existing; their category becomes empty (ON DELETE SET NULL).
        $stmt = db()->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$catId]);
        flash('success', 'Category deleted. Its items were kept without a category.');
        redirect('categories.php');
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editCat = null;
if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editCat = $stmt->fetch();
}

$cats = db()->query(
    'SELECT c.*, (SELECT COUNT(*) FROM menu_items mi WHERE mi.category_id = c.id) AS item_count
     FROM categories c ORDER BY c.sort_order, c.id'
)->fetchAll();

require __DIR__ . '/inc/header.php';
?>

<h1 class="page-title">Categories</h1>

<div class="detail-grid">
  <div>
    <h3><?php echo $editCat ? 'Edit category' : 'Add category'; ?></h3>
    <form method="post" action="categories.php" class="form">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="form" value="save">
      <input type="hidden" name="category_id" value="<?php echo $editCat ? (int) $editCat['id'] : 0; ?>">
      <div class="field">
        <label for="c-name">Name *</label>
        <input id="c-name" type="text" name="name" value="<?php echo esc($editCat['name'] ?? ''); ?>" maxlength="80" required>
      </div>
      <div class="field">
        <label for="c-sort">Sort order (lower shows first)</label>
        <input id="c-sort" type="number" name="sort_order" value="<?php echo (int) ($editCat['sort_order'] ?? 0); ?>">
      </div>
      <button type="submit" class="btn btn-primary">Save category</button>
      <?php if ($editCat): ?> <a class="btn btn-ghost" href="categories.php">Cancel</a><?php endif; ?>
    </form>
  </div>
  <div>
    <h3>All categories</h3>
    <?php if ($cats): ?>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Items</th><th>Order</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($cats as $c): ?>
        <tr>
          <td><strong><?php echo esc($c['name']); ?></strong></td>
          <td><?php echo (int) $c['item_count']; ?></td>
          <td><?php echo (int) $c['sort_order']; ?></td>
          <td class="row-actions">
            <a href="categories.php?edit=<?php echo (int) $c['id']; ?>">Edit</a>
            <form method="post" action="categories.php" class="inline-form" data-confirm="Delete the &quot;<?php echo esc($c['name']); ?>&quot; category? Its items will be kept.">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="form" value="delete">
              <input type="hidden" name="category_id" value="<?php echo (int) $c['id']; ?>">
              <button type="submit" class="link-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <p class="muted">No categories yet.</p>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
