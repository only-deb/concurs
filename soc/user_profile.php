<?php
session_start();
require 'includes/db.php';
require 'nav.php';

// Получение ID пользователя из GET-параметра
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    header("Location: index.php");
    exit;
}

// Получение информации о пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Пользователь не найден.");
}

// Получение постов пользователя
$stmt = $pdo->prepare("
    SELECT 
        posts.*, 
        (SELECT COUNT(*) FROM likes WHERE post_id = posts.id AND type = 'like') AS likes_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = posts.id AND type = 'dislike') AS dislikes_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) AS comments_count
    FROM posts 
    WHERE user_id = ?
    ORDER BY posts.created_at DESC
");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Профиль пользователя</h2>

    <!-- Информация о пользователе -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <img src="<?= $user['avatar'] ?: 'images/default_avatar.png' ?>" alt="Аватар" width="100" class="rounded-circle me-3">
                <div>
                    <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                    <p><strong>Город:</strong> <?= htmlspecialchars($user['city']) ?></p>
                    <p><strong>Вид деятельности:</strong> <?= htmlspecialchars($user['activity']) ?></p>
                    <p><strong>Компания:</strong> <?= htmlspecialchars($user['company']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Посты пользователя -->
    <h3>Посты пользователя</h3>
    <?php if (empty($posts)): ?>
        <p>У пользователя пока нет постов.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="card mb-3">
                <div class="card-header">
                    Опубликовано: <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    <?php if ($post['image']): ?>
                        <img src="<?= $post['image'] ?>" alt="Фото поста" class="img-fluid">
                    <?php endif; ?>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <div>
                        <span class="text-success">👍 <?= $post['likes_count'] ?></span>
                        <span class="text-danger ms-2">👎 <?= $post['dislikes_count'] ?></span>
                    </div>
                    <div>
                        <a href="comment.php?post_id=<?= $post['id'] ?>" class="btn btn-primary">
                            Комментарии (<?= $post['comments_count'] ?>)
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>