<?php
/**
 * Заглушка для остальных страниц
 */

require_once 'config.php';
checkAuth();

$page_name = basename($_SERVER['PHP_SELF'], '.php');
$page_titles = [
    'partners' => 'Контрагенты',
    'production' => 'Производство',
    'warehouse' => 'Склад',
    'materials' => 'Материалы',
    'users' => 'Сотрудники',
    'reports' => 'Отчеты'
];

$title = $page_titles[$page_name] ?? 'Страница';
$active_page = $page_name;
include 'header.php';
?>

<div class="content">
    <div class="placeholder" style="text-align: center; padding: 80px 40px;">
        <div class="placeholder-icon" style="font-size: 80px; margin-bottom: 24px;">🚧</div>
        <h2 style="font-size: 24px; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;">Раздел в разработке</h2>
        <p style="font-size: 16px; color: var(--text-muted); max-width: 500px; margin: 0 auto 32px;">Модуль "<?= htmlspecialchars($title) ?>" находится в стадии активной разработки и будет доступен в ближайшее время.</p>
        <a href="dashboard.php" class="btn">← Вернуться на главную</a>
    </div>
</div>

<?php include 'footer.php'; ?>
