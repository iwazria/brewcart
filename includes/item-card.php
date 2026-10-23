<?php
// BrewCart — one menu-item card with its add-to-cart form.
// Expects: $item (with an 'options' array) and optionally $returnTo.
$options  = $item['options'] ?? [];
$returnTo = $returnTo ?? 'menu.php';
?>
<div class="item-card">
  <?php if (!empty($item['image'])): ?>
    <img class="item-img" src="<?php echo esc($item['image']); ?>" alt="<?php echo esc($item['name']); ?>">
  <?php else: ?>
    <div class="item-img item-img--placeholder"><span><?php echo esc(str_initial($item['name'])); ?></span></div>
  <?php endif; ?>
  <div class="item-body">
    <div class="item-top">
      <h3><?php echo esc($item['name']); ?></h3>
      <span class="price"><?php echo money($item['price']); ?></span>
    </div>
    <?php if (!empty($item['description'])): ?>
      <p class="muted"><?php echo esc($item['description']); ?></p>
    <?php endif; ?>
    <form method="post" action="cart.php" class="add-form">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
      <input type="hidden" name="return_to" value="<?php echo esc($returnTo); ?>">
      <?php if ($options): ?>
        <select name="option_id" aria-label="Choose an option">
          <?php foreach ($options as $opt): ?>
            <option value="<?php echo (int) $opt['id']; ?>"><?php echo esc($opt['name']); ?><?php if ((float) $opt['price_delta'] > 0): ?> (+<?php echo money($opt['price_delta']); ?>)<?php endif; ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input type="hidden" name="option_id" value="0">
      <?php endif; ?>
      <button type="submit" class="btn">Add to cart</button>
    </form>
  </div>
</div>
