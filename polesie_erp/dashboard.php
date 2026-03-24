<?php
/**
 * Панель управления (Dashboard)
 * ОАО "Полесьеэлектромаш"
 */

require_once 'config.php';
checkAuth();

// Получение статистики
try {
    // Всего продукции
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $products_count = $stmt->fetch()['count'];
    
    // Активные заказы
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('new', 'processing')");
    $active_orders = $stmt->fetch()['count'];
    
    // Производственные задания в работе
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM production_tasks WHERE status = 'in_progress'");
    $tasks_in_progress = $stmt->fetch()['count'];
    
    // Последние заказы
    $stmt = $pdo->query("
        SELECT o.*, p.name as partner_name 
        FROM orders o 
        LEFT JOIN partners p ON o.partner_id = p.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll();
    
    // Новости
    $stmt = $pdo->query("
        SELECT n.*, u.full_name as author_name 
        FROM news n 
        LEFT JOIN users u ON n.author_id = u.id 
        ORDER BY n.date_published DESC 
        LIMIT 3
    ");
    $news_items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка загрузки данных: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1a1a2e;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            background: linear-gradient(180deg, var(--dark) 0%, #2d3748 100%);
            padding: 24px 0;
            z-index: 100;
        }

        .sidebar-header {
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }

        .logo-text {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
        }

        .nav-menu {
            padding: 16px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }

        .nav-item.active {
            background: rgba(102, 126, 234, 0.2);
            color: white;
            border-left: 3px solid var(--primary);
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            opacity: 0.7;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .user-role {
            font-size: 12px;
            color: var(--gray-500);
        }

        .btn-logout {
            padding: 8px 16px;
            background: var(--gray-100);
            color: var(--gray-700);
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: var(--gray-200);
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 32px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .stat-title {
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.blue { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        .stat-icon.green { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .stat-icon.orange { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .stat-icon.purple { background: rgba(102, 126, 234, 0.1); color: var(--primary); }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .stat-change {
            font-size: 13px;
            color: var(--gray-500);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .card-body {
            padding: 24px;
        }

        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--gray-200);
        }

        .table td {
            padding: 16px;
            font-size: 14px;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        .badge-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .badge-secondary { background: var(--gray-100); color: var(--gray-700); }

        /* News List */
        .news-list {
            list-style: none;
        }

        .news-item {
            padding: 16px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .news-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .news-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .news-meta {
            font-size: 12px;
            color: var(--gray-500);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-action {
            padding: 12px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-700);
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }

        .btn-action:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">ПЭ</div>
                <div class="logo-text">ОАО<br>Полесьеэлектромаш</div>
            </a>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item active">
                📊 Главная
            </a>
            <a href="products.php" class="nav-item">
                🏭 Продукция
            </a>
            <a href="orders.php" class="nav-item">
                📦 Заказы
            </a>
            <a href="partners.php" class="nav-item">
                🤝 Контрагенты
            </a>
            <a href="production.php" class="nav-item">
                ⚙️ Производство
            </a>
            <a href="warehouse.php" class="nav-item">
                📦 Склад
            </a>
            <a href="materials.php" class="nav-item">
                🔩 Материалы
            </a>
            <?php if (checkRole(['admin', 'director'])): ?>
            <a href="users.php" class="nav-item">
                👥 Сотрудники
            </a>
            <?php endif; ?>
            <a href="reports.php" class="nav-item">
                📈 Отчеты
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="top-bar">
            <h1 class="page-title">Панель управления</h1>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="user-role"><?= getRoleName($_SESSION['user_role']) ?></div>
                </div>
                <a href="logout.php" class="btn-logout">Выход</a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Продукция</div>
                        <div class="stat-icon blue">🏭</div>
                    </div>
                    <div class="stat-value"><?= $products_count ?></div>
                    <div class="stat-change">наименований</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Активные заказы</div>
                        <div class="stat-icon green">📦</div>
                    </div>
                    <div class="stat-value"><?= $active_orders ?></div>
                    <div class="stat-change">в работе</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Производство</div>
                        <div class="stat-icon orange">⚙️</div>
                    </div>
                    <div class="stat-value"><?= $tasks_in_progress ?></div>
                    <div class="stat-change">заданий в работе</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Месяц</div>
                        <div class="stat-icon purple">📅</div>
                    </div>
                    <div class="stat-value"><?= date('F Y') ?></div>
                    <div class="stat-change">текущий период</div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Последние заказы</h2>
                        <a href="orders.php" style="font-size: 13px; color: var(--primary); text-decoration: none;">Все заказы →</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>№ заказа</th>
                                    <th>Клиент</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): 
                                    $status_info = getOrderStatusName($order['status']);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['order_number']) ?></td>
                                    <td><?= htmlspecialchars($order['partner_name'] ?? '—') ?></td>
                                    <td><?= formatPrice($order['total_amount_byn']) ?></td>
                                    <td><span class="badge badge-<?= $status_info[1] ?>"><?= $status_info[0] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- News & Info -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Новости предприятия</h2>
                    </div>
                    <div class="card-body">
                        <ul class="news-list">
                            <?php foreach ($news_items as $news): ?>
                            <li class="news-item">
                                <div class="news-title"><?= htmlspecialchars($news['title']) ?></div>
                                <div class="news-meta">
                                    <?= date('d.m.Y', strtotime($news['date_published'])) ?> • 
                                    <?= htmlspecialchars($news['author_name']) ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="quick-actions">
                            <a href="orders.php?action=new" class="btn-action">+ Новый заказ</a>
                            <a href="products.php" class="btn-action">Каталог</a>
                            <a href="production.php" class="btn-action">Задания</a>
                            <a href="warehouse.php" class="btn-action">Склад</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
