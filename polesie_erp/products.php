<?php
/**
 * Каталог продукции
 * ОАО "Полесьеэлектромаш"
 */

require_once 'config.php';
checkAuth();

// Фильтрация и поиск
$category_filter = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? '');

$where_conditions = [];
$params = [];

if (!empty($category_filter)) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR article LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    $stmt = $pdo->prepare("SELECT * FROM products $where_sql ORDER BY category, name");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Категории для фильтра
    $categories = [
        'motor_async' => 'Асинхронные двигатели',
        'motor_single' => 'Однофазные двигатели',
        'motor_special' => 'Спец. двигатели',
        'pump' => 'Насосы',
        'heater' => 'Электроконфорки',
        'casting' => 'Литье'
    ];
} catch (PDOException $e) {
    $error = "Ошибка: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Продукция - <?= APP_NAME ?></title>
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

        .filters {
            background: white; border-radius: 12px; padding: 20px 24px;
            margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex; gap: 16px; align-items: center; flex-wrap: wrap;
        }

        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 12px; font-weight: 500; color: var(--gray-500); }
        
        select, input[type="text"] {
            padding: 10px 14px; border: 1.5px solid var(--gray-200); border-radius: 8px;
            font-size: 14px; font-family: inherit; outline: none; transition: all 0.2s;
        }
        select:focus, input[type="text"]:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }

        .btn {
            padding: 10px 20px; background: var(--primary); color: white; border: none;
            border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;
            text-decoration: none; transition: all 0.2s; display: inline-block;
        }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-secondary { background: var(--gray-100); color: var(--gray-700); }
        .btn-secondary:hover { background: var(--gray-200); }

        .products-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;
        }

        .product-card {
            background: white; border-radius: 12px; overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s;
        }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }

        .product-header {
            padding: 16px 20px; border-bottom: 1px solid var(--gray-100);
            display: flex; justify-content: space-between; align-items: center;
        }
        .product-article { font-size: 12px; font-weight: 600; color: var(--gray-500); }
        .product-category {
            font-size: 11px; padding: 4px 10px; border-radius: 9999px;
            background: rgba(102, 126, 234, 0.1); color: var(--primary); font-weight: 500;
        }

        .product-body { padding: 20px; }
        .product-name { font-size: 16px; font-weight: 600; color: var(--gray-900); margin-bottom: 12px; }
        .product-specs { font-size: 13px; color: var(--gray-600); margin-bottom: 16px; line-height: 1.8; }
        .product-specs strong { color: var(--gray-700); }

        .product-footer {
            padding: 16px 20px; border-top: 1px solid var(--gray-100);
            display: flex; justify-content: space-between; align-items: center;
        }
        .product-price { font-size: 20px; font-weight: 700; color: var(--gray-900); }
        .product-stock { font-size: 13px; color: var(--gray-500); }
        .stock-ok { color: var(--success); }
        .stock-low { color: var(--warning); }
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
            <a href="products.php" class="nav-item active">🏭 Продукция</a>
            <a href="orders.php" class="nav-item">📦 Заказы</a>
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
            <h1 class="page-title">Каталог продукции</h1>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="user-role"><?= getRoleName($_SESSION['user_role']) ?></div>
                </div>
                <a href="logout.php" class="btn-logout">Выход</a>
            </div>
        </header>

        <div class="content">
            <div class="filters">
                <form method="GET" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
                    <div class="filter-group">
                        <label>Категория</label>
                        <select name="category">
                            <option value="">Все категории</option>
                            <?php foreach ($categories as $key => $name): ?>
                            <option value="<?= $key ?>" <?= $category_filter === $key ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Поиск</label>
                        <input type="text" name="search" placeholder="Название или артикул" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="btn">Фильтр</button>
                    <a href="products.php" class="btn btn-secondary">Сброс</a>
                </form>
            </div>

            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-header">
                        <span class="product-article">Арт. <?= htmlspecialchars($product['article']) ?></span>
                        <span class="product-category"><?= getCategoryName($product['category']) ?></span>
                    </div>
                    <div class="product-body">
                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                        <div class="product-specs">
                            <?php if ($product['power_kw']): ?>
                            <div><strong>Мощность:</strong> <?= $product['power_kw'] ?> кВт</div>
                            <?php endif; ?>
                            <?php if ($product['voltage']): ?>
                            <div><strong>Напряжение:</strong> <?= htmlspecialchars($product['voltage']) ?></div>
                            <?php endif; ?>
                            <div><strong>Описание:</strong> <?= htmlspecialchars($product['description']) ?></div>
                        </div>
                    </div>
                    <div class="product-footer">
                        <div class="product-price"><?= formatPrice($product['price_byn']) ?></div>
                        <div class="product-stock <?= $product['stock_quantity'] < 20 ? 'stock-low' : 'stock-ok' ?>">
                            На складе: <strong><?= $product['stock_quantity'] ?> шт.</strong>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--gray-500);">
                <div style="font-size: 48px; margin-bottom: 16px;">🔍</div>
                <div>Продукция не найдена</div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
