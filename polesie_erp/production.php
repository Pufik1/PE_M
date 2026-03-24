<?php
require_once 'config.php';
checkAuth();

$page_title = 'Производство';
$active_page = 'production';

// Обработка действий
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$message = '';
$message_type = '';

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($action) {
            case 'add':
                $task_number = trim($_POST['task_number']);
                $product_id = (int)$_POST['product_id'];
                $quantity_plan = (int)$_POST['quantity_plan'];
                $workshop = $_POST['workshop'];
                $deadline = $_POST['deadline'];
                
                if (empty($task_number)) {
                    throw new Exception('Номер задания обязателен');
                }
                if (empty($product_id)) {
                    throw new Exception('Продукция обязательна');
                }
                if ($quantity_plan <= 0) {
                    throw new Exception('Плановое количество должно быть больше 0');
                }
                
                $stmt = $pdo->prepare("INSERT INTO production_tasks (task_number, product_id, quantity_plan, workshop, deadline, status) VALUES (?, ?, ?, ?, ?, 'planned')");
                $stmt->execute([$task_number, $product_id, $quantity_plan, $workshop, $deadline]);
                
                $message = 'Производственное задание успешно добавлено';
                $message_type = 'success';
                $action = 'list';
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $task_number = trim($_POST['task_number']);
                $product_id = (int)$_POST['product_id'];
                $quantity_plan = (int)$_POST['quantity_plan'];
                $quantity_fact = (int)$_POST['quantity_fact'];
                $workshop = $_POST['workshop'];
                $deadline = $_POST['deadline'];
                $status = $_POST['status'];
                
                if (empty($task_number)) {
                    throw new Exception('Номер задания обязателен');
                }
                if ($quantity_plan <= 0) {
                    throw new Exception('Плановое количество должно быть больше 0');
                }
                
                $stmt = $pdo->prepare("UPDATE production_tasks SET task_number=?, product_id=?, quantity_plan=?, quantity_fact=?, workshop=?, deadline=?, status=? WHERE id=?");
                $stmt->execute([$task_number, $product_id, $quantity_plan, $quantity_fact, $workshop, $deadline, $status, $id]);
                
                $message = 'Производственное задание успешно обновлено';
                $message_type = 'success';
                $action = 'list';
                break;
                
            case 'update_status':
                $id = (int)$_POST['id'];
                $status = $_POST['status'];
                
                $stmt = $pdo->prepare("UPDATE production_tasks SET status=? WHERE id=?");
                $stmt->execute([$status, $id]);
                
                $message = 'Статус задания обновлен';
                $message_type = 'success';
                $action = 'list';
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                $stmt = $pdo->prepare("DELETE FROM production_tasks WHERE id = ?");
                $stmt->execute([$id]);
                
                $message = 'Производственное задание успешно удалено';
                $message_type = 'success';
                $action = 'list';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

// Получение списка производственных заданий
$tasks = [];
$filter_status = $_GET['status'] ?? 'all';
$filter_workshop = $_GET['workshop'] ?? 'all';

try {
    $where_conditions = [];
    $params = [];
    
    if ($filter_status !== 'all') {
        $where_conditions[] = "pt.status = ?";
        $params[] = $filter_status;
    }
    
    if ($filter_workshop !== 'all') {
        $where_conditions[] = "pt.workshop = ?";
        $params[] = $filter_workshop;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT pt.*, p.name as product_name, p.article 
            FROM production_tasks pt 
            LEFT JOIN products p ON pt.product_id = p.id 
            $where_clause 
            ORDER BY pt.deadline ASC, pt.task_number DESC";
    
    if (empty($params)) {
        $stmt = $pdo->query($sql);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = 'Ошибка загрузки данных: ' . $e->getMessage();
    $message_type = 'error';
}

// Получение списков для фильтров и форм
try {
    $products = $pdo->query("SELECT id, article, name FROM products ORDER BY name ASC")->fetchAll();
    $workshops = $pdo->query("SELECT DISTINCT workshop FROM production_tasks WHERE workshop IS NOT NULL UNION SELECT 'Литейный цех' UNION SELECT 'Сборочный цех №1' UNION SELECT 'Сборочный цех №2' UNION SELECT 'Цех ТНП' UNION SELECT 'Спеццех' ORDER BY workshop ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $products = [];
    $workshops = ['Литейный цех', 'Сборочный цех №1', 'Сборочный цех №2', 'Цех ТНП', 'Спеццех'];
}

// Если режим просмотра формы
$form_mode = null;
$form_data = null;

if ($action === 'add' || $action === 'edit') {
    $form_mode = $action;
    
    if ($action === 'edit') {
        $id = (int)($_GET['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("SELECT * FROM production_tasks WHERE id = ?");
            $stmt->execute([$id]);
            $form_data = $stmt->fetch();
            
            if (!$form_data) {
                $message = 'Задание не найдено';
                $message_type = 'error';
                $form_mode = null;
                $action = 'list';
            }
        } catch (PDOException $e) {
            $message = 'Ошибка загрузки данных: ' . $e->getMessage();
            $message_type = 'error';
            $form_mode = null;
        }
    }
}

include 'header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?>" style="margin-bottom: 24px; padding: 16px 20px; border-radius: 8px; background: <?= $message_type === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; border: 1px solid <?= $message_type === 'success' ? 'rgba(16, 185, 129, 0.3)' : 'rgba(239, 68, 68, 0.3)' ?>; color: <?= $message_type === 'success' ? 'var(--success)' : 'var(--danger)' ?>;">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ($form_mode): ?>
<!-- Форма добавления/редактирования -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><?= $form_mode === 'add' ? 'Добавить производственное задание' : 'Редактировать производственное задание' ?></div>
        <a href="production.php" class="btn btn-sm btn-secondary">← Назад к списку</a>
    </div>
    <div class="card-body">
        <form method="POST" action="production.php">
            <input type="hidden" name="action" value="<?= $form_mode ?>">
            <?php if ($form_mode === 'edit' && $form_data): ?>
            <input type="hidden" name="id" value="<?= $form_data['id'] ?>">
            <?php endif; ?>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Номер задания *</label>
                    <input type="text" name="task_number" class="form-input" value="<?= htmlspecialchars($form_data['task_number'] ?? '') ?>" required placeholder="TASK-24-XXX">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Продукция *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Выберите продукцию</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?= $product['id'] ?>" <?= (($form_data['product_id'] ?? 0) == $product['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($product['article']) ?> - <?= htmlspecialchars($product['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Цех *</label>
                    <select name="workshop" class="form-select" required>
                        <option value="">Выберите цех</option>
                        <?php foreach ($workshops as $workshop): ?>
                        <option value="<?= htmlspecialchars($workshop) ?>" <?= (($form_data['workshop'] ?? '') === $workshop) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($workshop) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Плановое количество *</label>
                    <input type="number" name="quantity_plan" class="form-input" value="<?= htmlspecialchars($form_data['quantity_plan'] ?? '') ?>" required min="1" placeholder="100">
                </div>
                
                <?php if ($form_mode === 'edit'): ?>
                <div class="form-group">
                    <label class="form-label">Фактическое количество</label>
                    <input type="number" name="quantity_fact" class="form-input" value="<?= htmlspecialchars($form_data['quantity_fact'] ?? '0') ?>" min="0" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Статус *</label>
                    <select name="status" class="form-select" required>
                        <option value="planned" <?= ($form_data['status'] ?? '') === 'planned' ? 'selected' : '' ?>>Запланировано</option>
                        <option value="in_progress" <?= ($form_data['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>В работе</option>
                        <option value="completed" <?= ($form_data['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Выполнено</option>
                        <option value="cancelled" <?= ($form_data['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Отменено</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Срок исполнения *</label>
                    <input type="date" name="deadline" class="form-input" value="<?= htmlspecialchars($form_data['deadline'] ?? '') ?>" required>
                </div>
            </div>
            
            <div style="margin-top: 24px; display: flex; gap: 12px;">
                <button type="submit" class="btn">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Сохранить
                </button>
                <a href="production.php" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Список производственных заданий -->
<div class="filters">
    <div class="filter-group">
        <label class="form-label">Фильтр по статусу</label>
        <select class="form-select" onchange="window.location.href='production.php?status='+this.value+'&workshop=<?= urlencode($filter_workshop) ?>'" style="min-width: 180px;">
            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Все статусы</option>
            <option value="planned" <?= $filter_status === 'planned' ? 'selected' : '' ?>>Запланировано</option>
            <option value="in_progress" <?= $filter_status === 'in_progress' ? 'selected' : '' ?>>В работе</option>
            <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Выполнено</option>
            <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Отменено</option>
        </select>
    </div>
    
    <div class="filter-group">
        <label class="form-label">Фильтр по цеху</label>
        <select class="form-select" onchange="window.location.href='production.php?workshop='+this.value+'&status=<?= urlencode($filter_status) ?>'" style="min-width: 180px;">
            <option value="all" <?= $filter_workshop === 'all' ? 'selected' : '' ?>>Все цеха</option>
            <?php foreach ($workshops as $workshop): ?>
            <option value="<?= htmlspecialchars($workshop) ?>" <?= $filter_workshop === $workshop ? 'selected' : '' ?>>
                <?= htmlspecialchars($workshop) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div style="flex: 1;"></div>
    <a href="production.php?action=add" class="btn">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Добавить задание
    </a>
</div>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Номер задания</th>
                    <th>Продукция</th>
                    <th>Цех</th>
                    <th>План</th>
                    <th>Факт</th>
                    <th>Статус</th>
                    <th>Срок</th>
                    <th style="width: 140px;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px;">
                        <div style="color: var(--text-muted);">
                            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin: 0 auto 12px; opacity: 0.5;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Производственные задания не найдены
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td style="font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($task['task_number']) ?></td>
                    <td>
                        <div style="font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($task['product_name'] ?? '—') ?></div>
                        <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($task['article'] ?? '') ?></div>
                    </td>
                    <td><?= htmlspecialchars($task['workshop'] ?? '—') ?></td>
                    <td style="text-align: center;"><?= (int)$task['quantity_plan'] ?></td>
                    <td style="text-align: center;"><?= (int)$task['quantity_fact'] ?></td>
                    <td>
                        <?php
                        $status_badges = [
                            'planned' => ['badge-blue', 'Запланировано'],
                            'in_progress' => ['badge-yellow', 'В работе'],
                            'completed' => ['badge-green', 'Выполнено'],
                            'cancelled' => ['badge-red', 'Отменено']
                        ];
                        $badge_class = $status_badges[$task['status']][0] ?? 'badge-gray';
                        $badge_text = $status_badges[$task['status']][1] ?? $task['status'];
                        ?>
                        <span class="badge <?= $badge_class ?>"><?= $badge_text ?></span>
                    </td>
                    <td>
                        <?php
                        $deadline = new DateTime($task['deadline']);
                        $today = new DateTime();
                        $is_overdue = $deadline < $today && $task['status'] !== 'completed' && $task['status'] !== 'cancelled';
                        ?>
                        <span style="<?= $is_overdue ? 'color: var(--danger); font-weight: 600;' : 'color: var(--text-secondary);' ?>">
                            <?= $deadline->format('d.m.Y') ?>
                        </span>
                        <?php if ($is_overdue): ?>
                        <div style="font-size: 11px; color: var(--danger);">Просрочено</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="production.php?action=edit&id=<?= $task['id'] ?>" class="btn btn-sm btn-secondary" title="Редактировать">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <form method="POST" action="production.php" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить это задание?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.3);" title="Удалить">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--text-muted);">
    <div>
        Всего заданий: <strong style="color: var(--text-primary);"><?= count($tasks) ?></strong>
        <?php
        $stats = ['planned' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($tasks as $t) {
            if (isset($stats[$t['status']])) $stats[$t['status']]++;
        }
        ?>
        | Запланировано: <strong style="color: var(--info);"><?= $stats['planned'] ?></strong>
        | В работе: <strong style="color: var(--warning);"><?= $stats['in_progress'] ?></strong>
        | Выполнено: <strong style="color: var(--success);"><?= $stats['completed'] ?></strong>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
