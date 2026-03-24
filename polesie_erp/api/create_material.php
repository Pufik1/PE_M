<?php
/**
 * API для добавления нового материала на склад
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
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$type = isset($_POST['type']) ? $_POST['type'] : '';
$unit = isset($_POST['unit']) ? trim($_POST['unit']) : 'кг';
$price_byn = isset($_POST['price_byn']) && $_POST['price_byn'] !== '' ? (float)$_POST['price_byn'] : 0;
$current_stock = isset($_POST['current_stock']) && $_POST['current_stock'] !== '' ? (float)$_POST['current_stock'] : 0;
$min_stock_level = isset($_POST['min_stock_level']) && $_POST['min_stock_level'] !== '' ? (float)$_POST['min_stock_level'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Валидация обязательных полей
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Наименование обязательно']);
    exit;
}

if (empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Тип материала обязателен']);
    exit;
}

if ($price_byn < 0) {
    echo json_encode(['success' => false, 'message' => 'Цена не может быть отрицательной']);
    exit;
}

if ($current_stock < 0) {
    echo json_encode(['success' => false, 'message' => 'Количество не может быть отрицательным']);
    exit;
}

if ($min_stock_level < 0) {
    echo json_encode(['success' => false, 'message' => 'Минимальный уровень не может быть отрицательным']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO materials (name, type, unit, price_byn, current_stock, min_stock_level, description)
                           VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $name,
        $type,
        $unit,
        $price_byn,
        $current_stock,
        $min_stock_level,
        $description
    ]);

    $newMaterialId = $pdo->lastInsertId();

    // Логируем операцию добавления
    try {
        $logStmt = $pdo->prepare("INSERT INTO warehouse_logs (item_type, item_id, type, quantity, user_id, comment)
                                  VALUES ('material', ?, 'income', ?, ?, 'Добавление материала')");
        $logStmt->execute([$newMaterialId, $current_stock, $_SESSION['user_id']]);
    } catch (PDOException $logError) {
        // Игнорируем ошибку логирования, материал уже создан
    }

    echo json_encode([
        'success' => true,
        'message' => 'Материал успешно добавлен',
        'material_id' => $newMaterialId
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'Материал с таким наименованием уже существует']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
}
