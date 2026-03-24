<?php
/**
 * API для создания операции с материалами (приход/расход/списание)
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
$materialId = isset($_POST['material_id']) && $_POST['material_id'] !== '' ? (int)$_POST['material_id'] : 0;
$operationType = isset($_POST['type']) ? $_POST['type'] : ''; // income, outcome, write_off
$quantity = isset($_POST['quantity']) && $_POST['quantity'] !== '' ? (float)$_POST['quantity'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Валидация обязательных полей
if ($materialId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID материала обязателен']);
    exit;
}

if (empty($operationType)) {
    echo json_encode(['success' => false, 'message' => 'Тип операции обязателен']);
    exit;
}

if (!in_array($operationType, ['income', 'outcome', 'write_off'])) {
    echo json_encode(['success' => false, 'message' => 'Недопустимый тип операции']);
    exit;
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Количество должно быть больше 0']);
    exit;
}

try {
    // Проверяем существование материала и получаем текущий остаток
    $checkStmt = $pdo->prepare("SELECT id, current_stock, name FROM materials WHERE id = ?");
    $checkStmt->execute([$materialId]);
    $material = $checkStmt->fetch();

    if (!$material) {
        echo json_encode(['success' => false, 'message' => 'Материал не найден']);
        exit;
    }

    $currentStock = (float)$material['current_stock'];
    $newStock = $currentStock;

    // Вычисляем новый остаток в зависимости от типа операции
    if ($operationType === 'income') {
        $newStock = $currentStock + $quantity;
    } elseif ($operationType === 'outcome' || $operationType === 'write_off') {
        if ($currentStock < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Недостаточно материала на складе']);
            exit;
        }
        $newStock = $currentStock - $quantity;
    }

    // Обновляем остаток материала
    $updateStmt = $pdo->prepare("UPDATE materials SET current_stock = ? WHERE id = ?");
    $updateStmt->execute([$newStock, $materialId]);

    // Создаем запись в журнале операций
    $logStmt = $pdo->prepare("INSERT INTO warehouse_logs (item_type, item_id, type, quantity, user_id, comment)
                              VALUES ('material', ?, ?, ?, ?, ?)");
    $logStmt->execute([$materialId, $operationType, $quantity, $_SESSION['user_id'], $comment]);

    echo json_encode([
        'success' => true,
        'message' => 'Операция успешно выполнена',
        'new_stock' => $newStock,
        'material_name' => $material['name']
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
