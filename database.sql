-- ============================================================================
-- BrewCart v1 — database schema + starter (sample) data
-- ----------------------------------------------------------------------------
-- Used by install.php, which runs this file statement by statement.
-- You can also import it by hand in phpMyAdmin.
--
-- The menu items below are SAMPLE data so a fresh install looks alive.
-- Replace them with the real shop's menu from the admin panel.
-- ============================================================================

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  image VARCHAR(255) NOT NULL DEFAULT '',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_items_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS item_options (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  price_delta DECIMAL(10,2) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_options_item FOREIGN KEY (item_id)
    REFERENCES menu_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(20) NOT NULL UNIQUE,
  customer_name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(120) NOT NULL DEFAULT '',
  order_type ENUM('pickup','delivery') NOT NULL,
  address TEXT,
  payment_method VARCHAR(40) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('pending','confirmed','preparing','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
  notes TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  item_name VARCHAR(120) NOT NULL,
  option_name VARCHAR(80) NOT NULL DEFAULT '',
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  quantity INT NOT NULL DEFAULT 1,
  CONSTRAINT fk_orderitems_order FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  contact VARCHAR(120) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Store settings (defaults — the owner changes these on the Settings page)
-- ----------------------------------------------------------------------------
INSERT INTO settings (`key`, `value`) VALUES ('store_name', 'Demo Coffee House');
INSERT INTO settings (`key`, `value`) VALUES ('tagline', 'Brewed with love, served with a smile');
INSERT INTO settings (`key`, `value`) VALUES ('currency_symbol', 'Rs ');
INSERT INTO settings (`key`, `value`) VALUES ('currency_code', 'PKR');
INSERT INTO settings (`key`, `value`) VALUES ('primary_color', '#6F4E37');
INSERT INTO settings (`key`, `value`) VALUES ('accent_color', '#C89B5A');
INSERT INTO settings (`key`, `value`) VALUES ('logo_path', '');
INSERT INTO settings (`key`, `value`) VALUES ('hero_image', 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1600&q=80');
INSERT INTO settings (`key`, `value`) VALUES ('story_image', 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=1200&q=80');
INSERT INTO settings (`key`, `value`) VALUES ('phone', '+92 300 0000000');
INSERT INTO settings (`key`, `value`) VALUES ('address', 'Main Street, Your City');
INSERT INTO settings (`key`, `value`) VALUES ('hours', 'Mon-Sun, 8am to 11pm');
INSERT INTO settings (`key`, `value`) VALUES ('announcement', 'Free delivery on orders over Rs 2,000!');
INSERT INTO settings (`key`, `value`) VALUES ('delivery_fee', '150');
INSERT INTO settings (`key`, `value`) VALUES ('free_delivery_over', '2000');
INSERT INTO settings (`key`, `value`) VALUES ('bank_transfer_info', 'Bank: Demo Bank - Account: 0000-0000000 (Demo Coffee House). Please share the receipt on WhatsApp after transferring.');
INSERT INTO settings (`key`, `value`) VALUES ('admin_email', '');
INSERT INTO settings (`key`, `value`) VALUES ('order_email_enabled', '0');

-- ----------------------------------------------------------------------------
-- Sample categories
-- ----------------------------------------------------------------------------
INSERT INTO categories (name, sort_order) VALUES ('Espresso', 1);
INSERT INTO categories (name, sort_order) VALUES ('Brewed Coffee', 2);
INSERT INTO categories (name, sort_order) VALUES ('Cold and Iced', 3);
INSERT INTO categories (name, sort_order) VALUES ('Bakery', 4);

-- ----------------------------------------------------------------------------
-- Sample menu items (ids 1-13 on a fresh install — replace with the real menu)
-- ----------------------------------------------------------------------------
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (1, 'Espresso', 'A bold single shot of our house roast with rich golden crema on top.', 250.00, 'https://images.unsplash.com/photo-1504630083234-14187a9df0f5?auto=format&fit=crop&w=800&q=80', 1, 1);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (1, 'Cappuccino', 'Classic espresso with steamed milk and a thick cloud of foam, dusted with cocoa.', 350.00, 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=800&q=80', 1, 2);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (1, 'Cafe Latte', 'Smooth espresso folded into silky steamed milk. Gentle, milky and comforting.', 380.00, 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=800&q=80', 0, 3);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (1, 'Caramel Macchiato', 'Vanilla-kissed milk marked with espresso and a caramel drizzle.', 450.00, 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=80', 0, 4);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (2, 'French Press', 'Coarse-ground beans steeped slow for a full round cup. Serves one.', 400.00, 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=800&q=80', 0, 1);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (2, 'Pour Over (V60)', 'Clean and bright single-origin coffee, brewed to order just for you.', 450.00, 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=800&q=80', 1, 2);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (2, 'Cold Brew', 'Steeped for 18 hours for a naturally sweet and low-acid coffee.', 480.00, 'https://images.unsplash.com/photo-1517959105821-eaf2591984ca?auto=format&fit=crop&w=800&q=80', 0, 3);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (3, 'Iced Latte', 'Espresso poured over cold milk and ice. Simple and refreshing.', 420.00, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=800&q=80', 0, 1);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (3, 'Iced Caramel Latte', 'Iced latte with caramel and a soft whipped cream top.', 500.00, 'https://images.unsplash.com/photo-1498804103079-a6351b050096?auto=format&fit=crop&w=800&q=80', 0, 2);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (3, 'Coffee Frappe', 'Blended iced coffee, thick frosty and dangerously easy to drink.', 520.00, 'https://images.unsplash.com/photo-1512568400610-62da28bc8a13?auto=format&fit=crop&w=800&q=80', 0, 3);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (4, 'Butter Croissant', 'Flaky and golden, baked fresh every morning.', 280.00, 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=800&q=80', 0, 1);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (4, 'Chocolate Chip Cookie', 'Warm with a gooey center, baked in-house daily.', 220.00, 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=800&q=80', 0, 2);
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, sort_order) VALUES (4, 'Banana Bread Slice', 'Moist banana bread with a hint of cinnamon. A quiet bestseller.', 260.00, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80', 0, 3);

-- ----------------------------------------------------------------------------
-- Sample size options (Regular / Large) for a few drinks
-- ----------------------------------------------------------------------------
INSERT INTO item_options (item_id, name, price_delta, sort_order) VALUES (3, 'Regular', 0.00, 1);
INSERT INTO item_options (item_id, name, price_delta, sort_order) VALUES (3, 'Large', 80.00, 2);
INSERT INTO item_options (item_id, name, price_delta, sort_order) VALUES (7, 'Regular', 0.00, 1);
INSERT INTO item_options (item_id, name, price_delta, sort_order) VALUES (7, 'Large', 80.00, 2);
INSERT INTO item_options (item_id, name, price_delta, sort_order) VALUES (8, 'Regular', 0.00, 1);
INSERT INTO item_options (item_id, name, price_delta, sort_order) VALUES (8, 'Large', 80.00, 2);
