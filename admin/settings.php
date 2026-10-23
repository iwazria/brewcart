<?php
// BrewCart admin — store settings. Every setting in the settings table
// is editable here, so the shop can be rebranded without touching code.
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$adminTitle = 'Settings';

// Setting definitions: key => [label, type, group, hint].
// Types: text, textarea, color, number, email, checkbox.
$fields = [
    'Branding' => [
        'store_name'      => ['Store name', 'text', 'Shown in the header, hero and footer.'],
        'tagline'         => ['Tagline', 'text', 'Short line under the store name.'],
        'currency_symbol' => ['Currency symbol', 'text', 'e.g. Rs (include a trailing space if you like).'],
        'currency_code'   => ['Currency code', 'text', 'e.g. PKR. For your own reference.'],
        'primary_color'   => ['Primary color', 'color', 'Main brand color: buttons, header, links.'],
        'accent_color'    => ['Accent color', 'color', 'Highlights: badges, hovers, prices.'],
        'logo_path'       => ['Logo image path', 'text', 'e.g. assets/logo.png. Leave empty to use a letter mark.'],
        'announcement'    => ['Announcement bar', 'text', 'Banner above the header. Leave empty to hide it.'],
    ],
    'Contact and hours' => [
        'phone'               => ['Phone / WhatsApp', 'text', 'Shown in the footer and contact page.'],
        'address'             => ['Address', 'text', 'Shown on the home and contact pages.'],
        'hours'               => ['Opening hours', 'text', 'e.g. Mon-Sun, 8am to 11pm.'],
        'admin_email'         => ['Admin email', 'email', 'Receives order alerts when email alerts are enabled.'],
        'order_email_enabled' => ['Email me on new orders', 'checkbox', 'Needs working mail on the server (see README).'],
    ],
    'Delivery and payments' => [
        'delivery_fee'       => ['Delivery fee', 'number', 'Flat delivery charge.'],
        'free_delivery_over' => ['Free delivery over', 'number', 'Orders at or above this subtotal ship free. 0 disables.'],
        'bank_transfer_info' => ['Bank transfer details', 'textarea', 'Shown to shoppers who choose bank transfer.'],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? null);
    $posted = $_POST['settings'] ?? [];
    $errors = [];

    foreach ($fields as $group) {
        foreach ($group as $key => $def) {
            list($label, $type) = $def;
            if ($type === 'checkbox') {
                $value = isset($posted[$key]) ? '1' : '0';
            } else {
                $value = trim((string) ($posted[$key] ?? ''));
            }
            if ($type === 'number' && $value !== '' && !is_numeric($value)) {
                $errors[] = $label . ' must be a number.';
                continue;
            }
            if ($type === 'color' && !preg_match('#^#[0-9a-fA-F]{6}$#', $value)) {
                $errors[] = $label . ' must be a color like #6F4E37.';
                continue;
            }
            if ($type === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[] = $label . ' must be a valid email address.';
                continue;
            }
            $stmt = db()->prepare('UPDATE settings SET `value` = ? WHERE `key` = ?');
            $stmt->execute([$value, $key]);
        }
    }

    if ($errors) {
        flash('error', implode(' ', $errors));
    } else {
        flash('success', 'Settings saved.');
    }
    redirect('settings.php');
}

require __DIR__ . '/inc/header.php';
?>

<h1 class="page-title">Settings</h1>
<p class="muted">Everything that makes this shop yours — no code changes needed.</p>

<form method="post" action="settings.php" class="form">
  <?php echo csrf_field(); ?>
  <?php foreach ($fields as $groupName => $group): ?>
    <h3><?php echo esc($groupName); ?></h3>
    <?php foreach ($group as $key => $def):
      list($label, $type, $hint) = $def;
      $val = setting($key, '');
    ?>
      <div class="field">
        <label for="s-<?php echo esc($key); ?>"><?php echo esc($label); ?></label>
        <?php if ($type === 'textarea'): ?>
          <textarea id="s-<?php echo esc($key); ?>" name="settings[<?php echo esc($key); ?>]" rows="3"><?php echo esc($val); ?></textarea>
        <?php elseif ($type === 'checkbox'): ?>
          <label class="check"><input type="checkbox" name="settings[<?php echo esc($key); ?>]" value="1" <?php echo $val === '1' ? 'checked' : ''; ?>> Enabled</label>
        <?php elseif ($type === 'color'): ?>
          <input id="s-<?php echo esc($key); ?>" type="color" name="settings[<?php echo esc($key); ?>]" value="<?php echo esc($val !== '' ? $val : '#6F4E37'); ?>" class="color-input">
        <?php else: ?>
          <input id="s-<?php echo esc($key); ?>" type="<?php echo esc($type); ?>" name="settings[<?php echo esc($key); ?>]" value="<?php echo esc($val); ?>">
        <?php endif; ?>
        <?php if ($hint !== ''): ?><p class="muted small"><?php echo esc($hint); ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endforeach; ?>
  <button type="submit" class="btn btn-primary btn-big">Save settings</button>
</form>

<?php require __DIR__ . '/inc/footer.php'; ?>
