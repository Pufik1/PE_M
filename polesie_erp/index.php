<?php
session_start();

// Подключаем конфиг сразу для проверки БД
try {
    require_once 'config.php';
} catch (PDOException $e) {
    die("<div style='background:#ef4444;color:white;padding:20px;font-family:sans-serif;border-radius:8px;max-width:600px;margin:50px auto;'>
        <h2>❌ Ошибка подключения к базе данных</h2>
        <p><strong>Сообщение:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <hr style='border-color:rgba(255,255,255,0.3);margin:15px 0;'>
        <p><strong>Возможные причины:</strong></p>
        <ul>
            <li>База данных <code>polesie_erp</code> не создана</li>
            <li>Файл database.sql не импортирован в phpMyAdmin</li>
            <li>Неверный логин/пароль MySQL (в config.php: root/root)</li>
            <li>Сервер MySQL не запущен в MAMP</li>
        </ul>
        <p><strong>Решение:</strong></p>
        <ol>
            <li>Откройте MAMP и убедитесь, что серверы работают</li>
            <li>Зайдите в phpMyAdmin (http://localhost/phpMyAdmin)</li>
            <li>Создайте базу данных <code>polesie_erp</code> или импортируйте файл <code>database.sql</code></li>
        </ol>
    </div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['username'] ?? ''; // Имя поля в форме остается 'username'
    $password = $_POST['password'] ?? '';
    
    try {
        // В базе данных поле называется 'login', поэтому используем его в запросе
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ? AND password = ?");
        $stmt->execute([$login, $password]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Неверное имя пользователя или пароль";
        }
    } catch (PDOException $e) {
        $error = "Ошибка базы данных: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Полесьеэлектромаш | ERP Система</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0a0e17;
            --bg-secondary: #111827;
            --bg-card: #1f2937;
            --bg-hover: #374151;
            --text-primary: #f9fafb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --accent-primary: #3b82f6;
            --accent-secondary: #2563eb;
            --accent-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border: #374151;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -2px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(37, 99, 235, 0.08) 0%, transparent 50%);
        }

        .login-container {
            background: var(--bg-secondary);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid var(--border);
        }

        .login-left {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(135deg, rgba(31, 41, 55, 0.5) 0%, rgba(17, 24, 39, 0.8) 100%);
            position: relative;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .login-right {
            background: var(--bg-card);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .login-right::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .logo-section {
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: var(--accent-gradient);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }

        .logo-icon svg {
            width: 36px;
            height: 36px;
            fill: white;
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #f9fafb 0%, #d1d5db 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 400;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            background: var(--bg-secondary);
        }

        input[type="text"]::placeholder,
        input[type="password"]::placeholder {
            color: var(--text-muted);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--accent-gradient);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .info-section {
            position: relative;
            z-index: 1;
        }

        .info-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-primary);
        }

        .credentials-grid {
            display: grid;
            gap: 16px;
        }

        .credential-item {
            background: var(--bg-primary);
            padding: 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .credential-item:hover {
            border-color: var(--accent-primary);
            background: var(--bg-hover);
            transform: translateX(4px);
        }

        .credential-role {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .credential-role svg {
            width: 16px;
            height: 16px;
            fill: var(--accent-primary);
        }

        .credential-details {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .credential-code {
            font-family: 'Monaco', 'Consolas', monospace;
            background: var(--bg-card);
            padding: 4px 8px;
            border-radius: 6px;
            color: var(--accent-primary);
            font-size: 12px;
            border: 1px solid var(--border);
        }

        .footer-note {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-muted);
            text-align: center;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                margin: 20px;
            }

            .login-left,
            .login-right {
                padding: 40px 30px;
            }

            .login-right {
                order: -1;
                border-left: none;
                border-bottom: 1px solid var(--border);
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container fade-in">
        <div class="login-left">
            <div class="logo-section">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h1>Полесьеэлектромаш</h1>
                <p class="subtitle">Корпоративная система управления предприятием</p>
            </div>
            
            <div class="info-section">
                <h3 class="info-title">Тестовые учётные записи</h3>
                <div class="credentials-grid">
                    <div class="credential-item">
                        <div class="credential-role">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            Администратор системы
                        </div>
                        <div class="credential-details">
                            Логин: <span class="credential-code">admin</span><br>
                            Пароль: <span class="credential-code">admin</span>
                        </div>
                    </div>
                    
                    <div class="credential-item">
                        <div class="credential-role">
                            <svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
                            Менеджер по продажам
                        </div>
                        <div class="credential-details">
                            Логин: <span class="credential-code">manager1</span><br>
                            Пароль: <span class="credential-code">12345</span>
                        </div>
                    </div>
                    
                    <div class="credential-item">
                        <div class="credential-role">
                            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                            Главный инженер
                        </div>
                        <div class="credential-details">
                            Логин: <span class="credential-code">engineer1</span><br>
                            Пароль: <span class="credential-code">12345</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Имя пользователя</label>
                    <input type="text" id="username" name="username" required placeholder="Введите логин" autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required placeholder="Введите пароль" autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn-login">Войти в систему</button>
            </form>
            
            <div class="footer-note">
                © 2024 ОАО «Полесьеэлектромаш». Все права защищены.<br>
                Корпоративная ERP система v2.0
            </div>
        </div>
    </div>
</body>
</html>
