<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Новости предприятия';
$active_page = 'news';

$error = null;
$news_list = [];

try {
    $stmt = $pdo->query("
        SELECT n.id, n.title, n.content, n.date_published, u.full_name as author_name
        FROM news n
        LEFT JOIN users u ON n.author_id = u.id
        ORDER BY n.date_published DESC, n.id DESC
    ");
    $news_list = $stmt->fetchAll();
    
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

<!-- News Header -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div class="card-title">Последние события</div>
        <?php if (checkRole(['admin', 'director'])): ?>
        <button class="btn btn-primary" onclick="alert('Функция добавления новости будет доступна в следующей версии')">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Добавить новость
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- News Grid -->
<?php if (empty($news_list)): ?>
<div class="empty-state">
    <div class="empty-state-icon">📰</div>
    <h3>Новостей пока нет</h3>
    <p>Информация о событиях предприятия появится здесь</p>
</div>
<?php else: ?>
<div class="grid-2" style="grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));">
    <?php foreach ($news_list as $news): ?>
    <div class="card">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                <span class="badge badge-blue">
                    <?= date('d.m.Y', strtotime($news['date_published'])) ?>
                </span>
            </div>
            
            <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: var(--text-primary);">
                <?= htmlspecialchars($news['title']) ?>
            </h3>
            
            <?php if ($news['content']): ?>
            <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 16px;">
                <?= nl2br(htmlspecialchars($news['content'])) ?>
            </p>
            <?php endif; ?>
            
            <div style="display: flex; align-items: center; gap: 8px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                <div class="user-avatar" style="width: 32px; height: 32px;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size: 13px; font-weight: 500; color: var(--text-primary);">
                        <?= htmlspecialchars($news['author_name'] ?? 'Администрация') ?>
                    </div>
                    <div style="font-size: 11px; color: var(--text-muted);">Автор</div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php include 'footer.php'; ?>
