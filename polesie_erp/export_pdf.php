<?php
/**
 * Экспорт отчета в PDF
 * 
 * Этот скрипт генерирует PDF файл с данными отчета
 */

require_once 'config.php';
checkAuth();

// Подключаем библиотеку Dompdf через автозагрузчик или CDN
// Для работы требуется установка через composer: composer require dompdf/dompdf
// Или можно использовать альтернативный подход с TCPDF

header('Content-Type: text/html; charset=utf-8');

// Параметры фильтра
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'sales';

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
    
    // Выручка по дням
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
    
} catch (PDOException $e) {
    die("Ошибка загрузки данных: " . htmlspecialchars($e->getMessage()));
}

$status_names = [
    'new' => 'Новый',
    'processing' => 'В работе',
    'ready' => 'Готов',
    'shipped' => 'Отгружен',
    'closed' => 'Закрыт',
    'cancelled' => 'Отменен'
];

$report_type_names = [
    'sales' => 'Продажи',
    'orders' => 'Заказы',
    'products' => 'Продукция',
    'partners' => 'Контрагенты'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчет: <?= htmlspecialchars($report_type_names[$report_type] ?? $report_type) ?></title>
    <style>
        @page {
            margin: 20mm;
            size: A4 landscape;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1f2937;
            background: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1e40af;
            font-size: 20pt;
            margin: 0 0 10px 0;
        }
        .header .period {
            color: #6b7280;
            font-size: 10pt;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .stats-row {
            display: table-row;
        }
        .stat-card {
            display: table-cell;
            width: 25%;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            background: #f9fafb;
        }
        .stat-title {
            font-size: 9pt;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 16pt;
            font-weight: bold;
            color: #1e40af;
        }
        .stat-description {
            font-size: 8pt;
            color: #9ca3af;
            margin-top: 5px;
        }
        h2 {
            color: #1e40af;
            font-size: 14pt;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9pt;
        }
        th {
            background: #3b82f6;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .chart-info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Отчет: <?= htmlspecialchars($report_type_names[$report_type] ?? $report_type) ?></h1>
        <div class="period">Период: <?= htmlspecialchars($date_from) ?> - <?= htmlspecialchars($date_to) ?></div>
        <div class="period">Дата формирования: <?= date('d.m.Y H:i') ?></div>
    </div>

    <div class="stats-grid">
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-title">ВСЕГО ЗАКАЗОВ</div>
                <div class="stat-value"><?= number_format((int)($orders_stats['total_orders'] ?? 0)) ?></div>
                <div class="stat-description">за выбранный период</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">ВЫРУЧКА</div>
                <div class="stat-value"><?= number_format((float)($orders_stats['total_revenue'] ?? 0), 2, ',', ' ') ?> BYN</div>
                <div class="stat-description">за выбранный период</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">СРЕДНИЙ ЗАКАЗ</div>
                <div class="stat-value"><?= number_format((float)($orders_stats['avg_order_value'] ?? 0), 2, ',', ' ') ?> BYN</div>
                <div class="stat-description">средняя сумма заказа</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">АКТИВНЫХ КЛИЕНТОВ</div>
                <div class="stat-value"><?= count($top_partners) ?></div>
                <div class="stat-description">сделали заказы</div>
            </div>
        </div>
    </div>

    <h2>Статусы заказов</h2>
    <?php if (!empty($status_stats)): ?>
    <table>
        <thead>
            <tr>
                <th>Статус</th>
                <th>Количество</th>
                <th>Сумма, BYN</th>
                <th>Доля</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_orders = array_sum(array_column($status_stats, 'count')) ?: 1;
            foreach ($status_stats as $status): 
                $percent = round(((int)$status['count'] / $total_orders) * 100, 1);
            ?>
            <tr>
                <td><?= htmlspecialchars($status_names[$status['status']] ?? $status['status']) ?></td>
                <td><?= (int)$status['count'] ?></td>
                <td><?= number_format((float)$status['amount'], 2, ',', ' ') ?></td>
                <td><?= $percent ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="chart-info">Нет данных за выбранный период</div>
    <?php endif; ?>

    <h2>Динамика выручки по дням</h2>
    <?php if (!empty($revenue_by_period)): ?>
    <table>
        <thead>
            <tr>
                <th>Дата</th>
                <th>Заказов</th>
                <th>Выручка, BYN</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($revenue_by_period as $day): ?>
            <tr>
                <td><?= date('d.m.Y', strtotime($day['date'])) ?></td>
                <td><?= (int)$day['orders_count'] ?></td>
                <td><?= number_format((float)$day['daily_revenue'], 2, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="chart-info">Нет данных за выбранный период</div>
    <?php endif; ?>

    <h2>Топ продукции по продажам</h2>
    <?php if (!empty($top_products)): ?>
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
                <td><code><?= htmlspecialchars($product['article']) ?></code></td>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><?= (int)$product['sales_count'] ?></td>
                <td><?= number_format((float)$product['total_sales'], 2, ',', ' ') ?></td>
                <td><?= $share ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="chart-info">Нет данных за выбранный период</div>
    <?php endif; ?>

    <h2>Топ контрагентов</h2>
    <?php if (!empty($top_partners)): ?>
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
                <td><?= $share ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="chart-info">Нет данных за выбранный период</div>
    <?php endif; ?>

    <div class="footer">
        <div>Polesie ERP © <?= date('Y') ?> | Отчет сформирован автоматически</div>
    </div>
</body>
</html>
