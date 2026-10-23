<?php
// BrewCart — payment methods (v1).
//
// BrewCart v1 accepts MANUAL payments only: cash and bank transfer.
// There is deliberately NO online card / mobile-money gateway code here.
//
// ============================================================================
//  GATEWAY EXTENSION SLOT — for the shop owner / developer
// ----------------------------------------------------------------------------
//  To accept JazzCash, Easypaisa, Stripe (or a similar gateway) later:
//
//   1. The shop owner opens their OWN merchant account with the gateway,
//      in their own business name, and receives API keys from the provider.
//
//   2. A developer adds a new entry to the $methods array below, for example
//      'jazzcash' => [...], and writes the small amount of gateway-specific
//      code that the provider's own documentation describes: typically
//      "create the payment when the order is placed" plus "verify the
//      callback/webhook when the provider reports the result".
//
//   3. API keys go into the store settings or config.php — never hard-coded
//      into a template that is sold to someone else, and never committed
//      to version control.
//
//  Do NOT invent or fake gateway API calls. Only wire up a gateway using
//  that provider's real, current documentation, tested in their sandbox.
// ============================================================================

/**
 * Payment methods offered for an order type ('pickup' or 'delivery').
 * Each method lists which order types it supports.
 */
function available_payment_methods($orderType) {
    $methods = [
        'cash_pickup' => [
            'label'        => 'Cash on Pickup',
            'types'        => ['pickup'],
            'instructions' => 'Pay in cash when you collect your order from the shop.',
        ],
        'cash_delivery' => [
            'label'        => 'Cash on Delivery',
            'types'        => ['delivery'],
            'instructions' => 'Pay in cash to the rider when your order arrives.',
        ],
        'bank_transfer' => [
            'label'        => 'Bank Transfer',
            'types'        => ['pickup', 'delivery'],
            // The owner edits these bank details on the admin Settings page.
            'instructions' => setting('bank_transfer_info', ''),
        ],
        // <-- New gateways (JazzCash / Easypaisa / Stripe) plug in here. <--
    ];

    // Keep only the methods valid for this order type.
    $out = [];
    foreach ($methods as $code => $m) {
        if (in_array($orderType, $m['types'], true)) {
            $out[$code] = $m;
        }
    }
    return $out;
}

/**
 * Human-readable label for a payment method code stored on an order.
 */
function payment_label($code) {
    $labels = [
        'cash_pickup'   => 'Cash on Pickup',
        'cash_delivery' => 'Cash on Delivery',
        'bank_transfer' => 'Bank Transfer',
    ];
    return $labels[$code] ?? $code;
}
