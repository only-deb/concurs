<?php
session_start();
require 'includes/db.php';
require 'nav.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $city = $_POST['city'];
    $avatar = $user['avatar'];

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $uploadDir = 'uploads/';
        $avatarName = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $avatarPath = $uploadDir . $avatarName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath)) {
            $avatar = $avatarPath;
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, city = ?, avatar = ? WHERE id = ?");
    $stmt->execute([$first_name, $last_name, $city, $avatar, $user_id]);

    header("Location: profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Мой профиль</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="first_name" class="form-label">Имя</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>">
        </div>
        <div class="mb-3">
            <label for="last_name" class="form-label">Фамилия</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>">
        </div>
        <div class="mb-3">
            <label for="city" class="form-label">Город</label>
            <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($user['city']) ?>">
        </div>
        <div class="mb-3">
            <label for="avatar" class="form-label">Аватар</label>
            <input type="file" class="form-control" id="avatar" name="avatar">
            <?php if ($user['avatar']): ?>
                <img src="<?= $user['avatar'] ?>" alt="Текущий аватар" width="100" class="mt-2">
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Сохранить</button>
    </form>
</div>
</body>
</html>