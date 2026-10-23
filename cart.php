<?php
// BrewCart — shopping cart page.
// Also handles the add / update / remove actions posted from menu cards.
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Your Cart';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? null);
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $itemId   = (int) ($_POST['item_id'] ?? 0);
        $optionId = (int) ($_POST['option_id'] ?? 0);
        $qty      = max(1, min(20, (int) ($_POST['qty'] ?? 1)));

        // The item must exist and be available right now.
        $stmt = db()->prepare('SELECT id FROM menu_items WHERE id = ? AND is_available = 1');
        $stmt->execute([$itemId]);
        if (!$stmt->fetch()) {
            flash('error', 'Sorry, that item is not available.');
            redirect('menu.php');
        }
        // The option must belong to this item (prevents tampered forms).
        if ($optionId > 0) {
            $ostmt = db()->prepare('SELECT id FROM item_options WHERE id = ? AND item_id = ?');
            $ostmt->execute([$optionId, $itemId]);
            if (!$ostmt->fetch()) {
                $optionId = 0;
            }
        }

        cart_add($itemId, $optionId, $qty);
        flash('success', 'Added to your cart.');

        // Send the shopper back where they came from (strict allow-list).
        $returnTo = $_POST['return_to'] ?? 'menu.php';
        if (!preg_match('#^(index|menu|cart)\.php(\?cat=\d+)?$#', $returnTo)) {
            $returnTo = 'menu.php';
        }
        redirect($returnTo);
    }

    if (isset($_POST['remove_key'])) {
        // One row's Remove button was clicked (shares the update form, no nesting).
        $key = (string) $_POST['remove_key'];
        if (preg_match('#^\d+:\d+$#', $key)) {
            unset($_SESSION['cart'][$key]);
        }
        redirect('cart.php');
    }

    if ($action === 'update') {
        // Quantities arrive as qty["itemId:optionId"] = number.
        foreach ((array) ($_POST['qty'] ?? []) as $key => $q) {
            if (!preg_match('#^\d+:\d+$#', (string) $key)) {
                continue;
            }
            $q = (int) $q;
            if ($q <= 0) {
                unset($_SESSION['cart'][$key]);
            } else {
                $_SESSION['cart'][$key] = min(20, $q);
            }
        }
        flash('success', 'Cart updated.');
        redirect('cart.php');
    }
}

$lines    = cart_lines();
$subtotal = cart_subtotal();
$freeOver = (float) setting('free_delivery_over', '0');

require __DIR__ . '/includes/header.php';
?>

<section class="section narrow">
  <h1 class="page-title">Your cart</h1>

  <?php if (!$lines): ?>
    <p class="muted">Your cart is empty. <a href="menu.php">Browse the menu</a> to add something tasty.</p>
  <?php else: ?>
    <form method="post" action="cart.php">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="update">
      <table class="cart-table">
        <thead>
          <tr><th>Item</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($lines as $line): ?>
          <tr>
            <td>
              <strong><?php echo esc($line['name']); ?></strong>
              <?php if ($line['option_name'] !== ''): ?><br><span class="muted"><?php echo esc($line['option_name']); ?></span><?php endif; ?>
            </td>
            <td><?php echo money($line['unit_price']); ?></td>
            <td><input type="number" name="qty[<?php echo esc($line['key']); ?>]" value="<?php echo (int) $line['qty']; ?>" min="0" max="20" class="qty-input"></td>
            <td><?php echo money($line['line_total']); ?></td>
            <td>
              <button type="submit" name="remove_key" value="<?php echo esc($line['key']); ?>" class="link-danger">Remove</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="cart-actions">
        <button type="submit" class="btn btn-ghost">Update quantities</button>
      </div>
    </form>

    <div class="cart-summary">
      <div class="sum-row"><span>Subtotal</span><strong><?php echo money($subtotal); ?></strong></div>
      <?php if ($freeOver > 0): ?>
        <p class="muted small">
          <?php if ($subtotal >= $freeOver): ?>
            You have unlocked FREE delivery.
          <?php else: ?>
            Add <?php echo money($freeOver - $subtotal); ?> more for free delivery.
          <?php endif; ?>
        </p>
      <?php endif; ?>
      <p class="muted small">Delivery fee (if any) is calculated at checkout.</p>
      <a class="btn btn-primary btn-big" href="checkout.php">Proceed to checkout</a>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
