<?php
/**
 * API для создания нового заказа
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
$orderNumber = isset($_POST['order_number']) ? trim($_POST['order_number']) : '';
$partnerId = isset($_POST['partner_id']) && $_POST['partner_id'] !== '' ? (int)$_POST['partner_id'] : null;
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$status = isset($_POST['status']) ? $_POST['status'] : 'new';
$totalAmount = isset($_POST['total_amount_byn']) && $_POST['total_amount_byn'] !== '' ? (float)$_POST['total_amount_byn'] : 0;
$comment = isset($_POST['comment']) ? $_POST['comment'] : '';

if (empty($orderNumber)) {
    echo json_encode(['success' => false, 'message' => 'Номер заказа обязателен']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO orders (order_number, partner_id, user_id, status, total_amount_byn, comment) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $orderNumber,
        $partnerId,
        $userId,
        $status,
        $totalAmount,
        $comment
    ]);

    $newOrderId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Заказ успешно создан',
        'order_id' => $newOrderId
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
