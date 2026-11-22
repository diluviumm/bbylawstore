-- Bbylaa Store — skema untuk server (dipakai bin/install.php).
-- File ini dokumentasi; eksekusi nyata ada di bin/install.php agar sekaligus
-- menyeed katalog dari app/data/products.json.

CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT NOT NULL DEFAULT '');

CREATE TABLE categories (
    id TEXT PRIMARY KEY, name TEXT NOT NULL, emoji TEXT NOT NULL DEFAULT '',
    sort INTEGER NOT NULL DEFAULT 0, is_open INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE products (
    id INTEGER PRIMARY KEY, category_id TEXT NOT NULL,
    category_name TEXT NOT NULL DEFAULT '', name TEXT NOT NULL,
    subtext TEXT NOT NULL DEFAULT '', description TEXT NOT NULL DEFAULT '',
    features TEXT NOT NULL DEFAULT '[]', images TEXT NOT NULL DEFAULT '[]',
    badge TEXT NOT NULL DEFAULT '', badge_tone TEXT NOT NULL DEFAULT 'pink',
    accent TEXT NOT NULL DEFAULT 'pink', icon TEXT NOT NULL DEFAULT '',
    is_quote INTEGER NOT NULL DEFAULT 0, is_active INTEGER NOT NULL DEFAULT 1,
    sort INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    label TEXT NOT NULL, price INTEGER NOT NULL DEFAULT 0, price_max INTEGER,
    stock INTEGER, sold INTEGER NOT NULL DEFAULT 0,
    sort INTEGER NOT NULL DEFAULT 0, is_active INTEGER NOT NULL DEFAULT 1,
    UNIQUE (product_id, label)
);

CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT NOT NULL UNIQUE,
    customer_name TEXT NOT NULL, customer_phone TEXT NOT NULL,
    note TEXT NOT NULL DEFAULT '', payment TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'baru', source TEXT NOT NULL DEFAULT 'web',
    total INTEGER NOT NULL DEFAULT 0, item_count INTEGER NOT NULL DEFAULT 0,
    admin_note TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL, updated_at TEXT NOT NULL
);

CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL, product_name TEXT NOT NULL,
    variant_id INTEGER NOT NULL, variant_label TEXT NOT NULL,
    unit_price INTEGER NOT NULL DEFAULT 0, price_max INTEGER,
    qty INTEGER NOT NULL DEFAULT 1, subtotal INTEGER NOT NULL DEFAULT 0,
    category_name TEXT NOT NULL DEFAULT ''
);

CREATE TABLE admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL, created_at TEXT NOT NULL
);

CREATE TABLE audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT, actor TEXT NOT NULL DEFAULT '',
    action TEXT NOT NULL, detail TEXT NOT NULL DEFAULT '',
    ip_hash TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL
);

CREATE TABLE throttle (
    key TEXT PRIMARY KEY, count INTEGER NOT NULL DEFAULT 0,
    window_start INTEGER NOT NULL DEFAULT 0
);
