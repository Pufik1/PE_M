<?php
/**
 * API для удаления производственного задания
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

$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($taskId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID задания']);
    exit;
}

try {
    // Удаляем производственное задание
    $stmt = $pdo->prepare("DELETE FROM production_tasks WHERE id = ?");
    $stmt->execute([$taskId]);

    echo json_encode([
        'success' => true,
        'message' => 'Производственное задание успешно удалено'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
