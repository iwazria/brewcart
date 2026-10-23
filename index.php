<?php
// BrewCart — home page: hero, featured items, hours/location teaser.
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Home';

// Featured items for the home page (hand-picked by the owner in admin).
$stmt = db()->prepare(
    'SELECT mi.*, c.name AS category_name
     FROM menu_items mi
     LEFT JOIN categories c ON c.id = mi.category_id
     WHERE mi.is_featured = 1 AND mi.is_available = 1
     ORDER BY mi.sort_order, mi.id
     LIMIT 6'
);
$stmt->execute();
$featured = $stmt->fetchAll();

// Attach size/add-on options to each featured item (one query).
if ($featured) {
    $ids = array_column($featured, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $ostmt = db()->prepare("SELECT * FROM item_options WHERE item_id IN ($ph) ORDER BY sort_order, id");
    $ostmt->execute($ids);
    $byItem = [];
    foreach ($ostmt->fetchAll() as $opt) {
        $byItem[$opt['item_id']][] = $opt;
    }
    foreach ($featured as &$it) {
        $it['options'] = $byItem[$it['id']] ?? [];
    }
    unset($it);
}

require __DIR__ . '/includes/header.php';
$returnTo = 'index.php';
$heroImage = setting('hero_image', '');
?>

<?php if ($heroImage !== ''): ?>
<section class="hero hero--photo" style="background-image: linear-gradient(100deg, rgba(38, 25, 14, 0.88) 0%, rgba(38, 25, 14, 0.62) 55%, rgba(38, 25, 14, 0.30) 100%), url('<?php echo esc($heroImage); ?>');">
<?php else: ?>
<section class="hero">
<?php endif; ?>
  <div class="hero-inner">
    <p class="hero-kicker"><?php echo esc(setting('tagline', '')); ?></p>
    <h1><?php echo esc(setting('store_name', 'BrewCart')); ?></h1>
    <p class="hero-sub">Order your favorite coffee online — pickup or delivery, ready when you are.</p>
    <div class="hero-actions">
      <a class="btn btn-light" href="menu.php">View menu</a>
      <a class="btn btn-ghost-light" href="track.php">Track my order</a>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section">
  <div class="section-head">
    <h2>Customer favorites</h2>
    <a href="menu.php">See full menu &rarr;</a>
  </div>
  <div class="item-grid">
    <?php foreach ($featured as $item): ?>
      <?php require __DIR__ . '/includes/item-card.php'; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- Sample story copy shipped as starter content. The shop owner should
     replace this text (in index.php) and the story photo (Settings page)
     with their own story before launch. -->
<section class="section story">
  <?php $storyImage = setting('story_image', ''); ?>
  <?php if ($storyImage !== ''): ?>
  <div class="story-media">
    <img src="<?php echo esc($storyImage); ?>" alt="Inside <?php echo esc(setting('store_name', 'our coffee shop')); ?>">
  </div>
  <?php endif; ?>
  <div class="story-text">
    <p class="section-kicker">Our story</p>
    <h2>A small counter with a big love for coffee</h2>
    <p>We started with a single espresso machine, a stubborn belief that great
    coffee should never be rushed, and beans sourced from growers we know by
    name. Every cup is brewed to order — never sitting, never stale.</p>
    <p>Today the counter is a little bigger, but the idea is the same: honest
    coffee, warm pastries, and a place where the regulars are greeted by name.
    Order ahead and your drink will be waiting, exactly how you like it.</p>
    <a class="btn btn-ghost" href="menu.php">See what's brewing</a>
  </div>
</section>

<!-- Sample gallery photos (Unsplash starter images). Replace with the
     shop's own photos by editing this section in index.php. -->
<section class="section gallery-section">
  <div class="section-head">
    <h2>Moments from the counter</h2>
  </div>
  <div class="gallery-grid">
    <img src="https://images.unsplash.com/photo-1521017432531-fbd92d768814?auto=format&fit=crop&w=800&q=80" alt="Coffee shop interior with guests at the counter" loading="lazy">
    <img src="https://images.unsplash.com/photo-1445116572660-236099ec97a0?auto=format&fit=crop&w=800&q=80" alt="A quiet corner table with a cup of coffee" loading="lazy">
    <img src="https://images.unsplash.com/photo-1453614512568-c4024d13c247?auto=format&fit=crop&w=800&q=80" alt="Barista pouring latte art" loading="lazy">
    <img src="https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=800&q=80" alt="Fresh coffee being brewed by hand" loading="lazy">
  </div>
</section>

<!-- Sample testimonials: placeholder quotes shipped with the demo content.
     Replace with real customer reviews before launch. -->
<section class="section testimonials">
  <div class="section-head">
    <h2>What the neighbors say</h2>
  </div>
  <div class="testimonial-grid">
    <figure class="testimonial-card">
      <blockquote>"The cappuccino tastes like it was made by someone who genuinely cares. I order ahead every morning and it is always ready when I walk in."</blockquote>
      <figcaption><strong>Ayesha</strong> <span class="muted">— Gulberg</span></figcaption>
    </figure>
    <figure class="testimonial-card">
      <blockquote>"Finally, a cold brew in this neighborhood that is actually smooth. The delivery is quick and the cup arrives still ice cold."</blockquote>
      <figcaption><strong>Bilal</strong> <span class="muted">— DHA Phase 5</span></figcaption>
    </figure>
    <figure class="testimonial-card">
      <blockquote>"Their banana bread with a pour over is my weekend ritual now. Cozy place, kind people, and the online ordering just works."</blockquote>
      <figcaption><strong>Mahnoor</strong> <span class="muted">— Clifton</span></figcaption>
    </figure>
  </div>
</section>

<section class="section info-cards">
  <div class="info-card">
    <h3>Opening hours</h3>
    <p><?php echo esc(setting('hours', '')); ?></p>
  </div>
  <div class="info-card">
    <h3>Find us</h3>
    <p><?php echo esc(setting('address', '')); ?><br><?php echo esc(setting('phone', '')); ?></p>
  </div>
  <div class="info-card">
    <h3>How ordering works</h3>
    <p>Pick your drinks, check out in a minute, and track your order live until it is ready.</p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
