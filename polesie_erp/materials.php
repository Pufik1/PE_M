<?php
/**
 * Управление материалами и сырьем
 * ОАО "Полесьеэлектромаш"
 */

require_once 'config.php';
checkAuth();

// Получаем статистику по материалам
try {
    // Всего материалов
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM materials");
    $total_materials = (int)$stmt->fetch()['count'];

    // Общая стоимость материалов
    $stmt = $pdo->query("SELECT COALESCE(SUM(price_byn * current_stock), 0) as total FROM materials");
    $total_value = (float)$stmt->fetch()['total'];

    // Материалы с низким остатком
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM materials WHERE current_stock > 0 AND current_stock < min_stock_level");
    $low_stock = (int)$stmt->fetch()['count'];

    // Материалы без остатка
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM materials WHERE current_stock = 0 OR current_stock IS NULL");
    $out_of_stock = (int)$stmt->fetch()['count'];

} catch (PDOException $e) {
    $error_message = "Ошибка загрузки статистики: " . $e->getMessage();
}

// Получаем список материалов для таблицы
try {
    $type_filter = $_GET['type'] ?? '';
    $stock_filter = $_GET['stock'] ?? '';
    $search = trim($_GET['search'] ?? '');

    $where_conditions = [];
    $params = [];

    if (!empty($type_filter)) {
        $where_conditions[] = "type = ?";
        $params[] = $type_filter;
    }

    if ($stock_filter === 'low') {
        $where_conditions[] = "current_stock > 0 AND current_stock < min_stock_level";
    } elseif ($stock_filter === 'out') {
        $where_conditions[] = "current_stock = 0 OR current_stock IS NULL";
    } elseif ($stock_filter === 'in_stock') {
        $where_conditions[] = "current_stock > 0";
    }

    if (!empty($search)) {
        $where_conditions[] = "(name LIKE ?)";
        $params[] = "%$search%";
    }

    $where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    $sql = "SELECT m.*, 
            CASE m.type
                WHEN 'metal' THEN 'Металлы'
                WHEN 'paint' THEN 'Краски и покрытия'
                WHEN 'electronics' THEN 'Электроника'
                WHEN 'packaging' THEN 'Упаковка'
                ELSE m.type
            END as type_name,
            CASE 
                WHEN m.current_stock IS NULL OR m.current_stock = 0 THEN 'Нет в наличии'
                WHEN m.current_stock < m.min_stock_level THEN 'Заканчивается'
                ELSE 'В наличии'
            END as status_text
            FROM materials m $where_sql
            ORDER BY m.current_stock DESC, m.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Типы материалов для фильтра
    $material_types = [
        'metal' => 'Металлы',
        'paint' => 'Краски и покрытия',
        'electronics' => 'Электроника',
        'packaging' => 'Упаковка'
    ];

} catch (PDOException $e) {
    $error_message = "Ошибка загрузки данных: " . $e->getMessage();
    $materials = [];
}

// Получаем последние операции с материалами
try {
    $stmt = $pdo->query("SELECT wl.*, 
                        u.full_name as user_name,
                        m.name as item_name,
                        CASE wl.type
                            WHEN 'income' THEN 'Приход'
                            WHEN 'outcome' THEN 'Расход'
                            WHEN 'write_off' THEN 'Списание'
                            ELSE wl.type
                        END as type_text
                        FROM warehouse_logs wl
                        LEFT JOIN users u ON wl.user_id = u.id
                        LEFT JOIN materials m ON wl.item_id = m.id AND wl.item_type = 'material'
                        WHERE wl.item_type = 'material'
                        ORDER BY wl.date_op DESC
                        LIMIT 10");
    $recent_operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_operations = [];
}

$page_title = 'Материалы';
$active_page = 'materials';
include 'header.php';
?>

<div class="header-bar">
    <div class="page-title">
        <h1>Материалы</h1>
        <p>Управление сырьем и материалами для производства</p>
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
            <div class="stat-title">Всего материалов</div>
            <div class="stat-icon blue">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $total_materials; ?></div>
        <div class="stat-change">наименований материалов</div>
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
        <div class="stat-change">ниже мин. уровня</div>
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
        <div class="stat-change">материалов отсутствует</div>
    </div>
</div>

<!-- Фильтры -->
<div class="card" style="margin-bottom: 32px;">
    <form method="GET" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap; padding: 20px 24px;">
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Тип материала</label>
            <select name="type" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                <option value="">Все типы</option>
                <?php foreach ($material_types as $key => $name): ?>
                    <option value="<?php echo $key; ?>" <?php echo $type_filter === $key ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Статус остатка</label>
            <select name="stock" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                <option value="">Все материалы</option>
                <option value="in_stock" <?php echo $stock_filter === 'in_stock' ? 'selected' : ''; ?>>В наличии</option>
                <option value="low" <?php echo $stock_filter === 'low' ? 'selected' : ''; ?>>Заканчиваются</option>
                <option value="out" <?php echo $stock_filter === 'out' ? 'selected' : ''; ?>>Нет в наличии</option>
            </select>
        </div>
        <div style="flex: 2; min-width: 250px;">
            <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Поиск</label>
            <input type="text" name="search" placeholder="Название материала..." value="<?php echo htmlspecialchars($search); ?>" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Фильтр</button>
            <a href="materials.php" class="btn btn-secondary" style="margin-left: 8px;">Сброс</a>
        </div>
    </form>
</div>

<div class="grid-2" style="gap: 32px;">
    <!-- Таблица материалов -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <h3>Материалы на складе</h3>
            <button class="btn btn-sm" onclick="alert('Функция добавления материала')">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Добавить материал
            </button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Наименование</th>
                        <th>Тип</th>
                        <th>Ед. изм.</th>
                        <th>Цена</th>
                        <th>Остаток</th>
                        <th>Мин. уровень</th>
                        <th>Статус</th>
                        <th>Стоимость</th>
                        <th style="width: 100px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($materials)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <svg style="width: 48px; height: 48px; color: var(--text-muted); margin: 0 auto 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Материалы не найдены
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($materials as $material): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($material['name']); ?></strong></td>
                                <td><span class="badge badge-blue"><?php echo htmlspecialchars($material['type_name']); ?></span></td>
                                <td><?php echo htmlspecialchars($material['unit'] ?? 'кг'); ?></td>
                                <td><?php echo number_format($material['price_byn'], 2, ',', ' '); ?> BYN</td>
                                <td>
                                    <span style="font-weight: 600; color: <?php 
                                        echo ($material['current_stock'] === null || $material['current_stock'] == 0) ? 'var(--danger)' : 
                                            (($material['current_stock'] < $material['min_stock_level']) ? 'var(--warning)' : 'var(--success)'); 
                                    ?>">
                                        <?php echo number_format($material['current_stock'] ?? 0, 2, ',', ' '); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($material['min_stock_level'] ?? 0, 2, ',', ' '); ?></td>
                                <td>
                                    <?php if ($material['current_stock'] === null || $material['current_stock'] == 0): ?>
                                        <span class="badge badge-red">Нет в наличии</span>
                                    <?php elseif ($material['current_stock'] < $material['min_stock_level']): ?>
                                        <span class="badge badge-yellow">Заканчивается</span>
                                    <?php else: ?>
                                        <span class="badge badge-green">В наличии</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo number_format(($material['price_byn'] ?? 0) * ($material['current_stock'] ?? 0), 2, ',', ' '); ?> BYN</strong></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="editMaterial(<?php echo $material['id']; ?>)" 
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                                title="Редактировать"
                                                onmouseover="this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='translateY(-1px)';"
                                                onmouseout="this.style.background='rgba(59, 130, 246, 0.1)'; this.style.transform='translateY(0)';">
                                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="adjustStock(<?php echo $material['id']; ?>)" 
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                                title="Корректировка остатка"
                                                onmouseover="this.style.background='rgba(16, 185, 129, 0.2)'; this.style.transform='translateY(-1px)';"
                                                onmouseout="this.style.background='rgba(16, 185, 129, 0.1)'; this.style.transform='translateY(0)';">
                                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 36v-3m-3 3h.01M9 17h.01M9 21h.01M9 21h2u5a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
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
            <h3>Операции с материалами</h3>
            <button class="btn btn-sm" onclick="alert('Функция создания операции')">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Операция
            </button>
        </div>
        <div style="padding: 0;">
            <?php if (empty($recent_operations)): ?>
                <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 12px; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p>Операций пока нет</p>
                </div>
            <?php else: ?>
                <div class="operation-list">
                    <?php foreach ($recent_operations as $op): ?>
                        <div class="operation-item">
                            <div class="operation-icon <?php echo $op['type'] === 'income' ? 'green' : ($op['type'] === 'outcome' ? 'red' : 'orange'); ?>">
                                <?php if ($op['type'] === 'income'): ?>
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                    </svg>
                                <?php elseif ($op['type'] === 'outcome'): ?>
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                    </svg>
                                <?php else: ?>
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="operation-details">
                                <div class="operation-title"><?php echo htmlspecialchars($op['item_name'] ?? 'Неизвестно'); ?></div>
                                <div class="operation-meta">
                                    <span class="operation-type"><?php echo htmlspecialchars($op['type_text']); ?></span>
                                    <span class="operation-date"><?php echo date('d.m.Y H:i', strtotime($op['date_op'])); ?></span>
                                </div>
                            </div>
                            <div class="operation-amount">
                                <span style="color: <?php echo $op['type'] === 'income' ? 'var(--success)' : 'var(--danger)'; ?>; font-weight: 600;">
                                    <?php echo $op['type'] === 'income' ? '+' : '-'; ?><?php echo number_format(abs($op['quantity']), 2, ',', ' '); ?>
                                </span>
                                <div style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($op['unit'] ?? ''); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function editMaterial(id) {
    alert('Редактирование материала ID: ' + id);
    // Здесь будет логика открытия модального окна или перехода на страницу редактирования
}

function adjustStock(id) {
    alert('Корректировка остатка материала ID: ' + id);
    // Здесь будет логика открытия модального окна для корректировки остатка
}
</script>

<?php include 'footer.php'; ?>
