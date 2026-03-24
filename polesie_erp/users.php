<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Сотрудники';
$active_page = 'users';

$error = null;
$users_list = [];
$total_users = 0;
$admin_count = 0;
$director_count = 0;
$manager_count = 0;
$engineer_count = 0;
$warehouse_count = 0;
$accountant_count = 0;

try {
    // Общее количество
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $total_users = (int)$stmt->fetch()['count'];
    
    // По ролям
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $admin_count = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'director'");
    $director_count = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'manager'");
    $manager_count = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'engineer'");
    $engineer_count = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'warehouse'");
    $warehouse_count = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'accountant'");
    $accountant_count = (int)$stmt->fetch()['count'];
    
    // Список всех сотрудников
    $stmt = $pdo->query("
        SELECT id, full_name, login, role, department, avatar, created_at 
        FROM users 
        ORDER BY full_name ASC
    ");
    $users_list = $stmt->fetchAll();
    
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
            <div class="stat-title">Всего сотрудников</div>
            <div class="stat-icon blue">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($total_users) ?></div>
        <div class="stat-change">человек</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Администраторы</div>
            <div class="stat-icon purple">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($admin_count) ?></div>
        <div class="stat-change">администраторов</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Руководство</div>
            <div class="stat-icon green">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($director_count + $manager_count) ?></div>
        <div class="stat-change">директоров и менеджеров</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Специалисты</div>
            <div class="stat-icon orange">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($engineer_count + $warehouse_count + $accountant_count) ?></div>
        <div class="stat-change">инженеров, складских работников и бухгалтеров</div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Список сотрудников</div>
        <?php if (checkRole(['admin', 'director'])): ?>
        <button class="btn btn-primary" onclick="openAddUserModal()">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Добавить сотрудника
        </button>
        <?php endif; ?>
    </div>
    
    <?php if (empty($users_list)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">👥</div>
        <h3>Сотрудники не найдены</h3>
        <p>Список сотрудников пока пуст</p>
    </div>
    <?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Логин</th>
                    <th>Роль</th>
                    <th>Отдел</th>
                    <th>Дата регистрации</th>
                    <?php if (checkRole(['admin', 'director'])): ?>
                    <th>Действия</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users_list as $user): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 16px;">
                                <?= mb_substr($user['full_name'], 0, 1) ?>
                            </div>
                            <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($user['login']) ?></td>
                    <td>
                        <?php
                        $role_badges = [
                            'admin' => '<span class="badge badge-purple">Администратор</span>',
                            'director' => '<span class="badge badge-green">Директор</span>',
                            'manager' => '<span class="badge badge-blue">Менеджер</span>',
                            'engineer' => '<span class="badge badge-orange">Инженер</span>',
                            'warehouse' => '<span class="badge badge-brown">Складской работник</span>',
                            'accountant' => '<span class="badge badge-gray">Бухгалтер</span>'
                        ];
                        echo $role_badges[$user['role']] ?? '<span class="badge badge-blue">Неизвестно</span>';
                        ?>
                    </td>
                    <td><?= htmlspecialchars($user['department'] ?? '—') ?></td>
                    <td>
                        <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                    </td>
                    <?php if (checkRole(['admin', 'director'])): ?>
                    <td style="width: 100px;">
                        <div style="display: flex; gap: 8px;">
                            <button onclick="openEditUserModal(
                                    <?= $user['id'] ?>,
                                    '<?= addslashes($user['full_name']) ?>',
                                    '<?= addslashes($user['login']) ?>',
                                    '<?= $user['role'] ?>',
                                    '<?= addslashes($user['department'] ?? '') ?>'
                                )" 
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
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- Модальное окно для добавления/редактирования сотрудника -->
<div id="userModal" class="modal-overlay" style="display: none;" onclick="closeUserModal(event)">
    <div class="modal-content" style="max-width: 600px;">
        <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px;">
            <h3 id="userModalTitle" style="margin: 0;">Добавить сотрудника</h3>
            <button onclick="closeUserModalDirect()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="userForm" onsubmit="submitUserForm(event)" style="padding: 24px;">
            <input type="hidden" name="id" id="userId">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div style="grid-column: 1 / -1;">
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">ФИО *</label>
                    <input type="text" name="full_name" id="fullName" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Логин *</label>
                    <input type="text" name="login" id="login" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Пароль <span id="passwordHint" style="font-weight: normal; font-size: 11px;">(оставьте пустым для сохранения текущего)</span></label>
                    <input type="password" name="password" id="password" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Роль *</label>
                    <select name="role" id="role" required style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        <option value="">Выберите роль</option>
                        <option value="admin">Администратор</option>
                        <option value="director">Директор</option>
                        <option value="manager">Менеджер</option>
                        <option value="engineer">Инженер</option>
                        <option value="warehouse">Складской работник</option>
                        <option value="accountant">Бухгалтер</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Отдел</label>
                    <input type="text" name="department" id="department" style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeUserModalDirect()" class="btn btn-secondary">Отмена</button>
                <button type="submit" class="btn btn-primary" id="userSubmitBtn">Добавить сотрудника</button>
            </div>
        </form>
    </div>
</div>

<script>
// Глобальный массив пользователей для использования в модальных окнах
const usersData = <?php echo json_encode($users_list); ?>;

// Открытие модального окна для добавления сотрудника
function openAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Добавить сотрудника';
    document.getElementById('userId').value = '';
    document.getElementById('fullName').value = '';
    document.getElementById('login').value = '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = true;
    document.getElementById('passwordHint').style.display = 'inline';
    document.getElementById('role').value = '';
    document.getElementById('department').value = '';
    document.getElementById('userSubmitBtn').textContent = 'Добавить сотрудника';
    
    const modal = document.getElementById('userModal');
    modal.style.display = 'flex';
}

// Открытие модального окна для редактирования сотрудника
function openEditUserModal(userId, fullName, login, role, department) {
    document.getElementById('userModalTitle').textContent = 'Редактировать сотрудника';
    document.getElementById('userId').value = userId;
    document.getElementById('fullName').value = fullName;
    document.getElementById('login').value = login;
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('passwordHint').style.display = 'inline';
    document.getElementById('role').value = role;
    document.getElementById('department').value = department;
    document.getElementById('userSubmitBtn').textContent = 'Сохранить изменения';
    
    const modal = document.getElementById('userModal');
    modal.style.display = 'flex';
}

// Закрытие модального окна
function closeUserModal(event) {
    if (event.target.classList.contains('modal-overlay')) {
        closeUserModalDirect();
    }
}

function closeUserModalDirect() {
    document.getElementById('userModal').style.display = 'none';
}

// Отправка формы пользователя
function submitUserForm(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('userForm'));
    const isEdit = formData.get('id') !== '';
    const url = isEdit ? 'api/update_user.php' : 'api/create_user.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeUserModalDirect();
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка соединения: ' + error.message);
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
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
`;
document.head.appendChild(modalStyles);
</script>

<?php include 'footer.php'; ?>
