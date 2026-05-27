<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'redirect' => true]);
    exit();
}

$property_id = $_POST['property_id'];
$user_id = $_SESSION['user_id'];

// Check if already favorite
$stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND property_id = ?");
$stmt->execute([$user_id, $property_id]);

if($stmt->rowCount() > 0) {
    // Remove from favorites
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND property_id = ?");
    $stmt->execute([$user_id, $property_id]);
    echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
} else {
    // Add to favorites
    $stmt = $pdo->prepare("INSERT INTO favorites (user_id, property_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $property_id]);
    echo json_encode(['success' => true, 'message' => 'Added to favorites']);
}
?>