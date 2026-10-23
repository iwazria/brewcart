# BrewCart v1 — White-Label Coffee Shop Online Store

BrewCart is a complete, ready-to-sell online store for a coffee shop: a customer-facing
website (menu, cart, checkout, order tracking, contact) plus an admin panel (dashboard,
orders, menu manager, settings). It is **white-label** — every visible word, color and
price is editable from the admin panel, so the same package can be rebranded and
resold to many coffee shops.

**Tech:** plain PHP 7.4+ and MySQL/MariaDB. Zero dependencies, no frameworks, no
build step, no Composer. It runs on ordinary cheap shared (cPanel) hosting.

---

## Features

**Customer site**
- Home page with hero, featured items, hours/location cards, announcement bar
- Menu with category filter and size/add-on options (e.g. Regular / Large)
- Session cart with quantity updates, live totals, free-delivery progress hint
- Checkout: pickup or delivery, address for delivery, order notes
- Manual payments: Cash on Pickup, Cash on Delivery, Bank Transfer (details editable)
- Order confirmation with a hard-to-guess order number
- Order tracking by order number + phone verification, with a status timeline
- Contact form saved to the database

**Admin panel** (`/admin/`)
- Dashboard: today's orders and revenue, pending orders, item and message counts
- Orders: filter by status, view full details, update status step by step
- Menu items: add/edit/delete, category, price, featured/homepage and available toggles
- Per-item options: sizes and add-ons with price changes (e.g. Large +Rs 80)
- Categories: add/edit/delete with sort order
- Settings: store name, tagline, colors, logo, currency, phone, address, hours,
  announcement, delivery fee, free-delivery threshold, bank details, admin email

**Installer**
- `install.php` wizard: requirement checks → database/admin/store form → one-click
  setup (creates tables, seeds sample data, creates the admin, writes `config.php`)
- Locks itself after install and tells you to delete it

---

## Requirements

- PHP 7.4 or newer with the `pdo_mysql` extension (standard on cPanel)
- MySQL 5.7+ / MariaDB 10.2+
- Any shared hosting with cPanel (or any LAMP host)

---

## Installation (cPanel)

1. In cPanel, open **Database Wizard** and create a database, a database user, and
   give the user all privileges on the database. Note the three names.
2. Zip the BrewCart files on your computer, upload the zip in cPanel **File Manager**
   (usually into `public_html`, or a subfolder like `public_html/shop`), and **Extract**.
3. Visit `https://your-domain.com/install.php` (or `.../shop/install.php`).
4. Step 1 checks your server. Step 2 asks for the database details, your admin
   username/password, and the store name. Step 3 installs everything.
5. **Delete `install.php` from the server** when it tells you to (File Manager →
   right-click → Delete). This is important for security.
6. Log in at `https://your-domain.com/admin/` and replace the sample menu with the
   real one under **Menu items**.

Manual alternative (no wizard): import `database.sql` in phpMyAdmin, copy
`config.sample.php` to `config.php` with your credentials, create an empty file
named `installed.lock`, and insert an admin row with a `password_hash()` password.

---

## Rebranding checklist (for each new shop you sell to)

Everything below is on the admin **Settings** page — no code edits needed:

- [ ] Store name, tagline, phone, address, opening hours
- [ ] Primary color + accent color (the whole site re-themes instantly)
- [ ] Logo image path (upload e.g. `assets/logo.png`, or leave empty for a letter mark)
- [ ] Currency symbol / code
- [ ] Announcement bar text (or empty to hide)
- [ ] Delivery fee + free-delivery threshold
- [ ] Bank transfer details shown at checkout
- [ ] Admin email + order email alerts on/off
- [ ] Menu → Categories, then Menu items (delete the samples, add the real menu)
- [ ] Mark 3–6 items as **Featured** for the home page

---

## File map

```
index.php            home: hero, featured items, hours/location teaser
menu.php             menu with category filter (?cat=id) + add-to-cart forms
cart.php             cart view; handles add / update / remove actions
checkout.php         checkout form; validates, saves the order in a transaction
order-success.php    confirmation + order number (from session, not the URL)
track.php            order tracking by order number + phone, status timeline
contact.php          contact form → messages table; shows hours/address/phone
install.php          one-time installer wizard (delete after install)
config.sample.php    database config template (installer writes config.php)
database.sql         full schema + sample data (also used by the installer)
includes/
  db.php             PDO connection; redirects to install.php before setup
  helpers.php        esc(), setting(), money(), CSRF, flash, cart + order helpers
  header.php         shared layout head: brand colors, nav, announcement, flashes
  footer.php         shared footer
  payments.php       manual payment methods + the GATEWAY EXTENSION SLOT comment
  item-card.php      reusable menu-item card partial (used by home + menu)
assets/
  style.css          complete responsive design (brand colors as CSS variables)
  app.js             mobile nav, flash dismiss, delete confirms, checkout toggles
admin/
  login.php / logout.php
  index.php          dashboard: today's orders + revenue, pending, items
  orders.php         order list (status filter) + detail with status updates
  items.php          menu item CRUD + per-item size/add-on options
  categories.php     category CRUD + sort order
  settings.php       form for every settings key (rebranding lives here)
  inc/auth.php       login guard for all admin pages
  inc/header.php     admin layout + nav
  inc/footer.php     admin footer
```

---

## How to sell this

- **What the buyer needs:** their own domain name + any cheap shared hosting with
  cPanel and MySQL (most plans already include this). You install BrewCart for them
  with the wizard — it takes about 15 minutes.
- **Suggested model:** a one-time setup fee (install + rebrand + menu entry), plus an
  optional monthly maintenance fee (menu updates, backups, small changes). Price it
  for your market; the product costs you nothing to reproduce.
- **Payments:** v1 takes cash and bank transfer only — perfect for shops starting
  out. When a buyer wants online payments, *they* open their own JazzCash, Easypaisa
  or Stripe merchant account, and you (or a developer) wire it up in
  `includes/payments.php`, where the extension slot is clearly marked. Never invent
  or fake gateway API calls — only integrate using the provider's real documentation.
- **Each buyer gets their own install** on their own hosting with their own database.
  Never share one install (or `config.php`) between two shops.

---

## Security notes

- All database queries use PDO prepared statements — user input can never alter SQL.
- All dynamic output is escaped with `esc()` — protection against XSS.
- Every POST form carries a CSRF token verified with `hash_equals()`.
- Admin passwords are stored with `password_hash()` and checked with
  `password_verify()`; login regenerates the session id.
- Order numbers are random and unguessable, and tracking requires the phone number
  from the order — customers can't enumerate other people's orders.
- `install.php` locks itself with `installed.lock` and must be **deleted** after
  install; `config.php` (database credentials) must never be shared or published.
- Checkout totals are recomputed on the server from the database — prices sent by
  the browser are never trusted.

---

## How it works (beginner overview)

If you're learning backend development, BrewCart is a small, readable example of the
 classic pattern:

1. **Browser → PHP:** a page like `menu.php` runs PHP on the server, which asks the
   database for the menu items and prints HTML.
2. **Forms → POST:** clicking "Add to cart" submits a form to `cart.php`, which checks
   the CSRF token, validates the item, and stores it in `$_SESSION` (the cart).
3. **Checkout → transaction:** `checkout.php` validates everything again on the
   server, then saves the order and its items inside a database *transaction* — so
   either the whole order is saved or nothing is (no half-orders).
4. **Settings table:** the shop's name, colors and fees live in one `settings` table
   as key/value pairs. The `setting('store_name')` helper reads them, which is why
   rebranding needs zero code changes.
5. **Admin panel:** the same PHP + MySQL, behind a login check (`admin/inc/auth.php`),
   with forms that INSERT/UPDATE/DELETE rows.

Suggested learning path: read `includes/helpers.php` first (every helper is
commented), then `menu.php` (read from DB → HTML), then `cart.php` (forms + session),
then `checkout.php` (validation + transaction).

---

## Optional: order email alerts

On the Settings page you can enable "Email me on new orders". When on, BrewCart calls
PHP's `mail()` after each order inside a try/catch so a mail failure can never break
checkout. **Note:** this needs working outgoing mail on the server — many shared hosts
require you to set up an email account or SMTP first. If emails don't arrive, check
with the hosting provider; the orders are still safely saved in the admin panel.
