<?php
/**
 * API для удаления контрагента
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

$partnerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($partnerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID контрагента']);
    exit;
}

try {
    // Проверка, не используется ли контрагент в заказах
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE partner_id = ?");
    $stmt->execute([$partnerId]);
    $count = (int)$stmt->fetch()['count'];

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Невозможно удалить контрагента: есть связанные заказы']);
        exit;
    }

    // Удаляем контрагента
    $stmt = $pdo->prepare("DELETE FROM partners WHERE id = ?");
    $stmt->execute([$partnerId]);

    echo json_encode([
        'success' => true,
        'message' => 'Контрагент успешно удален'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
