<?php
require_once 'config.php';

// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    // Если не авторизован - перенаправляем на страницу входа
    header('Location: index.php');
    exit;
}

// Устанавливаем user_role из session role если он есть (для обратной совместимости)
if (isset($_SESSION['role']) && !isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = $_SESSION['role'];
}

$page_title = 'Главная панель';
$active_page = 'dashboard';

// Инициализация переменных
$products_count = 0;
$active_orders = 0;
$tasks_in_progress = 0;
$month_revenue = 0;
$recent_orders = [];
$partners_count = 0;
$order_status_stats = [];
$monthly_orders_data = [];
$error = null;
$debug_info = [];

// Получение статистики
try {
    $debug_info[] = "Подключение к БД успешно";
    
    // Всего продукции
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $products_count = (int)$stmt->fetch()['count'];
    $debug_info[] = "products_count: " . $products_count;
    
    // Активные заказы
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('new', 'processing')");
    $active_orders = (int)$stmt->fetch()['count'];
    $debug_info[] = "active_orders: " . $active_orders;
    
    // Производственные задания в работе
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM production_tasks WHERE status = 'in_progress'");
    $tasks_in_progress = (int)$stmt->fetch()['count'];
    $debug_info[] = "tasks_in_progress: " . $tasks_in_progress;
    
    // Общая сумма заказов за месяц
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount_byn), 0) as total FROM orders WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $result = $stmt->fetch();
    $month_revenue = (float)($result['total'] ?? 0);
    $debug_info[] = "month_revenue: " . $month_revenue;
    
    // Всего контрагентов
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM partners");
    $partners_count = (int)$stmt->fetch()['count'];
    $debug_info[] = "partners_count: " . $partners_count;
    
    // Статистика по статусам заказов
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM orders 
        GROUP BY status
    ");
    $order_status_stats = $stmt->fetchAll();
    $debug_info[] = "order_status_stats count: " . count($order_status_stats);
    
    // Данные для графика заказов по месяцам (последние 6 месяцев)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount_byn), 0) as total_amount
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_orders_data = $stmt->fetchAll();
    $debug_info[] = "monthly_orders_data count: " . count($monthly_orders_data);
    
    // Последние заказы
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, o.partner_id, o.status, o.total_amount_byn, o.created_at, p.name as partner_name 
        FROM orders o 
        LEFT JOIN partners p ON o.partner_id = p.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll();
    $debug_info[] = "recent_orders count: " . count($recent_orders);
    
} catch (PDOException $e) {
    $error = "Ошибка загрузки данных: " . $e->getMessage();
    $debug_info[] = "Error: " . $e->getMessage();
}

include 'header.php';
?>

<?php if ($error): ?>
<div class="content">
    <div class="empty-state" style="border-color: var(--danger);">
        <div class="empty-state-icon" style="color: var(--danger);">⚠️</div>
        <h3>Ошибка подключения к базе данных</h3>
        <p><?= htmlspecialchars($error) ?></p>
        <p style="margin-top: 16px; font-size: 13px;">
            Убедитесь, что:<br>
            1. База данных <code>polesie_erp</code> создана<br>
            2. Таблицы созданы и заполнены данными<br>
            3. Параметры подключения в config.php верны
        </p>
    </div>
</div>

<!-- Debug Info -->
<?php if (!empty($debug_info)): ?>
<div class="content">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Отладочная информация</div>
        </div>
        <div class="card-body">
            <pre style="background: #1a1a2e; padding: 16px; border-radius: 8px; overflow-x: auto;">
<?php foreach ($debug_info as $info): ?>
<?= htmlspecialchars($info) ?>

<?php endforeach; ?>
            </pre>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>

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
                        <td><?= number_format($order['total_amount_byn'] ?? 0, 2, ',', ' ') ?> BYN</td>
                        <td>
                            <?php
                            $status_badges = [
                                'new' => '<span class="badge badge-blue">Новый</span>',
                                'processing' => '<span class="badge badge-yellow">В работе</span>',
                                'ready' => '<span class="badge badge-green">Готов</span>',
                                'shipped' => '<span class="badge badge-purple">Отгружен</span>',
                                'closed' => '<span class="badge badge-green">Закрыт</span>',
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
    
    <!-- Order Status Distribution -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Статистика заказов</div>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <?php 
                $status_labels = [
                    'new' => ['label' => 'Новые', 'class' => 'badge-blue'],
                    'processing' => ['label' => 'В работе', 'class' => 'badge-yellow'],
                    'in_progress' => ['label' => 'Выполняются', 'class' => 'badge-orange'],
                    'ready' => ['label' => 'Готовы', 'class' => 'badge-green'],
                    'shipped' => ['label' => 'Отгружены', 'class' => 'badge-purple'],
                    'closed' => ['label' => 'Закрыты', 'class' => 'badge-green'],
                    'cancelled' => ['label' => 'Отменены', 'class' => 'badge-red']
                ];
                foreach ($order_status_stats as $stat): 
                    $status_info = $status_labels[$stat['status']] ?? ['label' => $stat['status'], 'class' => 'badge-blue'];
                ?>
                <div style="background: var(--bg-card); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;"><?= $status_info['label'] ?></div>
                    <div style="font-size: 20px; font-weight: 700; color: var(--text-primary);"><?= (int)$stat['count'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">Всего контрагентов:</div>
                <div style="font-size: 18px; font-weight: 600; color: var(--primary-light);"><?= number_format($partners_count) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Section -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <div class="card-title">Динамика заказов (последние 6 месяцев)</div>
    </div>
    <div class="card-body">
        <canvas id="ordersChart" height="80"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('ordersChart').getContext('2d');
    
    // Данные из PHP
    const monthlyData = <?= json_encode($monthly_orders_data) ?>;
    
    const labels = monthlyData.map(item => {
        const [year, month] = item.month.split('-');
        return `${month}.${year}`;
    });
    const orderCounts = monthlyData.map(item => parseInt(item.order_count));
    const totalAmounts = monthlyData.map(item => parseFloat(item.total_amount));
    
    // Создаем график
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Количество заказов',
                    data: orderCounts,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    yAxisID: 'y',
                    borderRadius: 6
                },
                {
                    label: 'Сумма (BYN)',
                    data: totalAmounts,
                    type: 'line',
                    borderColor: 'rgba(139, 92, 246, 1)',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(139, 92, 246, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#9ca3af',
                        font: {
                            family: 'Inter',
                            size: 12
                        },
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#f9fafb',
                    bodyColor: '#9ca3af',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.dataset.type === 'line') {
                                    label += new Intl.NumberFormat('ru-BY', { 
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2 
                                    }).format(context.parsed.y) + ' BYN';
                                } else {
                                    label += context.parsed.y;
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(55, 65, 81, 0.5)'
                    },
                    ticks: {
                        color: '#9ca3af',
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: {
                        color: 'rgba(55, 65, 81, 0.5)'
                    },
                    ticks: {
                        color: '#9ca3af',
                        font: {
                            family: 'Inter',
                            size: 11
                        },
                        callback: function(value) {
                            return Math.round(value);
                        }
                    },
                    title: {
                        display: true,
                        text: 'Заказы (шт)',
                        color: '#6b7280',
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        color: '#a78bfa',
                        font: {
                            family: 'Inter',
                            size: 11
                        },
                        callback: function(value) {
                            return value.toLocaleString('ru-BY') + ' BYN';
                        }
                    },
                    title: {
                        display: true,
                        text: 'Сумма (BYN)',
                        color: '#a78bfa',
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include 'footer.php'; ?>
