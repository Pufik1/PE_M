<?php
/**
 * Управление заказами
 * ОАО "Полесьеэлектромаш"
 */

require_once 'config.php';
checkAuth();

// Получение данных
try {
    $stmt = $pdo->query("
        SELECT o.*, p.name as partner_name, u.full_name as manager_name 
        FROM orders o 
        LEFT JOIN partners p ON o.partner_id = p.id 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();
    
    // Партнеры для создания заказа
    $stmt = $pdo->query("SELECT * FROM partners WHERE type = 'client' ORDER BY name");
    $clients = $stmt->fetchAll();
    
    // Продукция
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
    $products_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Ошибка: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea; --secondary: #764ba2; --success: #10b981;
            --warning: #f59e0b; --danger: #ef4444; --info: #3b82f6;
            --dark: #1a1a2e; --gray-50: #f9fafb; --gray-100: #f3f4f6;
            --gray-200: #e5e7eb; --gray-300: #d1d5db; --gray-500: #6b7280;
            --gray-700: #374151; --gray-900: #111827;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-50); color: var(--gray-900); }
        
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 260px;
            background: linear-gradient(180deg, var(--dark) 0%, #2d3748 100%);
            padding: 24px 0; z-index: 100;
        }
        .sidebar-header { padding: 0 24px 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo { display: flex; align-items: center; gap: 12px; color: white; text-decoration: none; }
        .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px;
        }
        .logo-text { font-size: 14px; font-weight: 600; line-height: 1.3; }
        .nav-menu { padding: 16px 0; }
        .nav-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 24px;
            color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.2s; font-size: 14px;
        }
        .nav-item:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-item.active { background: rgba(102, 126, 234, 0.2); color: white; border-left: 3px solid var(--primary); }
        
        .main-content { margin-left: 260px; min-height: 100vh; }
        .top-bar {
            background: white; border-bottom: 1px solid var(--gray-200);
            padding: 16px 32px; display: flex; justify-content: space-between; align-items: center;
        }
        .page-title { font-size: 20px; font-weight: 600; color: var(--gray-900); }
        .user-menu { display: flex; align-items: center; gap: 16px; }
        .user-info { text-align: right; }
        .user-name { font-size: 14px; font-weight: 600; color: var(--gray-900); }
        .user-role { font-size: 12px; color: var(--gray-500); }
        .btn-logout {
            padding: 8px 16px; background: var(--gray-100); color: var(--gray-700);
            border: none; border-radius: 6px; font-size: 13px; font-weight: 500;
            cursor: pointer; text-decoration: none; transition: all 0.2s;
        }
        .btn-logout:hover { background: var(--gray-200); }
        
        .content { padding: 32px; }
        
        .card {
            background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden; margin-bottom: 24px;
        }
        .card-header {
            padding: 20px 24px; border-bottom: 1px solid var(--gray-200);
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-title { font-size: 16px; font-weight: 600; color: var(--gray-900); }
        
        .table-container { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th {
            text-align: left; padding: 16px; font-size: 12px; font-weight: 600;
            color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px;
            border-bottom: 2px solid var(--gray-200); white-space: nowrap;
        }
        .table td {
            padding: 16px; font-size: 14px; color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
        }
        .table tr:last-child td { border-bottom: none; }
        .table tr:hover { background: var(--gray-50); }
        
        .badge {
            display: inline-block; padding: 6px 12px; border-radius: 9999px;
            font-size: 12px; font-weight: 600; text-transform: uppercase;
        }
        .badge-primary { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        .badge-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .badge-secondary { background: var(--gray-100); color: var(--gray-700); }
        .badge-info { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        
        .btn {
            padding: 10px 20px; background: var(--primary); color: white; border: none;
            border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;
            text-decoration: none; transition: all 0.2s; display: inline-block;
        }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">ПЭ</div>
                <div class="logo-text">ОАО<br>Полесьеэлектромаш</div>
            </a>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item">📊 Главная</a>
            <a href="products.php" class="nav-item">🏭 Продукция</a>
            <a href="orders.php" class="nav-item active">📦 Заказы</a>
            <a href="partners.php" class="nav-item">🤝 Контрагенты</a>
            <a href="production.php" class="nav-item">⚙️ Производство</a>
            <a href="warehouse.php" class="nav-item">📦 Склад</a>
            <a href="materials.php" class="nav-item">🔩 Материалы</a>
            <?php if (checkRole(['admin', 'director'])): ?>
            <a href="users.php" class="nav-item">👥 Сотрудники</a>
            <?php endif; ?>
            <a href="reports.php" class="nav-item">📈 Отчеты</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h1 class="page-title">Управление заказами</h1>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="user-role"><?= getRoleName($_SESSION['user_role']) ?></div>
                </div>
                <a href="logout.php" class="btn-logout">Выход</a>
            </div>
        </header>

        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Все заказы</h2>
                    <button class="btn" onclick="alert('Функция создания заказа в разработке')">+ Новый заказ</button>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>№ заказа</th>
                                <th>Дата</th>
                                <th>Клиент</th>
                                <th>Менеджер</th>
                                <th>Сумма (BYN)</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): 
                                $status_info = getOrderStatusName($order['status']);
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                <td><?= htmlspecialchars($order['partner_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($order['manager_name'] ?? '—') ?></td>
                                <td><strong><?= formatPrice($order['total_amount_byn']) ?></strong></td>
                                <td><span class="badge badge-<?= $status_info[1] ?>"><?= $status_info[0] ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="alert('Просмотр заказа <?= htmlspecialchars($order['order_number']) ?>')">Просмотр</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
