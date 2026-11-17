CREATE DATABASE IF NOT EXISTS sweet_dreams DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sweet_dreams;

-- Справочник категорий
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO categories (name) VALUES ('Стандартный'), ('Студия'), ('Люкс');

-- Номера
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    price INT NOT NULL CHECK (price > 0),
    capacity INT NOT NULL CHECK (capacity > 0),
    area DECIMAL(5,2) NOT NULL CHECK (area > 0),
    image VARCHAR(255) NOT NULL,
    description TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Удобства (справочник)
CREATE TABLE amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO amenities (name) VALUES 
('Кондиционер'), ('Телевизор'), ('Холодильник'), ('Wi-Fi'), 
('Душ + Ванна'), ('Кухонный уголок'), ('Мини-бар'), ('Вид на город');

-- Связь номеров и удобств
CREATE TABLE room_amenities (
    room_id INT NOT NULL,
    amenity_id INT NOT NULL,
    PRIMARY KEY (room_id, amenity_id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Заявки на бронирование
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('new','approved','rejected') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Администраторы
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Пароль: admin123 (хеш bcrypt)
INSERT INTO admins (login, password_hash) VALUES 
('admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1Hq3hB2WnjCQ4Wz6Q8K5vM3rL1yHwOa');

-- Демо-номера (6 штук — по 2 на категорию)
INSERT INTO rooms (category_id, price, capacity, area, image, description) VALUES
(1, 3500, 2, 18.5, 'room1.jpg', 'Уютный стандартный номер с полным санузлом'),
(1, 3800, 2, 20.0, 'room2.jpg', 'Стандартный номер с видом на двор'),
(2, 5200, 2, 28.0, 'room3.jpg', 'Просторная студия с кухонным уголком'),
(2, 6000, 3, 32.5, 'room4.jpg', 'Студия с холодильником и ТВ'),
(3, 12000, 2, 45.0, 'room5.jpg', 'Двухкомнатный люкс с кондиционером'),
(3, 15000, 4, 55.0, 'room6.jpg', 'Люкс премиум с мини-баром');

-- Привязка удобств
INSERT INTO room_amenities VALUES
(1,5),(1,2),  -- Стандарт 1: душ+ванна, ТВ
(2,5),(2,2),
(3,5),(3,2),(3,3),(3,6),  -- Студия 1: душ, ТВ, холодильник, кухня
(4,5),(4,2),(4,3),(4,6),
(5,1),(5,2),(5,3),(5,5),(5,7),(5,8),  -- Люкс 1
(6,1),(6,2),(6,3),(6,5),(6,7),(6,8);
