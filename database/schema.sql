CREATE DATABASE IF NOT EXISTS online_ordering CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE online_ordering;

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_path VARCHAR(255),
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','preparing','out_for_delivery','completed','cancelled') DEFAULT 'pending',
    delivery_address VARCHAR(255),
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
);

CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);


INSERT INTO categories (category_name, description) VALUES
('Rice meals', 'Filipino rice meal combos'),
('Burgers', 'Burgers and sandwiches'),
('Drinks', 'Beverages');

INSERT INTO products (category_id, product_name, description, price, stock_quantity) VALUES
(1, 'Chicken Adobo Meal', 'Chicken adobo with rice and egg', 89.00, 40),
(1, 'Pork Sisig Meal', 'Sizzling pork sisig with rice', 99.00, 30),
(2, 'Classic Cheeseburger', 'Beef patty with cheese and lettuce', 65.00, 50),
(3, 'Iced Tea', '16oz iced tea', 25.00, 100),
(3, 'Bottled Water', '500ml bottled water', 15.00, 100);
