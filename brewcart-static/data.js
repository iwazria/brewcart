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

   Demo content below is an American coffee shop: "Liberty Roast Coffee"
   of Austin, Texas, with prices in US dollars.
   ========================================================================== */

const CONFIG = {
  /* --- About the shop ------------------------------------------------- */
  storeName: "Liberty Roast Coffee",
  tagline: "Small-batch roasted, proudly American",
  phone: "+1 (512) 555-0147",        // shown on the site (human format)
  whatsappNumber: "15125550147",     // used for wa.me links (country code + number, no +, no spaces)
  address: "1200 Congress Ave, Austin, TX 78701",
  hours: "Mon-Sun, 7am to 9pm",
  announcement: "Free delivery on orders over $35!", // top bar text; set to "" to hide it

  /* --- Money ---------------------------------------------------------- */
  currencySymbol: "$",
  deliveryFee: 4.99,       // charged for delivery orders below the threshold
  freeDeliveryOver: 35,    // delivery is free at/above this subtotal (0 = never free)

  /* --- Branding --------------------------------------------------------- */
  primaryColor: "#6F4E37", // coffee brown — buttons, prices, headings accents
  accentColor: "#B3402A",  // brick red — highlights, badges, kickers

  /* --- Payments --------------------------------------------------------- */
  bankInfo: "Zelle: orders@libertyroast.coffee (Liberty Roast Coffee). Please share a screenshot of your payment on WhatsApp after sending.",

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
     price       base price in US dollars (options below can add to it)
     image       photo URL, or "" for a letter placeholder tile
     featured    true = also shown under "Customer favorites" on the home page
     options     size/add-on choices; priceDelta is ADDED to the base price.
                 Leave as [] when the item has no choices.
   -------------------------------------------------------------------------- */
const MENU_ITEMS = [
  { id: 1, category: "Espresso", name: "Espresso", price: 3.25,
    description: "A bold double shot of our house roast with rich golden crema on top.",
    image: "https://images.unsplash.com/photo-1504630083234-14187a9df0f5?auto=format&fit=crop&w=800&q=80",
    featured: true, options: [] },
  { id: 2, category: "Espresso", name: "Cappuccino", price: 4.95,
    description: "Classic espresso with steamed milk and a thick cloud of foam, dusted with cocoa.",
    image: "https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=800&q=80",
    featured: true, options: [] },
  { id: 3, category: "Espresso", name: "Caffe Latte", price: 5.25,
    description: "Smooth espresso folded into silky steamed milk. Gentle, milky and comforting.",
    image: "https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=800&q=80",
    featured: false,
    options: [ { name: "12 oz", priceDelta: 0 }, { name: "16 oz", priceDelta: 0.75 }, { name: "20 oz", priceDelta: 1.25 } ] },
  { id: 4, category: "Espresso", name: "Caramel Macchiato", price: 5.75,
    description: "Vanilla-kissed milk marked with espresso and a caramel drizzle.",
    image: "https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 5, category: "Brewed Coffee", name: "House Drip Coffee", price: 3.50,
    description: "Our everyday American drip, brewed fresh every half hour. Bottomless refills in store.",
    image: "https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 6, category: "Brewed Coffee", name: "Pour Over (V60)", price: 5.50,
    description: "Clean and bright single-origin coffee, brewed to order just for you.",
    image: "https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=800&q=80",
    featured: true, options: [] },
  { id: 7, category: "Brewed Coffee", name: "Cold Brew", price: 5.25,
    description: "Steeped for 18 hours for a naturally sweet and low-acid coffee.",
    image: "https://images.unsplash.com/photo-1517959105821-eaf2591984ca?auto=format&fit=crop&w=800&q=80",
    featured: true,
    options: [ { name: "12 oz", priceDelta: 0 }, { name: "16 oz", priceDelta: 0.75 }, { name: "20 oz", priceDelta: 1.25 } ] },
  { id: 8, category: "Cold and Iced", name: "Iced Latte", price: 5.25,
    description: "Espresso poured over cold milk and ice. Simple and refreshing.",
    image: "https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=800&q=80",
    featured: false,
    options: [ { name: "12 oz", priceDelta: 0 }, { name: "16 oz", priceDelta: 0.75 }, { name: "20 oz", priceDelta: 1.25 } ] },
  { id: 9, category: "Cold and Iced", name: "Iced Caramel Latte", price: 5.95,
    description: "Iced latte with caramel and a soft whipped cream top.",
    image: "https://images.unsplash.com/photo-1498804103079-a6351b050096?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 10, category: "Cold and Iced", name: "Coffee Frappe", price: 6.25,
    description: "Blended iced coffee, thick frosty and dangerously easy to drink.",
    image: "https://images.unsplash.com/photo-1512568400610-62da28bc8a13?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 11, category: "Bakery", name: "Butter Croissant", price: 3.95,
    description: "Flaky and golden, baked fresh every morning.",
    image: "https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 12, category: "Bakery", name: "Blueberry Muffin", price: 3.75,
    description: "Bursting with real blueberries under a golden crumb top.",
    image: "https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
  { id: 13, category: "Bakery", name: "Chocolate Chip Cookie", price: 3.25,
    description: "Warm with a gooey center, baked in-house daily.",
    image: "https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=800&q=80",
    featured: false, options: [] },
];
