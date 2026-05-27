<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit review']);
    exit();
}

$property_id = $_POST['property_id'];
$user_id = $_SESSION['user_id'];
$rating = (int)$_POST['rating'];
$comment = sanitize($_POST['comment']);

if($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit();
}

// Check if user has already reviewed
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND property_id = ?");
$stmt->execute([$user_id, $property_id]);
if($stmt->rowCount() > 0) {
    // Update existing review
    $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE user_id = ? AND property_id = ?");
    $success = $stmt->execute([$rating, $comment, $user_id, $property_id]);
} else {
    // Insert new review
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, property_id, rating, comment) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([$user_id, $property_id, $rating, $comment]);
}

if($success) {
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
}
?>