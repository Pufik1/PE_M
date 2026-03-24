<?php
/**
 * Конфигурация базы данных
 * ОАО "Полесьеэлектромаш" ERP система
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'polesie_erp');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // Стандартный пароль MAMP
define('DB_CHARSET', 'utf8mb4');

// Настройки приложения
define('APP_NAME', 'ОАО "Полесьеэлектромаш"');
define('APP_VERSION', '1.0.0');
define('CURRENCY', 'BYN');

// Создание подключения
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Старт сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Проверка авторизации пользователя
 */
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /polesie_erp/index.php');
        exit;
    }
}

/**
 * Проверка роли пользователя
 */
function checkRole($allowedRoles = []) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    if (empty($allowedRoles)) {
        return true;
    }
    
    return in_array($_SESSION['user_role'], $allowedRoles);
}

/**
 * Выход из системы
 */
function logout() {
    session_destroy();
    header('Location: /polesie_erp/index.php');
    exit;
}

/**
 * Форматирование цены в BYN
 */
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' BYN';
}

/**
 * Получение имени роли
 */
function getRoleName($role) {
    $roles = [
        'admin' => 'Администратор',
        'director' => 'Директор',
        'manager' => 'Менеджер',
        'engineer' => 'Инженер',
        'warehouse' => 'Кладовщик',
        'accountant' => 'Бухгалтер'
    ];
    return $roles[$role] ?? $role;
}

/**
 * Получение названия категории продукции
 */
function getCategoryName($category) {
    $categories = [
        'motor_async' => 'Асинхронные двигатели',
        'motor_single' => 'Однофазные двигатели',
        'motor_special' => 'Спец. двигатели',
        'pump' => 'Насосы',
        'heater' => 'Электроконфорки',
        'casting' => 'Литье'
    ];
    return $categories[$category] ?? $category;
}

/**
 * Получение статуса заказа
 */
function getOrderStatusName($status) {
    $statuses = [
        'new' => ['Новый', 'primary'],
        'processing' => ['В работе', 'warning'],
        'ready' => ['Готов', 'success'],
        'shipped' => ['Отгружен', 'info'],
        'closed' => ['Закрыт', 'secondary']
    ];
    return $statuses[$status] ?? [$status, 'secondary'];
}

/**
 * Получение имени пользователя из БД
 */
function getUserName() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        return 'Гость';
    }
    
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user['name'] ?? ($_SESSION['user_name'] ?? 'Пользователь');
    } catch (PDOException $e) {
        return $_SESSION['user_name'] ?? 'Пользователь';
    }
}

/**
 * Получение роли пользователя (название)
 */
function getUserRole() {
    if (!isset($_SESSION['user_role'])) {
        return '';
    }
    return getRoleName($_SESSION['user_role']);
}
