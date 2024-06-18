DROP TABLE IF EXISTS provinces;
CREATE TABLE provinces  (
    id INT AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    PRIMARY KEY(id)
);

DROP TABLE IF EXISTS roles;
CREATE TABLE roles  (
    id INT AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    user_level INT NOT NULL DEFAULT 3,
    description VARCHAR(500),
    PRIMARY KEY(id)
);

DROP TABLE IF EXISTS users;
CREATE TABLE users  (
    id INT AUTO_INCREMENT,
    user VARCHAR(255) UNIQUE NOT NULL,
    code VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    is_disabled TINYINT(1) DEFAULT FALSE,
    is_confirmed TINYINT(1) DEFAULT FALSE,
    session_key CHAR(26),
    PRIMARY KEY(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

DROP TABLE IF EXISTS users_code;
CREATE TABLE users_code (
    id INT AUTO_INCREMENT,
    user_id INT NOT NULL,
    activation_code CHAR(5) NOT NULL,
    is_creating TINYINT(1) DEFAULT TRUE,
    created_at DATETIME DEFAULT NOW(),
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

DROP TABLE IF EXISTS userinfo;
CREATE TABLE userinfo  ( 
    user_id INT,
    phone CHAR(10),
    province_id INT NOT NULL,
    address VARCHAR(500),
    is_verified TINYINT(1) DEFAULT FALSE,
    PRIMARY KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (province_id) REFERENCES provinces(id)
);

DROP TABLE IF EXISTS support_messages;
CREATE TABLE support_messages  (
    id INT AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255),
    message TEXT,
    sent_at DATETIME DEFAULT NOW(),
    is_unread TINYINT(1) DEFAULT TRUE,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

DROP TABLE IF EXISTS stores;
CREATE TABLE stores  (
    id INT AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    logo VARCHAR(255),
    province_id INT NOT NULL,
    address VARCHAR(500),
    user_id INT NOT NULL,
    comment VARCHAR(500),
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME ON UPDATE NOW(),
    price_round INT DEFAULT 1,
    is_disabled TINYINT(1) DEFAULT FALSE,
    is_city_enabled TINYINT(1) DEFAULT FALSE,
    PRIMARY KEY(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (province_id) REFERENCES provinces(id)
);

DROP TABLE IF EXISTS categories;
CREATE TABLE categories  (
    id INT AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at DATETIME DEFAULT NOW(),
    PRIMARY KEY(id)
);

DROP TABLE IF EXISTS subcategories;
CREATE TABLE subcategories  (
    id INT AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    singular_name VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    created_at DATETIME DEFAULT NOW(),
    PRIMARY KEY(id),
    UNIQUE(name, category_id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

DROP TABLE IF EXISTS sizes;
CREATE TABLE sizes  (
    id INT AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    subcategory_id INT NOT NULL,
    unique(name, subcategory_id),
    PRIMARY KEY(id),
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id)
);

DROP TABLE IF EXISTS products;
CREATE TABLE products  (
    id INT AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    subcategory_id INT NOT NULL,
    store_id INT NOT NULL,
    logo VARCHAR(255),
    price INT NOT NULL,
    is_disabled TINYINT(1) DEFAULT FALSE,
    added_by INT NOT NULL,
    comment VARCHAR(500),
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME ON UPDATE NOW(),
    UNIQUE(name, store_id),
    PRIMARY KEY(id),
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id),
    FOREIGN KEY (store_id) REFERENCES stores(id),
    FOREIGN KEY (added_by) REFERENCES users(id)
);

DROP TABLE IF EXISTS product_images;
CREATE TABLE product_images  (
    id INT AUTO_INCREMENT,
    product_id INT NOT NULL,
    src VARCHAR(500),
    color CHAR(6),
    UNIQUE (product_id, color),
    PRIMARY KEY(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

DROP TABLE IF EXISTS warehouse;
CREATE TABLE warehouse  (
    id INT AUTO_INCREMENT,
    product_id INT NOT NULL,
    product_images_id INT,
    size_id INT,
    count INT NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    UNIQUE(product_images_id, size_id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (product_images_id) REFERENCES product_images(id),
    FOREIGN KEY (size_id) REFERENCES sizes(id)
);

DROP TABLE IF EXISTS offers;
CREATE TABLE offers  (
    product_id INT,
    started_at DATE,
    end_at DATE,
    PRIMARY KEY(product_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS solds;
CREATE TABLE solds  (
    product_id INT,
    started_at DATE,
    end_at DATE,
    sold_amount INT NOT NULL,
    is_sold_percent TINYINT(1) DEFAULT TRUE,
    PRIMARY KEY(product_id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

DROP TABLE IF EXISTS favorites;
CREATE TABLE favorites  (
    user_id INT,
    product_id INT,
    added_at DATETIME DEFAULT NOW(),
    PRIMARY KEY (user_id, product_id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

DROP TABLE IF EXISTS statuses;
CREATE TABLE statuses  LIKE provinces;

DROP TABLE IF EXISTS shipmethods;
CREATE TABLE shipmethods LIKE provinces;

DROP TABLE IF EXISTS orders;
CREATE TABLE orders  (
    id INT(6) ZEROFILL AUTO_INCREMENT,
    user_id INT NOT NULL,
    status_id INT NOT NULL,
    ordered_at DATETIME DEFAULT NOW(),
    shipped_at DATE,
    updated_at DATETIME ON UPDATE NOW(),
    sold INT DEFAULT 0,
    remark VARCHAR(500),
    transport_cost INT DEFAULT 0,
    shipmethod_id INT,
    PRIMARY KEY(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (status_id) REFERENCES statuses(id),
    FOREIGN KEY (shipmethod_id) REFERENCES shipmethods(id)
);

DROP TABLE IF EXISTS ordered_products;
CREATE TABLE ordered_products  (
    id INT AUTO_INCREMENT,
    order_id INT(6) ZEROFILL NOT NULL,
    product_id INT NOT NULL,
    price INT DEFAULT 0,
    count INT DEFAULT 1,
    color CHAR(6),
    size VARCHAR(50),
    sold INT DEFAULT 0,
    remark VARCHAR(500),
    PRIMARY KEY (id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

DROP TABLE IF EXISTS settings;
CREATE TABLE settings ( 
    id INT AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(255) NOT NULL,
    value TEXT,
    UNIQUE(user_id, name),
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);


DROP TABLE IF EXISTS ads;
CREATE TABLE ads  (
    id INT AUTO_INCREMENT,
    link VARCHAR(500),
    image_url VARCHAR(500) NOT NULL,
    PRIMARY KEY (id)
);

DROP TABLE IF EXISTS ads_categories;
CREATE TABLE ads_categories  (
    ad_id INT,
    category_id INT,
    PRIMARY KEY(ad_id, category_id),
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);


DROP TABLE IF EXISTS ads_products;
CREATE TABLE ads_products  (
    ad_id INT,
    product_id INT,
    PRIMARY KEY(ad_id, product_id),
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS ads_stores;
CREATE TABLE ads_stores  (
    ad_id INT,
    store_id INT,
    PRIMARY KEY(ad_id, store_id),
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS ads_pages;
CREATE TABLE ads_pages  (
    ad_id INT,
    page_name VARCHAR(50),
    PRIMARY KEY(ad_id, page_name),
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
);

CREATE TABLE `coupons_details` (
  `coupon_id` int NOT NULL,
  `store_id` int,
  `subcategory_id` int,
  PRIMARY KEY (coupon_id, store_id, subcategory_id),
  FOREIGN KEY store_id REFERENCES stores(id),
  FOREIGN KEY subcategory_id REFERENCES subcategories(id)
);

DROP TABLE IF EXISTS `coupons_users_denied`;
CREATE TABLE `coupons_users_denied` (
  `user_id` int,
  `denied_at` DATETIME DEFAULT NOW(),
  PRIMARY KEY (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

DROP VIEW IF EXISTS provinces_view;
CREATE VIEW provinces_view AS
SELECT name FROM provinces ORDER BY id;

DROP VIEW IF EXISTS login_view;
CREATE VIEW login_view  AS
SELECT users.id, users.user, code, roles.name AS role, user_level, is_disabled, is_confirmed 
FROM users INNER JOIN roles ON users.role_id=roles.id;


DROP VIEW IF EXISTS users_view;
CREATE VIEW users_view  AS 
SELECT users.id, users.user, users.name, phone, provinces.name AS province, userinfo.address, roles.user_level, stores.id AS store_id, stores.name AS store, users.is_disabled 
FROM users INNER JOIN roles ON users.role_id=roles.id 
LEFT JOIN userinfo ON users.id=userinfo.user_id 
LEFT JOIN provinces ON userinfo.province_id=provinces.id 
LEFT JOIN stores ON users.id=stores.user_id
ORDER BY id DESC;

DROP VIEW IF EXISTS users_code_create_view;
CREATE VIEW users_code_create_view AS
SELECT user, activation_code 
FROM users_code INNER JOIN users ON users_code.user_id=users.id,
(SELECT user_id, MAX(created_at) AS max_created_at
FROM users_code 
GROUP BY user_id) max_date_table
WHERE created_at=max_date_table.max_created_at
AND users_code.user_id=max_date_table.user_id
AND TIMESTAMPDIFF(MINUTE, created_at, NOW())<=30
AND is_creating=1;

DROP VIEW IF EXISTS users_code_password_view;
CREATE VIEW users_code_password_view AS
SELECT user, activation_code 
FROM users_code INNER JOIN users ON users_code.user_id=users.id,
(SELECT user_id, MAX(created_at) AS max_created_at
FROM users_code 
GROUP BY user_id) max_date_table
WHERE created_at=max_date_table.max_created_at
AND users_code.user_id=max_date_table.user_id
AND TIMESTAMPDIFF(MINUTE, created_at, NOW())<=30
AND is_creating=0;

DROP VIEW IF EXISTS support_messages_view;
CREATE VIEW support_messages_view  AS
SELECT support_messages.id, users.user, title, message, sent_at, is_unread FROM support_messages INNER JOIN users ON support_messages.user_id=users.id
ORDER BY sent_at DESC;

DROP VIEW IF EXISTS stores_view;
CREATE VIEW stores_view  AS
SELECT stores.id, stores.name, logo, provinces.name AS province, stores.province_id, stores.address, users.user, stores.comment, created_at, updated_at, price_round, stores.is_disabled, stores.is_city_enabled
FROM stores INNER JOIN users ON stores.user_id=users.id
INNER JOIN provinces ON stores.province_id=provinces.id
ORDER BY created_at DESC;

DROP VIEW IF EXISTS stores_enabled_view;
CREATE VIEW stores_enabled_view  AS
SELECT id, name, logo, province, province_id, address, user, comment, created_at, updated_at, price_round, is_city_enabled 
FROM stores_view
WHERE is_disabled IS FALSE;

DROP VIEW IF EXISTS stores_enabled_random_view;
CREATE VIEW stores_enabled_random_view  AS
SELECT id, name, logo, province, province_id ,address, user, comment, created_at, updated_at, price_round, is_city_enabled 
FROM stores_enabled_view
ORDER BY RAND();

DROP VIEW IF EXISTS products_view;
CREATE VIEW products_view  AS
SELECT products.id, products.name, categories.name AS category, subcategories.name AS subcategory, singular_name AS type, stores.name AS store, stores.province_id, stores.is_city_enabled, stores.is_disabled AS is_store_disabled, products.logo, IF(price>10000000, CEIL(price/1000)*1000, IF(price>100000, CEIL(price/100)*100, price)) AS price, products.comment, IF((SELECT offers.product_id FROM offers WHERE offers.product_id=products.id), 1, 0) AS is_offer, IF((SELECT solds.product_id FROM solds WHERE solds.product_id=products.id AND NOW()>=solds.started_at AND NOW()<solds.end_at), 1, 0) AS is_sold, products.created_at, products.updated_at, products.is_disabled
FROM  products INNER JOIN subcategories ON products.subcategory_id=subcategories.id
INNER JOIN categories ON subcategories.category_id=categories.id
INNER JOIN stores ON products.store_id=stores.id
ORDER BY products.created_at DESC;

/*
SELECT products.id, products.name, categories.name AS category, subcategories.name AS subcategory, singular_name AS type, stores.name AS store, stores.province_id, stores.is_city_enabled, products.logo,
IF(solds.product_id IS NOT NULL AND NOW()>=solds.started_at AND NOW()<solds.end_at, 1, 0) AS is_sold,
IF(offers.product_id IS NOT NULL AND NOW()>=offers.started_at AND NOW()<offers.end_at, 1, 0) AS is_offer,
IF(is_sold, IF(is_sold_percent, price-FLOOR(price*sold_amount/100), price-sold_amount) , price) AS fine_price,
IF(fine_price>10000000,  CEIL(fine_price/1000)*1000, IF(fine_price>100000, CEIL(fine_price/100)*100, fine_price)) AS price, 
products.comment, products.created_at, products.updated_at, products.is_disabled
FROM  products
LEFT JOIN solds ON solds.product_id=products.id
LEFT JOIN offers ON offers.product_id=products.id
INNER JOIN subcategories ON products.subcategory_id=subcategories.id
INNER JOIN categories ON subcategories.category_id=categories.id
INNER JOIN stores ON products.store_id=stores.id
ORDER BY products.created_at DESC;
*/

DROP VIEW IF EXISTS products_enabled_view;
CREATE VIEW products_enabled_view  AS
SELECT id, name, type, category, subcategory, store, province_id, is_city_enabled, logo, IF(price>10000000, CEIL(price/1000)*1000, IF(price>100000, CEIL(price/100)*100, price)) AS price, comment, (SELECT sum(warehouse.count) FROM warehouse WHERE warehouse.product_id=products_view.id) AS count, created_at, is_offer, is_sold
FROM products_view 
WHERE is_disabled IS FALSE AND is_store_disabled IS FALSE
HAVING count>0;

DROP VIEW IF EXISTS store_categories_view;
CREATE VIEW store_categories_view AS
SELECT DISTINCT stores.name AS store, categories.name AS category
FROM  products INNER JOIN subcategories ON products.subcategory_id=subcategories.id
INNER JOIN categories ON subcategories.category_id=categories.id
INNER JOIN stores ON products.store_id=stores.id;

DROP VIEW IF EXISTS store_subcategories_view;
CREATE VIEW store_subcategories_view AS
SELECT DISTINCT stores.name AS store, categories.name AS category, subcategories.name AS subcategory
FROM  products INNER JOIN subcategories ON products.subcategory_id=subcategories.id
INNER JOIN categories ON subcategories.category_id=categories.id
INNER JOIN stores ON products.store_id=stores.id;

DROP VIEW IF EXISTS offers_view;
CREATE VIEW offers_view  AS
SELECT id, name, type, category, subcategory, store, logo, price, comment, created_at, started_at, end_at, province_id, is_city_enabled
FROM offers INNER JOIN products_enabled_view ON offers.product_id=products_enabled_view.id
WHERE is_offer=1 AND NOW()>=started_at AND NOW()<end_at;

DROP VIEW IF EXISTS solds_view;
CREATE VIEW solds_view  AS
SELECT id, name, type, category, subcategory, store, logo, price AS old_price, comment, created_at, started_at, end_at, IF(is_sold_percent, price-FLOOR(price*sold_amount/100), price-sold_amount) AS new_price, CONCAT(sold_amount, IF(is_sold_percent, "%", "")) AS sold_amount_type, province_id, is_city_enabled
FROM solds INNER JOIN products_enabled_view ON solds.product_id=products_enabled_view.id
WHERE NOW()>=started_at AND NOW()<end_at;

DROP VIEW IF EXISTS warehouse_view;
CREATE VIEW warehouse_view AS
SELECT warehouse.id, warehouse.product_id, src, CONCAT('#', color) AS color, IF(sizes.id IS NULL, "", sizes.id) AS size_id, IF(sizes.name IS NULL, "", sizes.name) AS size, count
FROM warehouse INNER JOIN product_images ON product_images.id=warehouse.product_images_id
LEFT JOIN sizes ON size_id=sizes.id;

DROP VIEW IF EXISTS warehouse_avaialbe_view;
CREATE VIEW warehouse_available_view AS
SELECT * FROM warehouse_view WHERE count>0;


DROP VIEW IF EXISTS favorites_view;
CREATE VIEW favorites_view  AS
SELECT users.user, product_id, products.name, logo, price, favorites.added_at
FROM favorites INNER JOIN products ON favorites.product_id=products.id
INNER JOIN users ON favorites.user_id=users.id
ORDER BY favorites.added_at DESC;

DROP VIEW IF EXISTS statuses_view;
CREATE VIEW statuses_view  AS
SELECT name FROM statuses WHERE id<>6 ORDER BY FIELD(id, 2, 1, 5, 3, 4);

DROP VIEW IF EXISTS orders_shopping_view;
CREATE VIEW orders_shopping_view  AS
SELECT orders.id, ordered_at, shipped_at, transport_cost , statuses.name AS status, shipmethods.name AS shipmethod,SUM(price*count)+transport_cost AS sum_total_price, SUM(count) AS item_count FROM orders
LEFT JOIN ordered_products ON orders.id=ordered_products.order_id
INNER JOIN statuses ON orders.status_id=statuses.id
LEFT JOIN shipmethods ON orders.shipmethod_id=shipmethods.id
WHERE statuses.id=6
GROUP BY orders.id, ordered_at, shipped_at, transport_cost, status, shipmethod
ORDER BY ordered_at DESC;



DROP VIEW IF EXISTS orders_view;
CREATE VIEW orders_view  AS
SELECT orders.id, users.user, users.name, ordered_at, shipped_at, transport_cost ,orders.sold, orders.remark, statuses.id AS status_id, statuses.name AS status, shipmethods.name AS shipmethod,SUM(price*count-ordered_products.sold)+IF(transport_cost<>0, transport_cost, 0) AS sum_total_price, SUM(count) AS item_count FROM orders
LEFT JOIN ordered_products ON orders.id=ordered_products.order_id
INNER JOIN users ON orders.user_id=users.id
INNER JOIN statuses ON orders.status_id=statuses.id
LEFT JOIN shipmethods ON orders.shipmethod_id=shipmethods.id
WHERE statuses.id<>6
GROUP BY orders.id, users.user, ordered_at, shipped_at, transport_cost, status
ORDER BY ordered_at DESC;

DROP VIEW IF EXISTS orders_store_view;
CREATE VIEW orders_store_view  AS
SELECT orders.id, stores.name AS store, users.user, ordered_at, shipped_at ,statuses.name AS status, SUM(ordered_products.price*ordered_products.count) AS sum_total_price, SUM(ordered_products.count) AS item_count FROM orders
INNER JOIN ordered_products ON orders.id=ordered_products.order_id
INNER JOIN products ON products.id=ordered_products.product_id
INNER JOIN stores ON stores.id=products.store_id
INNER JOIN users ON orders.user_id=users.id
INNER JOIN statuses ON orders.status_id=statuses.id
WHERE statuses.id<>6
GROUP BY orders.id, store, users.user, ordered_at, shipped_at, transport_cost, status
ORDER BY ordered_at DESC;


DROP VIEW IF EXISTS ordered_products_view;
CREATE VIEW ordered_products_view  AS
SELECT ordered_products.id, order_id, products.id AS product_id, products.name AS product, subcategories.name AS subcategory, stores.name AS store, stores.province_id, provinces.name AS province, ordered_products.price, ordered_products.sold AS sold, ordered_products.remark AS remark, ordered_products.count, ordered_products.price*ordered_products.count - ordered_products.sold AS total_price, size, CONCAT("#", color) AS color, IF(color="" OR color IS NULL, products.logo, (SELECT src FROM product_images WHERE product_images.product_id=products.id AND product_images.color=ordered_products.color)) AS logo
FROM ordered_products 
INNER JOIN products ON ordered_products.product_id=products.id
INNER JOIN stores ON products.store_id=stores.id
INNER JOIN subcategories ON products.subcategory_id=subcategories.id
INNER JOIN provinces ON stores.province_id=provinces.id;


DROP VIEW IF EXISTS ads_products_view;
CREATE VIEW ads_products_view  AS
SELECT ad_id, link, image_url, products.id AS product_id
FROM ads_products
INNER JOIN ads ON ads_products.ad_id=ads.id
INNER JOIN products ON ads_products.product_id=products.id;

DROP VIEW IF EXISTS ads_categories_view;
CREATE VIEW ads_categories_view  AS
SELECT ad_id, link, image_url, categories.name AS category 
FROM ads_categories
INNER JOIN ads ON ads_categories.ad_id=ads.id
INNER JOIN categories ON ads_categories.category_id=categories.id;

DROP VIEW IF EXISTS ads_stores_view;
CREATE VIEW ads_stores_view  AS
SELECT ad_id, link, image_url, stores.name AS store 
FROM ads_stores
INNER JOIN ads ON ads_stores.ad_id=ads.id
INNER JOIN stores ON ads_stores.store_id=stores.id;

DROP VIEW IF EXISTS ads_pages_view;
CREATE VIEW ads_pages_view  AS
SELECT ad_id, link, image_url, page_name
FROM ads_pages
INNER JOIN ads ON ads_pages.ad_id=ads.id;

DROP VIEW IF EXISTS statistics_view;
CREATE VIEW statistics_view  AS
SELECT (SELECT count(id) FROM support_messages) AS messages_count, (SELECT count(id) FROM support_messages WHERE is_unread IS TRUE) AS unread_messages_count, (SELECT count(id) FROM products) AS products_count, (SELECT count(id) FROM stores) AS stores_count, (SELECT count(users.id) FROM users INNER JOIN roles ON users.role_id=roles.id WHERE user_level=4) AS clients_count, (SELECT count(id) FROM orders) AS orders_count, (SELECT count(orders.id) FROM orders INNER JOIN statuses ON orders.status_id=statuses.id WHERE statuses.name="completed") AS completed_orders_count, (SELECT sum(ordered_products.count) FROM ordered_products) AS sum_ordered_products, (SELECT count(id) FROM ads) AS ads_count, (SELECT count(ad_id) FROM ads_products) AS ads_products_count, (SELECT count(ad_id) FROM ads_categories) AS ads_categories_count, (SELECT count(ad_id) FROM ads_pages) AS ads_pages_count, (SELECT count(ad_id) FROM ads_stores) AS ads_stores_count;  

DROP VIEW IF EXISTS coupons_users_denied_view;
CREATE VIEW coupons_users_denied_view AS
SELECT user, name, denied_at
FROM users INNER JOIN coupons_users_denied ON users.id=user_id;

CREATE VIEW `coupons_global_full_view` AS 
select `coupons`.`code` AS `code`,`coupons`.`valid_from` AS `valid_from`,`coupons`.`valid_to` AS `valid_to`,`coupons`.`sold_amount` AS `sold_amount`,`coupons`.`is_sold_percent` AS `is_sold_percent`,concat(`coupons`.`sold_amount`,if(`coupons`.`is_sold_percent`,'%','')) AS `sold`,`coupons`.`purchase_count` AS `purchase_count`,`coupons`.`max_count` AS `max_count`,`coupons`.`is_infinity` AS `is_infinity`,`coupons`.`is_user_ultimate` AS `is_user_ultimate`,`coupons`.`is_disabled` AS `is_disabled` 
from `coupons` 
where `coupons`.`is_global_code` is true 
order by `coupons`.`id` desc;

CREATE VIEW `coupons_global_view` AS 
select `coupons`.`code` AS `code`,`coupons`.`valid_from` AS `valid_from`,`coupons`.`valid_to` AS `valid_to`,`coupons`.`sold_amount` AS `sold_amount`,`coupons`.`is_sold_percent` AS `is_sold_percent`,concat(`coupons`.`sold_amount`,if(`coupons`.`is_sold_percent`,'%','')) AS `sold` 
from `coupons` 
where `coupons`.`is_disabled` is false and `coupons`.`is_global_code` is true and (`coupons`.`purchase_count` < `coupons`.`max_count` or `coupons`.`is_infinity` = 1) and `coupons`.`valid_from` < current_timestamp() and `coupons`.`valid_to` >= current_timestamp()

CREATE VIEW coupons_subcategories_full_view AS
SELECT coupons.code, coupons.valid_from, coupons.valid_to, coupons.sold_amount, coupons.is_sold_percent, concat(coupons.sold_amount,if(coupons.is_sold_percent,'%','')) AS sold,group_concat(subcategories.name separator ',') AS subcategories,coupons.purchase_count, coupons.max_count, coupons.is_infinity, coupons.is_user_ultimate, coupons.is_disabled
FROM ((coupons JOIN coupons_subcategories ON(coupons.id = coupons_subcategories.coupon_id)) 
JOIN subcategories ON(coupons_subcategories.subcategory_id = subcategories.id)) 
WHERE coupons.is_global_code is false group by coupons.code,coupons.valid_from,coupons.valid_to,coupons.sold_amount,coupons.is_sold_percent,concat(coupons.sold_amount,IF(coupons.is_sold_percent,'%','')),coupons.purchase_count,coupons.max_count,coupons.is_infinity,coupons.is_user_ultimate,coupons.is_disabled 
ORDER BY coupons.id DESC

CREATE EVENT onprogress_status ON SCHEDULE EVERY 1 HOUR DO
UPDATE orders SET status_id=1 WHERE status_id=2 AND  DATEDIFF(now(), ordered_at)>=2;

INSERT INTO provinces (name) VALUES ("دمشق"), ("ريف دمشق"), ("حمص"), ("حماة"), ("حلب"), ("السويداء"), ("درعا"), ("القنيطرة"), ("الرقة"), ("الحسكة"), ("دير الزور"), ("إدلب"), ("اللاذقية"), ("طرطوس");
INSERT INTO statuses (id, name) VALUES (1, "قيد المعالجة"), (2, "في الانتظار"), (3, "مكتمل"), (4, "ملغى"), (5, "قيد التسليم"), (6, "قيد التسوق");
INSERT INTO roles (name, description, user_level) VALUES
("مدير النظام", "يملك مدير النظام كافة الصلاحيات لإدارة المتاجر", "1"),
("صاحب متجر", "يملك صاحب المتجر الصلاحيات الخاصة بإدارة المتجر", "2"),
("مسؤول الطلبيات", "يقوم بإدارة ومتابعة الطلبيات المرسلة من قبل الزبائن", "3"),
("مستخدم", "حساب متصفح لتطبيق git", "4");
INSERT INTO users (id, user, name, code, role_id) VALUES (1, "sa", "Super Administrator", "$2y$10$EF.k/A7/YZRJZSh9hkk1J.RqMkzS.dAb3AtdcNHPMLcjSTimE6eKK", 1);
INSERT INTO userinfo (user_id, phone, province_id, address) VALUES (1, "0994545041", 1, "Midan"), (2, "0933655042", 8, "Sahwa");
INSERT INTO support_messages (user_id, title, message) VALUES (1, "No arabic language", "please add arabic language"), (1, "Error code 403", "please fix error code 403"), (2, "no tutorial", "I need app tutorial pelase.");
INSERT INTO settings (name, value) VALUES ("phone", "0996 334 444"), ("facebook-link", "www.facebook.com/get"), ("address", "دمشق-القصاع-شركة جبور");
INSERT INTO shipmethods (id, name) VALUES (1, "شحن"), (2, "توصيل"), (3, "شحن وتوصيل");
