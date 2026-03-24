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

-- Заказы (Примеры за разные периоды с актуальными датами)
INSERT INTO orders (order_number, partner_id, user_id, status, total_amount_byn, created_at) VALUES
('ORD-2023-001', 1, 3, 'closed', 45000.00, '2023-03-15 10:00:00'),
('ORD-2023-045', 2, 3, 'shipped', 12500.50, '2023-09-20 14:30:00'),
('ORD-2024-012', 4, 3, 'processing', 5600.00, '2024-01-10 09:15:00'),
('ORD-2024-089', 1, 3, 'new', 120000.00, '2024-05-22 11:00:00');

-- Добавим товары в первый заказ
INSERT INTO order_items (order_id, product_id, quantity, price_at_moment_byn) VALUES
(1, 1, 100, 450.00);

-- Производственные задания
INSERT INTO production_tasks (task_number, product_id, quantity_plan, quantity_fact, workshop, status, deadline) VALUES
('TASK-24-050', 1, 200, 150, 'Сборочный цех №1', 'in_progress', '2026-03-20'),
('TASK-24-051', 6, 1000, 0, 'Цех ТНП', 'planned', '2026-03-25'),
('TASK-24-048', 9, 50, 50, 'Спеццех', 'completed', '2026-03-10');

-- Материалы
INSERT INTO materials (name, type, current_stock, min_stock_level, price_byn) VALUES
('Лом чугунный', 'metal', 5000.00, 1000.00, 0.80),
('Алюминий АК5М2', 'metal', 1200.00, 500.00, 4.50),
('Медь обмоточная', 'metal', 300.00, 100.00, 25.00),
('Краска порошковая', 'paint', 150.00, 50.00, 12.00);

-- Новости
INSERT INTO news (title, content, date_published, author_id) VALUES
('Выставка "БелАгро-2026"', 'Предприятие приняло участие в выставке, представлены новые насосы.', '2026-03-05', 2),
('Внедрение энергоэффективных двигателей', 'Серия 2AIR показала прирост КПД на 5%.', '2026-02-12', 4),
('Новый контракт с ОАО "МТЗ"', 'Заключен договор на поставку 500 двигателей во втором квартале.', '2026-03-20', 3),
('Модернизация литейного цеха', 'Установлено новое оборудование для повышения качества отливок.', '2026-01-10', 4),
('Поздравление с Днем машиностроителя', 'Коллектив предприятия отмечен благодарностями за высокие показатели.', '2025-09-22', 2);

-- Оборудование (Инвентаризация)
INSERT INTO equipment (name, inventory_number, location, status, last_maintenance) VALUES
('Токарный станок 16К20', 'EQ-001', 'Механический цех', 'active', '2026-02-15'),
('Фрезерный станок 6Р12', 'EQ-002', 'Механический цех', 'active', '2026-01-20'),
('Плавильная печь ПЧ-1', 'EQ-003', 'Литейный цех', 'active', '2026-03-01'),
('Компрессор воздушный КВ-5', 'EQ-004', 'Компрессорная', 'repair', '2026-02-10'),
('Кран мостовой 5т', 'EQ-005', 'Сборочный цех', 'active', '2026-02-25'),
('Станок сверлильный 2Н135', 'EQ-006', 'Механический цех', 'active', '2026-01-15'),
('Пресс гидравлический П6320', 'EQ-007', 'Цех ТНП', 'decommissioned', '2025-12-01'),
('Вибрационный стол ВСТ-1', 'EQ-008', 'Цех ТНП', 'active', '2026-02-10'),
('Сушильная камера СЭК-2', 'EQ-009', 'Цех окраски', 'active', '2026-03-05'),
('Генератор дизельный АД-100', 'EQ-010', 'Энергоцех', 'active', '2026-02-28');

-- Дополнительные пользователи
INSERT INTO users (full_name, login, password, role, department) VALUES
('Кузнецов Андрей Николаевич', 'engineer2', '12345', 'engineer', 'Конструкторский отдел'),
('Морозова Ольга Викторовна', 'manager2', '12345', 'manager', 'Отдел сбыта'),
('Ткаченко Сергей Петрович', 'warehouse2', '12345', 'warehouse', 'Склад материалов'),
('Шевчук Наталья Ивановна', 'accountant2', '12345', 'accountant', 'Бухгалтерия');

-- Дополнительная продукция
INSERT INTO products (article, name, category, power_kw, voltage, price_byn, stock_quantity, description) VALUES
('2AIR100L4', 'Электродвигатель 2AIR100L4 IE2', 'motor_async', 5.5, '380V', 1150.00, 35, 'Промышленный двигатель повышенной мощности'),
('АИР132S4', 'Электродвигатель АИР132S4', 'motor_async', 7.5, '380V', 1580.00, 20, 'Для привода механизмов'),
('АИРЕ100S2', 'Электродвигатель однофазный АИРЕ100S2', 'motor_single', 2.2, '220V', 720.00, 40, 'Мощный однофазный двигатель'),
('БЦ-1,0-30', 'Насос бытовой центробежный БЦ-1,0-30', 'pump', 1.0, '220V', 250.00, 65, 'Повышенной производительности'),
('ЭКЧ200-220', 'Электроконфорка чугунная ЭКЧ200-220', 'heater', 2.0, '220V', 65.00, 280, 'Увеличенного диаметра');

-- Дополнительные контрагенты
INSERT INTO partners (name, type, inn, address, phone, email) VALUES
('ООО "Гомсельмаш"', 'client', '500333444', 'г. Гомель, ул. Советская 1', '+375 232 55-44-33', 'info@gomselmash.by'),
('ЧПУП "Технокомплект"', 'supplier', '600777888', 'г. Минск, пр-т Независимости 100', '+375 17 222-33-44', 'sales@technocomplect.by'),
('ОАО "Белоруснефть"', 'client', '700999000', 'г. Гомель, ул. Ильича 9', '+375 232 11-22-33', 'purchase@belorusneft.by'),
('ИП "Ковалев и Ко"', 'supplier', '800111333', 'г. Брест, ул. Московская 50', '+375 162 44-55-66', 'kovalev@mail.by'),
('РУП "Гортеплосеть"', 'client', '900222444', 'г. Могилев, ул. Лазаренко 15', '+375 222 77-88-99', 'info@teploset.mogilev.by');

-- Дополнительные заказы (с актуальными датами за последние 30 дней)
INSERT INTO orders (order_number, partner_id, user_id, status, total_amount_byn, created_at) VALUES
('ORD-2024-090', 5, 3, 'ready', 25000.00, '2026-03-01 10:30:00'),
('ORD-2024-091', 6, 7, 'processing', 8500.00, '2026-03-05 14:00:00'),
('ORD-2024-092', 8, 3, 'new', 15000.00, '2026-03-10 09:45:00'),
('ORD-2024-093', 5, 7, 'shipped', 32000.00, '2026-03-12 11:20:00'),
('ORD-2024-094', 9, 3, 'closed', 18500.00, '2026-03-15 16:00:00');

-- Товары в заказы (order_id соответствуют существующим заказам 1-9)
INSERT INTO order_items (order_id, product_id, quantity, price_at_moment_byn) VALUES
(1, 1, 100, 450.00),
(2, 2, 20, 580.50),
(3, 3, 5, 920.00),
(4, 4, 10, 1250.00),
(5, 5, 15, 490.00),
(6, 6, 100, 45.50),
(7, 7, 80, 52.00),
(8, 8, 25, 180.00),
(9, 1, 50, 450.00),
(9, 11, 30, 1150.00),
(9, 12, 15, 1580.00);

-- Дополнительные производственные задания
INSERT INTO production_tasks (task_number, product_id, quantity_plan, quantity_fact, workshop, status, deadline) VALUES
('TASK-24-052', 11, 100, 50, 'Сборочный цех №2', 'in_progress', '2026-03-28'),
('TASK-24-053', 12, 75, 0, 'Сборочный цех №1', 'planned', '2026-04-05'),
('TASK-24-054', 13, 60, 30, 'Спеццех', 'in_progress', '2026-03-26'),
('TASK-24-055', 14, 40, 0, 'Цех ТНП', 'planned', '2026-04-10'),
('TASK-24-056', 15, 200, 100, 'Цех ТНП', 'in_progress', '2026-03-30');

-- Дополнительные материалы
INSERT INTO materials (name, type, current_stock, min_stock_level, price_byn) VALUES
('Сталь листовая Ст3', 'metal', 2500.00, 800.00, 1.20),
('Цинк чушковый', 'metal', 400.00, 150.00, 8.50),
('Подшипники 204', 'electronics', 500.00, 200.00, 3.50),
('Картон упаковочный', 'packaging', 1000.00, 300.00, 0.90),
('Провод медный ПЭТВ', 'electronics', 250.00, 100.00, 18.00);

-- Дополнительные складские операции
INSERT INTO warehouse_logs (date_op, type, item_type, item_id, quantity, user_id, comment) VALUES
('2026-03-01 09:00:00', 'income', 'product', 1, 50, 4, 'Поступление со сборочного цеха'),
('2026-03-02 10:30:00', 'outcome', 'product', 2, 20, 4, 'Отгрузка по заказу ORD-2024-090'),
('2026-03-03 14:00:00', 'income', 'material', 1, 1000, 8, 'Поставка от ЗАО "Металлоторг"'),
('2026-03-05 11:15:00', 'write_off', 'material', 3, 15, 4, 'Списание брака'),
('2026-03-06 16:45:00', 'outcome', 'product', 6, 200, 4, 'Отгрузка в торговую сеть');
