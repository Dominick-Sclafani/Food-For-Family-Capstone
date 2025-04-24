CREATE DATABASE IF NOT EXISTS food_for_family;
USE food_for_family;

CREATE TABLE IF NOT EXISTS users  (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    Account_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verification_status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT NULL,
    role ENUM('regular', 'chef', 'admin') NOT NULL DEFAULT 'regular',
    id_document VARCHAR(255) NULL,
);

CREATE TABLE IF NOT EXISTS meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NO T NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    ingredients TEXT NOT NULL,
    allergies TEXT NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    image VARCHAR(255) NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE purchases (
  id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  meal_id int NOT NULL,
  purchase_time datetime DEFAULT NULL,
  PRIMARY KEY (id)
);


--all the sql alterations we do 




--ALTER TABLE users MODIFY COLUMN verification_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL;
--ALTER TABLE meals ADD COLUMN image VARCHAR(255) NULL;
--ALTER TABLE users ADD COLUMN role ENUM('regular', 'chef', 'admin') NOT NULL DEFAULT 'regular';
-- AlTER TABLE users ADD COLUMN id_document VARCHAR(255) NULL;

-- Create reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(10) UNSIGNED NOT NULL,
    chef_id INT(10) UNSIGNED NOT NULL,
    purchase_id INT(10) UNSIGNED NOT NULL,
    rating INT(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chef_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chef_warnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chef_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    warning_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_dismissed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (chef_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);