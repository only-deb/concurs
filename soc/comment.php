<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$post_id = $_GET['post_id'];

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT comments.*, users.username, users.avatar 
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    WHERE comments.post_id = ?
    ORDER BY comments.created_at ASC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = $_POST['content'];
    $image = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = 'uploads/';
        $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
        $imagePath = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            $image = $imagePath;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$post_id, $_SESSION['user_id'], $content, $image]);

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

    <form method="POST" enctype="multipart/form-data" class="mb-4">
        <div class="mb-3">
            <label for="content" class="form-label">Текст комментария</label>
            <textarea class="form-control" id="content" name="content" rows="4"></textarea>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Фото</label>
            <input type="file" class="form-control" id="image" name="image">
        </div>
        <button type="submit" class="btn btn-primary">Отправить</button>
    </form>

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
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>