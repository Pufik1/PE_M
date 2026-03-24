<?php
/**
 * API для удаления продукции
 * ОАО "Полесьеэлектромаш"
 */

header('Content-Type: application/json');
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Проверка прав доступа (только admin и director могут удалять)
if (!checkRole(['admin', 'director'])) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав для удаления']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID продукции']);
    exit;
}

try {
    // Проверяем, есть ли связанные заказы
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
    $stmt->execute([$productId]);
    $count = (int)$stmt->fetch()['count'];

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Невозможно удалить: есть связанные заказы']);
        exit;
    }

    // Проверяем, есть ли производственные задания
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM production_tasks WHERE product_id = ?");
    $stmt->execute([$productId]);
    $count = (int)$stmt->fetch()['count'];

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Невозможно удалить: есть производственные задания']);
        exit;
    }

    // Удаляем продукцию
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);

    echo json_encode([
        'success' => true,
        'message' => 'Продукция успешно удалена'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
