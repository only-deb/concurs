<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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

    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $content, $image]);

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать пост</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Создать пост</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="content" class="form-label">Текст поста</label>
            <textarea class="form-control" id="content" name="content" rows="4"></textarea>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Фото</label>
            <input type="file" class="form-control" id="image" name="image">
        </div>
        <button type="submit" class="btn btn-primary">Опубликовать</button>
    </form>
</div>
</body>
</html>