# BrewCart — Static Edition (HTML + CSS + JavaScript only)

BrewCart Static is a twin of the BrewCart PHP edition: the same branding,
photos, menu and design, rebuilt as **pure frontend code**. There is no
server, no database, no admin panel and no build step — just files you can
open in a browser or drop onto any static host.

**Tech:** plain HTML, one CSS file, and vanilla JavaScript (no libraries,
no frameworks). Works from `file://` and from any static hosting.

---

## How this edition differs from the PHP edition

| Thing | PHP edition | Static edition |
|---|---|---|
| Menu & settings | MySQL database + admin panel | Edited by hand in **`data.js`** |
| Cart | PHP session on the server | Saved in the visitor's browser (`localStorage`) |
| Orders | Saved in the database; owner sees them in admin | Saved in the **visitor's own browser**; the shop receives the order when the customer taps **"Send order on WhatsApp"** |
| Order tracking | Live status from the database | Shows the order saved on **this device**, always starting at "Order received" — the shop confirms real progress on WhatsApp |
| Contact form | Saved to the database | Opens WhatsApp with the message prefilled |
| Payments | Cash / bank transfer recorded on the order | Same methods, same wording — the customer tells the shop via WhatsApp |
| Hosting needed | PHP + MySQL (cPanel etc.) | Any static host, or even a USB stick |

Everything else is identical on purpose: same 13 sample menu items, same
prices, same Regular/Large options (+Rs 80), same delivery-fee rule
(Rs 150 under Rs 2,000, free at/above), same order-number format
(`BC-261023-A3F9K2`), same colors, fonts and layout.

---

## Files

| File | What it does |
|---|---|
| `index.html` | Home: photo hero, featured items, story, gallery, testimonials, info cards |
| `menu.html` | Full menu with category filter pills |
| `cart.html` | Cart with quantity +/− steppers, remove, subtotal, free-delivery hint |
| `checkout.html` | Customer form, pickup/delivery, payment methods, live order summary |
| `success.html` | Confirmation: order number, summary, **Send order on WhatsApp** button |
| `track.html` | Find an order placed on this device by its order number |
| `contact.html` | Shop info + a message form that opens WhatsApp |
| `style.css` | All styling (adapted from the PHP edition's stylesheet) |
| `data.js` | **The control panel:** shop settings (`CONFIG`), categories, and the whole menu (`MENU_ITEMS`) |
| `app.js` | All logic: header/footer, rendering, cart, checkout, orders, WhatsApp links |

Every page loads `style.css`, `data.js`, then `app.js` — in that order.

---

## How to customize (edit `data.js`)

Open `data.js` in any text editor. It is commented throughout.

**Shop details** — in `CONFIG`:
- `storeName`, `tagline` — shown in the header, hero and footer.
- `phone` — the number as displayed; `whatsappNumber` — the same number in
  international format with **no `+`, spaces or dashes** (e.g. `923001234567`).
  This is where orders and contact messages are sent, so set it to the shop's
  real WhatsApp number before launch.
- `address`, `hours`, `announcement` — the announcement is the colored bar at
  the very top; set it to `""` to hide it.
- `currencySymbol`, `deliveryFee`, `freeDeliveryOver` — money settings. The
  delivery fee applies to delivery orders below `freeDeliveryOver`
  (use `0` for "never free").
- `primaryColor` / `accentColor` — the whole site re-themes from these two.
- `bankInfo` — the bank-transfer instructions shown at checkout.
- `heroImage`, `storyImage`, `galleryImages` — photo URLs for the home page.

**The menu** — in `MENU_ITEMS` (and `CATEGORIES` for the sections):
Each item is one block like:

```js
{ id: 3, category: "Espresso", name: "Cafe Latte", price: 380,
  description: "Smooth espresso folded into silky steamed milk.",
  image: "https://…photo…",          // or "" for a letter placeholder tile
  featured: false,                    // true = show on the home page too
  options: [ { name: "Regular", priceDelta: 0 },
             { name: "Large",   priceDelta: 80 } ] },
```

- `price` is the base price; each option's `priceDelta` is **added** to it.
- Never reuse an `id` — the cart refers to items by id.
- The `category` must match one of the names in `CATEGORIES`.

After editing, just re-upload `data.js` — no other file needs to change.

---

## How to deploy

Upload all files (keeping them together in one folder) to any of these:

- **GitHub Pages** — push the folder to a repo, then Settings → Pages →
  deploy from branch. Free.
- **Netlify / Vercel** — drag-and-drop the folder in their dashboard. Free.
- **cPanel** — upload into `public_html` with File Manager, exactly like a
  static site. No database or Database Wizard needed.
- **Locally** — double-click `index.html`. (Cart and orders work because
  they live in the browser; a couple of very strict browsers restrict
  `file://` storage — if the cart ever misbehaves locally, run a tiny local
  server, e.g. `python3 -m http.server`, and open `http://localhost:8000`.)

No environment variables, no installer, nothing to configure on the server.

---

## How ordering works here (for the shop owner)

1. The customer builds a cart and checks out on the site.
2. The order is saved **in the customer's browser** and they land on a
   confirmation page with their order number.
3. They tap **Send order on WhatsApp** — WhatsApp opens with the full order
   (items, address, total, payment method) addressed to your number.
4. You confirm on WhatsApp like any other chat order, and prepare it.
5. The customer can re-open the order any time on **Track Order** — on the
   same phone/browser they ordered from.

Because there is no server, orders are **not** backed up anywhere else:
if a customer never taps the WhatsApp button, the shop never sees the order.
The confirmation page is deliberately built around that button.
