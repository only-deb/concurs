<?php
session_start();
require 'includes/db.php';
require 'nav.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$post_id = $_GET['post_id'];

// Получение поста
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

// Получение всех комментариев к посту
$stmt = $pdo->prepare("
    SELECT comments.*, users.username, users.avatar 
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    WHERE comments.post_id = ? AND comments.parent_id IS NULL
    ORDER BY comments.created_at ASC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = $_POST['content'];
    $image = '';
    $parent_id = $_POST['parent_id'] ?? null;

    // Обработка загрузки изображения
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = 'uploads/';
        $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
        $imagePath = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            $image = $imagePath;
        }
    }

    // Добавление комментария в базу данных
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, image, parent_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$post_id, $_SESSION['user_id'], $content, $image, $parent_id]);

    header("Location: comment.php?post_id=$post_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Комментарии</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Пост: <?= htmlspecialchars($post['content']) ?></h2>

    <!-- Форма для добавления комментария -->
    <form method="POST" enctype="multipart/form-data" class="mb-4">
        <div class="mb-3">
            <label for="content" class="form-label">Текст комментария</label>
            <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Фото</label>
            <input type="file" class="form-control" id="image" name="image">
        </div>
        <button type="submit" class="btn btn-primary">Отправить</button>
    </form>

    <!-- Список комментариев -->
    <h3>Комментарии:</h3>
    <?php foreach ($comments as $comment): ?>
        <div class="card mb-3">
            <div class="card-header">
                <img src="<?= $comment['avatar'] ?>" alt="Аватар" width="40" class="rounded-circle me-2">
                <?= htmlspecialchars($comment['username']) ?>
            </div>
            <div class="card-body">
                <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                <?php if ($comment['image']): ?>
                    <img src="<?= $comment['image'] ?>" alt="Фото комментария" class="img-fluid">
                <?php endif; ?>
                <!-- Кнопка "Ответить" -->
                <a href="#reply-form" class="btn btn-sm btn-outline-primary reply-btn" data-comment-id="<?= $comment['id'] ?>">Ответить</a>
            </div>
        </div>

        <!-- Вложенные комментарии -->
        <?php
        $stmt = $pdo->prepare("
            SELECT comments.*, users.username, users.avatar 
            FROM comments 
            JOIN users ON comments.user_id = users.id 
            WHERE comments.parent_id = ?
            ORDER BY comments.created_at ASC
        ");
        $stmt->execute([$comment['id']]);
        $replies = $stmt->fetchAll();
        ?>
        <?php foreach ($replies as $reply): ?>
            <div class="card mb-3 ms-5">
                <div class="card-header">
                    <img src="<?= $reply['avatar'] ?>" alt="Аватар" width="40" class="rounded-circle me-2">
                    <?= htmlspecialchars($reply['username']) ?>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                    <?php if ($reply['image']): ?>
                        <img src="<?= $reply['image'] ?>" alt="Фото комментария" class="img-fluid">
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <!-- Форма для ответа -->
    <form id="reply-form" method="POST" enctype="multipart/form-data" class="mt-4 d-none">
        <input type="hidden" name="parent_id" id="parent_id">
        <div class="mb-3">
            <label for="reply-content" class="form-label">Текст ответа</label>
            <textarea class="form-control" id="reply-content" name="content" rows="4" required></textarea>
        </div>
        <div class="mb-3">
            <label for="reply-image" class="form-label">Фото</label>
            <input type="file" class="form-control" id="reply-image" name="image">
        </div>
        <button type="submit" class="btn btn-primary">Отправить ответ</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const replyForm = document.getElementById('reply-form');
    const replyParentId = document.getElementById('parent_id');

    // Обработка клика на кнопку "Ответить"
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function () {
            const commentId = this.getAttribute('data-comment-id');
            replyParentId.value = commentId;
            replyForm.classList.remove('d-none');
        });
    });
});
</script>
</body>
</html>