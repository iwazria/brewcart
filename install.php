<?php
// ============================================================================
// BrewCart installer wizard.
// Open http://your-shop.com/install.php once, follow the steps, then
// DELETE THIS FILE from your server for security.
// ============================================================================
session_start();

$lockFile = __DIR__ . '/installed.lock';
$sqlFile  = __DIR__ . '/database.sql';
$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;

// --- CSRF token for the installer's own forms ---
if (empty($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
}
function install_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['install_csrf'], ENT_QUOTES, 'UTF-8') . '">';
}

// --- tiny page chrome (the main stylesheet may not exist yet in theory) ---
function install_head($title) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' — BrewCart Installer</title>'
       . '<link rel="stylesheet" href="assets/style.css"></head>'
       . '<body><main class="wrap"><section class="section narrow install-box">'
       . '<h1 class="page-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
}
function install_foot() {
    echo '</section></main></body></html>';
    exit;
}

// --- already installed? stop here (step 4 is the one-time success page,
// --- so it is allowed through even though the lock was just written) ---
if (file_exists($lockFile) && $step !== 4) {
    install_head('Already installed');
    echo '<p>BrewCart is already installed on this server.</p>'
       . '<p><strong>For security, please delete <code>install.php</code> now</strong> — '
       . 'leaving it here lets anyone re-run the installer.</p>'
       . '<p><a class="btn btn-primary" href="index.php">Visit the shop</a> '
       . '<a class="btn btn-ghost" href="admin/">Open the admin panel</a></p>';
    install_foot();
}

// ============================================================================
// STEP 1 — requirement checks
// ============================================================================
if ($step === 1) {
    $checks = [
        ['PHP 7.4 or newer', version_compare(PHP_VERSION, '7.4.0', '>='), 'Found PHP ' . PHP_VERSION],
        ['MySQL driver for PHP (pdo_mysql)', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'Available' : 'Missing — ask your host to enable pdo_mysql'],
        ['This folder is writable (needed to save config.php)', is_writable(__DIR__), is_writable(__DIR__) ? 'Writable' : 'Not writable — set folder permissions to 755'],
        ['database.sql is present', file_exists($sqlFile), file_exists($sqlFile) ? 'Found' : 'Missing — re-upload all BrewCart files'],
    ];
    $allOk = true;
    foreach ($checks as $c) {
        if (!$c[1]) {
            $allOk = false;
        }
    }

    install_head('Step 1 of 3 — Requirement checks');
    echo '<table class="detail-table"><tbody>';
    foreach ($checks as $c) {
        echo '<tr><td>' . htmlspecialchars($c[0], ENT_QUOTES, 'UTF-8') . '</td>'
           . '<td>' . ($c[1] ? '<span class="status-pill status-completed">OK</span>' : '<span class="status-pill status-cancelled">Failed</span>') . '</td>'
           . '<td class="muted">' . htmlspecialchars($c[2], ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }
    echo '</tbody></table>';
    if ($allOk) {
        echo '<p><a class="btn btn-primary" href="install.php?step=2">Continue</a></p>';
    } else {
        echo '<p class="field-error">Please fix the failed checks above, then <a href="install.php?step=1">re-check</a>.</p>';
    }
    install_foot();
}

// ============================================================================
// STEP 2 — collect database + admin details
// ============================================================================
if ($step === 2) {
    install_head('Step 2 of 3 — Database and admin account');
    echo '<p>Create the database first in your hosting control panel (cPanel: <em>Database Wizard</em>), then fill in the details below.</p>'
       . '<form method="post" action="install.php?step=3" class="form">'
       . install_csrf_field()
       . '<h3>Database</h3>'
       . '<div class="field"><label>Database host</label><input type="text" name="db_host" value="localhost" required></div>'
       . '<div class="field"><label>Database name</label><input type="text" name="db_name" placeholder="e.g. mycpanel_brewcart" required></div>'
       . '<div class="field-row">'
       . '<div class="field"><label>Database username</label><input type="text" name="db_user" required></div>'
       . '<div class="field"><label>Database password</label><input type="password" name="db_pass" required></div>'
       . '</div>'
       . '<h3>Admin account</h3>'
       . '<div class="field-row">'
       . '<div class="field"><label>Admin username</label><input type="text" name="admin_user" value="admin" required></div>'
       . '<div class="field"><label>Admin password (min. 8 characters)</label><input type="password" name="admin_pass" minlength="8" required></div>'
       . '</div>'
       . '<h3>Store</h3>'
       . '<div class="field"><label>Store name</label><input type="text" name="store_name" value="Demo Coffee House" required></div>'
       . '<button type="submit" class="btn btn-primary btn-big">Install BrewCart</button>'
       . '</form>';
    install_foot();
}

// ============================================================================
// STEP 3 — run the install
// ============================================================================
if ($step === 3) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: install.php?step=2');
        exit;
    }
    // CSRF check for the installer's own form.
    $tokenOk = isset($_SESSION['install_csrf']) && is_string($_POST['csrf_token'] ?? null)
        && hash_equals($_SESSION['install_csrf'], $_POST['csrf_token']);
    if (!$tokenOk) {
        http_response_code(403);
        exit('Security check failed. Please go back and try again.');
    }

    $dbHost    = trim((string) ($_POST['db_host'] ?? ''));
    $dbName    = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser    = trim((string) ($_POST['db_user'] ?? ''));
    $dbPass    = (string) ($_POST['db_pass'] ?? ''); // passwords may contain spaces
    $adminUser = trim((string) ($_POST['admin_user'] ?? ''));
    $adminPass = (string) ($_POST['admin_pass'] ?? '');
    $storeName = trim((string) ($_POST['store_name'] ?? ''));

    $error = '';
    if ($dbHost === '' || $dbName === '' || $dbUser === '' || $adminUser === '' || $storeName === '') {
        $error = 'Please fill in every field.';
    } elseif (strlen($adminPass) < 8) {
        $error = 'The admin password must be at least 8 characters.';
    }

    if ($error === '') {
        try {
            // 1. Connect to the database the owner created.
            $pdo = new PDO(
                'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // 2. Run database.sql statement by statement.
            //    Rules: skip empty lines and lines starting with -- or #;
            //    a statement ends at a line whose last character is ";".
            $buffer = '';
            $lines = file($sqlFile, FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                throw new RuntimeException('Could not read database.sql.');
            }
            foreach ($lines as $line) {
                $trim = trim($line);
                if ($trim === '' || strncmp($trim, '--', 2) === 0 || $trim[0] === '#') {
                    continue;
                }
                $buffer .= $line . "\n";
                if (substr($trim, -1) === ';') {
                    $pdo->exec($buffer);
                    $buffer = '';
                }
            }
            if (trim($buffer) !== '') {
                $pdo->exec($buffer);
            }

            // 3. Apply the store name chosen in the wizard.
            $upd = $pdo->prepare('UPDATE settings SET `value` = ? WHERE `key` = ?');
            $upd->execute([$storeName, 'store_name']);

            // 4. Create the admin account (password is hashed, never stored plain).
            $ins = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
            $ins->execute([$adminUser, password_hash($adminPass, PASSWORD_DEFAULT)]);

            // 5. Write config.php with the database credentials.
            $config = "<?php\n"
                    . "// BrewCart configuration — created by install.php on " . date('Y-m-d H:i:s') . "\n"
                    . "// Keep this file private. Never share it.\n"
                    . "define('DB_HOST', " . var_export($dbHost, true) . ");\n"
                    . "define('DB_NAME', " . var_export($dbName, true) . ");\n"
                    . "define('DB_USER', " . var_export($dbUser, true) . ");\n"
                    . "define('DB_PASS', " . var_export($dbPass, true) . ");\n";
            if (file_put_contents(__DIR__ . '/config.php', $config) === false) {
                throw new RuntimeException('Could not write config.php — check folder permissions.');
            }

            // 6. Lock the installer so it cannot run twice.
            file_put_contents($lockFile, 'Installed on ' . date('Y-m-d H:i:s') . "\n");

            header('Location: install.php?step=4');
            exit;
        } catch (Exception $e) {
            // Show a friendly error; never leak the raw database message (it may
            // contain the host/user details). The owner can retry safely.
            $error = 'Installation failed: could not connect to the database or run the setup. '
                   . 'Double-check the database name, username and password, then try again.';
        }
    }

    install_head('Step 3 of 3 — Installing');
    echo '<div class="flash flash-error"><span>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</span></div>'
       . '<p><a class="btn btn-ghost" href="install.php?step=2">Go back and try again</a></p>';
    install_foot();
}

// ============================================================================
// STEP 4 — done
// ============================================================================
install_head('Installation complete');
echo '<div class="success-hero"><div class="success-check">&#10003;</div>'
   . '<h2>Your coffee shop is ready!</h2></div>'
   . '<div class="notice"><strong>Important:</strong> for security, <strong>delete '
   . '<code>install.php</code> from your server now</strong> (via cPanel File Manager). '
   . 'Leaving it in place lets anyone re-run the installer.</div>'
   . '<p>The sample menu is placeholder data — replace it with the real menu in the admin panel.</p>'
   . '<div class="btn-row"><a class="btn btn-primary" href="index.php">Visit the shop</a> '
   . '<a class="btn btn-ghost" href="admin/">Open the admin panel</a></div>';
install_foot();
