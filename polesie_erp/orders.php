<?php
/**
 * Управление заказами
 * ОАО "Полесьеэлектромаш"
 */

require_once 'config.php';
checkAuth();

// Получаем статистику
try {
    // Всего заказов
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $total_orders = (int)$stmt->fetch()['count'];

    // Новые заказы
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'new'");
    $new_orders = (int)$stmt->fetch()['count'];

    // В работе
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('processing', 'in_progress')");
    $process_orders = (int)$stmt->fetch()['count'];

    // Выручка (закрытые заказы)
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount_byn), 0) as total FROM orders WHERE status = 'closed'");
    $revenue = (float)$stmt->fetch()['total'];

} catch (PDOException $e) {
    $error_message = "Ошибка загрузки статистики: " . $e->getMessage();
}

// Получаем список заказов
try {
    $sql = "SELECT o.*, 
            p.name as partner_name,
            u.full_name as manager_name,
            CASE 
                WHEN o.status = 'new' THEN 'Новый'
                WHEN o.status = 'processing' THEN 'В обработке'
                WHEN o.status = 'in_progress' THEN 'В производстве'
                WHEN o.status = 'shipped' THEN 'Отгружен'
                WHEN o.status = 'ready' THEN 'Готов'
                WHEN o.status = 'closed' THEN 'Завершен'
                WHEN o.status = 'cancelled' THEN 'Отменен'
                ELSE o.status
            END as status_text
            FROM orders o
            LEFT JOIN partners p ON o.partner_id = p.id
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC";
    
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Ошибка загрузки данных: " . $e->getMessage();
    $orders = [];
}

$page_title = 'Заказы';
$active_page = 'orders';
include 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="section-title">Управление заказами</h2>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Карточки статистики -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Всего заказов</div>
                        <div class="stat-icon blue">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-change">заказов всего</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Новые заказы</div>
                        <div class="stat-icon green">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $new_orders; ?></div>
                    <div class="stat-change">ожидают обработки</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">В работе</div>
                        <div class="stat-icon orange">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $process_orders; ?></div>
                    <div class="stat-change">в производстве</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Выручка</div>
                        <div class="stat-icon purple">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($revenue, 2, '.', ' '); ?> BYN</div>
                    <div class="stat-change">закрытые заказы</div>
                </div>
            </div>

            <!-- Таблица заказов -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Список заказов</h3>
                    <button onclick="openCreateModal()" 
                            style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)';"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Добавить заказ
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Клиент</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Менеджер</th>
                                <th>Дата</th>
                                <th style="width: 120px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">Заказов не найдено</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['partner_name'] ?? 'Не указан'); ?></td>
                                        <td><?php echo number_format((float)($order['total_amount_byn'] ?? 0), 2, '.', ' '); ?> BYN</td>
                                        <td>
                                            <?php
                                            $statusClass = 'status-new';
                                            switch($order['status']) {
                                                case 'processing':
                                                case 'in_progress':
                                                    $statusClass = 'status-process';
                                                    break;
                                                case 'shipped':
                                                case 'ready':
                                                    $statusClass = 'status-ready';
                                                    break;
                                                case 'closed':
                                                    $statusClass = 'status-closed';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'status-cancelled';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($order['status_text']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['manager_name'] ?? 'Не назначен'); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                                <button onclick="openEditModal(<?php echo htmlspecialchars($order['id']); ?>)" 
                                                        style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                                        title="Редактировать"
                                                        onmouseover="this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='translateY(-1px)';"
                                                        onmouseout="this.style.background='rgba(59, 130, 246, 0.1)'; this.style.transform='translateY(0)';">
                                                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                
                                                <button onclick="deleteOrder(<?php echo htmlspecialchars($order['id']); ?>)" 
                                                        style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                                        title="Удалить"
                                                        onmouseover="this.style.background='rgba(239, 68, 68, 0.2)'; this.style.transform='translateY(-1px)';"
                                                        onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.transform='translateY(0)';">
                                                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
        </div>
    </div>
</div>

<!-- Модальное окно создания/редактирования заказа -->
<div id="orderModal" class="modal-overlay" style="display: none;" onclick="closeModal(event)">
    <div class="modal-content" style="max-width: 600px;">
        <div class="card-header" style="border-bottom: 1px solid var(--border); padding: 20px 24px; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="margin: 0;">Добавить заказ</h3>
            <button onclick="closeModalDirect()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="orderForm" method="POST" action="" style="padding: 24px;">
            <input type="hidden" id="orderId" name="id" value="">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Номер заказа *</label>
                    <input type="text" id="orderNumber" name="order_number" required 
                           style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;"
                           placeholder="ORD-2024-XXX">
                </div>
                
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Статус</label>
                    <select id="status" name="status" 
                            style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        <option value="new">Новый</option>
                        <option value="processing">В обработке</option>
                        <option value="in_progress">В производстве</option>
                        <option value="ready">Готов</option>
                        <option value="shipped">Отгружен</option>
                        <option value="closed">Завершен</option>
                        <option value="cancelled">Отменен</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Клиент</label>
                    <select id="partnerId" name="partner_id" 
                            style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        <option value="">Не указан</option>
                        <?php
                        try {
                            $partners = $pdo->query("SELECT id, name FROM partners ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($partners as $partner) {
                                echo '<option value="' . htmlspecialchars($partner['id']) . '">' . htmlspecialchars($partner['name']) . '</option>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Сумма (BYN)</label>
                    <input type="number" id="totalAmount" name="total_amount_byn" step="0.01" min="0"
                           style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;"
                           placeholder="0.00">
                </div>
            </div>
            
            <!-- Поля только для редактирования -->
            <div id="editFields" style="display: none; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Менеджер</label>
                    <select id="userId" name="user_id" 
                            style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                        <option value="">Не назначен</option>
                        <?php
                        try {
                            $users = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('manager', 'admin', 'director') ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($users as $user) {
                                echo '<option value="' . htmlspecialchars($user['id']) . '">' . htmlspecialchars($user['full_name']) . '</option>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Дата создания</label>
                    <input type="datetime-local" id="createdAt" name="created_at"
                           style="width: 100%; padding: 10px 14px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-size: 14px;">
                </div>
            </div>
            
            <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeModalDirect()" class="btn btn-secondary">
                    Отмена
                </button>
                <button type="submit" class="btn">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Сохранить
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentMode = 'create';

function openCreateModal() {
    currentMode = 'create';
    document.getElementById('modalTitle').textContent = 'Добавить заказ';
    document.getElementById('orderForm').action = 'api/create_order.php';
    document.getElementById('orderId').value = '';
    document.getElementById('orderNumber').value = '';
    document.getElementById('partnerId').value = '';
    document.getElementById('userId').value = '';
    document.getElementById('status').value = 'new';
    document.getElementById('totalAmount').value = '';
    const now = new Date();
    const localDateTime = now.getFullYear() + '-' + 
                         String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                         String(now.getDate()).padStart(2, '0') + 'T' + 
                         String(now.getHours()).padStart(2, '0') + ':' + 
                         String(now.getMinutes()).padStart(2, '0');
    document.getElementById('createdAt').value = localDateTime;
    // Скрываем поля для редактирования
    document.getElementById('editFields').style.display = 'none';
    document.getElementById('orderModal').style.display = 'flex';
}

function openEditModal(orderId) {
    currentMode = 'edit';
    document.getElementById('modalTitle').textContent = 'Редактировать заказ';
    document.getElementById('orderForm').action = 'api/update_order.php';
    
    fetch('api/get_order.php?id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                document.getElementById('orderId').value = order.id;
                document.getElementById('orderNumber').value = order.order_number;
                document.getElementById('partnerId').value = order.partner_id || '';
                document.getElementById('userId').value = order.user_id || '';
                document.getElementById('status').value = order.status;
                document.getElementById('totalAmount').value = order.total_amount_byn || '';
                if (order.created_at) {
                    const createdAtDate = new Date(order.created_at);
                    const localDateTime = createdAtDate.getFullYear() + '-' + 
                                         String(createdAtDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                         String(createdAtDate.getDate()).padStart(2, '0') + 'T' + 
                                         String(createdAtDate.getHours()).padStart(2, '0') + ':' + 
                                         String(createdAtDate.getMinutes()).padStart(2, '0');
                    document.getElementById('createdAt').value = localDateTime;
                }
                // Показываем поля для редактирования
                document.getElementById('editFields').style.display = 'grid';
                document.getElementById('orderModal').style.display = 'flex';
            } else {
                alert('Ошибка загрузки данных заказа');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка загрузки данных заказа');
        });
}

function closeModal(event) {
    if (event && event.target.classList.contains('modal-overlay')) {
        closeModalDirect();
    }
}

function closeModalDirect() {
    document.getElementById('orderModal').style.display = 'none';
}

// Закрытие по клику вне модального окна уже реализовано через onclick на modal-overlay

// Обработка отправки формы
document.getElementById('orderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const actionUrl = this.action;
    
    fetch(actionUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModalDirect();
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка соединения с сервером');
    });
});

function deleteOrder(orderId) {
    if (confirm('Вы уверены, что хотите удалить этот заказ?')) {
        fetch('api/delete_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + orderId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Заказ удален');
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка соединения с сервером');
        });
    }
}
</script>
<?php include "footer.php"; ?>
