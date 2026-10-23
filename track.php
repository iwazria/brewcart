<?php
// BrewCart — track an order. Requires BOTH the order number and the phone
// number used at checkout, so strangers cannot look up other people's orders.
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/payments.php';
$pageTitle = 'Track Order';

$order = null;
$orderItems = [];
$searched = false;
$orderNumberInput = '';
$phoneInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? null);
    $searched = true;
    $orderNumberInput = trim((string) ($_POST['order_number'] ?? ''));
    $phoneInput = trim((string) ($_POST['phone'] ?? ''));

    if ($orderNumberInput !== '' && $phoneInput !== '') {
        $stmt = db()->prepare('SELECT * FROM orders WHERE order_number = ? AND phone = ?');
        $stmt->execute([$orderNumberInput, $phoneInput]);
        $order = $stmt->fetch();
        if ($order) {
            $istmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
            $istmt->execute([$order['id']]);
            $orderItems = $istmt->fetchAll();
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="section narrow">
  <h1 class="page-title">Track your order</h1>
  <p class="muted">Enter your order number and the phone number you ordered with.</p>

  <form method="post" action="track.php" class="form inline-search">
    <?php echo csrf_field(); ?>
    <div class="field">
      <label for="t-number">Order number</label>
      <input id="t-number" type="text" name="order_number" value="<?php echo esc($orderNumberInput); ?>" placeholder="e.g. BC-261019-A3F9K2" required>
    </div>
    <div class="field">
      <label for="t-phone">Phone number</label>
      <input id="t-phone" type="tel" name="phone" value="<?php echo esc($phoneInput); ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Track</button>
  </form>

  <?php if ($searched && !$order): ?>
    <div class="flash flash-error"><span>No order found with that number and phone combination. Please check both and try again.</span></div>
  <?php endif; ?>

  <?php if ($order): ?>
    <?php
      $labels = order_statuses();
      $steps = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];
    ?>
    <div class="order-card">
      <div class="order-card-head">
        <div>
          <strong><?php echo esc($order['order_number']); ?></strong>
          <span class="muted"> &middot; <?php echo esc(date('d M Y, h:i A', strtotime($order['created_at']))); ?></span>
        </div>
        <span class="status-pill status-<?php echo esc($order['status']); ?>"><?php echo esc($labels[$order['status']]); ?></span>
      </div>

      <?php if ($order['status'] === 'cancelled'): ?>
        <p class="muted">This order was cancelled. Please contact the shop if you have questions.</p>
      <?php else: ?>
        <?php $currentIndex = array_search($order['status'], $steps, true); ?>
        <ol class="timeline">
          <?php foreach ($steps as $i => $s): ?>
            <li class="<?php echo $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : ''); ?>">
              <span class="dot"></span>
              <span><?php echo esc($labels[$s]); ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>

      <table class="detail-table">
        <?php foreach ($orderItems as $oi): ?>
          <tr>
            <td><?php echo (int) $oi['quantity']; ?> &times; <?php echo esc($oi['item_name']); ?><?php echo $oi['option_name'] !== '' ? ' (' . esc($oi['option_name']) . ')' : ''; ?></td>
            <td class="num"><?php echo money($oi['unit_price'] * $oi['quantity']); ?></td>
          </tr>
        <?php endforeach; ?>
        <tr><th>Subtotal</th><td class="num"><?php echo money($order['subtotal']); ?></td></tr>
        <tr><th>Delivery fee</th><td class="num"><?php echo money($order['delivery_fee']); ?></td></tr>
        <tr><th>Total</th><td class="num"><strong><?php echo money($order['total']); ?></strong></td></tr>
        <tr><th>Order type</th><td><?php echo esc(ucfirst($order['order_type'])); ?></td></tr>
        <tr><th>Payment</th><td><?php echo esc(payment_label($order['payment_method'])); ?></td></tr>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
