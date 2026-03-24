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
        <button class="btn btn-primary" onclick="openAddNewsModal()">
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
                <?php if (checkRole(['admin', 'director'])): ?>
                <button onclick="deleteNews(<?= $news['id'] ?>)" 
                        style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                        title="Удалить"
                        onmouseover="this.style.background='rgba(239, 68, 68, 0.2)'; this.style.transform='translateY(-1px)';"
                        onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.transform='translateY(0)';">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
                <?php endif; ?>
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

<!-- Add News Modal -->
<div id="addNewsModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeAddNewsModal()"></div>
    <div class="modal-content" style="max-width: 600px;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Добавить новость</div>
                <button class="btn-close" onclick="closeAddNewsModal()">×</button>
            </div>
            <div class="card-body">
                <form id="addNewsForm" onsubmit="submitNews(event)">
                    <div class="form-group">
                        <label class="form-label">Заголовок *</label>
                        <input type="text" name="title" class="form-control" required minlength="5" maxlength="200" placeholder="Введите заголовок новости">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Дата публикации</label>
                        <input type="date" name="date_published" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Содержание</label>
                        <textarea name="content" class="form-control" rows="6" placeholder="Введите текст новости"></textarea>
                    </div>
                    
                    <div class="form-actions" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                        <button type="button" class="btn btn-secondary" onclick="closeAddNewsModal()">Отмена</button>
                        <button type="submit" class="btn btn-primary">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Опубликовать
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openAddNewsModal() {
    document.getElementById('addNewsModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeAddNewsModal() {
    document.getElementById('addNewsModal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('addNewsForm').reset();
}

function submitNews(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('api/create_news.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeAddNewsModal();
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка сети: ' + error);
    });
}

// Закрытие модального окна по ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddNewsModal();
    }
});

// Удаление новости
function deleteNews(id) {
    if (!confirm('Вы уверены, что хотите удалить эту новость?')) {
        return;
    }
    
    fetch('api/delete_news.php?id=' + id, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка сети: ' + error);
    });
}
</script>

<?php include 'footer.php'; ?>
