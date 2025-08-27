-- Create DB
CREATE DATABASE IF NOT EXISTS library_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_db;

-- Staff (admin is hardcoded; only staff are stored here)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,           -- hashed
  role ENUM('staff') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books: now with quantity
CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  category VARCHAR(100) DEFAULT NULL,
  quantity INT NOT NULL DEFAULT 1,          -- number of copies available
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Borrow history with issuer + who managed the transaction
CREATE TABLE IF NOT EXISTS borrow_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  issuer_name VARCHAR(100) NOT NULL,
  issuer_phone VARCHAR(20) NOT NULL,
  issuer_aadhaar VARCHAR(20) NOT NULL,
  managed_by_username VARCHAR(50) NOT NULL,         -- admin or staff username who processed
  managed_by_role ENUM('admin','staff') NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  due_date DATE NOT NULL,
  return_date DATE DEFAULT NULL,
  FOREIGN KEY (book_id) REFERENCES books(id)
);


-- Demo books (optional)
INSERT INTO books (title, author, category, quantity) VALUES
('Clean Code','Robert C. Martin','Technology', 3),
('Sapiens','Yuval Noah Harari','History', 2);

