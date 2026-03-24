<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Оборудование';
$active_page = 'equipment';

$error = null;
$equipment_list = [];
$total_equipment = 0;
$active_count = 0;
$repair_count = 0;
$decommissioned_count = 0;

try {
    // Общее количество
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM equipment");
    $total_equipment = (int)$stmt->fetch()['count'];
    
    // По статусам
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM equipment WHERE status = 'active'");
    $active_count = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM equipment WHERE status = 'repair'");
    $repair_count = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM equipment WHERE status = 'decommissioned'");
    $decommissioned_count = (int)$stmt->fetch()['count'];
    
    // Список всего оборудования
    $stmt = $pdo->query("
        SELECT id, name, inventory_number, location, status, last_maintenance 
        FROM equipment 
        ORDER BY name ASC
    ");
    $equipment_list = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка загрузки данных: " . $e->getMessage();
}

include 'header.php';
?>

<?php if ($error): ?>
<div class="content">
    <div class="empty-state" style="border-color: var(--danger);">
        <div class="empty-state-icon" style="color: var(--danger);">⚠️</div>
        <h3>Ошибка загрузки данных</h3>
        <p><?= htmlspecialchars($error) ?></p>
    </div>
</div>
<?php else: ?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Всего оборудования</div>
            <div class="stat-icon blue">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($total_equipment) ?></div>
        <div class="stat-change">единиц</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">В работе</div>
            <div class="stat-icon green">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($active_count) ?></div>
        <div class="stat-change">активно</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">В ремонте</div>
            <div class="stat-icon orange">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($repair_count) ?></div>
        <div class="stat-change">требуют внимания</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Списано</div>
            <div class="stat-icon red">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($decommissioned_count) ?></div>
        <div class="stat-change">выведено из эксплуатации</div>
    </div>
</div>

<!-- Equipment Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Реестр оборудования</div>
        <?php if (checkRole(['admin', 'director', 'engineer'])): ?>
        <button class="btn btn-primary" onclick="alert('Функция добавления будет доступна в следующей версии')">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Добавить оборудование
        </button>
        <?php endif; ?>
    </div>
    
    <?php if (empty($equipment_list)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📦</div>
        <h3>Оборудование не найдено</h3>
        <p>Список оборудования пока пуст</p>
    </div>
    <?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Наименование</th>
                    <th>Инвентарный №</th>
                    <th>Местоположение</th>
                    <th>Статус</th>
                    <th>Последнее ТО</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipment_list as $item): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                    <td><?= htmlspecialchars($item['inventory_number'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($item['location'] ?? 'Не указано') ?></td>
                    <td>
                        <?php
                        $status_badges = [
                            'active' => '<span class="badge badge-green">В работе</span>',
                            'repair' => '<span class="badge badge-orange">В ремонте</span>',
                            'decommissioned' => '<span class="badge badge-red">Списано</span>'
                        ];
                        echo $status_badges[$item['status']] ?? '<span class="badge badge-blue">Неизвестно</span>';
                        ?>
                    </td>
                    <td>
                        <?php if ($item['last_maintenance']): ?>
                            <?= date('d.m.Y', strtotime($item['last_maintenance'])) ?>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">Не проводилось</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php include 'footer.php'; ?>
