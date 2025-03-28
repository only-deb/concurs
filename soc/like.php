<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$post_id = $_POST['post_id'];
$type = $_POST['type'];

$stmt = $pdo->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$post_id, $_SESSION['user_id']]);
$like = $stmt->fetch();

if ($like) {
    $stmt = $pdo->prepare("UPDATE likes SET type = ? WHERE id = ?");
    $stmt->execute([$type, $like['id']]);
} else {
    $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id, type) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $_SESSION['user_id'], $type]);
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM likes WHERE post_id = ? AND type = ?");
$stmt->execute([$post_id, 'like']);
$likes_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM likes WHERE post_id = ? AND type = ?");
$stmt->execute([$post_id, 'dislike']);
$dislikes_count = $stmt->fetchColumn();

echo json_encode([
    'likes_count' => $likes_count,
    'dislikes_count' => $dislikes_count
]);
exit;
?>