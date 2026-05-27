<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$county_id = isset($_POST['county_id']) ? (int)$_POST['county_id'] : 0;

if($county_id > 0) {
    $stmt = $pdo->prepare("SELECT id, name FROM towns WHERE county_id = ? ORDER BY name");
    $stmt->execute([$county_id]);
    $towns = $stmt->fetchAll();
    
    echo json_encode($towns);
} else {
    echo json_encode([]);
}
?>