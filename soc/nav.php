<?php
$settings = include 'includes/settings.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $settings['site_name']; ?></title>
    <!-- Подключение Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Дополнительные стили для навигации */
        nav {
            background-color: #0d6efd; /* Синий цвет фона */
            padding: 10px 20px;
        }
        nav a {
            color: white !important; /* Белый цвет текста */
            margin-right: 15px;
            text-decoration: none;
            font-weight: bold;
        }
        nav a:hover {
            color: #ffdd57 !important; /* Желтый цвет при наведении */
        }
        nav a.active {
            border-bottom: 2px solid white; /* Подчеркивание активной ссылки */
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <!-- Название сайта -->
        <a class="navbar-brand" href="index.php"><?php echo $settings['site_name']; ?></a>

        <!-- Ссылки навигации -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Главная</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="register.php">Регистрация</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Авторизация</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="post.php">Создать пост</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Профиль</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Основной контент страницы -->
<div class="container mt-4">
    <?php
    // Здесь можно разместить основной контент страницы
    ?>
</div>

<!-- Подключение Bootstrap JS (необязательно, если не используете интерактивные компоненты) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>