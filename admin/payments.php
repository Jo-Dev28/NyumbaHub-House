<?php
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

// Handle payment actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $payment_id = (int)$_GET['id'];
    
    if($_GET['action'] == 'verify') {
        $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
        $stmt->execute([$payment_id]);
        $_SESSION['success'] = "Payment verified successfully";
    } elseif($_GET['action'] == 'refund') {
        $stmt = $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?");
        $stmt->execute([$payment_id]);
        $_SESSION['success'] = "Payment refunded";
    }
    redirect('payments.php');
}

// Get all payments
$stmt = $pdo->query("SELECT p.*, u.full_name as user_name, u.email as user_email, pr.title as property_title 
                     FROM payments p 
                     LEFT JOIN users u ON p.user_id = u.id 
                     LEFT JOIN properties pr ON p.property_id = pr.id 
                     ORDER BY p.created_at DESC");
$payments = $stmt->fetchAll();

$page_title = 'Payment Management';
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4">
        <h5 class="mb-0"><i class="fas fa-credit-card text-primary"></i> Payment Management</h5>
    </div>
    <div class="card-body">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Property</th>
                        <th>Package</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($payments as $payment): ?>
                    <tr>
                        <td>#<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($payment['user_name']); ?></strong><br>
                            <small><?php echo $payment['user_email']; ?></small>
                        </td>
                        <td><?php echo htmlspecialchars(substr($payment['property_title'] ?? 'N/A', 0, 30)); ?></td>
                        <td><span class="badge bg-primary"><?php echo ucfirst($payment['package_type']); ?></span></td>
                        <td><strong>KES <?php echo number_format($payment['amount']); ?></strong></td>
                        <td><?php echo strtoupper($payment['payment_method']); ?></td>
                        <td>
                            <span class="badge-status badge-<?php echo $payment['status'] == 'completed' ? 'approved' : ($payment['status'] == 'pending' ? 'pending' : 'rejected'); ?>">
                                <?php echo ucfirst($payment['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="../payment-receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-receipt"></i>
                                </a>
                                <?php if($payment['status'] == 'pending'): ?>
                                    <a href="?action=verify&id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="?action=refund&id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>