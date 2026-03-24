<?php
require_once 'config.php';
checkAuth();

$page_title = 'Отчеты и аналитика';
$active_page = 'reports';

// Инициализация переменных
$error = null;
$debug_info = [];

// Параметры фильтра
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'sales';

// Данные для отчетов
$sales_data = [];
$orders_stats = [];
$products_stats = [];
$partners_stats = [];
$revenue_by_period = [];
$top_products = [];
$top_partners = [];

try {
    // Общая статистика заказов за период
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount_byn), 0) as total_revenue,
            AVG(total_amount_byn) as avg_order_value
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$date_from, $date_to]);
    $orders_stats = $stmt->fetch();
    
    // Статусы заказов
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count, COALESCE(SUM(total_amount_byn), 0) as amount
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY status
    ");
    $stmt->execute([$date_from, $date_to]);
    $status_stats = $stmt->fetchAll();
    
    // Выручка по дням (для графика)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, 
               COUNT(*) as orders_count,
               COALESCE(SUM(total_amount_byn), 0) as daily_revenue
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$date_from, $date_to]);
    $revenue_by_period = $stmt->fetchAll();
    
    // Топ продукции по продажам
    $stmt = $pdo->prepare("
        SELECT p.name, p.article, 
               COUNT(oi.id) as sales_count,
               COALESCE(SUM(oi.quantity * oi.price_at_moment_byn), 0) as total_sales
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY p.id, p.name, p.article
        ORDER BY total_sales DESC
        LIMIT 10
    ");
    $stmt->execute([$date_from, $date_to]);
    $top_products = $stmt->fetchAll();
    
    // Топ контрагентов
    $stmt = $pdo->prepare("
        SELECT p.name, 
               COUNT(o.id) as orders_count,
               COALESCE(SUM(o.total_amount_byn), 0) as total_amount
        FROM orders o
        JOIN partners p ON o.partner_id = p.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY p.id, p.name
        ORDER BY total_amount DESC
        LIMIT 10
    ");
    $stmt->execute([$date_from, $date_to]);
    $top_partners = $stmt->fetchAll();
    
    // Статистика по продукции
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $products_stats['total'] = (int)$stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM partners");
    $partners_stats['total'] = (int)$stmt->fetch()['count'];
    
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
        <h3>Ошибка загрузки данных</h3>
        <p><?= htmlspecialchars($error) ?></p>
    </div>
</div>
<?php else: ?>

<!-- Filters -->
<div class="content">
    <div class="filters">
        <div class="filter-group">
            <label class="form-label">Тип отчета</label>
            <select class="form-select" style="min-width: 200px;" onchange="updateReportType(this.value)">
                <option value="sales" <?= $report_type === 'sales' ? 'selected' : '' ?>>Продажи</option>
                <option value="orders" <?= $report_type === 'orders' ? 'selected' : '' ?>>Заказы</option>
                <option value="products" <?= $report_type === 'products' ? 'selected' : '' ?>>Продукция</option>
                <option value="partners" <?= $report_type === 'partners' ? 'selected' : '' ?>>Контрагенты</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="form-label">Дата с</label>
            <input type="date" class="form-input" value="<?= htmlspecialchars($date_from) ?>" onchange="updateDateFrom(this.value)">
        </div>
        <div class="filter-group">
            <label class="form-label">Дата по</label>
            <input type="date" class="form-input" value="<?= htmlspecialchars($date_to) ?>" onchange="updateDateTo(this.value)">
        </div>
        <div class="filter-group">
            <label class="form-label">&nbsp;</label>
            <button class="btn" onclick="applyFilters()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Применить
            </button>
        </div>
        <div class="filter-group">
            <label class="form-label">&nbsp;</label>
            <button class="btn btn-secondary" onclick="exportReport()">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Экспорт
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Всего заказов</div>
                <div class="stat-icon blue">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?= number_format((int)($orders_stats['total_orders'] ?? 0)) ?></div>
            <div class="stat-change">за выбранный период</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Выручка</div>
                <div class="stat-icon green">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?= number_format((float)($orders_stats['total_revenue'] ?? 0), 0, '.', ' ') ?> BYN</div>
            <div class="stat-change">за выбранный период</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Средний заказ</div>
                <div class="stat-icon purple">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?= number_format((float)($orders_stats['avg_order_value'] ?? 0), 0, '.', ' ') ?> BYN</div>
            <div class="stat-change">средняя сумма заказа</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Активных клиентов</div>
                <div class="stat-icon orange">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?= count($top_partners) ?></div>
            <div class="stat-change">сделали заказы</div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid-2">
        <!-- Revenue Chart Placeholder -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Динамика выручки</div>
            </div>
            <div class="card-body">
                <div class="chart-placeholder" style="height: 300px; display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; padding: 20px 0;">
                    <?php 
                    $max_revenue = max(array_column($revenue_by_period, 'daily_revenue')) ?: 1;
                    foreach ($revenue_by_period as $day): 
                        $height = ((float)$day['daily_revenue'] / $max_revenue) * 240;
                    ?>
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <div style="width: 100%; height: <?= max($height, 4) ?>px; background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%); border-radius: 4px 4px 0 0; min-height: 4px;"></div>
                        <span style="font-size: 10px; color: var(--text-muted); transform: rotate(-45deg); white-space: nowrap;"><?= date('d.m', strtotime($day['date'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($revenue_by_period)): ?>
                    <div style="width: 100%; text-align: center; color: var(--text-muted); padding: 100px 0;">Нет данных за выбранный период</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Status Distribution -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Статусы заказов</div>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php 
                    $status_colors = [
                        'new' => 'blue',
                        'processing' => 'yellow',
                        'ready' => 'green',
                        'shipped' => 'purple',
                        'closed' => 'green',
                        'cancelled' => 'red'
                    ];
                    $status_names = [
                        'new' => 'Новый',
                        'processing' => 'В работе',
                        'ready' => 'Готов',
                        'shipped' => 'Отгружен',
                        'closed' => 'Закрыт',
                        'cancelled' => 'Отменен'
                    ];
                    $total_orders = array_sum(array_column($status_stats, 'count')) ?: 1;
                    foreach ($status_stats as $status): 
                        $percent = round(((int)$status['count'] / $total_orders) * 100);
                        $color = $status_colors[$status['status']] ?? 'blue';
                    ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 14px; color: var(--text-secondary);"><?= $status_names[$status['status']] ?? $status['status'] ?></span>
                            <span style="font-size: 14px; font-weight: 600; color: var(--text-primary);"><?= $status['count'] ?> (<?= $percent ?>%)</span>
                        </div>
                        <div style="height: 8px; background: var(--bg-card); border-radius: 4px; overflow: hidden;">
                            <div style="width: <?= $percent ?>%; height: 100%; background: var(--<?= $color ?>); border-radius: 4px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($status_stats)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 40px 0;">Нет данных за выбранный период</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products Table -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <div class="card-title">Топ продукции по продажам</div>
            <a href="products.php" class="btn btn-sm btn-secondary">Вся продукция</a>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Артикул</th>
                        <th>Наименование</th>
                        <th>Продаж, шт</th>
                        <th>Сумма, BYN</th>
                        <th>Доля</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_sales = array_sum(array_column($top_products, 'total_sales')) ?: 1;
                    $i = 1;
                    foreach ($top_products as $product): 
                        $share = round(((float)$product['total_sales'] / $total_sales) * 100, 1);
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><code style="background: var(--bg-card); padding: 2px 6px; border-radius: 4px; font-size: 12px;"><?= htmlspecialchars($product['article']) ?></code></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= (int)$product['sales_count'] ?></td>
                        <td><?= number_format((float)$product['total_sales'], 2, ',', ' ') ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 100px; height: 6px; background: var(--bg-card); border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?= $share ?>%; height: 100%; background: var(--primary); border-radius: 3px;"></div>
                                </div>
                                <span style="font-size: 12px; color: var(--text-muted);"><?= $share ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($top_products)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">Нет данных за выбранный период</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Partners Table -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <div class="card-title">Топ контрагентов</div>
            <a href="partners.php" class="btn btn-sm btn-secondary">Все контрагенты</a>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Контрагент</th>
                        <th>Заказов</th>
                        <th>Общая сумма, BYN</th>
                        <th>Доля</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_partner_amount = array_sum(array_column($top_partners, 'total_amount')) ?: 1;
                    $i = 1;
                    foreach ($top_partners as $partner): 
                        $share = round(((float)$partner['total_amount'] / $total_partner_amount) * 100, 1);
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($partner['name']) ?></td>
                        <td><?= (int)$partner['orders_count'] ?></td>
                        <td><?= number_format((float)$partner['total_amount'], 2, ',', ' ') ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 100px; height: 6px; background: var(--bg-card); border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?= $share ?>%; height: 100%; background: var(--success); border-radius: 3px;"></div>
                                </div>
                                <span style="font-size: 12px; color: var(--text-muted);"><?= $share ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($top_partners)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">Нет данных за выбранный период</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updateReportType(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('report_type', value);
    window.location.href = url.toString();
}

function updateDateFrom(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('date_from', value);
    window.location.href = url.toString();
}

function updateDateTo(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('date_to', value);
    window.location.href = url.toString();
}

function applyFilters() {
    const dateFrom = document.querySelector('input[type="date"]:nth-of-type(1)').value;
    const dateTo = document.querySelector('input[type="date"]:nth-of-type(2)').value;
    const reportType = document.querySelector('.form-select').value;
    
    const url = new URL(window.location.href);
    url.searchParams.set('date_from', dateFrom);
    url.searchParams.set('date_to', dateTo);
    url.searchParams.set('report_type', reportType);
    window.location.href = url.toString();
}

function exportReport() {
    try {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        if (dateInputs.length < 2) {
            alert('Ошибка: не найдены поля даты');
            return;
        }
        
        const dateFrom = dateInputs[0].value;
        const dateTo = dateInputs[1].value;
        const reportTypeSelect = document.querySelector('.form-select');
        const reportType = reportTypeSelect ? reportTypeSelect.value : 'sales';
        
        if (!dateFrom || !dateTo) {
            alert('Пожалуйста, выберите даты периода');
            return;
        }
        
        // Формируем URL с параметрами
        const url = `export_pdf.php?date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}&report_type=${encodeURIComponent(reportType)}`;
        
        // Открываем в новой вкладке напрямую
        const newWindow = window.open(url, '_blank');
        
        if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
            // Если браузер заблокировал всплывающее окно, используем форму
            const form = document.createElement('form');
            form.action = 'export_pdf.php';
            form.method = 'GET';
            form.target = '_blank';
            
            const dateFromInput = document.createElement('input');
            dateFromInput.type = 'hidden';
            dateFromInput.name = 'date_from';
            dateFromInput.value = dateFrom;
            
            const dateToInput = document.createElement('input');
            dateToInput.type = 'hidden';
            dateToInput.name = 'date_to';
            dateToInput.value = dateTo;
            
            const reportTypeInput = document.createElement('input');
            reportTypeInput.type = 'hidden';
            reportTypeInput.name = 'report_type';
            reportTypeInput.value = reportType;
            
            form.appendChild(dateFromInput);
            form.appendChild(dateToInput);
            form.appendChild(reportTypeInput);
            document.body.appendChild(form);
            form.submit();
            
            // Удаляем форму через небольшую задержку
            setTimeout(function() {
                document.body.removeChild(form);
            }, 1000);
        }
    } catch (e) {
        console.error('Ошибка экспорта:', e);
        alert('Произошла ошибка при экспорте: ' + e.message);
    }
}
</script>

<?php endif; ?>

<?php include 'footer.php'; ?>
