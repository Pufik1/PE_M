<?php
/**
 * Общий header для всех страниц
 * ОАО "Полесьеэлектромаш"
 */

if (!isset($page_title)) $page_title = 'ERP система';
if (!isset($active_page)) $active_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - ОАО "Полесьеэлектромаш"</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0e17;
            --bg-secondary: #111827;
            --bg-card: #1f2937;
            --bg-hover: #374151;
            --border-color: #374151;
            --border-light: #4b5563;
            --text-primary: #f9fafb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --primary: #3b82f6;
            --primary-light: #60a5fa;
            --primary-dark: #2563eb;
            --success: #10b981;
            --success-light: #34d399;
            --warning: #f59e0b;
            --warning-light: #fbbf24;
            --danger: #ef4444;
            --danger-light: #f87171;
            --info: #06b6d4;
            --purple: #8b5cf6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--bg-primary); 
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.5;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--bg-hover); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--border-light); }

        /* Sidebar */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 280px;
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border-right: 1px solid var(--border-color);
            padding: 24px 0; z-index: 100;
            display: flex; flex-direction: column;
        }
        .sidebar-header { 
            padding: 0 24px 24px; 
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 16px;
        }
        .logo { 
            display: flex; align-items: center; gap: 14px; 
            color: var(--text-primary); text-decoration: none; 
        }
        .logo-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 10px; 
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 15px; color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .logo-text { font-size: 13px; font-weight: 600; line-height: 1.4; letter-spacing: 0.3px; }
        
        .nav-menu { padding: 8px 0; flex: 1; }
        .nav-section {
            padding: 12px 24px 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 12px; 
            padding: 12px 24px;
            color: var(--text-secondary); 
            text-decoration: none; 
            transition: all 0.2s; 
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid transparent;
        }
        .nav-item:hover { 
            background: var(--bg-hover); 
            color: var(--text-primary); 
        }
        .nav-item.active { 
            background: rgba(59, 130, 246, 0.15); 
            color: var(--primary-light); 
            border-left-color: var(--primary);
        }
        .nav-icon {
            width: 20px; height: 20px;
            display: flex; align-items: center; justify-content: center;
            opacity: 0.8;
        }

        /* Main Content */
        .main-content { margin-left: 280px; min-height: 100vh; background: var(--bg-primary); }
        .top-bar {
            background: var(--bg-secondary); 
            border-bottom: 1px solid var(--border-color);
            padding: 20px 32px; 
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50;
        }
        .page-title { 
            font-size: 22px; 
            font-weight: 700; 
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }
        .user-menu { display: flex; align-items: center; gap: 16px; }
        .user-avatar {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--purple) 100%);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 14px; color: white;
        }
        .user-info { text-align: right; }
        .user-name { 
            font-size: 14px; 
            font-weight: 600; 
            color: var(--text-primary); 
        }
        .user-role { 
            font-size: 12px; 
            color: var(--text-muted); 
        }
        .btn-logout {
            padding: 8px 16px; 
            background: var(--bg-card); 
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px; 
            font-size: 13px; 
            font-weight: 500;
            cursor: pointer; 
            text-decoration: none; 
            transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-logout:hover { 
            background: var(--bg-hover);
            color: var(--text-primary);
            border-color: var(--text-muted);
        }

        /* Content */
        .content { padding: 32px; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s;
        }
        .stat-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }
        .stat-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 16px;
        }
        .stat-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: var(--primary-light); border: 1px solid rgba(59, 130, 246, 0.3); }
        .stat-icon.green { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.3); }
        .stat-icon.orange { background: rgba(245, 158, 11, 0.15); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.3); }
        .stat-icon.purple { background: rgba(139, 92, 246, 0.15); color: var(--purple); border: 1px solid rgba(139, 92, 246, 0.3); }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
            letter-spacing: -1px;
        }
        .stat-change {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Cards */
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .card-body { padding: 24px; }

        /* Tables */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--bg-card);
        }
        td { font-size: 14px; color: var(--text-secondary); }
        tr:hover td { background: var(--bg-hover); color: var(--text-primary); }
        tr:last-child td { border-bottom: none; }

        /* Buttons */
        .btn {
            padding: 10px 20px; 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white; 
            border: none;
            border-radius: 8px; 
            font-size: 14px; 
            font-weight: 600; 
            cursor: pointer;
            text-decoration: none; 
            transition: all 0.2s; 
            display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        .btn:hover { 
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        .btn-secondary { 
            background: var(--bg-card); 
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            box-shadow: none;
        }
        .btn-secondary:hover { 
            background: var(--bg-hover);
            color: var(--text-primary);
            border-color: var(--text-muted);
        }
        .btn-sm { padding: 6px 12px; font-size: 13px; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid;
        }
        .badge-blue { background: rgba(59, 130, 246, 0.15); color: var(--primary-light); border-color: rgba(59, 130, 246, 0.3); }
        .badge-green { background: rgba(16, 185, 129, 0.15); color: var(--success); border-color: rgba(16, 185, 129, 0.3); }
        .badge-yellow { background: rgba(245, 158, 11, 0.15); color: var(--warning); border-color: rgba(245, 158, 11, 0.3); }
        .badge-red { background: rgba(239, 68, 68, 0.15); color: var(--danger); border-color: rgba(239, 68, 68, 0.3); }
        .badge-purple { background: rgba(139, 92, 246, 0.15); color: var(--purple); border-color: rgba(139, 92, 246, 0.3); }

        /* Forms */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-input, .form-select {
            width: 100%;
            padding: 10px 14px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text-primary);
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .form-select option {
            background: var(--bg-card);
            color: var(--text-primary);
        }

        /* Filters */
        .filters {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex; gap: 16px; align-items: end; flex-wrap: wrap;
        }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }
        .empty-state-icon {
            font-size: 56px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Grid layouts */
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }

        @media (max-width: 1024px) {
            .grid-3, .grid-4 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .main-content { margin-left: 0; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">ПЭ</div>
                <div class="logo-text">ОАО<br>Полесьеэлектромаш</div>
            </a>
        </div>
        <nav class="nav-menu">
            <div class="nav-section">Основное</div>
            <a href="dashboard.php" class="nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Главная
            </a>
            <a href="products.php" class="nav-item <?= $active_page === 'products' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                Продукция
            </a>
            <a href="orders.php" class="nav-item <?= $active_page === 'orders' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Заказы
            </a>
            
            <div class="nav-section">Бизнес</div>
            <a href="partners.php" class="nav-item <?= $active_page === 'partners' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Контрагенты
            </a>
            <a href="production.php" class="nav-item <?= $active_page === 'production' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Производство
            </a>
            <a href="warehouse.php" class="nav-item <?= $active_page === 'warehouse' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                Склад
            </a>
            <a href="materials.php" class="nav-item <?= $active_page === 'materials' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Материалы
            </a>
            
            <?php if (checkRole(['admin', 'director'])): ?>
            <div class="nav-section">Администрирование</div>
            <a href="users.php" class="nav-item <?= $active_page === 'users' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Сотрудники
            </a>
            <?php endif; ?>
            <a href="reports.php" class="nav-item <?= $active_page === 'reports' ? 'active' : '' ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v10a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Отчеты
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>
            <div class="user-menu">
                <div class="user-avatar"><?= mb_substr(getUserName(), 0, 1) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars(getUserName()) ?></div>
                    <div class="user-role"><?= getUserRole() ?></div>
                </div>
                <a href="logout.php" class="btn-logout">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Выход
                </a>
            </div>
        </header>
        <div class="content">
