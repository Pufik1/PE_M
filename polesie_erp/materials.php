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

<!-- Таблица материалов -->
<div class="card" style="margin-bottom: 32px;">
        <div class="card-header">
            <h3>Материалы на складе</h3>
            <button class="btn btn-sm" onclick="addMaterial()">
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
                                        <button onclick="deleteMaterial(<?php echo $material['id']; ?>)" 
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                                title="Удалить"
                                                onmouseover="this.style.background='rgba(239, 68, 68, 0.2)'; this.style.transform='translateY(-1px)';"
                                                onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.transform='translateY(0)';">
                                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                        <button onclick="createOperation(<?php echo $material['id']; ?>)" 
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                                title="Операция с материалом"
                                                onmouseover="this.style.background='rgba(16, 185, 129, 0.2)'; this.style.transform='translateY(-1px)';"
                                                onmouseout="this.style.background='rgba(16, 185, 129, 0.1)'; this.style.transform='translateY(0)';">
                                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
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

<!-- Модальное окно для добавления/редактирования материала -->
<div id="materialModal" class="modal-overlay" style="display: none;" onclick="closeMaterialModal(event)">
    <div class="modal-content" style="max-width: 600px;">
        <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
            <h3 id="materialModalTitle" style="margin: 0;">Добавить материал</h3>
            <button onclick="closeMaterialModalDirect()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="materialForm" onsubmit="submitMaterialForm(event)" style="padding: 24px;">
            <input type="hidden" name="id" id="materialId">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Наименование *</label>
                    <input type="text" name="name" id="materialName" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Тип материала *</label>
                    <select name="type" id="materialType" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        <option value="">Выберите тип</option>
                        <option value="metal">Металлы</option>
                        <option value="paint">Краски и покрытия</option>
                        <option value="electronics">Электроника</option>
                        <option value="packaging">Упаковка</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Ед. измерения</label>
                    <input type="text" name="unit" id="materialUnit" value="кг" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Цена (BYN) *</label>
                    <input type="number" step="0.01" min="0" name="price_byn" id="materialPrice" required value="0" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Текущий остаток *</label>
                    <input type="number" step="0.01" min="0" name="current_stock" id="materialStock" required value="0" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Мин. уровень *</label>
                    <input type="number" step="0.01" min="0" name="min_stock_level" id="materialMinStock" required value="0" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
            </div>
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Описание</label>
                <textarea name="description" id="materialDescription" rows="3" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px; resize: vertical;"></textarea>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeMaterialModalDirect()" class="btn btn-secondary">Отмена</button>
                <button type="submit" class="btn btn-primary" id="materialSubmitBtn">Добавить материал</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно для создания операции -->
<div id="operationModal" class="modal-overlay" style="display: none;" onclick="closeOperationModal(event)">
    <div class="modal-content" style="max-width: 500px;">
        <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
            <h3 style="margin: 0;">Операция с материалом</h3>
            <button onclick="closeOperationModalDirect()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="operationForm" onsubmit="submitOperationForm(event)" style="padding: 24px;">
            <input type="hidden" name="material_id" id="operationMaterialId">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Материал</label>
                <div id="operationMaterialName" style="padding: 10px 14px; background: var(--bg-hover); border-radius: 8px; color: var(--text-main); font-size: 14px; font-weight: 600;"></div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Тип операции *</label>
                <select name="type" id="operationType" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                    <option value="income">Приход</option>
                    <option value="outcome">Расход</option>
                    <option value="write_off">Списание</option>
                </select>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Количество *</label>
                <input type="number" step="0.01" min="0.01" name="quantity" id="operationQuantity" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
            </div>
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Комментарий</label>
                <textarea name="comment" id="operationComment" rows="3" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px; resize: vertical;"></textarea>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeOperationModalDirect()" class="btn btn-secondary">Отмена</button>
                <button type="submit" class="btn btn-primary">Выполнить операцию</button>
            </div>
        </form>
    </div>
</div>

<script>
// Глобальный массив материалов для использования в модальных окнах
const materialsData = <?php echo json_encode($materials); ?>;

// Открытие модального окна для добавления материала
function addMaterial() {
    document.getElementById('materialModalTitle').textContent = 'Добавить материал';
    document.getElementById('materialId').value = '';
    document.getElementById('materialName').value = '';
    document.getElementById('materialType').value = '';
    document.getElementById('materialUnit').value = 'кг';
    document.getElementById('materialPrice').value = '0';
    document.getElementById('materialStock').value = '0';
    document.getElementById('materialMinStock').value = '0';
    document.getElementById('materialDescription').value = '';
    document.getElementById('materialSubmitBtn').textContent = 'Добавить материал';
    
    const modal = document.getElementById('materialModal');
    modal.style.display = 'flex';
}

// Открытие модального окна для редактирования материала
function editMaterial(id) {
    const material = materialsData.find(m => m.id == id);
    if (!material) {
        alert('Материал не найден');
        return;
    }
    
    document.getElementById('materialModalTitle').textContent = 'Редактировать материал';
    document.getElementById('materialId').value = material.id;
    document.getElementById('materialName').value = material.name;
    document.getElementById('materialType').value = material.type;
    document.getElementById('materialUnit').value = material.unit || 'кг';
    document.getElementById('materialPrice').value = material.price_byn || 0;
    document.getElementById('materialStock').value = material.current_stock || 0;
    document.getElementById('materialMinStock').value = material.min_stock_level || 0;
    document.getElementById('materialDescription').value = material.description || '';
    document.getElementById('materialSubmitBtn').textContent = 'Сохранить изменения';
    
    const modal = document.getElementById('materialModal');
    modal.style.display = 'flex';
}

// Закрытие модального окна материала
function closeMaterialModal(event) {
    if (event.target.classList.contains('modal-overlay')) {
        closeMaterialModalDirect();
    }
}

function closeMaterialModalDirect() {
    document.getElementById('materialModal').style.display = 'none';
}

// Отправка формы материала
function submitMaterialForm(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('materialForm'));
    const isEdit = formData.get('id') !== '';
    const url = isEdit ? 'api/update_material.php' : 'api/create_material.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeMaterialModalDirect();
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка соединения: ' + error.message);
    });
}

// Открытие модального окна для операции с материалом
function createOperation(id) {
    const material = materialsData.find(m => m.id == id);
    if (!material) {
        alert('Материал не найден');
        return;
    }
    
    document.getElementById('operationMaterialId').value = material.id;
    document.getElementById('operationMaterialName').textContent = material.name + ' (остаток: ' + (material.current_stock || 0) + ' ' + (material.unit || 'кг') + ')';
    document.getElementById('operationType').value = 'income';
    document.getElementById('operationQuantity').value = '';
    document.getElementById('operationComment').value = '';
    
    const modal = document.getElementById('operationModal');
    modal.style.display = 'flex';
}

// Закрытие модального окна операции
function closeOperationModal(event) {
    if (event.target.classList.contains('modal-overlay')) {
        closeOperationModalDirect();
    }
}

function closeOperationModalDirect() {
    document.getElementById('operationModal').style.display = 'none';
}

// Отправка формы операции
function submitOperationForm(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('operationForm'));
    
    fetch('api/create_material_operation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Операция выполнена успешно! Новый остаток: ' + data.new_stock + ' ' + (data.material_name || ''));
            closeOperationModalDirect();
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка соединения: ' + error.message);
    });
}

// Удаление материала
function deleteMaterial(id) {
    if (!confirm('Вы уверены, что хотите удалить этот материал?')) {
        return;
    }
    
    fetch('api/delete_material.php?id=' + id, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка сети: ' + error.message);
    });
}

// Добавляем стили для модальных окон
const modalStyles = document.createElement('style');
modalStyles.textContent = `
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
        width: 100%;
        max-width: 600px;
    }
`;
document.head.appendChild(modalStyles);
</script>

<?php include 'footer.php'; ?>
