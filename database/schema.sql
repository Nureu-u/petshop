CREATE DATABASE IF NOT EXISTS petshop_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE petshop_db;

-- =========================================================
-- 1. USERS
-- =========================================================
CREATE TABLE users (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT chk_users_role CHECK (role IN ('customer', 'admin')),
    CONSTRAINT chk_users_status CHECK (status IN ('active', 'blocked', 'deleted'))
) ENGINE=InnoDB;

-- =========================================================
-- 2. PASSWORD RESET TOKENS
-- =========================================================

CREATE TABLE password_reset_tokens (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    user_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_password_reset_token (token_hash),
    KEY idx_password_reset_user (user_id),

    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 3. PET CATEGORIES
-- =========================================================
CREATE TABLE pet_categories (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_pet_categories_slug (slug),
    CONSTRAINT chk_pet_categories_status CHECK (status IN ('active', 'inactive'))
) ENGINE=InnoDB;

-- =========================================================
-- 4. PETS
-- =========================================================
CREATE TABLE pets (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    category_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(100) NOT NULL,
    species VARCHAR(50) NOT NULL,
    breed VARCHAR(100) NULL,
    gender VARCHAR(20) NULL,
    birth_date DATE NULL,
    weight DECIMAL(6,2) NULL,
    price DECIMAL(15,0) NOT NULL,
    health_status VARCHAR(100) NULL,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_pets_category_status (category_id, status),
    KEY idx_pets_price (price),
    CONSTRAINT fk_pets_category
        FOREIGN KEY (category_id) REFERENCES pet_categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_pets_price CHECK (price >= 0),
    CONSTRAINT chk_pets_weight CHECK (weight IS NULL OR weight > 0),
    CONSTRAINT chk_pets_status CHECK (status IN ('available', 'sold', 'hidden'))
) ENGINE=InnoDB;

-- =========================================================
-- 5. PRODUCT CATEGORIES
-- =========================================================
CREATE TABLE product_categories (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_product_categories_slug (slug),
    CONSTRAINT chk_product_categories_status CHECK (status IN ('active', 'inactive'))
) ENGINE=InnoDB;

-- =========================================================
-- 6. PRODUCTS
-- =========================================================
CREATE TABLE products (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    category_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(100) NULL,
    price DECIMAL(15,0) NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_products_category_status (category_id, status),
    KEY idx_products_price (price),
    KEY idx_products_stock (stock),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES product_categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_products_price CHECK (price >= 0),
    CONSTRAINT chk_products_status CHECK (status IN ('active', 'inactive', 'hidden'))
) ENGINE=InnoDB;

-- =========================================================
-- 7. CARTS
-- =========================================================
CREATE TABLE carts (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    user_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_carts_user (user_id),
    CONSTRAINT fk_carts_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 8. CART ITEMS
-- =========================================================
CREATE TABLE cart_items (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    cart_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    pet_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NULL,
    product_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,0) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_cart_items_pet (cart_id, pet_id),
    UNIQUE KEY uq_cart_items_product (cart_id, product_id),
    KEY idx_cart_items_pet (pet_id),
    KEY idx_cart_items_product (product_id),

    CONSTRAINT chk_cart_item_type CHECK (
        (pet_id IS NOT NULL AND product_id IS NULL AND quantity = 1)
        OR
        (pet_id IS NULL AND product_id IS NOT NULL AND quantity >= 1)
    ),
    CONSTRAINT chk_cart_item_price CHECK (unit_price >= 0),
    CONSTRAINT fk_cart_items_cart
        FOREIGN KEY (cart_id) REFERENCES carts(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_pet
        FOREIGN KEY (pet_id) REFERENCES pets(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cart_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
-- 9. COUPONS
-- =========================================================
CREATE TABLE coupons (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    code VARCHAR(50) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    discount_type VARCHAR(20) NOT NULL,
    discount_value DECIMAL(15,0) NOT NULL,
    min_order_amount DECIMAL(15,0) NOT NULL DEFAULT 0,
    max_discount_amount DECIMAL(15,0) NULL,
    usage_limit INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_coupons_code (code),
    KEY idx_coupons_active_time (status, start_at, end_at),
    CONSTRAINT chk_coupons_discount_rule
    CHECK (
        (discount_type = 'percent' AND discount_value BETWEEN 1 AND 100)
        OR
        (discount_type = 'fixed' AND discount_value > 0 AND max_discount_amount IS NULL)
    ),
    CONSTRAINT chk_coupons_type CHECK (discount_type IN ('percent', 'fixed')),
    CONSTRAINT chk_coupons_min_order CHECK (min_order_amount >= 0),
    CONSTRAINT chk_coupons_max_discount CHECK (max_discount_amount IS NULL OR max_discount_amount >= 0),
    CONSTRAINT chk_coupons_date CHECK (end_at > start_at),
    CONSTRAINT chk_coupons_usage CHECK (usage_limit IS NULL OR used_count <= usage_limit),
    CONSTRAINT chk_coupons_status CHECK (status IN ('active', 'inactive'))
) ENGINE=InnoDB;

-- =========================================================
-- 12. ORDERS
-- =========================================================
CREATE TABLE orders (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    order_code VARCHAR(50) NOT NULL,
    user_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,

    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,

    subtotal DECIMAL(15,0) NOT NULL,
    discount_amount DECIMAL(15,0) NOT NULL DEFAULT 0,
    shipping_fee DECIMAL(15,0) NOT NULL DEFAULT 0,
    total_amount DECIMAL(15,0) NOT NULL,

    status VARCHAR(30) NOT NULL DEFAULT 'PENDING_PAYMENT',
    note VARCHAR(500) NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_code (order_code),
    KEY idx_orders_user_status (user_id, status),
    KEY idx_orders_created_at (created_at),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_orders_amounts CHECK (
        subtotal >= 0 AND
        discount_amount >= 0 AND
        shipping_fee >= 0 AND
        total_amount >= 0 AND
        discount_amount <= subtotal AND
        total_amount = subtotal - discount_amount + shipping_fee
    ),
    CONSTRAINT chk_orders_status CHECK (
        status IN ('PENDING_PAYMENT', 'CONFIRMED', 'SHIPPING', 'DELIVERED', 'CANCELLED')
    )
) ENGINE=InnoDB;

-- =========================================================
-- 11. COUPONS USAGES
-- =========================================================
CREATE TABLE coupon_usages (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,

    coupon_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    user_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    order_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    discount_amount DECIMAL(15,0) NOT NULL,

    used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_coupon_usages_order (order_id),

    UNIQUE KEY uq_coupon_usages_user (coupon_id, user_id),

    KEY idx_coupon_usages_user (user_id),

    CONSTRAINT fk_coupon_usages_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_coupon_usages_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_coupon_usages_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_coupon_usages_discount CHECK (discount_amount >= 0)
) ENGINE=InnoDB;

-- =========================================================
-- 12. ORDER ITEMS
-- =========================================================
CREATE TABLE order_items (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    order_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    pet_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NULL,
    product_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NULL,

    item_name VARCHAR(150) NOT NULL,
    unit_price DECIMAL(15,0) NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL,
    line_total DECIMAL(15,0) NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_order_items_pet (order_id, pet_id),
    UNIQUE KEY uq_order_items_product (order_id, product_id),
    KEY idx_order_items_pet (pet_id),
    KEY idx_order_items_product (product_id),

    CONSTRAINT chk_order_item_type CHECK (
        (pet_id IS NOT NULL AND product_id IS NULL AND quantity = 1)
        OR
        (pet_id IS NULL AND product_id IS NOT NULL AND quantity >= 1)
    ),
    CONSTRAINT chk_order_item_amounts CHECK (
        unit_price >= 0 AND line_total = unit_price * quantity
    ),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_items_pet
        FOREIGN KEY (pet_id) REFERENCES pets(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
-- 13. PAYMENTS
-- =========================================================
CREATE TABLE payments (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    order_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,

    payment_method VARCHAR(30) NOT NULL,
    transaction_ref VARCHAR(100) NOT NULL,

    amount DECIMAL(15,0) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',

    vnp_transaction_no VARCHAR(50) NULL,
    vnp_response_code VARCHAR(10) NULL,
    vnp_transaction_status VARCHAR(10) NULL,
    vnp_bank_code VARCHAR(50) NULL,
    vnp_pay_date VARCHAR(20) NULL,
    vnp_paid_at DATETIME NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_payments_transaction_ref (transaction_ref),
    KEY idx_payments_status (status),
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_payments_amount CHECK (amount > 0),
    CONSTRAINT chk_payments_method CHECK (payment_method IN ('VNPAY')),
    CONSTRAINT chk_payments_status CHECK (status IN ('PENDING', 'PAID', 'FAILED', 'REFUNDED'))
) ENGINE=InnoDB;

-- =========================================================
-- 14. REVIEWS
-- =========================================================
CREATE TABLE reviews (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    user_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,

    pet_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NULL,
    product_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NULL,

    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'visible',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_reviews_user_pet (user_id, pet_id),
    UNIQUE KEY uq_reviews_user_product (user_id, product_id),
    KEY idx_reviews_pet (pet_id),
    KEY idx_reviews_product (product_id),
    CONSTRAINT chk_review_item_type CHECK (
        (pet_id IS NOT NULL AND product_id IS NULL)
        OR
        (pet_id IS NULL AND product_id IS NOT NULL)
    ),
    CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_reviews_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reviews_pet
        FOREIGN KEY (pet_id) REFERENCES pets(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reviews_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_reviews_status CHECK (status IN ('visible', 'hidden'))
) ENGINE=InnoDB;

-- =========================================================
-- 15. REVIEWS IMAGES
-- =========================================================
CREATE TABLE review_images (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    review_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    image VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_review_images_review (review_id),

    CONSTRAINT fk_review_images_review
        FOREIGN KEY (review_id)
        REFERENCES reviews(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 16. POST CATEGORIES
-- =========================================================
CREATE TABLE post_categories (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_post_categories_slug (slug)
) ENGINE=InnoDB;

-- =========================================================
-- 17. POSTS
-- =========================================================
CREATE TABLE posts (
    id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    category_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    author_id VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,

    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    excerpt VARCHAR(500) NULL,
    content LONGTEXT NOT NULL,
    image VARCHAR(255) NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_posts_slug (slug),
    KEY idx_posts_category_status (category_id, status),
    KEY idx_posts_author (author_id),
    CONSTRAINT fk_posts_category
        FOREIGN KEY (category_id) REFERENCES post_categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_posts_author
        FOREIGN KEY (author_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_posts_published_at
    CHECK (
        (status = 'draft' AND published_at IS NULL)
        OR
        (status = 'published' AND published_at IS NOT NULL)
        OR
        (status = 'hidden')
    ),
    CONSTRAINT chk_posts_status CHECK (status IN ('draft', 'published', 'hidden'))
) ENGINE=InnoDB;
