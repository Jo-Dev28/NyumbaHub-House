<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to send inquiry']);
    exit();
}

$property_id = $_POST['property_id'];
$user_id = $_SESSION['user_id'];
$message = sanitize($_POST['message']);

$property = getPropertyById($property_id);
if(!$property) {
    echo json_encode(['success' => false, 'message' => 'Property not found']);
    exit();
}

$stmt = $pdo->prepare("INSERT INTO property_inquiries (property_id, user_id, message) VALUES (?, ?, ?)");
if($stmt->execute([$property_id, $user_id, $message])) {
    // Send notification to owner
    addNotification($property['owner_id'], 'New Inquiry', 'Someone inquired about your property: ' . $property['title']);
    echo json_encode(['success' => true, 'message' => 'Inquiry sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send inquiry']);
}
?>