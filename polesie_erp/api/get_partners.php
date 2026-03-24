<?php
/**
 * API для получения списка партнеров (контрагентов)
 * ОАО "Полесьеэлектромаш"
 */

header('Content-Type: application/json');
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

try {
    // Получаем только клиентов
    $stmt = $pdo->query("SELECT id, name FROM partners WHERE type = 'client' ORDER BY name");
    $partners = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'partners' => $partners
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
