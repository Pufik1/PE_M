<?php
/**
 * API для удаления новости
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

$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($newsId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID новости']);
    exit;
}

try {
    // Удаляем новость
    $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
    $stmt->execute([$newsId]);

    echo json_encode([
        'success' => true,
        'message' => 'Новость успешно удалена'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
