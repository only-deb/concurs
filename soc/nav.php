<?php
$settings = include 'includes/settings.php';
?>
<nav>
    <a href="index.php"><?php echo $settings['site_name']; ?></a>
        <a href="register.php">регистрация</a>
        <a href="login.php">авторизация</a>
        <a href="post.php">Создать пост</a>
        <a href="profile.php">Профиль</a>
</nav>