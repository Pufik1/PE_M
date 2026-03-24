<?php
/**
 * API для получения списка пользователей (менеджеров)
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
    // Получаем пользователей с ролями менеджеров и администраторов
    $stmt = $pdo->query("SELECT id, full_name, login FROM users WHERE role IN ('admin', 'director', 'manager') ORDER BY full_name");
    $users = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
