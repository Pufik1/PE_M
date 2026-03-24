<?php
/**
 * Заглушка для остальных страниц
 */

require_once 'config.php';
checkAuth();

$page_name = basename($_SERVER['PHP_SELF'], '.php');
$page_titles = [
    'partners' => 'Контрагенты',
    'production' => 'Производство',
    'warehouse' => 'Склад',
    'materials' => 'Материалы',
    'users' => 'Сотрудники',
    'reports' => 'Отчеты'
];

$title = $page_titles[$page_name] ?? 'Страница';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - <?= APP_NAME ?></title>
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
        
        .placeholder {
            background: white; border-radius: 12px; padding: 80px 40px;
            text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .placeholder-icon { font-size: 80px; margin-bottom: 24px; }
        .placeholder h2 { font-size: 24px; font-weight: 600; color: var(--gray-900); margin-bottom: 12px; }
        .placeholder p { font-size: 16px; color: var(--gray-500); max-width: 500px; margin: 0 auto 32px; }
        .btn {
            padding: 12px 24px; background: var(--primary); color: white; border: none;
            border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;
            text-decoration: none; transition: all 0.2s; display: inline-block;
        }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
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
            <a href="orders.php" class="nav-item">📦 Заказы</a>
            <a href="partners.php" class="nav-item <?= $page_name === 'partners' ? 'active' : '' ?>">🤝 Контрагенты</a>
            <a href="production.php" class="nav-item <?= $page_name === 'production' ? 'active' : '' ?>">⚙️ Производство</a>
            <a href="warehouse.php" class="nav-item <?= $page_name === 'warehouse' ? 'active' : '' ?>">📦 Склад</a>
            <a href="materials.php" class="nav-item <?= $page_name === 'materials' ? 'active' : '' ?>">🔩 Материалы</a>
            <?php if (checkRole(['admin', 'director'])): ?>
            <a href="users.php" class="nav-item <?= $page_name === 'users' ? 'active' : '' ?>">👥 Сотрудники</a>
            <?php endif; ?>
            <a href="reports.php" class="nav-item <?= $page_name === 'reports' ? 'active' : '' ?>">📈 Отчеты</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h1 class="page-title"><?= $title ?></h1>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="user-role"><?= getRoleName($_SESSION['user_role']) ?></div>
                </div>
                <a href="logout.php" class="btn-logout">Выход</a>
            </div>
        </header>

        <div class="content">
            <div class="placeholder">
                <div class="placeholder-icon">🚧</div>
                <h2>Раздел в разработке</h2>
                <p>Модуль "<?= $title ?>" находится в стадии активной разработки и будет доступен в ближайшее время.</p>
                <a href="dashboard.php" class="btn">← Вернуться на главную</a>
            </div>
        </div>
    </main>
</body>
</html>
