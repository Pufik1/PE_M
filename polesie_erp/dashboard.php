<?php
/**
 * Панель управления (Dashboard)
 * ОАО "Полесьеэлектромаш"
 */

require_once 'config.php';
checkAuth();

$page_title = 'Главная панель';
$active_page = 'dashboard';

// Получение статистики
try {
    // Всего продукции
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $products_count = $stmt->fetch()['count'];
    
    // Активные заказы
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('new', 'processing')");
    $active_orders = $stmt->fetch()['count'];
    
    // Производственные задания в работе
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM production_tasks WHERE status = 'in_progress'");
    $tasks_in_progress = $stmt->fetch()['count'];
    
    // Общая сумма заказов за месяц
    $stmt = $pdo->query("SELECT SUM(total_byn) as total FROM orders WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $month_revenue = $stmt->fetch()['total'] ?? 0;
    
    // Последние заказы
    $stmt = $pdo->query("
        SELECT o.*, p.name as partner_name 
        FROM orders o 
        LEFT JOIN partners p ON o.partner_id = p.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка загрузки данных: " . $e->getMessage();
}

include 'header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Всего продукции</div>
            <div class="stat-icon blue">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($products_count) ?></div>
        <div class="stat-change">единиц в каталоге</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Активные заказы</div>
            <div class="stat-icon green">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($active_orders) ?></div>
        <div class="stat-change">в работе</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Производство</div>
            <div class="stat-icon orange">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($tasks_in_progress) ?></div>
        <div class="stat-change">заданий в работе</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">Выручка за месяц</div>
            <div class="stat-icon purple">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="stat-value"><?= number_format($month_revenue, 0, '.', ' ') ?> BYN</div>
        <div class="stat-change">за последние 30 дней</div>
    </div>
</div>

<!-- Content Grid -->
<div class="grid-2">
    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Последние заказы</div>
            <a href="orders.php" class="btn btn-sm btn-secondary">Все заказы</a>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>№ заказа</th>
                        <th>Клиент</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($order['id']) ?></td>
                        <td><?= htmlspecialchars($order['partner_name'] ?? 'Не указан') ?></td>
                        <td><?= number_format($order['total_byn'], 2, ',', ' ') ?> BYN</td>
                        <td>
                            <?php
                            $status_badges = [
                                'new' => '<span class="badge badge-blue">Новый</span>',
                                'processing' => '<span class="badge badge-yellow">В работе</span>',
                                'completed' => '<span class="badge badge-green">Готов</span>',
                                'shipped' => '<span class="badge badge-purple">Отгружен</span>',
                                'cancelled' => '<span class="badge badge-red">Отменен</span>'
                            ];
                            echo $status_badges[$order['status']] ?? '<span class="badge badge-blue">Новый</span>';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Быстрые действия</div>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <a href="orders.php?action=new" class="btn" style="justify-content: center;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Новый заказ
                </a>
                <a href="products.php" class="btn btn-secondary" style="justify-content: center;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Каталог
                </a>
                <a href="partners.php" class="btn btn-secondary" style="justify-content: center;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Контрагенты
                </a>
                <a href="reports.php" class="btn btn-secondary" style="justify-content: center;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Отчеты
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
