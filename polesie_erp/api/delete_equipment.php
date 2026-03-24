<?php
/**
 * API для удаления оборудования
 * ОАО "Полесьеэлектромаш"
 */

header('Content-Type: application/json');
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Проверка прав доступа (только admin, director, engineer могут удалять)
if (!checkRole(['admin', 'director', 'engineer'])) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав для удаления']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

$equipmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($equipmentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID оборудования']);
    exit;
}

try {
    // Удаляем оборудование
    $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->execute([$equipmentId]);

    echo json_encode([
        'success' => true,
        'message' => 'Оборудование успешно удалено'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
