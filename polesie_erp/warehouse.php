<?php
/**
 * Складской учет
 * ОАО "Полесьеэлектромаш"
 */

require_once 'config.php';
checkAuth();

// Получаем статистику по складу
try {
    // Всего товаров на складе
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity > 0");
    $total_products = (int)$stmt->fetch()['count'];

    // Общая стоимость товаров
    $stmt = $pdo->query("SELECT COALESCE(SUM(price_byn * stock_quantity), 0) as total FROM products");
    $total_value = (float)$stmt->fetch()['total'];

    // Товары с низким остатком (< 10 шт)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity > 0 AND stock_quantity < 10");
    $low_stock = (int)$stmt->fetch()['count'];

    // Товары без остатка
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0");
    $out_of_stock = (int)$stmt->fetch()['count'];

    // Всего операций за месяц
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM warehouse_logs WHERE date_op >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $month_operations = (int)$stmt->fetch()['count'];

} catch (PDOException $e) {
    $error_message = "Ошибка загрузки статистики: " . $e->getMessage();
}

// Получаем список товаров для таблицы
try {
    $category_filter = $_GET['category'] ?? '';
    $stock_filter = $_GET['stock'] ?? '';
    $search = trim($_GET['search'] ?? '');

    $where_conditions = [];
    $params = [];

    if (!empty($category_filter)) {
        $where_conditions[] = "category = ?";
        $params[] = $category_filter;
    }

    if ($stock_filter === 'low') {
        $where_conditions[] = "stock_quantity > 0 AND stock_quantity < 10";
    } elseif ($stock_filter === 'out') {
        $where_conditions[] = "stock_quantity = 0";
    } elseif ($stock_filter === 'in_stock') {
        $where_conditions[] = "stock_quantity > 0";
    }

    if (!empty($search)) {
        $where_conditions[] = "(name LIKE ? OR article LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    $sql = "SELECT p.*, 
            CASE p.category
                WHEN 'motor_async' THEN 'Асинхронные двигатели'
                WHEN 'motor_single' THEN 'Однофазные двигатели'
                WHEN 'motor_special' THEN 'Спец. двигатели'
                WHEN 'pump' THEN 'Насосы'
                WHEN 'heater' THEN 'Электроконфорки'
                WHEN 'casting' THEN 'Литье'
                ELSE p.category
            END as category_name
            FROM products p $where_sql
            ORDER BY p.stock_quantity DESC, p.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $error_message = "Ошибка загрузки данных: " . $e->getMessage();
    $products = [];
}

// Получаем последние операции
try {
    $stmt = $pdo->query("SELECT wl.*, 
                        u.full_name as user_name,
                        CASE wl.item_type
                            WHEN 'product' THEN p.name
                            WHEN 'material' THEN m.name
                            ELSE NULL
                        END as item_name,
                        CASE wl.type
                            WHEN 'income' THEN 'Приход'
                            WHEN 'outcome' THEN 'Расход'
                            WHEN 'write_off' THEN 'Списание'
                            ELSE wl.type
                        END as type_text
                        FROM warehouse_logs wl
                        LEFT JOIN users u ON wl.user_id = u.id
                        LEFT JOIN products p ON wl.item_id = p.id AND wl.item_type = 'product'
                        LEFT JOIN materials m ON wl.item_id = m.id AND wl.item_type = 'material'
                        ORDER BY wl.date_op DESC
                        LIMIT 10");
    $recent_operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_operations = [];
}

$page_title = 'Склад';
$active_page = 'warehouse';
include 'header.php';
?>

<div class="header-bar">
    <div class="page-title">
        <h1>Склад</h1>
        <p>Управление складскими запасами и операциями</p>
    </div>
    <div class="user-profile">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['user_login'], 0, 2)); ?></div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <span class="user-role"><?php echo getRoleName($_SESSION['user_role']); ?></span>
        </div>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="card" style="border-color: var(--danger); margin-bottom: 24px;">
        <p style="color: var(--danger);"><?php echo htmlspecialchars($error_message); ?></p>
    </div>
<?php endif; ?>

<!-- Статистика -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Товаров на складе</div>
            <div class="stat-icon blue">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $total_products; ?></div>
        <div class="stat-change">наименований в наличии</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Общая стоимость</div>
            <div class="stat-icon green">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($total_value, 2, ',', ' '); ?> BYN</div>
        <div class="stat-change">стоимость запасов</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Заканчиваются</div>
            <div class="stat-icon orange">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $low_stock; ?></div>
        <div class="stat-change">менее 10 шт. на складе</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Нет в наличии</div>
            <div class="stat-icon purple">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $out_of_stock; ?></div>
        <div class="stat-change">товаров отсутствует</div>
    </div>
</div>

<!-- Фильтры -->
<div class="card" style="margin-bottom: 32px;">
    <form method="GET" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap; padding: 20px 24px;">
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Категория</label>
            <select name="category" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                <option value="">Все категории</option>
                <?php foreach ($categories as $key => $name): ?>
                    <option value="<?php echo $key; ?>" <?php echo $category_filter === $key ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Статус остатка</label>
            <select name="stock" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                <option value="">Все товары</option>
                <option value="in_stock" <?php echo $stock_filter === 'in_stock' ? 'selected' : ''; ?>>В наличии</option>
                <option value="low" <?php echo $stock_filter === 'low' ? 'selected' : ''; ?>>Заканчиваются (< 10)</option>
                <option value="out" <?php echo $stock_filter === 'out' ? 'selected' : ''; ?>>Нет в наличии</option>
            </select>
        </div>
        <div style="flex: 2; min-width: 250px;">
            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Поиск</label>
            <input type="text" name="search" placeholder="Название или артикул..." value="<?php echo htmlspecialchars($search); ?>" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Фильтр</button>
            <a href="warehouse.php" class="btn btn-secondary" style="margin-left: 8px;">Сброс</a>
        </div>
    </form>
</div>

<div class="grid-2" style="gap: 32px;">
    <!-- Таблица товаров -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <h3>Товары на складе</h3>
            <button class="btn btn-sm" onclick="openAddProductModal()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Добавить товар
            </button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Артикул</th>
                        <th>Наименование</th>
                        <th>Категория</th>
                        <th>Цена</th>
                        <th>Остаток</th>
                        <th>Статус</th>
                        <th>Стоимость</th>
                        <th style="width: 100px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <svg style="width: 48px; height: 48px; color: var(--text-muted); margin: 0 auto 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Товары не найдены
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['article']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><span class="badge badge-blue"><?php echo htmlspecialchars($product['category_name']); ?></span></td>
                                <td><?php echo number_format($product['price_byn'], 2, ',', ' '); ?> BYN</td>
                                <td>
                                    <span style="font-weight: 600; color: <?php echo $product['stock_quantity'] > 10 ? 'var(--success)' : ($product['stock_quantity'] > 0 ? 'var(--warning)' : 'var(--danger)'); ?>">
                                        <?php echo $product['stock_quantity']; ?> шт.
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['stock_quantity'] > 10): ?>
                                        <span class="badge badge-green">В наличии</span>
                                    <?php elseif ($product['stock_quantity'] > 0): ?>
                                        <span class="badge badge-yellow">Заканчивается</span>
                                    <?php else: ?>
                                        <span class="badge badge-red">Нет в наличии</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo number_format($product['price_byn'] * $product['stock_quantity'], 2, ',', ' '); ?> BYN</strong></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="editProduct(<?php echo $product['id']; ?>)" 
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                                title="Редактировать"
                                                onmouseover="this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='translateY(-1px)';"
                                                onmouseout="this.style.background='rgba(59, 130, 246, 0.1)'; this.style.transform='translateY(0)';">
                                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Последние операции -->
    <div class="card">
        <div class="card-header">
            <h3>Последние операции</h3>
            <a href="#" class="btn btn-sm btn-secondary">Все операции</a>
        </div>
        <div style="padding: 0;">
            <?php if (empty($recent_operations)): ?>
                <div style="padding: 40px 24px; text-align: center; color: var(--text-muted);">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 12px; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Операций не найдено
                </div>
            <?php else: ?>
                <?php foreach ($recent_operations as $op): ?>
                    <div style="padding: 16px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; 
                            <?php echo $op['type'] === 'income' ? 'background: rgba(16, 185, 129, 0.15); color: var(--success);' : ($op['type'] === 'outcome' ? 'background: rgba(245, 158, 11, 0.15); color: var(--warning);' : 'background: rgba(239, 68, 68, 0.15); color: var(--danger);'); ?>">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php echo $op['type'] === 'income' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>' : ($op['type'] === 'outcome' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>' : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>'); ?>
                            </svg>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                <?php echo htmlspecialchars($op['item_name'] ?? 'Не указано'); ?>
                            </div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                <?php echo htmlspecialchars($op['type_text']); ?> • <?php echo htmlspecialchars($op['user_name'] ?? 'Не указан'); ?>
                            </div>
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <div style="font-weight: 600; color: <?php echo $op['type'] === 'income' ? 'var(--success)' : 'var(--danger)'; ?>">
                                <?php echo $op['type'] === 'income' ? '+' : '-'; ?><?php echo $op['quantity']; ?> <?php echo htmlspecialchars($op['item_type'] === 'product' ? 'шт.' : 'кг'); ?>
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted);">
                                <?php echo date('d.m.Y H:i', strtotime($op['date_op'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Категории товаров
const categories = {
    'motor_async': 'Асинхронные двигатели',
    'motor_single': 'Однофазные двигатели',
    'motor_special': 'Спец. двигатели',
    'pump': 'Насосы',
    'heater': 'Электроконфорки',
    'casting': 'Литье'
};

// Открытие модального окна добавления товара
function openAddProductModal() {
    const modalHtml = `
        <div id="productModal" class="modal-overlay" onclick="closeProductModal(event)">
            <div class="modal-content" style="max-width: 600px;">
                <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
                    <h3 style="margin: 0;">Добавить товар</h3>
                    <button onclick="closeProductModalDirect()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <form id="addProductForm" onsubmit="submitAddProduct(event)" style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Артикул *</label>
                            <input type="text" name="article" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Категория *</label>
                            <select name="category" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                                <option value="">Выберите категорию</option>
                                ${Object.entries(categories).map(([key, value]) => `<option value="${key}">${value}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Наименование *</label>
                        <input type="text" name="name" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Мощность (кВт)</label>
                            <input type="number" step="0.01" name="power_kw" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Напряжение</label>
                            <input type="text" name="voltage" placeholder="220/380В" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Цена (BYN) *</label>
                            <input type="number" step="0.01" min="0" name="price_byn" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Количество (шт) *</label>
                            <input type="number" min="0" name="stock_quantity" required value="0" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        </div>
                    </div>
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Описание</label>
                        <textarea name="description" rows="3" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px; resize: vertical;"></textarea>
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" onclick="closeProductModalDirect()" class="btn btn-secondary">Отмена</button>
                        <button type="submit" class="btn btn-primary">Добавить товар</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    // Добавляем стили для модального окна если их еще нет
    if (!document.getElementById('productModalStyles')) {
        const styles = document.createElement('style');
        styles.id = 'productModalStyles';
        styles.textContent = `
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }
            .modal-content {
                background: var(--bg-secondary);
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-height: 90vh;
                overflow-y: auto;
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

// Закрытие модального окна
function closeProductModal(event) {
    if (event.target.classList.contains('modal-overlay')) {
        closeProductModalDirect();
    }
}

function closeProductModalDirect() {
    const modal = document.getElementById('productModal');
    if (modal) {
        modal.remove();
    }
}

// Отправка формы добавления товара
function submitAddProduct(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('api/create_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeProductModalDirect();
            location.reload(); // Перезагружаем страницу для обновления данных
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка сети: ' + error.message);
    });
}

// Редактирование товара
function editProduct(id) {
    // Получаем данные о товаре через AJAX
    fetch(`api/get_product.php?id=${id}`)
        .then(response => response.json())
        .then(product => {
            if (!product.success) {
                alert('Ошибка: ' + product.message);
                return;
            }
            openEditProductModal(product.data);
        })
        .catch(error => {
            alert('Ошибка сети: ' + error.message);
        });
}

// Открытие модального окна редактирования
function openEditProductModal(product) {
    const modalHtml = `
        <div id="productModal" class="modal-overlay" onclick="closeProductModal(event)">
            <div class="modal-content" style="max-width: 600px;">
                <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
                    <h3 style="margin: 0;">Редактировать товар</h3>
                    <button onclick="closeProductModalDirect()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <form id="editProductForm" onsubmit="submitEditProduct(event)" style="padding: 24px;">
                    <input type="hidden" name="id" value="${product.id}">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Артикул *</label>
                            <input type="text" name="article" required value="${escapeHtml(product.article)}" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Категория *</label>
                            <select name="category" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                                <option value="">Выберите категорию</option>
                                ${Object.entries(categories).map(([key, value]) => `<option value="${key}" ${product.category === key ? 'selected' : ''}>${value}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Наименование *</label>
                        <input type="text" name="name" required value="${escapeHtml(product.name)}" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Мощность (кВт)</label>
                            <input type="number" step="0.01" name="power_kw" value="${product.power_kw || ''}" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Напряжение</label>
                            <input type="text" name="voltage" value="${escapeHtml(product.voltage || '')}" placeholder="220/380В" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Цена (BYN) *</label>
                            <input type="number" step="0.01" min="0" name="price_byn" required value="${product.price_byn}" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Количество (шт) *</label>
                            <input type="number" min="0" name="stock_quantity" required value="${product.stock_quantity}" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        </div>
                    </div>
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Описание</label>
                        <textarea name="description" rows="3" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px; resize: vertical;">${escapeHtml(product.description || '')}</textarea>
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" onclick="closeProductModalDirect()" class="btn btn-secondary">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    // Добавляем стили для модального окна если их еще нет
    if (!document.getElementById('productModalStyles')) {
        const styles = document.createElement('style');
        styles.id = 'productModalStyles';
        styles.textContent = `
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }
            .modal-content {
                background: var(--bg-secondary);
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-height: 90vh;
                overflow-y: auto;
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

// Отправка формы редактирования товара
function submitEditProduct(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('api/update_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeProductModalDirect();
            location.reload(); // Перезагружаем страницу для обновления данных
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка сети: ' + error.message);
    });
}

// Функция экранирования HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function adjustStock(id) {
    const quantity = prompt('Введите количество для корректировки (положительное - приход, отрицательное - расход):');
    if (quantity !== null && quantity !== '') {
        alert('Корректировка остатка для товара ID: ' + id + ', количество: ' + quantity);
        // Здесь будет AJAX запрос к серверу
    }
}
</script>

<?php include 'footer.php'; ?>
