<?php
/**
 * API для обновления заказа
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
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$partnerId = isset($_POST['partner_id']) && $_POST['partner_id'] !== '' ? (int)$_POST['partner_id'] : null;
$userId = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : null;
$status = isset($_POST['status']) ? $_POST['status'] : 'new';
$totalAmount = isset($_POST['total_amount_byn']) && $_POST['total_amount_byn'] !== '' ? (float)$_POST['total_amount_byn'] : 0;
$comment = isset($_POST['comment']) ? $_POST['comment'] : '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID заказа']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE orders SET 
        partner_id = ?, 
        user_id = ?, 
        status = ?, 
        total_amount_byn = ?, 
        comment = ? 
        WHERE id = ?");
    
    $stmt->execute([
        $partnerId,
        $userId,
        $status,
        $totalAmount,
        $comment,
        $id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Заказ успешно обновлен'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
