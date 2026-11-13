-- =====================================================================
-- Цветочный магазин с конструктором букетов — физическая модель БД (MySQL 8)
-- Скрипт нужен для проверки проекта БД (КП 3). В приложении таблицы
-- создаются миграциями Laravel: php artisan migrate
-- =====================================================================

DROP DATABASE IF EXISTS flower_shop_check;
CREATE DATABASE flower_shop_check CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flower_shop_check;

-- ---------------------------------------------------------------------
-- Таблицы
-- ---------------------------------------------------------------------

CREATE TABLE users (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(255) NOT NULL,
    email             VARCHAR(255) NOT NULL,
    phone             VARCHAR(20)  NULL,
    email_verified_at TIMESTAMP    NULL,
    password          VARCHAR(255) NOT NULL,
    role              VARCHAR(20)  NOT NULL DEFAULT 'customer',
    remember_token    VARCHAR(100) NULL,
    created_at        TIMESTAMP    NULL,
    updated_at        TIMESTAMP    NULL,
    CONSTRAINT users_email_unique UNIQUE (email),
    INDEX users_role_index (role)
) ENGINE=InnoDB;

CREATE TABLE occasions (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    slug       VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT occasions_slug_unique UNIQUE (slug)
) ENGINE=InnoDB;

CREATE TABLE flowers (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100)  NOT NULL,
    color          VARCHAR(20)   NOT NULL,
    price          DECIMAL(10,2) NOT NULL,
    stock_quantity INT UNSIGNED  NOT NULL DEFAULT 0,
    description    TEXT          NULL,
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,
    INDEX flowers_name_index (name),
    INDEX flowers_color_index (color),
    INDEX flowers_is_active_index (is_active),
    CONSTRAINT flowers_price_check CHECK (price > 0)
) ENGINE=InnoDB;

CREATE TABLE packagings (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)      NOT NULL,
    type        VARCHAR(20)       NOT NULL DEFAULT 'paper',
    color       VARCHAR(50)       NULL,
    price       DECIMAL(10,2)     NOT NULL DEFAULT 0,
    max_flowers SMALLINT UNSIGNED NOT NULL DEFAULT 51,
    is_active   TINYINT(1)        NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    INDEX packagings_is_active_index (is_active)
) ENGINE=InnoDB;

CREATE TABLE bouquets (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)  NOT NULL,
    description TEXT          NULL,
    price       DECIMAL(10,2) NOT NULL,
    image_path  VARCHAR(255)  NULL,
    is_active   TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    INDEX bouquets_name_index (name),
    INDEX bouquets_price_index (price),
    INDEX bouquets_is_active_index (is_active),
    CONSTRAINT bouquets_price_check CHECK (price > 0)
) ENGINE=InnoDB;

-- Состав готового букета: многие ко многим Bouquet ⇄ Flower
CREATE TABLE bouquet_flower (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bouquet_id BIGINT UNSIGNED   NOT NULL,
    flower_id  BIGINT UNSIGNED   NOT NULL,
    quantity   SMALLINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT bouquet_flower_bouquet_id_flower_id_unique UNIQUE (bouquet_id, flower_id),
    CONSTRAINT bouquet_flower_bouquet_id_foreign FOREIGN KEY (bouquet_id) REFERENCES bouquets (id) ON DELETE CASCADE,
    CONSTRAINT bouquet_flower_flower_id_foreign  FOREIGN KEY (flower_id)  REFERENCES flowers (id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Поводы букета: многие ко многим Bouquet ⇄ Occasion
CREATE TABLE bouquet_occasion (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bouquet_id  BIGINT UNSIGNED NOT NULL,
    occasion_id BIGINT UNSIGNED NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    CONSTRAINT bouquet_occasion_bouquet_id_occasion_id_unique UNIQUE (bouquet_id, occasion_id),
    CONSTRAINT bouquet_occasion_bouquet_id_foreign  FOREIGN KEY (bouquet_id)  REFERENCES bouquets (id)  ON DELETE CASCADE,
    CONSTRAINT bouquet_occasion_occasion_id_foreign FOREIGN KEY (occasion_id) REFERENCES occasions (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE custom_bouquets (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT UNSIGNED NOT NULL,
    packaging_id BIGINT UNSIGNED NOT NULL,
    name         VARCHAR(100) NOT NULL,
    comment      VARCHAR(500) NULL,
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL,
    CONSTRAINT custom_bouquets_user_id_foreign      FOREIGN KEY (user_id)      REFERENCES users (id)      ON DELETE CASCADE,
    CONSTRAINT custom_bouquets_packaging_id_foreign FOREIGN KEY (packaging_id) REFERENCES packagings (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE custom_bouquet_items (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    custom_bouquet_id BIGINT UNSIGNED   NOT NULL,
    flower_id         BIGINT UNSIGNED   NOT NULL,
    quantity          SMALLINT UNSIGNED NOT NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    CONSTRAINT custom_bouquet_items_custom_bouquet_id_flower_id_unique UNIQUE (custom_bouquet_id, flower_id),
    CONSTRAINT custom_bouquet_items_custom_bouquet_id_foreign FOREIGN KEY (custom_bouquet_id) REFERENCES custom_bouquets (id) ON DELETE CASCADE,
    CONSTRAINT custom_bouquet_items_flower_id_foreign         FOREIGN KEY (flower_id)         REFERENCES flowers (id)         ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE orders (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        BIGINT UNSIGNED NOT NULL,
    status         VARCHAR(20)   NOT NULL DEFAULT 'new',
    items_total    DECIMAL(10,2) NOT NULL,
    delivery_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    postcard_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_price    DECIMAL(10,2) NOT NULL,
    comment        VARCHAR(500)  NULL,
    cancelled_at   TIMESTAMP NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,
    INDEX orders_status_index (status),
    INDEX orders_created_at_index (created_at),
    CONSTRAINT orders_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id          BIGINT UNSIGNED   NOT NULL,
    bouquet_id        BIGINT UNSIGNED   NULL,
    custom_bouquet_id BIGINT UNSIGNED   NULL,
    quantity          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price        DECIMAL(10,2)     NOT NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    CONSTRAINT order_items_order_id_foreign          FOREIGN KEY (order_id)          REFERENCES orders (id)          ON DELETE CASCADE,
    CONSTRAINT order_items_bouquet_id_foreign        FOREIGN KEY (bouquet_id)        REFERENCES bouquets (id)        ON DELETE RESTRICT,
    CONSTRAINT order_items_custom_bouquet_id_foreign FOREIGN KEY (custom_bouquet_id) REFERENCES custom_bouquets (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE deliveries (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT UNSIGNED NOT NULL,
    courier_id      BIGINT UNSIGNED NULL,
    address         VARCHAR(255) NOT NULL,
    recipient_name  VARCHAR(100) NOT NULL,
    recipient_phone VARCHAR(20)  NOT NULL,
    delivery_date   DATE NOT NULL,
    time_from       TIME NOT NULL,
    time_to         TIME NOT NULL,
    delivered_at    TIMESTAMP NULL,
    courier_comment VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    CONSTRAINT deliveries_order_id_unique UNIQUE (order_id),
    INDEX deliveries_delivery_date_index (delivery_date),
    CONSTRAINT deliveries_order_id_foreign   FOREIGN KEY (order_id)   REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT deliveries_courier_id_foreign FOREIGN KEY (courier_id) REFERENCES users (id)  ON DELETE SET NULL,
    CONSTRAINT deliveries_time_check CHECK (time_to > time_from)
) ENGINE=InnoDB;

CREATE TABLE postcards (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   BIGINT UNSIGNED NOT NULL,
    design     VARCHAR(20)  NOT NULL DEFAULT 'classic',
    text       VARCHAR(500) NOT NULL,
    signature  VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT postcards_order_id_unique UNIQUE (order_id),
    CONSTRAINT postcards_order_id_foreign FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Тестовые данные (пароль — bcrypt-хеш строки "password")
-- ---------------------------------------------------------------------

INSERT INTO users (name, email, phone, password, role, created_at, updated_at) VALUES
('Администратор',   'admin@flowers.test',    '+7 900 000-00-01', '$2y$12$mTGpiC1UNqiSBuomj3p/D.ZK1gv7Z5ieDCzq6hYI7wXve7XvrbltG', 'admin',    NOW(), NOW()),
('Мария Флорист',   'florist@flowers.test',  '+7 900 000-00-02', '$2y$12$mTGpiC1UNqiSBuomj3p/D.ZK1gv7Z5ieDCzq6hYI7wXve7XvrbltG', 'florist',  NOW(), NOW()),
('Иван Курьер',     'courier@flowers.test',  '+7 900 000-00-03', '$2y$12$mTGpiC1UNqiSBuomj3p/D.ZK1gv7Z5ieDCzq6hYI7wXve7XvrbltG', 'courier',  NOW(), NOW()),
('Анна Покупатель', 'customer@flowers.test', '+7 900 000-00-04', '$2y$12$mTGpiC1UNqiSBuomj3p/D.ZK1gv7Z5ieDCzq6hYI7wXve7XvrbltG', 'customer', NOW(), NOW()),
('Олег Петров',     'oleg@example.com',      '+7 900 000-00-05', '$2y$12$mTGpiC1UNqiSBuomj3p/D.ZK1gv7Z5ieDCzq6hYI7wXve7XvrbltG', 'customer', NOW(), NOW());

INSERT INTO occasions (name, slug, created_at, updated_at) VALUES
('День рождения', 'birthday', NOW(), NOW()),
('Юбилей', 'anniversary', NOW(), NOW()),
('8 Марта', 'march-8', NOW(), NOW()),
('Свадьба', 'wedding', NOW(), NOW()),
('Для мамы', 'mom', NOW(), NOW());

INSERT INTO flowers (name, color, price, stock_quantity, description, created_at, updated_at) VALUES
('Роза красная «Freedom»', 'red', 190, 250, 'Эквадорская роза, 60 см', NOW(), NOW()),
('Роза белая «Mondial»', 'white', 200, 180, NULL, NOW(), NOW()),
('Пион розовый', 'pink', 450, 60, NULL, NOW(), NOW()),
('Тюльпан жёлтый', 'yellow', 90, 220, NULL, NOW(), NOW()),
('Эустома фиолетовая', 'purple', 220, 110, NULL, NOW(), NOW()),
('Эвкалипт (зелень)', 'green', 120, 200, NULL, NOW(), NOW());

INSERT INTO packagings (name, type, color, price, max_flowers, created_at, updated_at) VALUES
('Крафт-бумага', 'paper', 'бежевый', 150, 51, NOW(), NOW()),
('Матовая плёнка', 'film', 'прозрачный', 120, 101, NOW(), NOW()),
('Шляпная коробка малая', 'box', 'белый', 600, 25, NOW(), NOW()),
('Шляпная коробка большая', 'box', 'чёрный', 900, 51, NOW(), NOW()),
('Плетёная корзина', 'basket', 'натуральный', 1200, 101, NOW(), NOW());

INSERT INTO bouquets (name, description, price, created_at, updated_at) VALUES
('Классика', '25 красных роз', 6250, NOW(), NOW()),
('Белое облако', 'Белые розы, эустома и эвкалипт', 4350, NOW(), NOW()),
('Пионовая нежность', '9 розовых пионов', 5400, NOW(), NOW()),
('Солнечный день', 'Жёлтые тюльпаны с эвкалиптом', 1650, NOW(), NOW()),
('Для мамы', 'Пионы и эустома', 3900, NOW(), NOW());

INSERT INTO bouquet_flower (bouquet_id, flower_id, quantity, created_at, updated_at) VALUES
(1, 1, 25, NOW(), NOW()),
(2, 2, 9, NOW(), NOW()), (2, 5, 5, NOW(), NOW()), (2, 6, 3, NOW(), NOW()),
(3, 3, 9, NOW(), NOW()),
(4, 4, 11, NOW(), NOW()), (4, 6, 2, NOW(), NOW()),
(5, 3, 5, NOW(), NOW()), (5, 5, 4, NOW(), NOW());

INSERT INTO bouquet_occasion (bouquet_id, occasion_id, created_at, updated_at) VALUES
(1, 1, NOW(), NOW()), (1, 2, NOW(), NOW()),
(2, 4, NOW(), NOW()),
(3, 1, NOW(), NOW()), (3, 5, NOW(), NOW()),
(4, 3, NOW(), NOW()),
(5, 5, NOW(), NOW()), (5, 2, NOW(), NOW());

INSERT INTO custom_bouquets (user_id, packaging_id, name, comment, created_at, updated_at) VALUES
(4, 1, 'Мой букет №1', 'Без ленты', NOW(), NOW()),
(4, 3, 'Для подруги', NULL, NOW(), NOW()),
(5, 2, 'На свидание', NULL, NOW(), NOW()),
(5, 1, 'Коллеге', NULL, NOW(), NOW()),
(4, 5, 'Корзина', NULL, NOW(), NOW());

INSERT INTO custom_bouquet_items (custom_bouquet_id, flower_id, quantity, created_at, updated_at) VALUES
(1, 1, 3, NOW(), NOW()), (1, 6, 2, NOW(), NOW()),
(2, 3, 3, NOW(), NOW()), (2, 5, 4, NOW(), NOW()),
(3, 1, 7, NOW(), NOW()),
(4, 4, 5, NOW(), NOW()),
(5, 2, 9, NOW(), NOW()), (5, 5, 6, NOW(), NOW());

INSERT INTO orders (user_id, status, items_total, delivery_price, postcard_price, total_price, created_at, updated_at) VALUES
(4, 'delivered', 6250, 0, 100, 6350, NOW() - INTERVAL 5 DAY, NOW()),
(4, 'new', 1650, 400, 0, 2050, NOW(), NOW()),
(5, 'assembling', 4350, 400, 100, 4850, NOW(), NOW()),
(5, 'ready', 2570, 400, 0, 2970, NOW(), NOW()),
(4, 'cancelled', 5400, 0, 0, 5400, NOW() - INTERVAL 2 DAY, NOW());

INSERT INTO order_items (order_id, bouquet_id, custom_bouquet_id, quantity, unit_price, created_at, updated_at) VALUES
(1, 1, NULL, 1, 6250, NOW(), NOW()),
(2, 4, NULL, 1, 1650, NOW(), NOW()),
(3, 2, NULL, 1, 4350, NOW(), NOW()),
(4, NULL, 3, 1, 2570, NOW(), NOW()),
(5, 3, NULL, 1, 5400, NOW(), NOW());

INSERT INTO deliveries (order_id, courier_id, address, recipient_name, recipient_phone, delivery_date, time_from, time_to, delivered_at, created_at, updated_at) VALUES
(1, 3, 'г. Екатеринбург, ул. Ленина, д. 10', 'Ольга', '+7 912 000-00-01', CURDATE() - INTERVAL 4 DAY, '12:00', '14:00', NOW() - INTERVAL 4 DAY, NOW(), NOW()),
(2, NULL, 'г. Екатеринбург, ул. Мира, д. 5', 'Елена', '+7 912 000-00-02', CURDATE() + INTERVAL 2 DAY, '10:00', '12:00', NULL, NOW(), NOW()),
(3, NULL, 'г. Екатеринбург, ул. Малышева, д. 40', 'Ирина', '+7 912 000-00-03', CURDATE() + INTERVAL 1 DAY, '15:00', '17:00', NULL, NOW(), NOW()),
(4, NULL, 'г. Екатеринбург, ул. 8 Марта, д. 8', 'Светлана', '+7 912 000-00-04', CURDATE() + INTERVAL 1 DAY, '18:00', '20:00', NULL, NOW(), NOW()),
(5, NULL, 'г. Екатеринбург, ул. Радищева, д. 3', 'Мама', '+7 912 000-00-05', CURDATE() + INTERVAL 1 DAY, '09:00', '11:00', NULL, NOW(), NOW());

INSERT INTO postcards (order_id, design, text, signature, created_at, updated_at) VALUES
(1, 'birthday', 'С днём рождения!', 'Анна', NOW(), NOW()),
(3, 'romantic', 'Люблю тебя!', 'Олег', NOW(), NOW());

-- ---------------------------------------------------------------------
-- Проверочные запросы с JOIN
-- ---------------------------------------------------------------------

-- 1. Состав готовых букетов (многие ко многим)
SELECT b.name AS bouquet, f.name AS flower, bf.quantity
FROM bouquets b
JOIN bouquet_flower bf ON bf.bouquet_id = b.id
JOIN flowers f ON f.id = bf.flower_id
ORDER BY b.id, f.name;

-- 2. Букеты для повода «Для мамы» дешевле 5000 ₽
SELECT b.name, b.price
FROM bouquets b
JOIN bouquet_occasion bo ON bo.bouquet_id = b.id
JOIN occasions o ON o.id = bo.occasion_id
WHERE o.slug = 'mom' AND b.price < 5000;

-- 3. Стоимость индивидуальных букетов: цветы + упаковка + сборка 300 ₽
SELECT cb.id, cb.name, u.name AS customer, p.name AS packaging,
       SUM(f.price * cbi.quantity) + p.price + 300 AS total_price,
       SUM(cbi.quantity) AS stems
FROM custom_bouquets cb
JOIN users u ON u.id = cb.user_id
JOIN packagings p ON p.id = cb.packaging_id
JOIN custom_bouquet_items cbi ON cbi.custom_bouquet_id = cb.id
JOIN flowers f ON f.id = cbi.flower_id
GROUP BY cb.id, cb.name, u.name, p.name, p.price;

-- 4. Очередь флориста: активные заказы с датой доставки и получателем
SELECT o.id, o.status, u.name AS customer, d.delivery_date, d.time_from, d.time_to, d.recipient_name,
       pc.text AS postcard
FROM orders o
JOIN users u ON u.id = o.user_id
JOIN deliveries d ON d.order_id = o.id
LEFT JOIN postcards pc ON pc.order_id = o.id
WHERE o.status IN ('new', 'assembling', 'ready')
ORDER BY d.delivery_date, d.time_from;

-- 5. Количество заказов и сумма по статусам
SELECT status, COUNT(*) AS orders_count, SUM(total_price) AS amount
FROM orders
GROUP BY status;
