-- ============================================================
--  Base de données : ecommerce_db  (Projet PureGain)
--  Compatible : MySQL / MariaDB (XAMPP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS ecommerce_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ecommerce_db;

-- ============================================================
-- 1. TABLE : users
--    Utilisée par : signup.php, login.php, account.php,
--                   admin/customers.php, admin/orders.php
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id         INT           NOT NULL AUTO_INCREMENT,
  name       VARCHAR(120)  NOT NULL,
  email      VARCHAR(180)  NOT NULL UNIQUE,
  password   VARCHAR(255)  NOT NULL,        -- bcrypt hash
  role       ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. TABLE : categories
--    Utilisée par : add_product.php, products.php, Index.php
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
  id   INT          NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. TABLE : products
--    Utilisée par : add_product.php, products.php,
--                   product.php, order.php, Index.php
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
  id          INT            NOT NULL AUTO_INCREMENT,
  name        VARCHAR(200)   NOT NULL,
  description TEXT,
  price       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  stock       INT            NOT NULL DEFAULT 0,
  image       VARCHAR(255)   DEFAULT NULL,       -- nom du fichier dans /uploads/
  category_id INT            DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_product_category
    FOREIGN KEY (category_id) REFERENCES categories (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. TABLE : orders
--    Utilisée par : order.php, account.php,
--                   admin/orders.php, admin/dashboard.php
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
  id         INT           NOT NULL AUTO_INCREMENT,
  user_id    INT           NOT NULL,
  total      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status     ENUM('pending','confirmed','delivered','cancelled')
             NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_order_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. TABLE : order_items
--    Utilisée par : order.php  (INSERT)
--    (détail de chaque commande : produit + quantité + prix)
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
  id         INT           NOT NULL AUTO_INCREMENT,
  order_id   INT           NOT NULL,
  product_id INT           NOT NULL,
  quantity   INT           NOT NULL DEFAULT 1,
  price      DECIMAL(10,2) NOT NULL,           -- prix unitaire au moment de l'achat
  PRIMARY KEY (id),
  CONSTRAINT fk_item_order
    FOREIGN KEY (order_id)   REFERENCES orders  (id) ON DELETE CASCADE,
  CONSTRAINT fk_item_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  INSERTIONS DES DONNÉES DE TEST
-- ============================================================

-- ------------------------------------------------------------
-- users
--   • mot de passe "admin123"  → hash bcrypt généré en PHP
--   • mot de passe "user123"   → hash bcrypt généré en PHP
--   (remplacez les hashs par de vrais hashes si vous testez)
-- ------------------------------------------------------------
INSERT INTO users (name, email, password, role) VALUES
  ('Admin PureGain',  'admin@puregain.tn',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
  ('Ahmed Ben Salem', 'ahmed@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
  ('Sana Trabelsi',   'sana@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
  ('Mohamed Ayari',   'mohamed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- ------------------------------------------------------------
-- categories
-- ------------------------------------------------------------
INSERT INTO categories (name) VALUES
  ('Protéines'),
  ('Créatines'),
  ('Brûleurs de graisse'),
  ('Vitamines & Minéraux'),
  ('Barres & Snacks');

-- ------------------------------------------------------------
-- products
-- ------------------------------------------------------------
INSERT INTO products (name, description, price, stock, image, category_id) VALUES
  ('Whey Protein Gold 2kg',
   'Whey protéine de haute qualité, idéale pour la récupération musculaire après l\'entraînement. Saveur chocolat.',
   129.90, 50, NULL, 1),

  ('Isolat de Whey 1kg',
   'Protéine isolée à 90 % de pureté, faible en glucides et en matières grasses. Saveur vanille.',
   99.00, 35, NULL, 1),

  ('Créatine Monohydrate 500g',
   'Créatine pure micronisée pour augmenter la force et les performances physiques.',
   49.90, 80, NULL, 2),

  ('Créatine HCL 250g',
   'Créatine chlorhydrate à absorption rapide, sans rétention d\'eau.',
   64.00, 40, NULL, 2),

  ('Fat Burner Thermogenic 60 caps',
   'Complexe thermogénique pour accélérer le métabolisme et favoriser la perte de poids.',
   79.90, 60, NULL, 3),

  ('L-Carnitine Liquide 500ml',
   'L-Carnitine liquide pour optimiser l\'utilisation des graisses comme source d\'énergie.',
   44.50, 45, NULL, 3),

  ('Multivitamines Sport 90 tabs',
   'Formule complète en vitamines et minéraux adaptée aux sportifs.',
   34.90, 100, NULL, 4),

  ('Oméga-3 Fish Oil 90 caps',
   'Huile de poisson riche en EPA et DHA pour la santé cardiovasculaire et articulaire.',
   29.90, 75, NULL, 4),

  ('Barre Protéinée Chocolat x12',
   'Pack de 12 barres protéinées au chocolat, 20g de protéines par barre.',
   39.90, 120, NULL, 5),

  ('Mass Gainer 3kg',
   'Gainer hypercalorique pour la prise de masse musculaire. Riche en glucides complexes et protéines.',
   149.90, 25, NULL, 1);

-- ------------------------------------------------------------
-- orders
-- ------------------------------------------------------------
INSERT INTO orders (user_id, total, status, created_at) VALUES
  (2, 129.90, 'delivered',  '2026-04-10 10:23:00'),
  (3,  99.00, 'confirmed',  '2026-04-18 14:05:00'),
  (2,  49.90, 'pending',    '2026-05-01 09:11:00'),
  (4, 209.80, 'delivered',  '2026-05-05 16:44:00'),
  (3,  79.90, 'cancelled',  '2026-05-10 11:30:00'),
  (4,  34.90, 'pending',    '2026-05-17 08:55:00');

-- ------------------------------------------------------------
-- order_items
-- ------------------------------------------------------------
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
  -- commande #1 : Ahmed → Whey Gold 2kg x1
  (1, 1, 1, 129.90),
  -- commande #2 : Sana → Isolat Whey 1kg x1
  (2, 2, 1, 99.00),
  -- commande #3 : Ahmed → Créatine Monohydrate x1
  (3, 3, 1, 49.90),
  -- commande #4 : Mohamed → Whey Gold 2kg x1 + Isolat x1 (total 229.80... arrondi)
  (4, 1, 1, 129.90),
  (4, 2, 1, 79.90),
  -- commande #5 : Sana → Fat Burner x1
  (5, 5, 1, 79.90),
  -- commande #6 : Mohamed → Multivitamines x1
  (6, 7, 1, 34.90);
