CREATE DATABASE IF NOT EXISTS food_for_family;
USE food_for_family;

CREATE TABLE IF NOT EXISTS users  (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    Account_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verification_status ENUM('pending', 'approved', 'rejected'),
    role ENUM('regular', 'chef', 'admin') NOT NULL DEFAULT 'regular'
);

CREATE TABLE IF NOT EXISTS meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


--all the sql alterations we do 




--ALTER TABLE users MODIFY COLUMN verification_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL;
--ALTER TABLE meals ADD COLUMN image VARCHAR(255) NULL;
--ALTER TABLE users ADD COLUMN role ENUM('regular', 'chef', 'admin') NOT NULL DEFAULT 'regular';
