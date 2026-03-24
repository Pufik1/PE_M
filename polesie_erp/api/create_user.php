<?php
/**
 * API для добавления нового сотрудника
 * ОАО "Полесьеэлектромаш"
 */

header('Content-Type: application/json');
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Проверка прав доступа (только admin и director могут добавлять сотрудников)
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
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$login = isset($_POST['login']) ? trim($_POST['login']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role = isset($_POST['role']) ? $_POST['role'] : '';
$department = isset($_POST['department']) ? trim($_POST['department']) : '';

// Валидация обязательных полей
if (empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'ФИО обязательно']);
    exit;
}

if (empty($login)) {
    echo json_encode(['success' => false, 'message' => 'Логин обязателен']);
    exit;
}

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Пароль обязателен']);
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

// Проверка длины пароля
if (strlen($password) < 4) {
    echo json_encode(['success' => false, 'message' => 'Пароль должен быть не менее 4 символов']);
    exit;
}

try {
    // Проверка существования пользователя с таким логином
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
    $checkStmt->execute([$login]);
    $existingUser = $checkStmt->fetch();
    
    if ($existingUser) {
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (full_name, login, password, role, department) 
                           VALUES (?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $full_name,
        $login,
        $password,
        $role,
        $department
    ]);

    $newUserId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Сотрудник успешно добавлен',
        'user_id' => $newUserId
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
}

