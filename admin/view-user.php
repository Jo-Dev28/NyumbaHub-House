<?php
ob_start();
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($user_id == 0) {
    header("Location: users.php");
    exit();
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if(!$user) {
    header("Location: users.php");
    exit();
}

// Get user properties
$stmt = $pdo->prepare("SELECT p.*, 
                       (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                       FROM properties p 
                       WHERE p.owner_id = ? 
                       ORDER BY p.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$properties = $stmt->fetchAll();

// Get user payments
$stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$payments = $stmt->fetchAll();

// Get user messages
$stmt = $pdo->prepare("SELECT COUNT(*) as sent FROM messages WHERE sender_id = ?");
$stmt->execute([$user_id]);
$messages_sent = $stmt->fetch()['sent'];

$stmt = $pdo->prepare("SELECT COUNT(*) as received FROM messages WHERE receiver_id = ?");
$stmt->execute([$user_id]);
$messages_received = $stmt->fetch()['received'];

$page_title = 'View User - ' . htmlspecialchars($user['full_name']);
?>

<style>
    .info-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }
    .stat-card {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 1rem;
        text-align: center;
    }
    .role-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .role-user { background: #e7f3ff; color: #0d6efd; }
    .role-admin { background: #d4edda; color: #155724; }
    .role-super { background: #f8d7da; color: #721c24; }
    .badge-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
    }
    .badge-active { background: #d4edda; color: #155724; }
    .badge-suspended { background: #f8d7da; color: #721c24; }
    .badge-approved { background: #d4edda; color: #155724; }
    .badge-pending { background: #fff3cd; color: #856404; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-user-circle text-primary"></i> User Details</h4>
    <div>
        <a href="users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        <a href="edit-user.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit User
        </a>
    </div>
</div>

<div class="row">
    <!-- User Information -->
    <div class="col-md-4">
        <div class="info-card text-center">
            <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($user['profile_image'] ?? 'default-avatar.png'); ?>" 
                 class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover; border: 3px solid #0d6efd;">
            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
            <p class="text-muted">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
            </p>
            <p class="text-muted">
                <i class="fas fa-phone"></i> <?php echo $user['phone'] ?? 'Not provided'; ?>
            </p>
            <div class="mb-2">
                <span class="role-badge <?php 
                    echo $user['role'] == 'user' ? 'role-user' : ($user['role'] == 'admin' ? 'role-admin' : 'role-super'); 
                ?>">
                    <?php 
                        if($user['role'] == 'user') echo 'Property Seeker';
                        elseif($user['role'] == 'admin') echo 'Property Owner';
                        else echo 'Super Administrator';
                    ?>
                </span>
            </div>
            <div class="mt-3">
                <span class="badge-status <?php echo $user['status'] == 'active' ? 'badge-active' : 'badge-suspended'; ?>">
                    <i class="fas <?php echo $user['status'] == 'active' ? 'fa-check-circle' : 'fa-ban'; ?>"></i>
                    <?php echo ucfirst($user['status']); ?>
                </span>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-chart-line text-primary"></i> Statistics</h6>
            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-card">
                        <i class="fas fa-home fa-2x text-primary mb-2"></i>
                        <h5 class="mb-0"><?php echo count($properties); ?></h5>
                        <small class="text-muted">Properties</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <i class="fas fa-credit-card fa-2x text-success mb-2"></i>
                        <h5 class="mb-0"><?php echo count($payments); ?></h5>
                        <small class="text-muted">Payments</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <i class="fas fa-paper-plane fa-2x text-info mb-2"></i>
                        <h5 class="mb-0"><?php echo $messages_sent; ?></h5>
                        <small class="text-muted">Messages Sent</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <i class="fas fa-inbox fa-2x text-warning mb-2"></i>
                        <h5 class="mb-0"><?php echo $messages_received; ?></h5>
                        <small class="text-muted">Messages Received</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Account Info -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-info-circle text-primary"></i> Account Info</h6>
            <table class="table table-sm">
                <tr><td width="45%"><strong>Member Since:</strong></td><td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td></tr>
                <tr><td><strong>Last Login:</strong></td><td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td></tr>
                <tr><td><strong>Email Verified:</strong></td><td><?php echo ($user['email_verified'] ?? 0) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?></td></tr>
                <?php if($user['role'] == 'admin'): ?>
                <tr><td><strong>Subscription:</strong></td><td><?php echo ucfirst($user['subscription_plan'] ?? 'Bronze'); ?></td></tr>
                <tr><td><strong>Expiry:</strong></td><td><?php echo $user['subscription_expiry'] ? date('M d, Y', strtotime($user['subscription_expiry'])) : 'N/A'; ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Business Info (for admins) -->
        <?php if($user['role'] == 'admin' && ($user['company_name'] || $user['business_reg_no'])): ?>
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-building text-primary"></i> Business Information</h6>
            <table class="table table-sm">
                <?php if($user['company_name']): ?>
                <tr><td width="45%"><strong>Company:</strong></td><td><?php echo htmlspecialchars($user['company_name']); ?></td></tr>
                <?php endif; ?>
                <?php if($user['business_reg_no']): ?>
                <tr><td><strong>Reg Number:</strong></td><td><?php echo htmlspecialchars($user['business_reg_no']); ?></td></tr>
                <?php endif; ?>
                <?php if($user['experience_years']): ?>
                <tr><td><strong>Experience:</strong></td><td><?php echo $user['experience_years']; ?> years</td></tr>
                <?php endif; ?>
                <?php if($user['license_number']): ?>
                <tr><td><strong>License:</strong></td><td><?php echo htmlspecialchars($user['license_number']); ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <!-- Properties -->
        <div class="info-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="fas fa-home text-primary"></i> Properties Listed</h6>
                <a href="properties.php?user=<?php echo $user_id; ?>" class="btn btn-sm btn-link">View All</a>
            </div>
            <?php if(count($properties) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Image</th><th>Title</th><th>Price</th><th>Status</th><th>Views</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($properties as $prop): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo !empty($prop['primary_image']) ? SITE_URL . $prop['primary_image'] : SITE_URL . 'assets/images/placeholder.jpg'; ?>" 
                                         width="50" height="50" style="object-fit: cover; border-radius: 8px;">
                                </td>
                                <td><?php echo htmlspecialchars(substr($prop['title'], 0, 35)); ?></td>
                                <td>KES <?php echo number_format($prop['category'] == 'sale' ? $prop['price_sale'] : $prop['price_rent']); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $prop['status'] == 'approved' ? 'badge-approved' : 'badge-pending'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $prop['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($prop['views_count']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($prop['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-3">No properties listed by this user</p>
            <?php endif; ?>
        </div>
        
        <!-- Payments -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-credit-card text-primary"></i> Recent Payments</h6>
            <?php if(count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Date</th><th>Amount</th><th>Package</th><th>Method</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($payments as $payment): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                <td>KES <?php echo number_format($payment['amount']); ?></td>
                                <td><?php echo ucfirst($payment['package_type']); ?></td>
                                <td><?php echo strtoupper($payment['payment_method']); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $payment['status'] == 'completed' ? 'badge-approved' : 'badge-pending'; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-3">No payment history</p>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-cog text-primary"></i> Actions</h6>
            <div class="d-flex gap-2 flex-wrap">
                <?php if($user['status'] == 'active' && $user['role'] != 'super_admin'): ?>
                    <a href="users.php?action=suspend&id=<?php echo $user_id; ?>" class="btn btn-warning" onclick="return confirm('Suspend this user?')">
                        <i class="fas fa-ban"></i> Suspend User
                    </a>
                <?php elseif($user['status'] == 'suspended' && $user['role'] != 'super_admin'): ?>
                    <a href="users.php?action=activate&id=<?php echo $user_id; ?>" class="btn btn-success" onclick="return confirm('Activate this user?')">
                        <i class="fas fa-check"></i> Activate User
                    </a>
                <?php endif; ?>
                <?php if($user['role'] != 'super_admin'): ?>
                    <a href="users.php?action=delete&id=<?php echo $user_id; ?>" class="btn btn-danger" onclick="return confirm('Delete this user? This action cannot be undone!')">
                        <i class="fas fa-trash"></i> Delete User
                    </a>
                <?php endif; ?>
                <a href="edit-user.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit User
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>