<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to view phone number']);
    exit();
}

$property_id = $_POST['property_id'];
$property = getPropertyById($property_id);

if($property) {
    // Log phone view
    $stmt = $pdo->prepare("INSERT INTO phone_views (property_id, user_id, ip_address) VALUES (?, ?, ?)");
    $stmt->execute([$property_id, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
    
    echo json_encode(['success' => true, 'phone' => $property['owner_phone']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Property not found']);
}
?>