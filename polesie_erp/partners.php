<?php
require_once 'config.php';
checkAuth();

$page_title = 'Контрагенты';
$active_page = 'partners';

// Обработка действий
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$message = '';
$message_type = '';

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($action) {
            case 'add':
                $name = trim($_POST['name']);
                $type = $_POST['type'];
                $inn = trim($_POST['inn']);
                $address = trim($_POST['address']);
                $phone = trim($_POST['phone']);
                $email = trim($_POST['email']);
                
                if (empty($name)) {
                    throw new Exception('Название контрагента обязательно');
                }
                
                $stmt = $pdo->prepare("INSERT INTO partners (name, type, inn, address, phone, email) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $type, $inn, $address, $phone, $email]);
                
                $message = 'Контрагент успешно добавлен';
                $message_type = 'success';
                $action = 'list';
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = trim($_POST['name']);
                $type = $_POST['type'];
                $inn = trim($_POST['inn']);
                $address = trim($_POST['address']);
                $phone = trim($_POST['phone']);
                $email = trim($_POST['email']);
                
                if (empty($name)) {
                    throw new Exception('Название контрагента обязательно');
                }
                
                $stmt = $pdo->prepare("UPDATE partners SET name=?, type=?, inn=?, address=?, phone=?, email=? WHERE id=?");
                $stmt->execute([$name, $type, $inn, $address, $phone, $email, $id]);
                
                $message = 'Контрагент успешно обновлен';
                $message_type = 'success';
                $action = 'list';
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Проверка, не используется ли контрагент в заказах
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE partner_id = ?");
                $stmt->execute([$id]);
                $count = (int)$stmt->fetch()['count'];
                
                if ($count > 0) {
                    throw new Exception('Невозможно удалить контрагента: есть связанные заказы');
                }
                
                $stmt = $pdo->prepare("DELETE FROM partners WHERE id = ?");
                $stmt->execute([$id]);
                
                $message = 'Контрагент успешно удален';
                $message_type = 'success';
                $action = 'list';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

// Получение списка контрагентов
$partners = [];
$filter_type = $_GET['type'] ?? 'all';

try {
    if ($filter_type === 'all') {
        $stmt = $pdo->query("SELECT * FROM partners ORDER BY name ASC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM partners WHERE type = ? ORDER BY name ASC");
        $stmt->execute([$filter_type]);
    }
    $partners = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = 'Ошибка загрузки данных: ' . $e->getMessage();
    $message_type = 'error';
}

// Если режим просмотра формы
$form_mode = null;
$form_data = null;

if ($action === 'add' || $action === 'edit') {
    $form_mode = $action;
    
    if ($action === 'edit') {
        $id = (int)($_GET['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("SELECT * FROM partners WHERE id = ?");
            $stmt->execute([$id]);
            $form_data = $stmt->fetch();
            
            if (!$form_data) {
                $message = 'Контрагент не найден';
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
        <div class="card-title"><?= $form_mode === 'add' ? 'Добавить контрагента' : 'Редактировать контрагента' ?></div>
        <a href="partners.php" class="btn btn-sm btn-secondary">← Назад к списку</a>
    </div>
    <div class="card-body">
        <form method="POST" action="partners.php">
            <input type="hidden" name="action" value="<?= $form_mode ?>">
            <?php if ($form_mode === 'edit' && $form_data): ?>
            <input type="hidden" name="id" value="<?= $form_data['id'] ?>">
            <?php endif; ?>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Название *</label>
                    <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($form_data['name'] ?? '') ?>" required placeholder="ООО &quot;Пример&quot;">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Тип *</label>
                    <select name="type" class="form-select" required>
                        <option value="client" <?= ($form_data['type'] ?? '') === 'client' ? 'selected' : '' ?>>Клиент</option>
                        <option value="supplier" <?= ($form_data['type'] ?? '') === 'supplier' ? 'selected' : '' ?>>Поставщик</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">УНП/ИНН</label>
                    <input type="text" name="inn" class="form-input" value="<?= htmlspecialchars($form_data['inn'] ?? '') ?>" placeholder="123456789">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Телефон</label>
                    <input type="text" name="phone" class="form-input" value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" placeholder="+375 XX XXX-XX-XX">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Адрес</label>
                    <input type="text" name="address" class="form-input" value="<?= htmlspecialchars($form_data['address'] ?? '') ?>" placeholder="г. Минск, ул. Примерная, д. 1">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" placeholder="info@example.by">
                </div>
            </div>
            
            <div style="margin-top: 24px; display: flex; gap: 12px;">
                <button type="submit" class="btn">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Сохранить
                </button>
                <a href="partners.php" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Список контрагентов -->
<div class="filters">
    <div class="filter-group">
        <label class="form-label">Фильтр по типу</label>
        <select class="form-select" onchange="window.location.href='partners.php?type='+this.value" style="min-width: 200px;">
            <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>Все контрагенты</option>
            <option value="client" <?= $filter_type === 'client' ? 'selected' : '' ?>>Клиенты</option>
            <option value="supplier" <?= $filter_type === 'supplier' ? 'selected' : '' ?>>Поставщики</option>
        </select>
    </div>
    <div style="flex: 1;"></div>
    <a href="partners.php?action=add" class="btn">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Добавить контрагента
    </a>
</div>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Тип</th>
                    <th>УНП/ИНН</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th style="width: 120px;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($partners)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <div style="color: var(--text-muted);">
                            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin: 0 auto 12px; opacity: 0.5;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Контрагенты не найдены
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($partners as $partner): ?>
                <tr>
                    <td style="font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($partner['name']) ?></td>
                    <td>
                        <?php if ($partner['type'] === 'client'): ?>
                        <span class="badge badge-blue">Клиент</span>
                        <?php else: ?>
                        <span class="badge badge-purple">Поставщик</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($partner['inn'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($partner['phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($partner['email'] ?? '—') ?></td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="partners.php?action=edit&id=<?= $partner['id'] ?>" class="btn btn-sm btn-secondary" title="Редактировать">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <form method="POST" action="partners.php" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этого контрагента?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $partner['id'] ?>">
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

<div style="margin-top: 16px; font-size: 13px; color: var(--text-muted);">
    Всего контрагентов: <strong style="color: var(--text-primary);"><?= count($partners) ?></strong>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
