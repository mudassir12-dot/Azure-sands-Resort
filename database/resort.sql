-- ============================================================
-- Azure Sands Resort - Complete Database Schema
-- Database: azure_sands_resort
-- ============================================================

CREATE DATABASE IF NOT EXISTS azure_sands_resort CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE azure_sands_resort;

-- ============================================================
-- TABLE: admins
-- ============================================================
CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(191) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(191) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    gender ENUM('Male','Female','Other') DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    loyalty_points INT UNSIGNED DEFAULT 0,
    membership_tier ENUM('Bronze','Silver','Gold','Platinum') DEFAULT 'Bronze',
    remember_token VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: rooms
-- ============================================================
CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_type ENUM('Deluxe Ocean View','Executive Suite','Presidential Suite','Family Suite','Premium Villa','Oceanfront Bungalow') NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity INT UNSIGNED DEFAULT 2,
    description TEXT,
    features JSON,
    image VARCHAR(255) DEFAULT NULL,
    badge VARCHAR(50) DEFAULT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: bookings
-- ============================================================
CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_ref VARCHAR(20) NOT NULL UNIQUE,
    user_id INT UNSIGNED DEFAULT NULL,
    guest_name VARCHAR(191) NOT NULL,
    guest_email VARCHAR(191) NOT NULL,
    guest_city VARCHAR(100) DEFAULT NULL,
    room_id INT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    nights INT UNSIGNED NOT NULL DEFAULT 1,
    check_out DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    special_requests TEXT,
    status ENUM('Pending','Confirmed','Checked In','Checked Out','Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    INDEX idx_status (status),
    INDEX idx_check_in (check_in)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: food_items
-- ============================================================
CREATE TABLE food_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(191) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('Main Course','Appetizer','Dessert','Beverage','Special') DEFAULT 'Main Course',
    image VARCHAR(255) DEFAULT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: food_orders
-- ============================================================
CREATE TABLE food_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_ref VARCHAR(20) NOT NULL UNIQUE,
    user_id INT UNSIGNED DEFAULT NULL,
    guest_name VARCHAR(191) NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    delivery_datetime DATETIME NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending','Preparing','Out for Delivery','Delivered','Cancelled') DEFAULT 'Pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: food_order_items
-- ============================================================
CREATE TABLE food_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    food_item_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES food_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_item_id) REFERENCES food_items(id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: memberships
-- ============================================================
CREATE TABLE memberships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    tier ENUM('Bronze','Silver','Gold','Platinum') DEFAULT 'Bronze',
    points INT UNSIGNED DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0.00,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: careers (job listings)
-- ============================================================
CREATE TABLE careers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    position_title VARCHAR(191) NOT NULL,
    position_key VARCHAR(100) NOT NULL UNIQUE,
    department VARCHAR(100),
    description TEXT,
    requirements TEXT,
    salary_range VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: applications
-- ============================================================
CREATE TABLE applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    app_ref VARCHAR(20) NOT NULL UNIQUE,
    applicant_name VARCHAR(191) NOT NULL,
    applicant_email VARCHAR(191) NOT NULL,
    position_applied VARCHAR(191) NOT NULL,
    available_date DATE NOT NULL,
    cv_filename VARCHAR(255) DEFAULT NULL,
    cover_letter TEXT,
    status ENUM('Received','Under Review','Shortlisted','Interview','Hired','Rejected') DEFAULT 'Received',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: reviews
-- ============================================================
CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    reviewer_name VARCHAR(191) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: contact_messages
-- ============================================================
CREATE TABLE contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(191) NOT NULL,
    sender_email VARCHAR(191) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: newsletter_subscribers
-- ============================================================
CREATE TABLE newsletter_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin (password: admin123)
-- Hash generated with: password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10])
INSERT INTO admins (username, email, password, full_name) VALUES
('admin', 'admin@azuresands.com', '$2y$10$TKh8H1.PfBKGWZm.wddPIuDLwnhnhMDkh4p1jJkbdj7N4TMZF7eza', 'Resort Administrator');

-- Rooms (image paths match assets/images/ directory)
INSERT INTO rooms (room_number, room_type, price_per_night, capacity, description, features, image, badge) VALUES
('1001', 'Deluxe Ocean View', 199.00, 2, 'Spacious room with panoramic ocean views, king-sized bed, and modern amenities. Perfect for couples and solo travelers.', '["King Bed","Pool Access","Free WiFi","Smart TV"]', 'assets/images/14.jpg', 'Popular'),
('1002', 'Executive Suite', 299.00, 2, 'Luxurious suite with separate living area, premium furnishings, and exclusive amenities. Ideal for business travelers.', '["King Bed","Jacuzzi","Breakfast Included","Work Desk"]', 'assets/images/15.jpg', 'Best Seller'),
('1003', 'Presidential Suite', 499.00, 4, 'Ultimate luxury with private balcony, butler service, and premium amenities. Perfect for special occasions.', '["Super King Bed","Private Spa","Limo Service","Minibar"]', 'assets/images/16.jpg', 'Luxury'),
('1004', 'Family Suite', 349.00, 5, 'Spacious accommodation with separate bedrooms, perfect for families with kid-friendly amenities.', '["2 Queen Beds","2 Smart TVs","Play Area","Kids Menu"]', 'assets/images/11.jfif', 'Family Friendly'),
('1005', 'Premium Villa', 599.00, 6, 'Private villa with personal pool, garden, and dedicated staff for complete privacy and luxury.', '["Private Pool","Private Garden","Private Chef","Private Garage"]', 'assets/images/9.jfif', 'Ultimate Luxury'),
('1006', 'Oceanfront Bungalow', 449.00, 3, 'Beachfront bungalow with direct ocean access. Wake up to the sound of waves and sea views.', '["King Bed","Beach Access","Sun Deck","Coffee Maker"]', 'assets/images/17.jpg', 'Beachfront');

-- Food Items
INSERT INTO food_items (item_code, name, description, price, category, image) VALUES
('2001', 'Signature Biryani', 'Traditional aromatic rice dish with tender meat and exotic spices, served with raita and salad.', 24.99, 'Main Course', 'img/f1.jpg'),
('2002', 'Premium Karhai', 'Savory traditional curry cooked in a wok with fresh herbs, spices, and tender meat.', 29.99, 'Main Course', 'img/f2.jpg'),
('2003', 'Truffle Pizza', 'Artisanal pizza with black truffle, fresh mozzarella, wild mushrooms, and truffle oil.', 18.99, 'Main Course', 'img/f3.jpg'),
('2004', 'Gourmet Burger', 'Premium beef patty with aged cheddar, truffle aioli, and fresh vegetables in a brioche bun.', 14.99, 'Main Course', 'img/f4.jpg'),
('2005', 'Truffle Fries', 'Crispy golden fries tossed in truffle oil, served with garlic aioli and parmesan cheese.', 9.99, 'Appetizer', 'img/f5.jpg'),
('2006', 'Artisan Kheer', 'Traditional rice pudding infused with saffron, cardamom, and topped with pistachios.', 12.99, 'Dessert', 'img/f6.jpg'),
('2007', 'Seafood Platter', 'Fresh selection of lobster, shrimp, and scallops grilled to perfection with lemon butter.', 34.99, 'Special', 'img/f1.jpg'),
('2008', 'Chocolate Lava Cake', 'Warm chocolate cake with molten center, served with vanilla ice cream and berry compote.', 10.99, 'Dessert', 'img/f3.jpg');

-- Career Positions
INSERT INTO careers (position_title, position_key, department, description) VALUES
('Resort Manager', 'Manager', 'Management', 'Lead and oversee daily resort operations ensuring exceptional guest experience.'),
('Food & Beverage Server', 'Waiter', 'Dining', 'Provide exceptional dining experiences with attentive, professional service.'),
('Housekeeping Supervisor', 'Room cleaner', 'Housekeeping', 'Maintain impeccable standards of cleanliness and room presentation.'),
('Front Desk Associate', 'Cashier', 'Front Office', 'Welcome and assist guests, manage reservations and inquiries.');
