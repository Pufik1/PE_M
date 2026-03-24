<?php
/**
 * API для добавления новости
 * ОАО "Полесьеэлектромаш"
 */

header('Content-Type: application/json');
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Проверка прав доступа (только admin и director могут добавлять новости)
if (!checkRole(['admin', 'director'])) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав для выполнения операции']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

// Получение данных из формы
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$date_published = isset($_POST['date_published']) ? $_POST['date_published'] : date('Y-m-d');

// Валидация обязательных полей
if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Заголовок обязателен']);
    exit;
}

// Проверка длины заголовка
if (strlen($title) > 200) {
    echo json_encode(['success' => false, 'message' => 'Заголовок слишком длинный (максимум 200 символов)']);
    exit;
}

if (strlen($title) < 5) {
    echo json_encode(['success' => false, 'message' => 'Заголовок слишком короткий (минимум 5 символов)']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO news (title, content, date_published, author_id) 
                           VALUES (?, ?, ?, ?)");
    
    $stmt->execute([
        $title,
        $content,
        $date_published,
        $_SESSION['user_id']
    ]);

    $newNewsId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Новость успешно добавлена',
        'news_id' => $newNewsId
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}

