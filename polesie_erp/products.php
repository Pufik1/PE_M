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

$page_title = 'Продукция';
$active_page = 'products';
include 'header.php';
?>

<div class="header-bar">
    <div class="page-title">
        <h1>Продукция</h1>
        <p>Каталог изделий ОАО "Полесьеэлектромаш"</p>
    </div>
    <div class="user-profile">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['user_login'], 0, 2)); ?></div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <span class="user-role"><?php echo getRoleName($_SESSION['user_role']); ?></span>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="card" style="border-color: var(--danger); margin-bottom: 24px;">
        <p style="color: var(--danger);"><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card" style="margin-bottom: 32px;">
    <form method="GET" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Категория</label>
            <select name="category" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                <option value="">Все категории</option>
                <?php foreach ($categories as $key => $name): ?>
                    <option value="<?php echo $key; ?>" <?php echo $category_filter === $key ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex: 2; min-width: 250px;">
            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Поиск</label>
            <input type="text" name="search" placeholder="Название или артикул..." value="<?php echo htmlspecialchars($search); ?>" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Фильтр</button>
            <a href="products.php" class="btn btn-secondary" style="margin-left: 8px;">Сброс</a>
        </div>
    </form>
</div>

<!-- Products Grid -->
<?php if (empty($products)): ?>
    <div class="card" style="text-align: center; padding: 60px 20px;">
        <svg style="width: 64px; height: 64px; color: var(--text-muted); margin-bottom: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h3 style="color: var(--text-main); margin-bottom: 8px;">Нет данных</h3>
        <p style="color: var(--text-muted);">По вашему запросу ничего не найдено</p>
    </div>
<?php else: ?>
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
        <?php foreach ($products as $product): ?>
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--bg-hover);">
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Арт. <?php echo htmlspecialchars($product['article']); ?></span>
                    <span class="badge badge-info"><?php echo htmlspecialchars($categories[$product['category']] ?? $product['category']); ?></span>
                </div>
                <div style="padding: 24px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--text-main); margin-bottom: 12px;"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div style="font-size: 13px; color: var(--text-muted); line-height: 1.8; margin-bottom: 20px;">
                        <?php if ($product['power']): ?>
                            <div><strong style="color: var(--text-main);">Мощность:</strong> <?php echo htmlspecialchars($product['power']); ?> кВт</div>
                        <?php endif; ?>
                        <?php if ($product['speed']): ?>
                            <div><strong style="color: var(--text-main);">Обороты:</strong> <?php echo htmlspecialchars($product['speed']); ?> об/мин</div>
                        <?php endif; ?>
                        <?php if ($product['voltage']): ?>
                            <div><strong style="color: var(--text-main);">Напряжение:</strong> <?php echo htmlspecialchars($product['voltage']); ?> В</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="padding: 20px 24px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--bg-hover);">
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted);">Цена</div>
                        <div style="font-size: 20px; font-weight: 700; color: var(--text-main);"><?php echo number_format($product['price_byn'], 2, ',', ' '); ?> BYN</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 12px; color: var(--text-muted);">На складе</div>
                        <div style="font-size: 14px; font-weight: 600; color: <?php echo $product['stock_quantity'] > 10 ? 'var(--success)' : ($product['stock_quantity'] > 0 ? 'var(--warning)' : 'var(--danger)'); ?>">
                            <?php echo $product['stock_quantity']; ?> шт.
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
