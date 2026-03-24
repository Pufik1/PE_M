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
                    <div class="stat-icon" style="background-color: #e3f2fd;">
                        <i class="fas fa-shopping-cart" style="color: #1976d2;"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Всего заказов</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #fff3e0;">
                        <i class="fas fa-clock" style="color: #f57c00;"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $new_orders; ?></h3>
                        <p>Новые заказы</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #e8f5e9;">
                        <i class="fas fa-cogs" style="color: #388e3c;"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $process_orders; ?></h3>
                        <p>В работе</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #f3e5f5;">
                        <i class="fas fa-ruble-sign" style="color: #7b1fa2;"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($revenue, 2, '.', ' '); ?> BYN</h3>
                        <p>Выручка (закрытые)</p>
                    </div>
                </div>
            </div>

            <!-- Таблица заказов -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Список заказов</h3>
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
                                            <div class="flex items-center justify-center space-x-2">
                                                <button onclick="openEditModal(<?php echo htmlspecialchars($order['id']); ?>)" 
                                                        class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-200 border border-blue-200 shadow-sm"
                                                        title="Редактировать">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                
                                                <button onclick="deleteOrder(<?php echo htmlspecialchars($order['id']); ?>)" 
                                                        class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors duration-200 border border-red-200 shadow-sm"
                                                        title="Удалить">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

<?php include 'footer.php'; ?>
