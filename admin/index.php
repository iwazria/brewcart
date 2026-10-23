<?php
// BrewCart admin — dashboard: today's orders + revenue, pending count, item count.
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$adminTitle = 'Dashboard';

// Today's orders and revenue (cancelled orders don't count as revenue).
$today = db()->query(
    "SELECT COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS revenue
     FROM orders
     WHERE DATE(created_at) = CURDATE() AND status <> 'cancelled'"
)->fetch();
$pendingCount = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$itemCount    = (int) db()->query('SELECT COUNT(*) FROM menu_items')->fetchColumn();
$msgCount     = (int) db()->query('SELECT COUNT(*) FROM messages')->fetchColumn();

// Newest orders first.
$recent = db()->query('SELECT * FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();
$labels = order_statuses();

require __DIR__ . '/inc/header.php';
?>

<h1 class="page-title">Dashboard</h1>

<div class="stat-grid">
  <div class="stat-card">
    <span class="stat-label">Orders today</span>
    <strong class="stat-value"><?php echo (int) $today['cnt']; ?></strong>
  </div>
  <div class="stat-card">
    <span class="stat-label">Revenue today</span>
    <strong class="stat-value"><?php echo money($today['revenue']); ?></strong>
  </div>
  <div class="stat-card">
    <span class="stat-label">Pending orders</span>
    <strong class="stat-value"><?php echo $pendingCount; ?></strong>
    <a href="orders.php?status=pending">Review &rarr;</a>
  </div>
  <div class="stat-card">
    <span class="stat-label">Menu items</span>
    <strong class="stat-value"><?php echo $itemCount; ?></strong>
    <a href="items.php">Manage &rarr;</a>
  </div>
  <div class="stat-card">
    <span class="stat-label">Contact messages</span>
    <strong class="stat-value"><?php echo $msgCount; ?></strong>
    <span class="muted small">See contact.php messages table</span>
  </div>
</div>

<h2>Latest orders</h2>
<?php if ($recent): ?>
<table class="admin-table">
  <thead><tr><th>Order</th><th>Customer</th><th>Type</th><th>Total</th><th>Status</th><th>Placed</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($recent as $o): ?>
    <tr>
      <td><strong><?php echo esc($o['order_number']); ?></strong></td>
      <td><?php echo esc($o['customer_name']); ?><br><span class="muted small"><?php echo esc($o['phone']); ?></span></td>
      <td><?php echo esc(ucfirst($o['order_type'])); ?></td>
      <td><?php echo money($o['total']); ?></td>
      <td><span class="status-pill status-<?php echo esc($o['status']); ?>"><?php echo esc($labels[$o['status']]); ?></span></td>
      <td class="muted small"><?php echo esc(date('d M, h:i A', strtotime($o['created_at']))); ?></td>
      <td><a href="orders.php?id=<?php echo (int) $o['id']; ?>">Open</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<p class="muted">No orders yet.</p>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
