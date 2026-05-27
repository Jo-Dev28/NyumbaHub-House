<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit();
}

$payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;

if($payment_id) {
    $stmt = $pdo->prepare("SELECT p.*, pr.title as property_title, u.full_name as payer_name, u.email as payer_email
                           FROM payments p
                           LEFT JOIN properties pr ON p.property_id = pr.id
                           LEFT JOIN users u ON p.user_id = u.id
                           WHERE p.id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();
    
    if($payment) {
        $method_icons = [
            'mpesa' => 'M-Pesa',
            'paypal' => 'PayPal',
            'card' => 'Credit/Debit Card'
        ];
        
        echo json_encode([
            'success' => true,
            'receipt_no' => 'INV-' . str_pad($payment['id'], 6, '0', STR_PAD_LEFT),
            'transaction_id' => '#' . str_pad($payment['id'], 6, '0', STR_PAD_LEFT),
            'date' => date('F d, Y H:i', strtotime($payment['created_at'])),
            'status' => ucfirst($payment['status']),
            'method' => $method_icons[$payment['payment_method']] ?? ucfirst($payment['payment_method']),
            'property_title' => $payment['property_title'] ?? 'N/A',
            'package' => ucfirst($payment['package_type']),
            'amount' => 'KES ' . number_format($payment['amount']),
            'payer_name' => $payment['payer_name'] ?? 'Guest',
            'payer_email' => $payment['payer_email'] ?? 'N/A'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
}
?>