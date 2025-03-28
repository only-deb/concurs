<?php
session_start();
require 'includes/db.php';
require 'nav.php';

$stmt = $pdo->query("
    SELECT 
        posts.*, 
        users.username, 
        users.avatar,
        (SELECT COUNT(*) FROM likes WHERE post_id = posts.id AND type = 'like') AS likes_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = posts.id AND type = 'dislike') AS dislikes_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) AS comments_count
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();
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

    <?php foreach ($posts as $post): ?>
        <div class="card mb-3" data-post-id="<?= $post['id'] ?>">
            <div class="card-header">
                <img src="<?= $post['avatar'] ?>" alt="Аватар" width="40" class="rounded-circle me-2">
                <?= htmlspecialchars($post['username']) ?>
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
$(document).ready(function() {
    $('.like-btn').on('click', function() {
        const card = $(this).closest('.card');
        const postId = card.data('post-id');

        $.ajax({
            url: 'like.php',
            method: 'POST',
            data: { post_id: postId, type: 'like' },
            success: function(response) {
                const data = JSON.parse(response);
                card.find('.likes-count').text(data.likes_count);
                card.find('.dislikes-count').text(data.dislikes_count);
            }
        });
    });

    $('.dislike-btn').on('click', function() {
        const card = $(this).closest('.card');
        const postId = card.data('post-id');

        $.ajax({
            url: 'like.php',
            method: 'POST',
            data: { post_id: postId, type: 'dislike' },
            success: function(response) {
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