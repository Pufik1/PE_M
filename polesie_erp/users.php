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
                    <td>
                        <button class="btn btn-sm btn-secondary" 
                                onclick="openEditUserModal(
                                    <?= $user['id'] ?>,
                                    '<?= addslashes($user['full_name']) ?>',
                                    '<?= addslashes($user['login']) ?>',
                                    '<?= $user['role'] ?>',
                                    '<?= addslashes($user['department'] ?? '') ?>'
                                )">
                            Редактировать
                        </button>
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

<!-- Modal: Добавить/Редактировать сотрудника -->
<div id="userModal" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="modalTitle">Добавить сотрудника</h3>
            <button class="btn-close" onclick="closeUserModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="userId" name="id" value="">
                
                <div class="form-group">
                    <label for="full_name">ФИО *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="login">Логин *</label>
                    <input type="text" id="login" name="login" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль <span id="passwordHint">(оставьте пустым для сохранения текущего)</span></label>
                    <input type="password" id="password" name="password" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="role">Роль *</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Выберите роль</option>
                        <option value="admin">Администратор</option>
                        <option value="director">Директор</option>
                        <option value="manager">Менеджер</option>
                        <option value="engineer">Инженер</option>
                        <option value="warehouse">Складской работник</option>
                        <option value="accountant">Бухгалтер</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="department">Отдел</label>
                    <input type="text" id="department" name="department" class="form-control">
                </div>
                
                <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary" id="saveUserBtn">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
}
.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}
.modal-content {
    position: relative;
    background: white;
    border-radius: 8px;
    margin: 50px auto;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}
.modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #333;
}
.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
    padding: 0;
    line-height: 1;
}
.btn-close:hover {
    color: #333;
}
.modal-body {
    padding: 20px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}
.form-group label span {
    font-weight: normal;
    font-size: 12px;
    color: #999;
}
.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
}
.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
</style>

<script>
// Открытие модального окна для добавления сотрудника
function openAddUserModal() {
    document.getElementById('modalTitle').textContent = 'Добавить сотрудника';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('passwordHint').style.display = 'none';
    document.getElementById('userModal').style.display = 'block';
}

// Открытие модального окна для редактирования сотрудника
function openEditUserModal(userId, fullName, login, role, department) {
    document.getElementById('modalTitle').textContent = 'Редактировать сотрудника';
    document.getElementById('userId').value = userId;
    document.getElementById('full_name').value = fullName;
    document.getElementById('login').value = login;
    document.getElementById('role').value = role;
    document.getElementById('department').value = department;
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('passwordHint').style.display = 'inline';
    document.getElementById('userModal').style.display = 'block';
}

// Закрытие модального окна
function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
    document.getElementById('userForm').reset();
}

// Закрытие по клику на overlay
document.querySelector('.modal-overlay').addEventListener('click', function() {
    closeUserModal();
});

// Обработка отправки формы
document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const userId = document.getElementById('userId').value;
    const isEdit = userId !== '';
    
    const url = isEdit ? 'api/update_user.php' : 'api/create_user.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeUserModal();
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка соединения: ' + error);
    });
});
</script>

<?php include 'footer.php'; ?>
