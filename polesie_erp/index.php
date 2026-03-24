<?php
/**
 * Страница авторизации
 * ОАО "Полесьеэлектромаш"
 */

require_once 'config.php';

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($login) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ? AND password = ?");
            $stmt->execute([$login, $password]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Успешная авторизация
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_department'] = $user['department'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Неверный логин или пароль';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация - ОАО "Полесьеэлектромаш"</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            padding: 48px 40px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 28px;
            font-weight: 700;
        }

        h1 {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 13px;
            color: #6b7280;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
            outline: none;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 24px;
        }

        .demo-credentials {
            margin-top: 32px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .demo-credentials h3 {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .credential-item {
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .credential-item strong {
            color: #1f2937;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo-icon">ПЭ</div>
            <h1>ОАО "Полесьеэлектромаш"</h1>
            <p class="subtitle">Корпоративная система управления</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="login">Логин</label>
                <input 
                    type="text" 
                    id="login" 
                    name="login" 
                    required 
                    autocomplete="username"
                    placeholder="Введите логин"
                    value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Введите пароль"
                >
            </div>

            <button type="submit" class="btn-submit">Войти в систему</button>
        </form>

        <div class="demo-credentials">
            <h3>Тестовые учетные записи</h3>
            <div class="credential-item">
                <strong>admin / admin</strong> — Администратор системы
            </div>
            <div class="credential-item">
                <strong>director / 12345</strong> — Директор предприятия
            </div>
            <div class="credential-item">
                <strong>manager1 / 12345</strong> — Менеджер по продажам
            </div>
            <div class="credential-item">
                <strong>engineer / 12345</strong> — Главный инженер
            </div>
            <div class="credential-item">
                <strong>warehouse1 / 12345</strong> — Кладовщик
            </div>
            <div class="credential-item">
                <strong>accountant / 12345</strong> — Бухгалтер
            </div>
        </div>

        <div class="footer">
            © 2024 ОАО "Полесьеэлектромаш". Все права защищены.
        </div>
    </div>
</body>
</html>
