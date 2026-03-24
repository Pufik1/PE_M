<?php
/**
 * API для редактирования оборудования
 * ОАО "Полесьеэлектромаш"
 */

header('Content-Type: application/json');
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

// Получение данных из формы
$equipmentId = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$inventory_number = isset($_POST['inventory_number']) ? trim($_POST['inventory_number']) : '';
$location = isset($_POST['location']) ? trim($_POST['location']) : '';
$status = isset($_POST['status']) ? $_POST['status'] : 'active';
$last_maintenance = isset($_POST['last_maintenance']) && $_POST['last_maintenance'] !== '' ? $_POST['last_maintenance'] : null;

// Валидация обязательных полей
if ($equipmentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID оборудования обязателен']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Наименование обязательно']);
    exit;
}

if (empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Статус обязателен']);
    exit;
}

// Проверка допустимых значений статуса
$allowed_statuses = ['active', 'repair', 'decommissioned'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Недопустимое значение статуса']);
    exit;
}

try {
    // Проверяем существование оборудования
    $checkStmt = $pdo->prepare("SELECT id FROM equipment WHERE id = ?");
    $checkStmt->execute([$equipmentId]);
    $existingEquipment = $checkStmt->fetch();
    
    if (!$existingEquipment) {
        echo json_encode(['success' => false, 'message' => 'Оборудование не найдено']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE equipment 
                           SET name = ?, inventory_number = ?, location = ?, status = ?, last_maintenance = ?
                           WHERE id = ?");
    
    $stmt->execute([
        $name,
        $inventory_number,
        $location,
        $status,
        $last_maintenance,
        $equipmentId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Оборудование успешно обновлено',
        'equipment_id' => $equipmentId
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'Оборудование с таким инвентарным номером уже существует']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
}

