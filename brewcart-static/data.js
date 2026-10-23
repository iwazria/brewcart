"use strict";
/* ==========================================================================
   BrewCart — STATIC EDITION — shop data (data.js)
   --------------------------------------------------------------------------
   This file is the "control panel" of the static edition. There is no
   database and no admin page: to change the shop's name, colors, phone
   number, fees or menu, you edit the values below and re-upload the files.

   CONFIG     = everything about the shop itself.
   CATEGORIES = the menu sections, in display order.
   MENU_ITEMS = every dish/drink on the menu.
   ========================================================================== */

const CONFIG = {
  /* --- About the shop ------------------------------------------------- */
  storeName: "Demo Coffee House",
  tagline: "Brewed with love, served with a smile",
  phone: "+92 300 0000000",          // shown on the site (human format)
  whatsappNumber: "923000000000",    // used for wa.me links (country code + number, no +, no spaces)
  address: "Main Street, Your City",
  hours: "Mon-Sun, 8am to 11pm",
  announcement: "Free delivery on orders over Rs 2,000!", // top bar text; set to "" to hide it

  /* --- Money ---------------------------------------------------------- */
  currencySymbol: "Rs ",
  deliveryFee: 150,        // charged for delivery orders below the threshold
  freeDeliveryOver: 2000,  // delivery is free at/above this subtotal (0 = never free)

  /* --- Branding --------------------------------------------------------- */
  primaryColor: "#6F4E37", // coffee brown — buttons, prices, headings accents
  accentColor: "#C89B5A",  // caramel — highlights, badges, kickers

  /* --- Payments --------------------------------------------------------- */
  bankInfo: "Bank: Demo Bank - Account: 0000-0000000 (Demo Coffee House). Please share the receipt on WhatsApp after transferring.",

  /* --- Photos (any image URLs work; these are the starter Unsplash ones) - */
  heroImage: "https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1600&q=80",
  storyImage: "https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=1200&q=80",
  galleryImages: [
    "https://images.unsplash.com/photo-1521017432531-fbd92d768814?auto=format&fit=crop&w=800&q=80",
    "https://images.unsplash.com/photo-1445116572660-236099ec97a0?auto=format&fit=crop&w=800&q=80",
    "https://images.unsplash.com/photo-1453614512568-c4024d13c247?auto=format&fit=crop&w=800&q=80",
    "https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=800&q=80",
  ],
};

/* Menu sections, in the order they appear as filter pills on menu.html. */
const CATEGORIES = ["Espresso", "Brewed Coffee", "Cold and Iced", "Bakery"];

/* --------------------------------------------------------------------------
   The menu. One object per item:
     id          unique number (never reuse an id — the cart refers to it)
     category    must match one of CATEGORIES above
     name        item name
     description short, tasty description
     price       base price in rupees (options below can add to it)
     image       photo URL, or "" for a letter placeholder tile
     featured    true = also shown under "Customer favorites" on the home page
     options     size/add-on choices; priceDelta is ADDED to the base price.
                 Leave as [] when the item has no choices.
   -------------------------------------------------------------------------- */
const MENU_ITEMS = [
  { id: 1, category: "Espresso", name: "Espresso", price: 250,
    description: "A bold single shot of our house roast with rich golden crema on top.",
    image: "https://images.unsplash.com/photo-1504630083234-14187a9df0f5?auto=format&fit=crop&w=800&q=80",
    featured: true, options: [] },
  { id: 2, category: "Espresso", name: "Cappuccino", price: 350,
    description: "Classic espresso with steamed milk and a thick cloud of foam, dusted with cocoa.",
    image: "https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=800&q=80",
    featured: true, options: [] },
  { id: 3, category: "Espresso", name: "Cafe Latte", price: 380,
    description: "Smooth espresso folded into silky steamed milk. Gentle, milky and comforting.",
    image: "https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=800&q=80",
    featured: false,
    options: [ { name: "Regular", priceDelta: 0 }, { name: "Large", priceDelta: 80 } ] },
  { id: 4, category: "Espresso", name: "Caramel Macchiato", price: 450,
    description: "Vanilla-kissed milk marked with espresso and a caramel drizzle.",
    image: "https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 5, category: "Brewed Coffee", name: "French Press", price: 400,
    description: "Coarse-ground beans steeped slow for a full round cup. Serves one.",
    image: "https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 6, category: "Brewed Coffee", name: "Pour Over (V60)", price: 450,
    description: "Clean and bright single-origin coffee, brewed to order just for you.",
    image: "https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=800&q=80",
    featured: true, options: [] },
  { id: 7, category: "Brewed Coffee", name: "Cold Brew", price: 480,
    description: "Steeped for 18 hours for a naturally sweet and low-acid coffee.",
    image: "https://images.unsplash.com/photo-1517959105821-eaf2591984ca?auto=format&fit=crop&w=800&q=80",
    featured: false,
    options: [ { name: "Regular", priceDelta: 0 }, { name: "Large", priceDelta: 80 } ] },
  { id: 8, category: "Cold and Iced", name: "Iced Latte", price: 420,
    description: "Espresso poured over cold milk and ice. Simple and refreshing.",
    image: "https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=800&q=80",
    featured: false,
    options: [ { name: "Regular", priceDelta: 0 }, { name: "Large", priceDelta: 80 } ] },
  { id: 9, category: "Cold and Iced", name: "Iced Caramel Latte", price: 500,
    description: "Iced latte with caramel and a soft whipped cream top.",
    image: "https://images.unsplash.com/photo-1498804103079-a6351b050096?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 10, category: "Cold and Iced", name: "Coffee Frappe", price: 520,
    description: "Blended iced coffee, thick frosty and dangerously easy to drink.",
    image: "https://images.unsplash.com/photo-1512568400610-62da28bc8a13?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 11, category: "Bakery", name: "Butter Croissant", price: 280,
    description: "Flaky and golden, baked fresh every morning.",
    image: "https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 12, category: "Bakery", name: "Chocolate Chip Cookie", price: 220,
    description: "Warm with a gooey center, baked in-house daily.",
    image: "https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 13, category: "Bakery", name: "Banana Bread Slice", price: 260,
    description: "Moist banana bread with a hint of cinnamon. A quiet bestseller.",
    image: "https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
];
