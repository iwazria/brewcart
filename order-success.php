<?php
// BrewCart — order confirmation page.
// The order number comes from the session (set at checkout), never from the URL,
// so visitors cannot guess other people's order numbers by changing the address.
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/payments.php';
$pageTitle = 'Order confirmed';

$orderNumber = $_SESSION['last_order_number'] ?? null;
$order = null;
if ($orderNumber) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE order_number = ?');
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();
}

require __DIR__ . '/includes/header.php';
?>

<section class="section narrow center">
  <?php if ($order): ?>
    <div class="success-hero">
      <div class="success-check">&#10003;</div>
      <h1>Thank you, <?php echo esc($order['customer_name']); ?>!</h1>
      <p>Your order has been received and is being prepared.</p>
    </div>
    <div class="order-number-box">
      <span>Your order number</span>
      <strong><?php echo esc($order['order_number']); ?></strong>
      <small>Save this number — you need it (plus your phone number) to track your order.</small>
    </div>
    <table class="detail-table">
      <tr><th>Order type</th><td><?php echo esc(ucfirst($order['order_type'])); ?></td></tr>
      <tr><th>Payment</th><td><?php echo esc(payment_label($order['payment_method'])); ?></td></tr>
      <tr><th>Total</th><td><strong><?php echo money($order['total']); ?></strong></td></tr>
    </table>
    <?php if ($order['payment_method'] === 'bank_transfer' && setting('bank_transfer_info', '') !== ''): ?>
      <div class="notice">
        <strong>Bank transfer details</strong>
        <p><?php echo esc(setting('bank_transfer_info', '')); ?></p>
      </div>
    <?php endif; ?>
    <div class="btn-row">
      <a class="btn btn-primary" href="track.php">Track my order</a>
      <a class="btn btn-ghost" href="menu.php">Back to menu</a>
    </div>
  <?php else: ?>
    <h1>No recent order found</h1>
    <p class="muted">If you just placed an order, it may have expired from this page. You can still track it with your order number.</p>
    <div class="btn-row">
      <a class="btn btn-primary" href="track.php">Track an order</a>
      <a class="btn btn-ghost" href="menu.php">Browse menu</a>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
