SQL Query for LogiX---OnlineStoreManagementSystem

-- Drop and recreate the entire database
DROP DATABASE IF EXISTS online_store_db;
CREATE DATABASE online_store_db;
USE online_store_db;

-- -----------------
-- 1. USERS TABLE
-- -----------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    photo VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------
-- 2. PRODUCTS TABLE
-- (Added 'is_active' for safe archiving)
-- -----------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    available_stock INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE, -- NEW FIELD FOR ARCHIVING
    photo VARCHAR(255) DEFAULT 'default_product.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------
-- 3. ORDERS TABLE (Header/Metadata for the entire transaction)
-- -----------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Shipped', 'Delivered', 'Canceled') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------
-- 4. ORDER_ITEMS TABLE (Details for each product in the order)
-- Note: FOREIGN KEY (product_id) uses ON DELETE RESTRICT (default) to maintain history
-- -----------------
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL, 
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);


-- -----------------
-- INITIAL DATA
-- -----------------

-- Create admin (hash for 'password')
INSERT INTO users (username, email, password, role) 
VALUES ('admin', 'admin@store.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Add sample products
INSERT INTO products (title, description, price, available_stock) VALUES
('Wireless Headphones', 'Noise cancelling headphones with a 30-hour battery life.', 99.99, 50),
('Smartphone X', 'The latest model smartphone featuring an edge-to-edge display and dual camera system.', 699.99, 25),
('Portable Charger', '10,000mAh Power bank, essential for travelers.', 25.00, 100),
('Mechanical Keyboard', 'RGB mechanical keyboard with tactile brown switches.', 120.50, 30);

-- Example Data: A user placing an order
INSERT INTO users (username, email, password, role) 
VALUES ('customer1', 'user@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO orders (user_id, status) VALUES (2, 'Pending');
SET @last_order_id = LAST_INSERT_ID();

INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES
(@last_order_id, 1, 2, 99.99),
(@last_order_id, 2, 1, 699.99);
