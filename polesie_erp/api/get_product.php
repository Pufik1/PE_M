<?php
/**
 * API для получения данных о товаре
 * ОАО "Полесьеэлектромаш"
 */

header('Content-Type: application/json');
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Получение ID товара
$productId = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID товара не указан']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Товар не найден']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $product
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
