<?php
// BrewCart — shared helper functions used by every page.
// Short plain-English explanations above each function.

// The cart, CSRF tokens and flash messages all live in the PHP session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape text for safe HTML output.
 * Use this on EVERY piece of dynamic data printed into HTML.
 */
function esc($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Read a store setting (shop name, colors, fees...) from the settings table.
 * Results are cached, so repeated calls cost nothing.
 */
function setting($key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT `key`, `value` FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

/**
 * Format a price with the store's currency symbol, e.g. "Rs 450.00".
 * The symbol is escaped here so every call site is XSS-safe.
 */
function money($amount) {
    return esc(setting('currency_symbol', 'Rs ')) . number_format((float) $amount, 2);
}

/**
 * First character of a string (safe for UTF-8). Used for placeholder graphics.
 */
function str_initial($s) {
    $parts = preg_split('//u', (string) $s, 2, PREG_SPLIT_NO_EMPTY);
    return strtoupper($parts[0] ?? '?');
}

/**
 * Create (or reuse) this visitor's CSRF token.
 * Every POST form carries it; the server rejects posts without a match.
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token. Stops with HTTP 403 when it does not match.
 */
function csrf_check($token) {
    $ok = isset($_SESSION['csrf_token']) && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
    if (!$ok) {
        http_response_code(403);
        exit('Security check failed. Please go back and try again.');
    }
}

/**
 * The hidden CSRF input — call csrf_field() inside every POST form.
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . esc(csrf_token()) . '">';
}

/**
 * Store a one-time message ("Order placed!") shown on the next page load.
 */
function flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Fetch all pending flash messages and clear them.
 */
function take_flashes() {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Send the browser to another page and stop running.
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/* ------------------------------ cart ------------------------------ */

/**
 * The cart lives in $_SESSION['cart'] as  [ "itemId:optionId" => quantity ].
 * Pairing item + option in the key means "Large Latte" and "Regular Latte"
 * are correctly treated as two different lines.
 */
function cart_key($itemId, $optionId) {
    return (int) $itemId . ':' . (int) $optionId;
}

/**
 * Add units of an item+option to the cart.
 */
function cart_add($itemId, $optionId, $qty = 1) {
    $key = cart_key($itemId, $optionId);
    $_SESSION['cart'][$key] = ($_SESSION['cart'][$key] ?? 0) + max(1, min(20, (int) $qty));
}

/**
 * Full cart lines with fresh item/option details from the database.
 * Items that were removed or disabled since are skipped silently.
 */
function cart_lines() {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [];
    }
    $lines = [];
    foreach ($cart as $key => $qty) {
        if (!preg_match('#^(\d+):(\d+)$#', (string) $key, $m)) {
            continue;
        }
        $itemId = (int) $m[1];
        $optionId = (int) $m[2];

        $stmt = db()->prepare('SELECT id, name, price, is_available FROM menu_items WHERE id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        if (!$item || !$item['is_available']) {
            continue;
        }

        $optionName = '';
        $unitPrice = (float) $item['price'];
        if ($optionId > 0) {
            $ostmt = db()->prepare('SELECT name, price_delta FROM item_options WHERE id = ? AND item_id = ?');
            $ostmt->execute([$optionId, $itemId]);
            $opt = $ostmt->fetch();
            if ($opt) {
                $optionName = $opt['name'];
                $unitPrice += (float) $opt['price_delta'];
            }
        }

        $qty = (int) $qty;
        $lines[] = [
            'key'         => $key,
            'item_id'     => $itemId,
            'option_id'   => $optionId,
            'name'        => $item['name'],
            'option_name' => $optionName,
            'unit_price'  => $unitPrice,
            'qty'         => $qty,
            'line_total'  => $unitPrice * $qty,
        ];
    }
    return $lines;
}

/**
 * Total number of units in the cart (for the nav badge).
 */
function cart_count() {
    $n = 0;
    foreach (cart_lines() as $line) {
        $n += $line['qty'];
    }
    return $n;
}

/**
 * Cart subtotal before any delivery fee.
 */
function cart_subtotal() {
    $sum = 0.0;
    foreach (cart_lines() as $line) {
        $sum += $line['line_total'];
    }
    return $sum;
}

/**
 * Empty the cart (called after an order is placed).
 */
function cart_clear() {
    unset($_SESSION['cart']);
}

/* ------------------------------ orders ---------------------------- */

/**
 * Build a unique, hard-to-guess order number like BC-261019-A3F9K2.
 * Uniqueness is verified against the database before returning.
 */
function order_number() {
    for ($i = 0; $i < 10; $i++) {
        $num = 'BC-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $stmt = db()->prepare('SELECT id FROM orders WHERE order_number = ?');
        $stmt->execute([$num]);
        if (!$stmt->fetch()) {
            return $num;
        }
    }
    // Practically unreachable fallback — still unique.
    return 'BC-' . date('ymdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

/**
 * Delivery fee for a subtotal: 0 for pickup, and 0 for delivery when the
 * order reaches the "free delivery over" threshold set by the owner.
 */
function delivery_fee_for($subtotal, $orderType) {
    if ($orderType !== 'delivery') {
        return 0.0;
    }
    $fee = (float) setting('delivery_fee', '0');
    $freeOver = (float) setting('free_delivery_over', '0');
    if ($freeOver > 0 && (float) $subtotal >= $freeOver) {
        return 0.0;
    }
    return $fee;
}

/**
 * The fixed list of order statuses and their customer-friendly labels.
 */
function order_statuses() {
    return [
        'pending'   => 'Order received',
        'confirmed' => 'Confirmed',
        'preparing' => 'Being prepared',
        'ready'     => 'Ready for pickup/delivery',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
}
