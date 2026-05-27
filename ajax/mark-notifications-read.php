<?php
session_start();
require_once '../../includes/config.php';

if(!isset($_SESSION['user_id'])) {
    exit();
}

$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

echo json_encode(['success' => true]);
?>