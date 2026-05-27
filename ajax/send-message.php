<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to send messages']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];
$message = sanitize($_POST['message']);
$property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : null;

if(empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit();
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, property_id, message) VALUES (?, ?, ?, ?)");
if($stmt->execute([$sender_id, $receiver_id, $property_id, $message])) {
    // Add notification
    addNotification($receiver_id, 'New Message', 'You have a new message from ' . $_SESSION['user_name']);
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
?>