<?php
// BrewCart — checkout: customer details, order type, payment; creates the order.
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/payments.php';
$pageTitle = 'Checkout';

$lines = cart_lines();
if (!$lines) {
    flash('info', 'Your cart is empty.');
    redirect('cart.php');
}
$subtotal = cart_subtotal();

$errors = [];
$input = [
    'name' => '', 'phone' => '', 'email' => '',
    'order_type' => 'pickup', 'address' => '',
    'payment_method' => '', 'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? null);
    foreach ($input as $k => $v) {
        $input[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    // ---- server-side validation ----
    if (strlen($input['name']) < 2 || strlen($input['name']) > 120) {
        $errors['name'] = 'Please enter your name.';
    }
    $digits = preg_replace('/\D/', '', $input['phone']);
    if (strlen($digits) < 6 || strlen($digits) > 20) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    if ($input['email'] !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address (or leave it empty).';
    }
    if (!in_array($input['order_type'], ['pickup', 'delivery'], true)) {
        $errors['order_type'] = 'Please choose pickup or delivery.';
    }
    if ($input['order_type'] === 'delivery' && strlen($input['address']) < 5) {
        $errors['address'] = 'Please enter your delivery address.';
    }
    $methods = available_payment_methods($input['order_type']);
    if (!isset($methods[$input['payment_method']])) {
        $errors['payment_method'] = 'Please choose a payment method.';
    }

    // ---- save the order inside a transaction ----
    if (!$errors) {
        $deliveryFee = delivery_fee_for($subtotal, $input['order_type']);
        $total       = $subtotal + $deliveryFee;
        $orderNumber = order_number();

        $pdo = db();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO orders
                 (order_number, customer_name, phone, email, order_type, address,
                  payment_method, subtotal, delivery_fee, total, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $orderNumber,
                $input['name'],
                $input['phone'],
                $input['email'],
                $input['order_type'],
                $input['order_type'] === 'delivery' ? $input['address'] : '',
                $input['payment_method'],
                $subtotal,
                $deliveryFee,
                $total,
                $input['notes'],
            ]);
            $orderId = (int) $pdo->lastInsertId();

            // Snapshot each line so the order never changes if the menu does.
            $istmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, item_name, option_name, unit_price, quantity)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($lines as $l) {
                $istmt->execute([$orderId, $l['name'], $l['option_name'], $l['unit_price'], $l['qty']]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = 'Something went wrong while saving your order. Please try again.';
        }
    }

    // ---- optional admin email alert (best effort, never breaks checkout) ----
    if (!$errors) {
        if (setting('order_email_enabled', '0') === '1'
            && filter_var(setting('admin_email', ''), FILTER_VALIDATE_EMAIL)) {
            try {
                $subject = 'New order ' . $orderNumber . ' — ' . setting('store_name', 'BrewCart');
                $body = "A new order was placed:\n\n"
                      . "Order:  " . $orderNumber . "\n"
                      . "Name:   " . $input['name'] . "\n"
                      . "Phone:  " . $input['phone'] . "\n"
                      . "Type:   " . $input['order_type'] . "\n"
                      . "Total:  " . money($total) . "\n";
                @mail(setting('admin_email'), $subject, $body);
            } catch (Throwable $t) {
                // Email is a courtesy — the order itself is already saved.
            }
        }

        cart_clear();
        $_SESSION['last_order_number'] = $orderNumber;
        redirect('order-success.php');
    }
}

// Payment methods shown for the currently selected order type.
$methods = available_payment_methods($input['order_type']);
if ($input['payment_method'] === '' && $methods) {
    $input['payment_method'] = array_key_first($methods);
}

require __DIR__ . '/includes/header.php';
?>

<section class="section narrow">
  <h1 class="page-title">Checkout</h1>

  <?php if (isset($errors['general'])): ?>
    <div class="flash flash-error"><span><?php echo esc($errors['general']); ?></span></div>
  <?php endif; ?>

  <div class="checkout-grid">
    <form method="post" action="checkout.php" class="form" id="checkout-form">
      <?php echo csrf_field(); ?>

      <h3>Your details</h3>
      <div class="field">
        <label for="f-name">Full name *</label>
        <input id="f-name" type="text" name="name" value="<?php echo esc($input['name']); ?>" maxlength="120" required>
        <?php if (isset($errors['name'])): ?><p class="field-error"><?php echo esc($errors['name']); ?></p><?php endif; ?>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="f-phone">Phone *</label>
          <input id="f-phone" type="tel" name="phone" value="<?php echo esc($input['phone']); ?>" maxlength="40" required>
          <?php if (isset($errors['phone'])): ?><p class="field-error"><?php echo esc($errors['phone']); ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label for="f-email">Email (optional)</label>
          <input id="f-email" type="email" name="email" value="<?php echo esc($input['email']); ?>" maxlength="120">
          <?php if (isset($errors['email'])): ?><p class="field-error"><?php echo esc($errors['email']); ?></p><?php endif; ?>
        </div>
      </div>

      <h3>Pickup or delivery?</h3>
      <div class="radio-cards">
        <label class="radio-card">
          <input type="radio" name="order_type" value="pickup" <?php echo $input['order_type'] === 'pickup' ? 'checked' : ''; ?>>
          <span><strong>Pickup</strong><small>Collect from the shop</small></span>
        </label>
        <label class="radio-card">
          <input type="radio" name="order_type" value="delivery" <?php echo $input['order_type'] === 'delivery' ? 'checked' : ''; ?>>
          <span><strong>Delivery</strong><small>Brought to your door</small></span>
        </label>
      </div>
      <?php if (isset($errors['order_type'])): ?><p class="field-error"><?php echo esc($errors['order_type']); ?></p><?php endif; ?>

      <div class="field" data-address-wrap style="<?php echo $input['order_type'] === 'delivery' ? '' : 'display:none'; ?>">
        <label for="f-address">Delivery address *</label>
        <textarea id="f-address" name="address" rows="2" maxlength="500"><?php echo esc($input['address']); ?></textarea>
        <?php if (isset($errors['address'])): ?><p class="field-error"><?php echo esc($errors['address']); ?></p><?php endif; ?>
      </div>

      <h3>Payment method</h3>
      <div class="field">
        <?php foreach (['pickup' => available_payment_methods('pickup'), 'delivery' => available_payment_methods('delivery')] as $type => $list): ?>
          <?php foreach ($list as $code => $m): ?>
            <label class="radio-line" data-payment-option data-types="<?php echo esc(implode(' ', $m['types'])); ?>" style="<?php echo in_array($input['order_type'], $m['types'], true) ? '' : 'display:none'; ?>">
              <input type="radio" name="payment_method" value="<?php echo esc($code); ?>" <?php echo $input['payment_method'] === $code ? 'checked' : ''; ?>>
              <span>
                <strong><?php echo esc($m['label']); ?></strong>
                <?php if ($m['instructions'] !== ''): ?><small><?php echo esc($m['instructions']); ?></small><?php endif; ?>
              </span>
            </label>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (isset($errors['payment_method'])): ?><p class="field-error"><?php echo esc($errors['payment_method']); ?></p><?php endif; ?>
      </div>

      <div class="field">
        <label for="f-notes">Order notes (optional)</label>
        <textarea id="f-notes" name="notes" rows="2" maxlength="500" placeholder="Anything we should know?"><?php echo esc($input['notes']); ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-big">Place order</button>
    </form>

    <aside class="order-summary">
      <h3>Order summary</h3>
      <?php foreach ($lines as $line): ?>
        <div class="sum-line">
          <span><?php echo (int) $line['qty']; ?> &times; <?php echo esc($line['name']); ?><?php echo $line['option_name'] !== '' ? ' (' . esc($line['option_name']) . ')' : ''; ?></span>
          <span><?php echo money($line['line_total']); ?></span>
        </div>
      <?php endforeach; ?>
      <div class="sum-row"><span>Subtotal</span><span><?php echo money($subtotal); ?></span></div>
      <div class="sum-row"><span>Delivery fee</span><span>calculated on submit</span></div>
      <p class="muted small">The exact total, including any delivery fee, is confirmed after you place the order.</p>
    </aside>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
