<?php
// BrewCart — database connection.
// This file connects the shop to MySQL using PDO (the safe, modern way).

// The app's root folder is one level above the "includes" folder.
$appRoot = dirname(__DIR__);
$configFile = $appRoot . '/config.php';

// No config file yet? The shop has not been installed — send the visitor
// to the installer instead of crashing with a database error.
if (!file_exists($configFile)) {
    // install.php always lives at the app root. Work out its web address
    // from the current page's address (admin pages sit one folder deeper).
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if (substr($dir, -6) === '/admin') {
        $dir = substr($dir, 0, -6);
    }
    header('Location: ' . $dir . '/install.php');
    exit;
}

// Load the database credentials (created by install.php).
require_once $configFile;

/**
 * Return the one shared PDO connection, creating it on first use.
 * Every query in BrewCart goes through this function and uses
 * prepared statements, so user input can never break the SQL.
 */
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
