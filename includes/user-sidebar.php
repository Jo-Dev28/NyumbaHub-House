<?php
// Make sure user data is available
if(!isset($user) && isset($_SESSION['user_id'])) {
    $user = getUserById($_SESSION['user_id']);
}

if(!isset($user)) {
    $user = [
        'full_name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'profile_image' => 'default-avatar.png',
        'user_type' => 'owner',
        'subscription_plan' => 'bronze'
    ];
}
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body text-center p-4">
        <div class="position-relative d-inline-block mb-3">
            <img src="<?php echo SITE_URL . 'uploads/profiles/' . (isset($user['profile_image']) && $user['profile_image'] && $user['profile_image'] != 'default-avatar.png' ? $user['profile_image'] : 'default-avatar.png'); ?>" 
                 class="rounded-circle" width="100" height="100" style="object-fit: cover; border: 3px solid #0d6efd;">
            <a href="profile.php" class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-1" style="width: 30px; height: 30px;">
                <i class="fas fa-camera text-white" style="font-size: 12px;"></i>
            </a>
        </div>
        <h5 class="mb-1"><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></h5>
        <p class="text-muted small mb-2"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
        <div class="mb-3">
            <?php 
            $plan = $user['subscription_plan'] ?? 'bronze';
            $planClass = $plan == 'gold' ? 'warning' : ($plan == 'silver' ? 'secondary' : 'secondary');
            ?>
            <span class="badge bg-<?php echo $planClass; ?>">
                <i class="fas fa-crown"></i> <?php echo ucfirst($plan); ?> Plan
            </span>
        </div>
        <hr>
        <div class="d-grid gap-2">
            <a href="profile.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a>
            <a href="add-property.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus-circle"></i> Add Property
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="list-group list-group-flush">
        <a href="<?php echo SITE_URL; ?>dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a href="<?php echo SITE_URL; ?>my-properties.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'my-properties.php' ? 'active' : ''; ?>">
            <i class="fas fa-home me-2"></i> My Properties
            <?php
            if(isset($_SESSION['user_id'])) {
                $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) as count FROM properties WHERE owner_id = ? AND status = 'pending_approval'");
                $stmt->execute([$_SESSION['user_id']]);
                $pending = $stmt->fetch()['count'];
                if($pending > 0) {
                    echo '<span class="badge bg-warning float-end">' . $pending . '</span>';
                }
            }
            ?>
        </a>
        <a href="<?php echo SITE_URL; ?>favorites.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'favorites.php' ? 'active' : ''; ?>">
            <i class="fas fa-heart me-2"></i> Favorites
        </a>
        <a href="<?php echo SITE_URL; ?>messages.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope me-2"></i> Messages
            <?php
            if(isset($_SESSION['user_id'])) {
                $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
                $stmt->execute([$_SESSION['user_id']]);
                $unread = $stmt->fetch()['count'];
                if($unread > 0): ?>
                    <span class="badge bg-danger float-end"><?php echo $unread; ?></span>
                <?php endif;
            } ?>
        </a>
        <a href="<?php echo SITE_URL; ?>subscription.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'subscription.php' ? 'active' : ''; ?>">
            <i class="fas fa-crown me-2"></i> Subscription
        </a>
        <a href="<?php echo SITE_URL; ?>payments.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card me-2"></i> Payments History
        </a>
        <a href="<?php echo SITE_URL; ?>profile.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog me-2"></i> Settings
        </a>
        <a href="<?php echo SITE_URL; ?>logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </a>
    </div>
</div>

<style>
    .list-group-item.active {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        border-color: transparent;
        color: white;
    }
    .list-group-item.active i {
        color: white;
    }
    .list-group-item {
        border-radius: 10px !important;
        margin-bottom: 5px;
        transition: all 0.2s ease;
        border: none;
    }
    .list-group-item:hover:not(.active) {
        background: #f8f9fa;
    }
    .badge.bronze {
        background-color: #cd7f32;
    }
</style>