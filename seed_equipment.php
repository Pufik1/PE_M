<?php
require_once 'config/db.php';

// Проверяем, есть ли данные в таблице
$stmt = $pdo->query("SELECT COUNT(*) FROM equipment");
$count = $stmt->fetchColumn();

if ($count == 0) {
    // Вставляем тестовые данные
    $sql = "INSERT INTO equipment (name, type, serial_number, status, purchase_date, location, responsible_person) VALUES 
    ('Сервер Dell PowerEdge R740', 'server', 'SN-DELL-001', 'active', '2022-05-15', 'Серверная №1', 'Иванов И.И.'),
    ('Принтер HP LaserJet Pro', 'printer', 'SN-HP-002', 'active', '2023-01-20', 'Бухгалтерия', 'Петрова А.С.'),
    ('Ноутбук Lenovo ThinkPad', 'laptop', 'SN-LNV-003', 'repair', '2021-11-10', 'Склад', 'Сидоров В.В.'),
    ('Монитор Samsung 27\"', 'monitor', 'SN-SAM-004', 'inactive', '2020-08-05', 'Офис 101', 'Кузнецов П.П.'),
    ('Роутер MikroTik RB4011', 'network', 'SN-MKT-005', 'active', '2023-06-12', 'Серверная №1', 'Иванов И.И.'),
    ('Источник бесперебойного питания APC', 'ups', 'SN-APC-006', 'active', '2022-09-30', 'Серверная №1', 'Иванов И.И.'),
    ('Сканер штрих-кодов Zebra', 'scanner', 'SN-ZBR-007', 'written_off', '2019-03-15', 'Склад', 'Сидоров В.В.'),
    ('Планшет iPad Air', 'tablet', 'SN-APL-008', 'active', '2023-04-22', 'Отдел продаж', 'Смирнова Е.А.')";
    
    $pdo->exec($sql);
    echo "Успешно добавлено 8 записей оборудования.";
} else {
    echo "В таблице уже есть данные: $count записей.";
}
?>
