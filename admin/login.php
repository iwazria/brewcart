<?php
// BrewCart admin — login page.
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';

// Already logged in? Go straight to the dashboard.
if (!empty($_SESSION['admin_id'])) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? null);
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    // password_verify checks the typed password against the stored hash.
    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true); // new session id on login (session fixation defense)
        $_SESSION['admin_id'] = (int) $admin['id'];
        redirect('index.php');
    }
    $error = 'Wrong username or password. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin login — BrewCart</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body class="admin-login">
<main class="wrap">
  <div class="login-box">
    <h1>Admin login</h1>
    <p class="muted"><?php echo esc(setting('store_name', 'BrewCart')); ?></p>
    <?php if ($error !== ''): ?>
      <div class="flash flash-error"><span><?php echo esc($error); ?></span></div>
    <?php endif; ?>
    <form method="post" action="login.php" class="form">
      <?php echo csrf_field(); ?>
      <div class="field">
        <label for="l-user">Username</label>
        <input id="l-user" type="text" name="username" autocomplete="username" required>
      </div>
      <div class="field">
        <label for="l-pass">Password</label>
        <input id="l-pass" type="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-big">Log in</button>
    </form>
    <p class="muted small"><a href="../index.php">&larr; Back to the shop</a></p>
  </div>
</main>
</body>
</html>
