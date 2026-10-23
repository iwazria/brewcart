<?php
// BrewCart — contact page. Messages are saved to the messages table
// so the owner can read them in the admin panel later.
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
$pageTitle = 'Contact';

$errors = [];
$input = ['name' => '', 'contact' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? null);
    foreach ($input as $k => $v) {
        $input[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    if (strlen($input['name']) < 2) {
        $errors['name'] = 'Please enter your name.';
    }
    if (strlen($input['contact']) < 5) {
        $errors['contact'] = 'Please enter your phone number or email.';
    }
    if (strlen($input['message']) < 5) {
        $errors['message'] = 'Please write your message.';
    }

    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO messages (name, contact, message) VALUES (?, ?, ?)');
        $stmt->execute([$input['name'], $input['contact'], $input['message']]);
        flash('success', 'Thanks! Your message has been sent. We will get back to you soon.');
        redirect('contact.php');
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="section narrow">
  <h1 class="page-title">Contact us</h1>

  <div class="info-cards">
    <div class="info-card">
      <h3>Phone</h3>
      <p><?php echo esc(setting('phone', '')); ?></p>
    </div>
    <div class="info-card">
      <h3>Address</h3>
      <p><?php echo esc(setting('address', '')); ?></p>
    </div>
    <div class="info-card">
      <h3>Hours</h3>
      <p><?php echo esc(setting('hours', '')); ?></p>
    </div>
  </div>

  <form method="post" action="contact.php" class="form">
    <?php echo csrf_field(); ?>
    <h3>Send us a message</h3>
    <div class="field-row">
      <div class="field">
        <label for="c-name">Your name *</label>
        <input id="c-name" type="text" name="name" value="<?php echo esc($input['name']); ?>" maxlength="120" required>
        <?php if (isset($errors['name'])): ?><p class="field-error"><?php echo esc($errors['name']); ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="c-contact">Phone or email *</label>
        <input id="c-contact" type="text" name="contact" value="<?php echo esc($input['contact']); ?>" maxlength="120" required>
        <?php if (isset($errors['contact'])): ?><p class="field-error"><?php echo esc($errors['contact']); ?></p><?php endif; ?>
      </div>
    </div>
    <div class="field">
      <label for="c-message">Message *</label>
      <textarea id="c-message" name="message" rows="5" required><?php echo esc($input['message']); ?></textarea>
      <?php if (isset($errors['message'])): ?><p class="field-error"><?php echo esc($errors['message']); ?></p><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary">Send message</button>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
