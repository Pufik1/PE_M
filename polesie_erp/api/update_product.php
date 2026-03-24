<?php
/**
 * API для редактирования товара на складе
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
$productId = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
$article = isset($_POST['article']) ? trim($_POST['article']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$category = isset($_POST['category']) ? $_POST['category'] : '';
$power_kw = isset($_POST['power_kw']) && $_POST['power_kw'] !== '' ? (float)$_POST['power_kw'] : null;
$voltage = isset($_POST['voltage']) ? trim($_POST['voltage']) : null;
$price_byn = isset($_POST['price_byn']) && $_POST['price_byn'] !== '' ? (float)$_POST['price_byn'] : 0;
$stock_quantity = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '' ? (int)$_POST['stock_quantity'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Валидация обязательных полей
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID товара обязателен']);
    exit;
}

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
    // Проверяем существование товара
    $checkStmt = $pdo->prepare("SELECT id, stock_quantity FROM products WHERE id = ?");
    $checkStmt->execute([$productId]);
    $existingProduct = $checkStmt->fetch();
    
    if (!$existingProduct) {
        echo json_encode(['success' => false, 'message' => 'Товар не найден']);
        exit;
    }
    
    // Получаем старое количество для логирования
    $oldQuantity = (int)$existingProduct['stock_quantity'];
    $quantityDiff = $stock_quantity - $oldQuantity;

    $stmt = $pdo->prepare("UPDATE products 
                           SET article = ?, name = ?, category = ?, power_kw = ?, voltage = ?, 
                               price_byn = ?, stock_quantity = ?, description = ?
                           WHERE id = ?");
    
    $stmt->execute([
        $article,
        $name,
        $category,
        $power_kw,
        $voltage,
        $price_byn,
        $stock_quantity,
        $description,
        $productId
    ]);

    // Логируем изменение количества, если оно изменилось
    if ($quantityDiff != 0) {
        try {
            $logType = $quantityDiff > 0 ? 'income' : 'outcome';
            $logQty = abs($quantityDiff);
            $logStmt = $pdo->prepare("INSERT INTO warehouse_logs (item_type, item_id, type, quantity, user_id, comment) 
                                      VALUES ('product', ?, ?, ?, ?, 'Редактирование товара')");
            $logStmt->execute([$productId, $logType, $logQty, $_SESSION['user_id']]);
        } catch (PDOException $logError) {
            // Игнорируем ошибку логирования
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Товар успешно обновлен',
        'product_id' => $productId
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'Товар с таким артикулом уже существует']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
}
