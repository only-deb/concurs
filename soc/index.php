<?php
session_start();
require 'includes/db.php';
require 'nav.php';

$stmt = $pdo->query("
    SELECT 
        posts.*, 
        users.username, 
        users.avatar,
        users.first_name,
        (SELECT COUNT(*) FROM likes WHERE post_id = posts.id AND type = 'like') AS likes_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = posts.id AND type = 'dislike') AS dislikes_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) AS comments_count
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();

// Получение рекомендаций для текущего пользователя
$user_id = $_SESSION['user_id'] ?? null;
$recommended_posts = [];
if ($user_id) {
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username, users.avatar 
        FROM recommendations 
        JOIN posts ON recommendations.post_id = posts.id 
        JOIN users ON posts.user_id = users.id 
        WHERE recommendations.user_id = ?
        ORDER BY recommendations.score DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recommended_posts = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h2>Лента постов</h2>

        <!-- Рекомендации -->
        <?php if (!empty($recommended_posts)): ?>
            <div class="card mb-4">
                <div class="card-header">Рекомендуемые посты</div>
                <div class="card-body">
                    <?php foreach ($recommended_posts as $post): ?>
                        <div class="mb-3">
                            <strong><?= htmlspecialchars($post['username']) ?>:</strong>
                            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                            <?php if ($post['image']): ?>
                                <img src="<?= $post['image'] ?>" alt="Фото поста" class="img-fluid">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <div class="card mb-3" data-post-id="<?= $post['id'] ?>">
                <div class="card-header">
                    <img src="<?= $post['avatar'] ?>" alt="Аватар" width="40" class="rounded-circle me-2">
                    <a href="user_profile.php?user_id=<?= $post['user_id'] ?>">
                        <?= htmlspecialchars($post['first_name']) ?>
                    </a>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    <?php if ($post['image']): ?>
                        <img src="<?= $post['image'] ?>" alt="Фото поста" class="img-fluid">
                    <?php endif; ?>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div>
                        <span class="like-btn text-success" style="cursor: pointer;">
                            👍 <span class="likes-count"><?= $post['likes_count'] ?></span>
                        </span>
                        <span class="dislike-btn text-danger ms-3" style="cursor: pointer;">
                            👎 <span class="dislikes-count"><?= $post['dislikes_count'] ?></span>
                        </span>
                    </div>
                    <div>
                        <a href="comment.php?post_id=<?= $post['id'] ?>" class="btn btn-primary">
                            Комментарии (<?= $post['comments_count'] ?>)
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        $(document).ready(function () {
            $('.like-btn').on('click', function () {
                const card = $(this).closest('.card');
                const postId = card.data('post-id');

                $.ajax({
                    url: 'like.php',
                    method: 'POST',
                    data: { post_id: postId, type: 'like' },
                    success: function (response) {
                        const data = JSON.parse(response);
                        card.find('.likes-count').text(data.likes_count);
                        card.find('.dislikes-count').text(data.dislikes_count);
                    }
                });
            });

            $('.dislike-btn').on('click', function () {
                const card = $(this).closest('.card');
                const postId = card.data('post-id');

                $.ajax({
                    url: 'like.php',
                    method: 'POST',
                    data: { post_id: postId, type: 'dislike' },
                    success: function (response) {
                        const data = JSON.parse(response);
                        card.find('.likes-count').text(data.likes_count);
                        card.find('.dislikes-count').text(data.dislikes_count);
                    }
                });
            });
        });
    </script>
</body>

</html>