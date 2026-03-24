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
        <button class="btn btn-primary" onclick="addEquipment()">
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
                    <?php if (checkRole(['admin', 'director', 'engineer'])): ?>
                    <th style="width: 100px;">Действия</th>
                    <?php endif; ?>
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
                    <?php if (checkRole(['admin', 'director', 'engineer'])): ?>
                    <td style="width: 100px;">
                        <div style="display: flex; gap: 8px;">
                            <button onclick="editEquipment(<?= $item['id'] ?>)" 
                                    style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                    title="Редактировать"
                                    onmouseover="this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='translateY(-1px)';"
                                    onmouseout="this.style.background='rgba(59, 130, 246, 0.1)'; this.style.transform='translateY(0)';">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button onclick="deleteEquipment(<?= $item['id'] ?>)" 
                                    style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                    title="Удалить"
                                    onmouseover="this.style.background='rgba(239, 68, 68, 0.2)'; this.style.transform='translateY(-1px)';"
                                    onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.transform='translateY(0)';">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- Модальное окно для добавления/редактирования оборудования -->
<div id="equipmentModal" class="modal-overlay" style="display: none;" onclick="closeEquipmentModal(event)">
    <div class="modal-content" style="max-width: 600px;">
        <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
            <h3 id="equipmentModalTitle" style="margin: 0;">Добавить оборудование</h3>
            <button onclick="closeEquipmentModalDirect()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="equipmentForm" onsubmit="submitEquipmentForm(event)" style="padding: 24px;">
            <input type="hidden" name="id" id="equipmentId">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div style="grid-column: 1 / -1;">
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Наименование *</label>
                    <input type="text" name="name" id="equipmentName" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Инвентарный №</label>
                    <input type="text" name="inventory_number" id="equipmentInventoryNumber" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Статус *</label>
                    <select name="status" id="equipmentStatus" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        <option value="active">В работе</option>
                        <option value="repair">В ремонте</option>
                        <option value="decommissioned">Списано</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Местоположение</label>
                <input type="text" name="location" id="equipmentLocation" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
            </div>
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Последнее ТО</label>
                <input type="date" name="last_maintenance" id="equipmentLastMaintenance" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeEquipmentModalDirect()" class="btn btn-secondary">Отмена</button>
                <button type="submit" class="btn btn-primary" id="equipmentSubmitBtn">Добавить оборудование</button>
            </div>
        </form>
    </div>
</div>

<script>
// Глобальный массив оборудования для использования в модальных окнах
const equipmentData = <?php echo json_encode($equipment_list); ?>;

// Открытие модального окна для добавления оборудования
function addEquipment() {
    document.getElementById('equipmentModalTitle').textContent = 'Добавить оборудование';
    document.getElementById('equipmentId').value = '';
    document.getElementById('equipmentName').value = '';
    document.getElementById('equipmentInventoryNumber').value = '';
    document.getElementById('equipmentLocation').value = '';
    document.getElementById('equipmentStatus').value = 'active';
    document.getElementById('equipmentLastMaintenance').value = '';
    document.getElementById('equipmentSubmitBtn').textContent = 'Добавить оборудование';
    
    const modal = document.getElementById('equipmentModal');
    modal.style.display = 'flex';
}

// Открытие модального окна для редактирования оборудования
function editEquipment(id) {
    const equipment = equipmentData.find(e => e.id == id);
    if (!equipment) {
        alert('Оборудование не найдено');
        return;
    }
    
    document.getElementById('equipmentModalTitle').textContent = 'Редактировать оборудование';
    document.getElementById('equipmentId').value = equipment.id;
    document.getElementById('equipmentName').value = equipment.name;
    document.getElementById('equipmentInventoryNumber').value = equipment.inventory_number || '';
    document.getElementById('equipmentLocation').value = equipment.location || '';
    document.getElementById('equipmentStatus').value = equipment.status;
    document.getElementById('equipmentLastMaintenance').value = equipment.last_maintenance || '';
    document.getElementById('equipmentSubmitBtn').textContent = 'Сохранить изменения';
    
    const modal = document.getElementById('equipmentModal');
    modal.style.display = 'flex';
}

// Закрытие модального окна оборудования
function closeEquipmentModal(event) {
    if (event.target.classList.contains('modal-overlay')) {
        closeEquipmentModalDirect();
    }
}

function closeEquipmentModalDirect() {
    document.getElementById('equipmentModal').style.display = 'none';
}

// Отправка формы оборудования
function submitEquipmentForm(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('equipmentForm'));
    const isEdit = formData.get('id') !== '';
    const url = isEdit ? 'api/update_equipment.php' : 'api/create_equipment.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeEquipmentModalDirect();
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка соединения: ' + error.message);
    });
}

// Удаление оборудования
function deleteEquipment(id) {
    if (!confirm('Вы уверены, что хотите удалить это оборудование?')) {
        return;
    }
    
    fetch('api/delete_equipment.php?id=' + id, {
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
