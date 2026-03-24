<?php
/**
 * API для удаления материала
 * ОАО "Полесьеэлектромаш"
 */

header('Content-Type: application/json');
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Проверка прав доступа (только admin, director, warehouse могут удалять)
if (!checkRole(['admin', 'director', 'warehouse'])) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав для удаления']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

$materialId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($materialId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID материала']);
    exit;
}

try {
    // Проверяем, есть ли складские операции с этим материалом
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM warehouse_logs WHERE item_type = 'material' AND item_id = ?");
    $stmt->execute([$materialId]);
    $count = (int)$stmt->fetch()['count'];

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Невозможно удалить: есть складские операции с этим материалом']);
        exit;
    }

    // Удаляем материал
    $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
    $stmt->execute([$materialId]);

    echo json_encode([
        'success' => true,
        'message' => 'Материал успешно удален'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
