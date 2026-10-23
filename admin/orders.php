<?php
// BrewCart admin — order list + per-order detail with status updates.
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/payments.php';
$adminTitle = 'Orders';

$labels   = order_statuses();
$statuses = array_keys($labels);

// ---- status filter for the list ----
$filter = $_GET['status'] ?? '';
if ($filter !== '' && !in_array($filter, $statuses, true)) {
    $filter = '';
}

// ---- handle a status update (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'status') {
    csrf_check($_POST['csrf_token'] ?? null);
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if ($orderId > 0 && in_array($newStatus, $statuses, true)) {
        $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $orderId]);
        flash('success', 'Order status updated to "' . $labels[$newStatus] . '".');
    } else {
        flash('error', 'Invalid status change.');
    }
    redirect('orders.php?id=' . $orderId);
}

// ---- single order view ----
$order = null;
$orderItems = [];
$viewId = (int) ($_GET['id'] ?? 0);
if ($viewId > 0) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$viewId]);
    $order = $stmt->fetch();
    if ($order) {
        $istmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
        $istmt->execute([$viewId]);
        $orderItems = $istmt->fetchAll();
    }
}

// ---- order list ----
if ($filter !== '') {
    $stmt = db()->prepare('SELECT * FROM orders WHERE status = ? ORDER BY id DESC');
    $stmt->execute([$filter]);
} else {
    $stmt = db()->query('SELECT * FROM orders ORDER BY id DESC');
}
$orders = $stmt->fetchAll();

require __DIR__ . '/inc/header.php';
?>

<?php if ($order): ?>
  <p><a href="orders.php">&larr; Back to orders</a></p>
  <h1 class="page-title">Order <?php echo esc($order['order_number']); ?></h1>

  <div class="detail-grid">
    <div>
      <h3>Items</h3>
      <table class="admin-table">
        <thead><tr><th>Item</th><th>Unit price</th><th>Qty</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach ($orderItems as $oi): ?>
          <tr>
            <td><?php echo esc($oi['item_name']); ?><?php echo $oi['option_name'] !== '' ? ' <span class="muted">(' . esc($oi['option_name']) . ')</span>' : ''; ?></td>
            <td><?php echo money($oi['unit_price']); ?></td>
            <td><?php echo (int) $oi['quantity']; ?></td>
            <td><?php echo money($oi['unit_price'] * $oi['quantity']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <table class="detail-table">
        <tr><th>Subtotal</th><td class="num"><?php echo money($order['subtotal']); ?></td></tr>
        <tr><th>Delivery fee</th><td class="num"><?php echo money($order['delivery_fee']); ?></td></tr>
        <tr><th>Total</th><td class="num"><strong><?php echo money($order['total']); ?></strong></td></tr>
      </table>
      <?php if ($order['notes'] !== ''): ?>
        <h3>Customer notes</h3>
        <p><?php echo esc($order['notes']); ?></p>
      <?php endif; ?>
    </div>
    <div>
      <h3>Customer</h3>
      <table class="detail-table">
        <tr><th>Name</th><td><?php echo esc($order['customer_name']); ?></td></tr>
        <tr><th>Phone</th><td><?php echo esc($order['phone']); ?></td></tr>
        <?php if ($order['email'] !== ''): ?><tr><th>Email</th><td><?php echo esc($order['email']); ?></td></tr><?php endif; ?>
        <tr><th>Type</th><td><?php echo esc(ucfirst($order['order_type'])); ?></td></tr>
        <?php if ($order['address'] !== ''): ?><tr><th>Address</th><td><?php echo esc($order['address']); ?></td></tr><?php endif; ?>
        <tr><th>Payment</th><td><?php echo esc(payment_label($order['payment_method'])); ?></td></tr>
        <tr><th>Placed</th><td><?php echo esc(date('d M Y, h:i A', strtotime($order['created_at']))); ?></td></tr>
      </table>

      <h3>Update status</h3>
      <form method="post" action="orders.php" class="form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form" value="status">
        <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
        <div class="field">
          <label for="o-status">Status</label>
          <select id="o-status" name="status">
            <?php foreach ($labels as $code => $label): ?>
              <option value="<?php echo esc($code); ?>" <?php echo $order['status'] === $code ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Save status</button>
      </form>
    </div>
  </div>

<?php else: ?>
  <h1 class="page-title">Orders</h1>
  <div class="cat-pills">
    <a href="orders.php" class="<?php echo $filter === '' ? 'active' : ''; ?>">All</a>
    <?php foreach ($labels as $code => $label): ?>
      <a href="orders.php?status=<?php echo esc($code); ?>" class="<?php echo $filter === $code ? 'active' : ''; ?>"><?php echo esc($label); ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($orders): ?>
  <table class="admin-table">
    <thead><tr><th>Order</th><th>Customer</th><th>Type</th><th>Payment</th><th>Total</th><th>Status</th><th>Placed</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td><strong><?php echo esc($o['order_number']); ?></strong></td>
        <td><?php echo esc($o['customer_name']); ?><br><span class="muted small"><?php echo esc($o['phone']); ?></span></td>
        <td><?php echo esc(ucfirst($o['order_type'])); ?></td>
        <td class="muted small"><?php echo esc(payment_label($o['payment_method'])); ?></td>
        <td><?php echo money($o['total']); ?></td>
        <td><span class="status-pill status-<?php echo esc($o['status']); ?>"><?php echo esc($labels[$o['status']]); ?></span></td>
        <td class="muted small"><?php echo esc(date('d M, h:i A', strtotime($o['created_at']))); ?></td>
        <td><a href="orders.php?id=<?php echo (int) $o['id']; ?>">Open</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p class="muted">No orders found.</p>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
