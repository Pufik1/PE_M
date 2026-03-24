<?php
/**
 * API для добавления нового товара на склад
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
$article = isset($_POST['article']) ? trim($_POST['article']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$category = isset($_POST['category']) ? $_POST['category'] : '';
$power_kw = isset($_POST['power_kw']) && $_POST['power_kw'] !== '' ? (float)$_POST['power_kw'] : null;
$voltage = isset($_POST['voltage']) ? trim($_POST['voltage']) : null;
$price_byn = isset($_POST['price_byn']) && $_POST['price_byn'] !== '' ? (float)$_POST['price_byn'] : 0;
$stock_quantity = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '' ? (int)$_POST['stock_quantity'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Валидация обязательных полей
if (empty($article)) {
    echo json_encode(['success' => false, 'message' => 'Артикул обязателен']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Наименование обязательно']);
    exit;
}

if (empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Категория обязательна']);
    exit;
}

if ($price_byn < 0) {
    echo json_encode(['success' => false, 'message' => 'Цена не может быть отрицательной']);
    exit;
}

if ($stock_quantity < 0) {
    echo json_encode(['success' => false, 'message' => 'Количество не может быть отрицательным']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO products (article, name, category, power_kw, voltage, price_byn, stock_quantity, description) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $article,
        $name,
        $category,
        $power_kw,
        $voltage,
        $price_byn,
        $stock_quantity,
        $description
    ]);

    $newProductId = $pdo->lastInsertId();

    // Логируем операцию добавления
    try {
        $logStmt = $pdo->prepare("INSERT INTO warehouse_logs (item_type, item_id, type, quantity, user_id, comment) 
                                  VALUES ('product', ?, 'income', ?, ?, 'Добавление товара')");
        $logStmt->execute([$newProductId, $stock_quantity, $_SESSION['user_id']]);
    } catch (PDOException $logError) {
        // Игнорируем ошибку логирования, товар уже создан
    }

    echo json_encode([
        'success' => true,
        'message' => 'Товар успешно добавлен',
        'product_id' => $newProductId
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'Товар с таким артикулом уже существует']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
}
