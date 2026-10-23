"use strict";
/* ==========================================================================
   BrewCart — STATIC EDITION — app logic (app.js)
   --------------------------------------------------------------------------
   Pure vanilla JavaScript: no libraries, no server, no database.
   The shop data lives in data.js (CONFIG + MENU_ITEMS). This file:
     1. builds the shared header/footer and applies CONFIG branding
     2. renders menu item cards (home "favorites" + the menu page)
     3. runs the cart (saved in the visitor's browser — localStorage)
     4. handles checkout, order saving, order tracking and WhatsApp links

   Your data never leaves the visitor's browser except when they tap a
   WhatsApp button, which opens WhatsApp with the order/message prefilled.
   ========================================================================== */

/* ---------------- tiny helpers ---------------- */

// Shortcut for document.querySelector / querySelectorAll.
function $(sel, root) { return (root || document).querySelector(sel); }
function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

// Escape text before putting it into HTML — use on EVERY dynamic value,
// exactly like the PHP edition's esc() helper. Prevents broken pages and
// script injection when a name or note contains <, >, & or quotes.
function esc(value) {
  return String(value == null ? "" : value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

// Format a price the same way the PHP money() helper did: "$2,850.00".
function money(amount) {
  var n = Number(amount) || 0;
  return CONFIG.currencySymbol + n.toLocaleString("en-US", {
    minimumFractionDigits: 2, maximumFractionDigits: 2
  });
}

// Find a menu item by its id (from data.js).
function findItem(itemId) {
  for (var i = 0; i < MENU_ITEMS.length; i++) {
    if (MENU_ITEMS[i].id === Number(itemId)) return MENU_ITEMS[i];
  }
  return null;
}

// Format a saved date the way the PHP track page did: "23 Oct 2026, 04:05 PM".
function formatDate(iso) {
  var d = new Date(iso);
  if (isNaN(d.getTime())) return "";
  var months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  var pad = function (n) { return String(n).padStart(2, "0"); };
  var h = d.getHours();
  var ampm = h >= 12 ? "PM" : "AM";
  h = h % 12 || 12;
  return pad(d.getDate()) + " " + months[d.getMonth()] + " " + d.getFullYear() +
         ", " + pad(h) + ":" + pad(d.getMinutes()) + " " + ampm;
}

/* ---------------- storage: cart + orders ----------------
   Both live in localStorage (the browser's built-in key/value store):
     brewcart_cart   -> { "itemId|Option name": {itemId, name, optionName, unitPrice, qty} }
     brewcart_orders -> [ order, order, ... ]  (orders placed on THIS device)
     brewcart_last   -> order number of the most recent order            */

var CART_KEY = "brewcart_cart";
var ORDERS_KEY = "brewcart_orders";
var LAST_KEY = "brewcart_last_order";

function loadJSON(key, fallback) {
  try {
    var raw = localStorage.getItem(key);
    return raw ? JSON.parse(raw) : fallback;
  } catch (e) {
    return fallback; // corrupted storage should never break the page
  }
}
function saveJSON(key, value) {
  try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* storage full/blocked */ }
}

function loadCart() { return loadJSON(CART_KEY, {}); }
function saveCart(cart) { saveJSON(CART_KEY, cart); }
function loadOrders() { return loadJSON(ORDERS_KEY, []); }
function saveOrders(orders) { saveJSON(ORDERS_KEY, orders); }

// A cart line is identified by item + chosen option, so "Cafe Latte (Large)"
// and "Cafe Latte (Regular)" stay two separate lines — same as the PHP cart.
function cartKey(itemId, optionName) { return Number(itemId) + "|" + (optionName || ""); }

function cartLines() {
  var cart = loadCart();
  return Object.keys(cart).map(function (k) {
    var l = cart[k];
    l.key = k;
    l.lineTotal = l.unitPrice * l.qty;
    return l;
  });
}
function cartCount() {
  return cartLines().reduce(function (n, l) { return n + l.qty; }, 0);
}
function cartSubtotal() {
  return cartLines().reduce(function (sum, l) { return sum + l.lineTotal; }, 0);
}

/* Delivery fee — the SAME rule as the PHP edition:
   pickup = free; delivery = CONFIG.deliveryFee, but free when the subtotal
   reaches CONFIG.freeDeliveryOver (when that threshold is above 0). */
function deliveryFeeFor(subtotal, orderType) {
  if (orderType !== "delivery") return 0;
  if (CONFIG.freeDeliveryOver > 0 && subtotal >= CONFIG.freeDeliveryOver) return 0;
  return Number(CONFIG.deliveryFee) || 0;
}

/* ---------------- payments (mirrors the PHP payments.php) ---------------- */

var PAYMENT_METHODS = [
  { code: "cash_pickup", label: "Cash on Pickup", types: ["pickup"],
    instructions: "Pay in cash when you collect your order from the shop." },
  { code: "cash_delivery", label: "Cash on Delivery", types: ["delivery"],
    instructions: "Pay in cash to the rider when your order arrives." },
  { code: "bank_transfer", label: "Bank Transfer", types: ["pickup", "delivery"],
    instructions: CONFIG.bankInfo },
];
function paymentLabel(code) {
  for (var i = 0; i < PAYMENT_METHODS.length; i++) {
    if (PAYMENT_METHODS[i].code === code) return PAYMENT_METHODS[i].label;
  }
  return code;
}

/* Order statuses + customer-friendly labels (same list as the PHP edition).
   Static orders are saved with status "pending": the shop confirms the real
   progress on WhatsApp, because there is no server to update it here. */
var ORDER_STATUSES = {
  pending: "Order received",
  confirmed: "Confirmed",
  preparing: "Being prepared",
  ready: "Ready for pickup/delivery",
  completed: "Completed",
  cancelled: "Cancelled"
};

/* ---------------- WhatsApp ---------------- */

// Build a wa.me link with a prefilled message.
function whatsappLink(text) {
  return "https://wa.me/" + CONFIG.whatsappNumber + "?text=" + encodeURIComponent(text);
}

// The full order summary a customer sends to the shop from success.html.
function orderWhatsappText(order) {
  var lines = [];
  lines.push("Hello " + CONFIG.storeName + "! I just placed an order on your website.");
  lines.push("");
  lines.push("Order: " + order.number);
  lines.push("Name: " + order.name);
  lines.push("Phone: " + order.phone);
  lines.push("Type: " + (order.orderType === "delivery" ? "Delivery" : "Pickup"));
  if (order.orderType === "delivery" && order.address) lines.push("Address: " + order.address);
  lines.push("");
  lines.push("Items:");
  order.lines.forEach(function (l) {
    lines.push(l.qty + " x " + l.name + (l.optionName ? " (" + l.optionName + ")" : "") + " — " + money(l.unitPrice * l.qty));
  });
  lines.push("");
  lines.push("Subtotal: " + money(order.subtotal));
  lines.push("Delivery fee: " + money(order.deliveryFee));
  lines.push("Total: " + money(order.total));
  lines.push("Payment: " + paymentLabel(order.paymentMethod));
  if (order.notes) lines.push("Notes: " + order.notes);
  return lines.join("\n");
}

/* ---------------- shared header + footer ----------------
   Every page has empty <div id="site-header"></div> and
   <div id="site-footer"></div> placeholders; they are filled from here so
   the navigation exists in exactly ONE place. */

var NAV_LINKS = [
  { href: "index.html", label: "Home" },
  { href: "menu.html", label: "Menu" },
  { href: "track.html", label: "Track Order" },
  { href: "contact.html", label: "Contact" },
];

function currentPage() {
  var base = window.location.pathname.split("/").pop();
  return base === "" ? "index.html" : base;
}

function buildHeader() {
  var host = $("#site-header");
  if (!host) return;
  var page = currentPage();
  var initial = CONFIG.storeName.trim().charAt(0).toUpperCase() || "B";
  var nav = NAV_LINKS.map(function (l) {
    return '<a href="' + l.href + '"' + (page === l.href ? ' class="active"' : "") + ">" + esc(l.label) + "</a>";
  }).join("");
  var cartActive = page === "cart.html" ? ' class="cart-link active"' : ' class="cart-link"';

  host.innerHTML =
    (CONFIG.announcement ? '<div class="announcement">' + esc(CONFIG.announcement) + "</div>" : "") +
    '<header class="site-header"><div class="wrap header-inner">' +
      '<a class="brand" href="index.html">' +
        '<span class="brand-mark">' + esc(initial) + "</span>" +
        '<span class="brand-name">' + esc(CONFIG.storeName) + "</span>" +
      "</a>" +
      '<button class="nav-toggle" type="button" data-nav-toggle aria-label="Open menu">&#9776;</button>' +
      '<nav class="site-nav" data-nav>' + nav +
        '<a href="cart.html"' + cartActive + ">Cart" +
          '<span class="cart-badge" data-cart-badge hidden></span>' +
        "</a>" +
      "</nav>" +
    "</div></header>";

  // Mobile menu toggle (same behavior as the PHP edition's app.js).
  var toggle = $("[data-nav-toggle]", host);
  var navEl = $("[data-nav]", host);
  if (toggle && navEl) {
    toggle.addEventListener("click", function () { navEl.classList.toggle("open"); });
  }
  updateCartBadge();
}

function buildFooter() {
  var host = $("#site-footer");
  if (!host) return;
  var year = new Date().getFullYear();
  host.innerHTML =
    '<footer class="site-footer"><div class="wrap footer-grid">' +
      "<div><h4>" + esc(CONFIG.storeName) + "</h4><p>" + esc(CONFIG.tagline) + "</p></div>" +
      "<div><h4>Visit us</h4><p>" + esc(CONFIG.address) + "<br>" + esc(CONFIG.hours) + "</p></div>" +
      "<div><h4>Contact</h4><p>" + esc(CONFIG.phone) + "</p>" +
      '<p><a href="menu.html">Menu</a> &middot; <a href="track.html">Track order</a> &middot; <a href="contact.html">Contact</a></p></div>' +
    "</div>" +
    '<div class="footer-bottom">&copy; ' + year + " " + esc(CONFIG.storeName) + ". All rights reserved.</div></footer>";
}

// The little cart count bubble in the navigation.
function updateCartBadge() {
  var badge = $("[data-cart-badge]");
  if (!badge) return;
  var n = cartCount();
  badge.textContent = n;
  badge.hidden = n === 0;
}

/* ---------------- toast (the static edition's flash message) ---------------- */

var toastTimer = null;
function showToast(message) {
  var toast = $("#toast");
  if (!toast) return;
  toast.textContent = message;
  toast.classList.add("show");
  window.clearTimeout(toastTimer);
  toastTimer = window.setTimeout(function () { toast.classList.remove("show"); }, 2200);
}

/* ---------------- menu item cards ----------------
   Same markup as the PHP includes/item-card.php. */

function itemCardHTML(item) {
  var media = item.image
    ? '<img class="item-img" src="' + esc(item.image) + '" alt="' + esc(item.name) + '" loading="lazy">'
    : '<div class="item-img item-img--placeholder"><span>' + esc(item.name.charAt(0).toUpperCase()) + "</span></div>";

  var optionsHTML = "";
  if (item.options && item.options.length) {
    var opts = item.options.map(function (o) {
      var label = o.name + (o.priceDelta > 0 ? " (+" + money(o.priceDelta) + ")" : "");
      return '<option value="' + esc(o.name) + '">' + esc(label) + "</option>";
    }).join("");
    optionsHTML = '<select name="option_name" aria-label="Choose an option">' + opts + "</select>";
  }

  return '<div class="item-card">' + media +
    '<div class="item-body">' +
      '<div class="item-top"><h3>' + esc(item.name) + '</h3><span class="price">' + esc(money(item.price)) + "</span></div>" +
      (item.description ? '<p class="muted">' + esc(item.description) + "</p>" : "") +
      '<form class="add-form" data-add-form>' +
        '<input type="hidden" name="item_id" value="' + item.id + '">' +
        optionsHTML +
        '<button type="submit" class="btn">Add to cart</button>' +
      "</form>" +
    "</div></div>";
}

function renderCards(container, items) {
  if (!container) return;
  container.innerHTML = items.length
    ? items.map(itemCardHTML).join("")
    : '<p class="muted">No items here yet — check back soon.</p>';
}

// One submit handler for every add-to-cart form on the page (event delegation,
// so it also works for cards rendered after the page loaded).
document.addEventListener("submit", function (e) {
  var form = e.target.closest("[data-add-form]");
  if (!form) return;
  e.preventDefault();

  var item = findItem(form.elements.item_id.value);
  if (!item) return;

  // Work out the chosen option (if the item has any) and the unit price.
  var optionName = "";
  var unitPrice = item.price;
  if (item.options && item.options.length) {
    var select = form.elements.option_name;
    optionName = select ? select.value : item.options[0].name;
    item.options.forEach(function (o) {
      if (o.name === optionName) unitPrice = item.price + (Number(o.priceDelta) || 0);
    });
  }

  var cart = loadCart();
  var key = cartKey(item.id, optionName);
  if (cart[key]) {
    cart[key].qty = Math.min(20, cart[key].qty + 1); // same 20-per-line cap as PHP
  } else {
    cart[key] = { itemId: item.id, name: item.name, optionName: optionName, unitPrice: unitPrice, qty: 1 };
  }
  saveCart(cart);
  updateCartBadge();
  showToast("Added to your cart.");
});

/* ---------------- home page ---------------- */

function initHome() {
  // Photo hero: background picture comes from CONFIG (like the PHP setting).
  var hero = $("#hero");
  if (hero && CONFIG.heroImage) {
    hero.classList.add("hero--photo");
    hero.style.backgroundImage =
      "linear-gradient(100deg, rgba(38, 25, 14, 0.88) 0%, rgba(38, 25, 14, 0.62) 55%, rgba(38, 25, 14, 0.30) 100%), url('" + CONFIG.heroImage + "')";
  }
  var kicker = $("#hero-kicker"); if (kicker) kicker.textContent = CONFIG.tagline;
  var title = $("#hero-title"); if (title) title.textContent = CONFIG.storeName;

  // Featured items ("Customer favorites").
  renderCards($("#featured-grid"), MENU_ITEMS.filter(function (i) { return i.featured; }));

  // Story photo.
  var storyImg = $("#story-img");
  if (storyImg && CONFIG.storyImage) {
    storyImg.src = CONFIG.storyImage;
    storyImg.alt = "Inside " + CONFIG.storeName;
  }

  // Gallery strip.
  var gallery = $("#gallery-grid");
  if (gallery) {
    var alts = [
      "Coffee shop interior with guests at the counter",
      "A quiet corner table with a cup of coffee",
      "Barista pouring latte art",
      "Fresh coffee being brewed by hand"
    ];
    gallery.innerHTML = CONFIG.galleryImages.map(function (src, i) {
      return '<img src="' + esc(src) + '" alt="' + esc(alts[i] || "Inside the coffee shop") + '" loading="lazy">';
    }).join("");
  }

  fillShopInfo();
}

/* ---------------- menu page ---------------- */

function initMenu() {
  var pillsHost = $("#cat-pills");
  var grid = $("#menu-grid");
  if (!grid) return;

  // Start on the category from the URL (?cat=Espresso), like the PHP ?cat=id.
  var requested = new URLSearchParams(window.location.search).get("cat");
  var active = CATEGORIES.indexOf(requested) !== -1 ? requested : "All";

  function drawPills() {
    if (!pillsHost) return;
    var names = ["All"].concat(CATEGORIES);
    pillsHost.innerHTML = names.map(function (name) {
      return '<button type="button" data-cat="' + esc(name) + '"' +
             (name === active ? ' class="active"' : "") + ">" + esc(name) + "</button>";
    }).join("");
  }
  function drawItems() {
    renderCards(grid, active === "All"
      ? MENU_ITEMS
      : MENU_ITEMS.filter(function (i) { return i.category === active; }));
  }
  if (pillsHost) {
    pillsHost.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-cat]");
      if (!btn) return;
      active = btn.getAttribute("data-cat");
      drawPills();
      drawItems();
    });
  }
  drawPills();
  drawItems();
}

/* ---------------- cart page ---------------- */

function initCartPage() {
  var root = $("#cart-root");
  if (!root) return;

  function draw() {
    var lines = cartLines();
    updateCartBadge();
    if (!lines.length) {
      root.innerHTML = '<p class="muted">Your cart is empty. <a href="menu.html">Browse the menu</a> to add something tasty.</p>';
      return;
    }
    var subtotal = cartSubtotal();
    var rows = lines.map(function (l) {
      return "<tr>" +
        "<td><strong>" + esc(l.name) + "</strong>" +
          (l.optionName ? '<br><span class="muted">' + esc(l.optionName) + "</span>" : "") + "</td>" +
        "<td>" + esc(money(l.unitPrice)) + "</td>" +
        '<td><span class="qty-stepper">' +
          '<button type="button" class="qty-btn" data-dec="' + esc(l.key) + '" aria-label="One less">&minus;</button>' +
          '<span class="qty-value">' + l.qty + "</span>" +
          '<button type="button" class="qty-btn" data-inc="' + esc(l.key) + '" aria-label="One more">+</button>' +
        "</span></td>" +
        "<td>" + esc(money(l.lineTotal)) + "</td>" +
        '<td><button type="button" class="link-danger" data-remove="' + esc(l.key) + '">Remove</button></td>' +
      "</tr>";
    }).join("");

    var freeNote = "";
    if (CONFIG.freeDeliveryOver > 0) {
      freeNote = subtotal >= CONFIG.freeDeliveryOver
        ? '<p class="muted small">You have unlocked FREE delivery.</p>'
        : '<p class="muted small">Add ' + esc(money(CONFIG.freeDeliveryOver - subtotal)) + " more for free delivery.</p>";
    }

    root.innerHTML =
      '<table class="cart-table"><thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr></thead>' +
      "<tbody>" + rows + "</tbody></table>" +
      '<div class="cart-summary">' +
        '<div class="sum-row"><span>Subtotal</span><strong>' + esc(money(subtotal)) + "</strong></div>" +
        freeNote +
        '<p class="muted small">Delivery fee (if any) is calculated at checkout.</p>' +
        '<a class="btn btn-primary btn-big" href="checkout.html">Proceed to checkout</a>' +
      "</div>";
  }

  // Quantity +/- and Remove buttons (event delegation — rows are re-rendered).
  root.addEventListener("click", function (e) {
    var cart = loadCart();
    var inc = e.target.closest("[data-inc]");
    var dec = e.target.closest("[data-dec]");
    var rem = e.target.closest("[data-remove]");
    if (inc && cart[inc.getAttribute("data-inc")]) {
      var k1 = inc.getAttribute("data-inc");
      cart[k1].qty = Math.min(20, cart[k1].qty + 1);
    } else if (dec && cart[dec.getAttribute("data-dec")]) {
      var k2 = dec.getAttribute("data-dec");
      cart[k2].qty -= 1;
      if (cart[k2].qty <= 0) delete cart[k2]; // stepping down from 1 removes the line (same as PHP qty 0)
    } else if (rem) {
      delete cart[rem.getAttribute("data-remove")];
    } else {
      return;
    }
    saveCart(cart);
    draw();
  });

  draw();
}

/* ---------------- checkout page ---------------- */

function initCheckout() {
  var form = $("#checkout-form");
  if (!form) return;

  var lines = cartLines();
  if (!lines.length) {
    window.location.href = "cart.html"; // nothing to check out (PHP did the same)
    return;
  }
  var subtotal = cartSubtotal();

  var summary = $("#order-summary");
  var addressWrap = $("[data-address-wrap]", form);
  var payHost = $("#payment-options");

  function currentType() {
    var checked = $('input[name="order_type"]:checked', form);
    return checked ? checked.value : "pickup";
  }

  // Payment radio list for the selected order type (mirrors payments.php:
  // e.g. "Cash on Pickup" is only offered for pickup orders).
  function drawPayments() {
    var type = currentType();
    var current = $('input[name="payment_method"]:checked', form);
    var currentCode = current ? current.value : "";
    var visible = PAYMENT_METHODS.filter(function (m) { return m.types.indexOf(type) !== -1; });
    if (!visible.some(function (m) { return m.code === currentCode; })) {
      currentCode = visible.length ? visible[0].code : "";
    }
    payHost.innerHTML = visible.map(function (m) {
      return '<label class="radio-line">' +
        '<input type="radio" name="payment_method" value="' + esc(m.code) + '"' + (m.code === currentCode ? " checked" : "") + ">" +
        "<span><strong>" + esc(m.label) + "</strong>" +
        (m.instructions ? "<small>" + esc(m.instructions) + "</small>" : "") + "</span></label>";
    }).join("");
  }

  function drawSummary() {
    var type = currentType();
    var fee = deliveryFeeFor(subtotal, type);
    var lineHTML = lines.map(function (l) {
      return '<div class="sum-line"><span>' + l.qty + " &times; " + esc(l.name) +
        (l.optionName ? " (" + esc(l.optionName) + ")" : "") + "</span><span>" + esc(money(l.lineTotal)) + "</span></div>";
    }).join("");
    summary.innerHTML = "<h3>Order summary</h3>" + lineHTML +
      '<div class="sum-row"><span>Subtotal</span><span>' + esc(money(subtotal)) + "</span></div>" +
      '<div class="sum-row"><span>Delivery fee</span><span>' + (fee > 0 ? esc(money(fee)) : "FREE") + "</span></div>" +
      '<div class="sum-row"><span>Total</span><strong>' + esc(money(subtotal + fee)) + "</strong></div>";
  }

  function refresh() {
    if (addressWrap) addressWrap.style.display = currentType() === "delivery" ? "" : "none";
    drawPayments();
    drawSummary();
  }
  $$('input[name="order_type"]', form).forEach(function (r) {
    r.addEventListener("change", refresh);
  });
  // Payment radios are re-rendered by drawPayments, so listen on the container.
  payHost.addEventListener("change", drawSummary);
  refresh();

  /* --- validation + placing the order (mirrors the PHP checkout rules) --- */
  function setError(name, message) {
    var slot = $('[data-error-for="' + name + '"]', form);
    if (slot) slot.textContent = message || "";
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    ["name", "phone", "email", "address", "payment_method"].forEach(function (n) { setError(n, ""); });

    var name = form.elements.name.value.trim();
    var phone = form.elements.phone.value.trim();
    var email = form.elements.email.value.trim();
    var type = currentType();
    var address = form.elements.address.value.trim();
    var notes = form.elements.notes.value.trim();
    var payRadio = $('input[name="payment_method"]:checked', form);
    var payment = payRadio ? payRadio.value : "";
    var ok = true;

    if (name.length < 2 || name.length > 120) { setError("name", "Please enter your name."); ok = false; }
    var digits = phone.replace(/\D/g, "");
    if (digits.length < 6 || digits.length > 20) { setError("phone", "Please enter a valid phone number."); ok = false; }
    if (email !== "" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { setError("email", "Please enter a valid email address (or leave it empty)."); ok = false; }
    if (type === "delivery" && address.length < 5) { setError("address", "Please enter your delivery address."); ok = false; }
    if (!payment) { setError("payment_method", "Please choose a payment method."); ok = false; }
    if (!ok) return;

    var fee = deliveryFeeFor(subtotal, type);
    var order = {
      number: makeOrderNumber(),
      name: name,
      phone: phone,
      email: email,
      orderType: type,
      address: type === "delivery" ? address : "",
      paymentMethod: payment,
      lines: lines.map(function (l) {
        return { name: l.name, optionName: l.optionName, unitPrice: l.unitPrice, qty: l.qty };
      }),
      subtotal: subtotal,
      deliveryFee: fee,
      total: subtotal + fee,
      notes: notes,
      status: "pending",
      createdAt: new Date().toISOString()
    };

    var orders = loadOrders();
    orders.push(order);
    saveOrders(orders);
    saveJSON(LAST_KEY, order.number);
    saveCart({}); // cart is empty once the order is placed
    window.location.href = "success.html";
  });
}

// Order number in the PHP format: BC- + YYMMDD + "-" + 6 random hex chars,
// e.g. BC-261023-A3F9K2. Checked against saved orders so it never repeats.
function makeOrderNumber() {
  var d = new Date();
  var pad = function (n) { return String(n).padStart(2, "0"); };
  var datePart = String(d.getFullYear()).slice(2) + pad(d.getMonth() + 1) + pad(d.getDate());
  var existing = loadOrders().map(function (o) { return o.number; });
  for (var i = 0; i < 10; i++) {
    var suffix = "";
    var hex = "0123456789ABCDEF";
    for (var j = 0; j < 6; j++) suffix += hex[Math.floor(Math.random() * 16)];
    var num = "BC-" + datePart + "-" + suffix;
    if (existing.indexOf(num) === -1) return num;
  }
  return "BC-" + datePart + "-" + Date.now().toString(16).toUpperCase().slice(-6);
}

function findOrder(number) {
  var wanted = String(number || "").trim().toUpperCase();
  var orders = loadOrders();
  for (var i = 0; i < orders.length; i++) {
    if (orders[i].number.toUpperCase() === wanted) return orders[i];
  }
  return null;
}

/* ---------------- success page ---------------- */

function initSuccess() {
  var root = $("#success-root");
  if (!root) return;
  var last = loadJSON(LAST_KEY, "");
  var order = last ? findOrder(last) : null;

  if (!order) {
    root.innerHTML =
      "<h1>No recent order found</h1>" +
      '<p class="muted">If you just placed an order, it may have expired from this page. You can still track it with your order number.</p>' +
      '<div class="btn-row"><a class="btn btn-primary" href="track.html">Track an order</a>' +
      '<a class="btn btn-ghost" href="menu.html">Browse menu</a></div>';
    return;
  }

  var itemsRows = order.lines.map(function (l) {
    return "<tr><td>" + l.qty + " &times; " + esc(l.name) +
      (l.optionName ? " (" + esc(l.optionName) + ")" : "") + '</td><td class="num">' + esc(money(l.unitPrice * l.qty)) + "</td></tr>";
  }).join("");

  root.innerHTML =
    '<div class="success-hero"><div class="success-check">&#10003;</div>' +
      "<h1>Thank you, " + esc(order.name) + "!</h1>" +
      "<p>Your order has been saved. Send it to the shop on WhatsApp so they can confirm and start preparing it.</p></div>" +
    '<div class="order-number-box"><span>Your order number</span><strong>' + esc(order.number) + "</strong>" +
      "<small>Save this number — you can use it to find this order again on the Track page (on this device).</small></div>" +
    '<table class="detail-table">' + itemsRows +
      '<tr><th>Subtotal</th><td class="num">' + esc(money(order.subtotal)) + "</td></tr>" +
      '<tr><th>Delivery fee</th><td class="num">' + esc(money(order.deliveryFee)) + "</td></tr>" +
      '<tr><th>Order type</th><td>' + (order.orderType === "delivery" ? "Delivery" : "Pickup") + "</td></tr>" +
      '<tr><th>Payment</th><td>' + esc(paymentLabel(order.paymentMethod)) + "</td></tr>" +
      '<tr><th>Total</th><td><strong>' + esc(money(order.total)) + "</strong></td></tr>" +
    "</table>" +
    (order.paymentMethod === "bank_transfer" && CONFIG.bankInfo
      ? '<div class="notice"><strong>Bank transfer details</strong><p>' + esc(CONFIG.bankInfo) + "</p></div>" : "") +
    '<div class="btn-row">' +
      '<a class="btn btn-whatsapp btn-big" href="' + esc(whatsappLink(orderWhatsappText(order))) + '" target="_blank" rel="noopener">Send order on WhatsApp</a>' +
    "</div>" +
    '<div class="btn-row">' +
      '<a class="btn btn-primary" href="track.html">Track my order</a>' +
      '<a class="btn btn-ghost" href="menu.html">Back to menu</a>' +
    "</div>";
}

/* ---------------- track page ---------------- */

function initTrack() {
  var form = $("#track-form");
  if (!form) return;
  var result = $("#track-result");

  // If we just came from the success page, prefill the last order number.
  var last = loadJSON(LAST_KEY, "");
  if (last) form.elements.order_number.value = last;

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var order = findOrder(form.elements.order_number.value);
    if (!order) {
      result.innerHTML = '<div class="flash flash-error"><span>No order found with that number on this device. ' +
        "Please check the number and try again.</span></div>";
      return;
    }

    var label = ORDER_STATUSES[order.status] || "Order received";
    var steps = ["pending", "confirmed", "preparing", "ready", "completed"];
    var currentIndex = steps.indexOf(order.status);
    if (currentIndex === -1) currentIndex = 0;
    var timeline = steps.map(function (s, i) {
      var cls = i < currentIndex ? "done" : (i === currentIndex ? "current" : "");
      return '<li class="' + cls + '"><span class="dot"></span><span>' + esc(ORDER_STATUSES[s]) + "</span></li>";
    }).join("");

    var itemsRows = order.lines.map(function (l) {
      return "<tr><td>" + l.qty + " &times; " + esc(l.name) +
        (l.optionName ? " (" + esc(l.optionName) + ")" : "") + '</td><td class="num">' + esc(money(l.unitPrice * l.qty)) + "</td></tr>";
    }).join("");

    result.innerHTML =
      '<div class="order-card"><div class="order-card-head"><div>' +
        "<strong>" + esc(order.number) + '</strong> <span class="muted"> &middot; ' + esc(formatDate(order.createdAt)) + "</span></div>" +
        '<span class="status-pill status-' + esc(order.status) + '">' + esc(label) + "</span></div>" +
        '<ol class="timeline">' + timeline + "</ol>" +
        '<table class="detail-table">' + itemsRows +
          '<tr><th>Subtotal</th><td class="num">' + esc(money(order.subtotal)) + "</td></tr>" +
          '<tr><th>Delivery fee</th><td class="num">' + esc(money(order.deliveryFee)) + "</td></tr>" +
          '<tr><th>Total</th><td class="num"><strong>' + esc(money(order.total)) + "</strong></td></tr>" +
          '<tr><th>Order type</th><td>' + (order.orderType === "delivery" ? "Delivery" : "Pickup") + "</td></tr>" +
          '<tr><th>Payment</th><td>' + esc(paymentLabel(order.paymentMethod)) + "</td></tr>" +
        "</table>" +
        '<div class="notice"><strong>Please note</strong><p>This static edition keeps your orders on this device only, ' +
        "so the status here always starts at \u201COrder received\u201D. For live updates, the shop confirms your order " +
        "on WhatsApp — message them any time to ask how it is coming along.</p></div>" +
        '<div class="btn-row"><a class="btn btn-whatsapp" href="' + esc(whatsappLink(orderWhatsappText(order))) +
          '" target="_blank" rel="noopener">Message the shop on WhatsApp</a></div>' +
      "</div>";
  });
}

/* ---------------- contact page ---------------- */

function fillShopInfo() {
  // Pages repeat phone/address/hours in a few places — fill them all from CONFIG.
  $$("[data-fill]").forEach(function (el) {
    var key = el.getAttribute("data-fill");
    if (key === "phone") el.textContent = CONFIG.phone;
    if (key === "address") el.textContent = CONFIG.address;
    if (key === "hours") el.textContent = CONFIG.hours;
    if (key === "storeName") el.textContent = CONFIG.storeName;
    if (key === "tagline") el.textContent = CONFIG.tagline;
  });
}

function initContact() {
  fillShopInfo();
  var form = $("#contact-form");
  if (!form) return;
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var name = form.elements.name.value.trim();
    var contact = form.elements.contact.value.trim();
    var message = form.elements.message.value.trim();
    var err = $("#contact-error");
    if (name.length < 2 || contact.length < 5 || message.length < 5) {
      if (err) {
        err.hidden = false;
        err.querySelector("span").textContent = "Please fill in every field (name, phone or email, and a short message).";
      }
      return;
    }
    if (err) err.hidden = true;
    var text = "Hello " + CONFIG.storeName + "! I'm " + name + " (" + contact + ").\n\n" + message;
    window.open(whatsappLink(text), "_blank", "noopener");
    showToast("Opening WhatsApp…");
  });
}

/* ---------------- boot ----------------
   Runs on every page: build the shared chrome first, then start whichever
   page-specific section exists on this page. */

document.addEventListener("DOMContentLoaded", function () {
  // Brand colors from CONFIG become the site's CSS variables.
  document.documentElement.style.setProperty("--primary", CONFIG.primaryColor);
  document.documentElement.style.setProperty("--accent", CONFIG.accentColor);

  buildHeader();
  buildFooter();

  initHome();
  initMenu();
  initCartPage();
  initCheckout();
  initSuccess();
  initTrack();
  initContact();
});
