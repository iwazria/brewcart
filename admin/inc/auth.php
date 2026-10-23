<?php
// BrewCart admin — access guard.
// Include this at the very top of every admin page (except login.php).
// Visitors without a logged-in admin session are sent to the login page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
