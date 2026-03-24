-- База данных для ОАО "Полесьеэлектромаш"
-- Кодировка: utf8mb4

CREATE DATABASE IF NOT EXISTS polesie_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE polesie_erp;

-- 1. Таблица пользователей (Сотрудники)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    login VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL, -- Без хеша по требованию
    role ENUM('admin', 'director', 'manager', 'engineer', 'warehouse', 'accountant') NOT NULL,
    department VARCHAR(50),
    avatar VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Справочник продукции (Номенклатура)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category ENUM('motor_async', 'motor_single', 'motor_special', 'pump', 'heater', 'casting') NOT NULL,
    power_kw DECIMAL(10, 2), -- Мощность кВт
    voltage VARCHAR(20), -- Напряжение
    price_byn DECIMAL(10, 2) NOT NULL, -- Цена в BYN
    stock_quantity INT DEFAULT 0,
    description TEXT
);

-- 3. Контрагенты (Клиенты и Поставщики)
CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('client', 'supplier') NOT NULL,
    inn VARCHAR(20), -- УНП в Беларуси
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(50)
);

-- 4. Заказы (Продажи)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    partner_id INT,
    user_id INT, -- Менеджер
    status ENUM('new', 'processing', 'ready', 'shipped', 'closed') DEFAULT 'new',
    total_amount_byn DECIMAL(12, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 5. Состав заказа
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price_at_moment_byn DECIMAL(10, 2),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 6. Сырье и материалы (Для литейного цеха)
CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('metal', 'paint', 'electronics', 'packaging') NOT NULL,
    unit VARCHAR(20) DEFAULT 'кг',
    current_stock DECIMAL(10, 2),
    min_stock_level DECIMAL(10, 2),
    price_byn DECIMAL(10, 2)
);

-- 7. Производственные задания (Цеха)
CREATE TABLE production_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_number VARCHAR(20) NOT NULL,
    product_id INT,
    quantity_plan INT,
    quantity_fact INT DEFAULT 0,
    workshop VARCHAR(50), -- Например, "Литейный цех", "Сборочный цех"
    status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
    deadline DATE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 8. Складские операции (Приход/Расход)
CREATE TABLE warehouse_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    type ENUM('income', 'outcome', 'write_off') NOT NULL,
    item_type ENUM('product', 'material'),
    item_id INT,
    quantity DECIMAL(10, 2),
    user_id INT,
    comment TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 9. Оборудование (Инвентаризация)
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    inventory_number VARCHAR(50),
    location VARCHAR(100),
    status ENUM('active', 'repair', 'decommissioned') DEFAULT 'active',
    last_maintenance DATE
);

-- 10. Новости предприятия (Для главной страницы)
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    date_published DATE,
    author_id INT,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- ==========================================
-- ЗАПОЛНЕНИЕ ДАННЫМИ (REAL DATA EXAMPLES)
-- ==========================================

-- Пользователи (Пароли открытые: 12345, admin, engineer и т.д.)
INSERT INTO users (full_name, login, password, role, department) VALUES
('Иванов Петр Сергеевич', 'admin', 'admin', 'admin', 'Администрация'),
('Сидоров Алексей Владимирович', 'director', '12345', 'director', 'Дирекция'),
('Козлович Марина Игоревна', 'manager1', '12345', 'manager', 'Отдел сбыта'),
('Петров Дмитрий Олегович', 'engineer', '12345', 'engineer', 'Главный инженер'),
('Васильчук Игорь Петрович', 'warehouse1', '12345', 'warehouse', 'Склад готовой продукции'),
('Новикова Елена Павловна', 'accountant', '12345', 'accountant', 'Бухгалтерия');

-- Продукция (На основе реального ассортимента Полесьеэлектромаш)
INSERT INTO products (article, name, category, power_kw, voltage, price_byn, stock_quantity, description) VALUES
('2AIR80A4', 'Электродвигатель 2AIR80A4 IE2', 'motor_async', 1.1, '380V', 450.00, 120, 'Энергоэффективный двигатель класса IE2'),
('2AIR90L6', 'Электродвигатель 2AIR90L6 IE2', 'motor_async', 1.5, '380V', 580.50, 85, 'Повышенный КПД, чугунная станина'),
('АИР100S4', 'Электродвигатель АИР100S4', 'motor_async', 4.0, '380V', 920.00, 45, 'Общепромышленный двигатель'),
('АИВР80A4', 'Электродвигатель взрывозащищенный АИВР80A4', 'motor_special', 1.1, '380V', 1250.00, 15, 'Для взрывоопасных сред, чугун'),
('АИРЕ80C2', 'Электродвигатель однофазный АИРЕ80C2', 'motor_single', 1.1, '220V', 490.00, 60, 'С конденсатором, бытовой и пром'),
('ЭКЧ145-220', 'Электроконфорка чугунная ЭКЧ145-220', 'heater', 1.0, '220V', 45.50, 500, 'Бытовая электроконфорка, белый эмаль'),
('ЭКЧ180-220', 'Электроконфорка чугунная ЭКЧ180-220', 'heater', 1.5, '220V', 52.00, 350, 'Бытовая электроконфорка, черный эмаль'),
('БЦ-0,5-20', 'Насос бытовой центробежный БЦ-0,5-20', 'pump', 0.5, '220V', 180.00, 90, 'Для полива и водоснабжения'),
('АИРЧ71A4', 'Электродвигатель для стрелочных приводов АИРЧ', 'motor_special', 0.37, '380V', 650.00, 20, 'Железнодорожное назначение'),
('Литье СЧ15', 'Отливка из серого чугуна СЧ15', 'casting', NULL, NULL, 15.00, 1000, 'За кг, техническое литье');

-- Контрагенты
INSERT INTO partners (name, type, inn, address, phone) VALUES
('ОАО "МТЗ"', 'client', '100123456', 'г. Минск, тракторный проезд 1', '+375 17 123-45-67'),
('ООО "Белстройэнерго"', 'client', '200987654', 'г. Гомель, ул. Строителей 5', '+375 232 98-76-54'),
('ЗАО "Металлоторг"', 'supplier', '300555666', 'г. Лунинец, промзона 2', '+375 1647 5-55-55'),
('РУП "Белэнергоремналадка"', 'client', '400111222', 'г. Минск, ул. Энергетиков 10', '+375 17 333-22-11');

-- Заказы (Примеры за разные периоды)
INSERT INTO orders (order_number, partner_id, user_id, status, total_amount_byn, created_at) VALUES
('ORD-2023-001', 1, 3, 'closed', 45000.00, '2023-03-15 10:00:00'),
('ORD-2023-045', 2, 3, 'shipped', 12500.50, '2023-09-20 14:30:00'),
('ORD-2024-012', 4, 3, 'processing', 5600.00, '2024-01-10 09:15:00'),
('ORD-2024-089', 1, 3, 'new', 120000.00, '2024-05-22 11:00:00');

-- Добавим товары в первый заказ
INSERT INTO order_items (order_id, product_id, quantity, price_at_moment_byn) VALUES
(1, 1, 100, 450.00);

-- Производственные задания
INSERT INTO production_tasks (task_number, product_id, quantity_plan, workshop, status, deadline) VALUES
('TASK-24-050', 1, 200, 'Сборочный цех №1', 'in_progress', '2024-06-01'),
('TASK-24-051', 6, 1000, 'Цех ТНП', 'planned', '2024-06-15'),
('TASK-24-048', 9, 50, 'Спеццех', 'completed', '2024-05-10');

-- Материалы
INSERT INTO materials (name, type, current_stock, min_stock_level, price_byn) VALUES
('Лом чугунный', 'metal', 5000.00, 1000.00, 0.80),
('Алюминий АК5М2', 'metal', 1200.00, 500.00, 4.50),
('Медь обмоточная', 'metal', 300.00, 100.00, 25.00),
('Краска порошковая', 'paint', 150.00, 50.00, 12.00);

-- Новости
INSERT INTO news (title, content, date_published, author_id) VALUES
('Выставка "БелАгро-2024"', 'Предприятие приняло участие в выставке, представлены новые насосы.', '2024-06-05', 2),
('Внедрение энергоэффективных двигателей', 'Серия 2AIR показала прирост КПД на 5%.', '2024-04-12', 4);
