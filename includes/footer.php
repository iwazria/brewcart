<?php
// BrewCart — shared storefront footer. Closes <main>, prints the footer,
// and loads the tiny JavaScript file.
?>
</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <h4><?php echo esc(setting('store_name', 'BrewCart')); ?></h4>
      <p><?php echo esc(setting('tagline', '')); ?></p>
    </div>
    <div>
      <h4>Visit us</h4>
      <p><?php echo esc(setting('address', '')); ?><br><?php echo esc(setting('hours', '')); ?></p>
    </div>
    <div>
      <h4>Contact</h4>
      <p><?php echo esc(setting('phone', '')); ?></p>
      <p><a href="menu.php">Menu</a> &middot; <a href="track.php">Track order</a> &middot; <a href="contact.php">Contact</a></p>
    </div>
  </div>
  <div class="footer-bottom">&copy; <?php echo date('Y'); ?> <?php echo esc(setting('store_name', 'BrewCart')); ?>. All rights reserved.</div>
</footer>
<script src="assets/app.js"></script>
</body>
</html>
