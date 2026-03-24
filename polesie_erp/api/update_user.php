<?php
/**
 * API для редактирования сотрудника
 * ОАО "Полесьеэлектромаш"
 */

header('Content-Type: application/json');
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Проверка прав доступа (только admin и director могут редактировать сотрудников)
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
$userId = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$login = isset($_POST['login']) ? trim($_POST['login']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role = isset($_POST['role']) ? $_POST['role'] : '';
$department = isset($_POST['department']) ? trim($_POST['department']) : '';

// Валидация обязательных полей
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID сотрудника обязателен']);
    exit;
}

if (empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'ФИО обязательно']);
    exit;
}

if (empty($login)) {
    echo json_encode(['success' => false, 'message' => 'Логин обязателен']);
    exit;
}

if (empty($role)) {
    echo json_encode(['success' => false, 'message' => 'Роль обязательна']);
    exit;
}

// Проверка допустимых значений роли
$allowed_roles = ['admin', 'director', 'manager', 'engineer', 'warehouse', 'accountant'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Недопустимое значение роли']);
    exit;
}

try {
    // Проверяем существование сотрудника
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->execute([$userId]);
    $existingUser = $checkStmt->fetch();
    
    if (!$existingUser) {
        echo json_encode(['success' => false, 'message' => 'Сотрудник не найден']);
        exit;
    }

    // Если пароль указан, обновляем с паролем, иначе без него
    if (!empty($password)) {
        // Проверка длины пароля
        if (strlen($password) < 4) {
            echo json_encode(['success' => false, 'message' => 'Пароль должен быть не менее 4 символов']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE users 
                               SET full_name = ?, login = ?, password = ?, role = ?, department = ?
                               WHERE id = ?");
        
        $stmt->execute([
            $full_name,
            $login,
            $password,
            $role,
            $department,
            $userId
        ]);
    } else {
        // Обновление без изменения пароля
        $stmt = $pdo->prepare("UPDATE users 
                               SET full_name = ?, login = ?, role = ?, department = ?
                               WHERE id = ?");
        
        $stmt->execute([
            $full_name,
            $login,
            $role,
            $department,
            $userId
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Сотрудник успешно обновлен',
        'user_id' => $userId
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
}

