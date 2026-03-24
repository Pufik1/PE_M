<?php
/**
 * API для редактирования материала на складе
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
$materialId = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$type = isset($_POST['type']) ? $_POST['type'] : '';
$unit = isset($_POST['unit']) ? trim($_POST['unit']) : 'кг';
$price_byn = isset($_POST['price_byn']) && $_POST['price_byn'] !== '' ? (float)$_POST['price_byn'] : 0;
$current_stock = isset($_POST['current_stock']) && $_POST['current_stock'] !== '' ? (float)$_POST['current_stock'] : 0;
$min_stock_level = isset($_POST['min_stock_level']) && $_POST['min_stock_level'] !== '' ? (float)$_POST['min_stock_level'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Валидация обязательных полей
if ($materialId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID материала обязателен']);
    exit;
}

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
    // Проверяем существование материала
    $checkStmt = $pdo->prepare("SELECT id, current_stock FROM materials WHERE id = ?");
    $checkStmt->execute([$materialId]);
    $existingMaterial = $checkStmt->fetch();

    if (!$existingMaterial) {
        echo json_encode(['success' => false, 'message' => 'Материал не найден']);
        exit;
    }

    // Получаем старое количество для логирования
    $oldQuantity = (float)$existingMaterial['current_stock'];
    $quantityDiff = $current_stock - $oldQuantity;

    $stmt = $pdo->prepare("UPDATE materials
                           SET name = ?, type = ?, unit = ?, price_byn = ?, 
                               current_stock = ?, min_stock_level = ?, description = ?
                           WHERE id = ?");

    $stmt->execute([
        $name,
        $type,
        $unit,
        $price_byn,
        $current_stock,
        $min_stock_level,
        $description,
        $materialId
    ]);

    // Логируем изменение количества, если оно изменилось
    if ($quantityDiff != 0) {
        try {
            $logType = $quantityDiff > 0 ? 'income' : 'outcome';
            $logQty = abs($quantityDiff);
            $logStmt = $pdo->prepare("INSERT INTO warehouse_logs (item_type, item_id, type, quantity, user_id, comment)
                                      VALUES ('material', ?, ?, ?, ?, 'Редактирование материала')");
            $logStmt->execute([$materialId, $logType, $logQty, $_SESSION['user_id']]);
        } catch (PDOException $logError) {
            // Игнорируем ошибку логирования
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Материал успешно обновлен',
        'material_id' => $materialId
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'Материал с таким наименованием уже существует']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
}
