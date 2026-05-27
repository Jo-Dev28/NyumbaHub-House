<?php
require_once 'includes/config.php';

// Get verified agents (users with properties and verification badge)
$stmt = $pdo->query("SELECT u.*, COUNT(p.id) as property_count 
                     FROM users u 
                     LEFT JOIN properties p ON u.id = p.owner_id AND p.status = 'approved'
                     WHERE u.user_type = 'owner' AND u.verification_badge = 1
                     GROUP BY u.id 
                     HAVING property_count > 0
                     ORDER BY property_count DESC");
$agents = $stmt->fetchAll();

$page_title = 'Our Agents';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold">Meet Our <span class="text-primary">Verified Agents</span></h1>
        <p class="text-muted">Professional real estate agents ready to help you find your dream property</p>
    </div>
    
    <div class="row g-4">
        <?php foreach($agents as $agent): ?>
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3">
                <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($agent['profile_image'] ?? 'default-avatar.png'); ?>" 
                     class="rounded-circle mx-auto mb-3" width="120" height="120" style="object-fit: cover;">
                <h5 class="mb-1"><?php echo htmlspecialchars($agent['full_name']); ?></h5>
                <p class="text-muted small">
                    <i class="fas fa-check-circle text-success"></i> Verified Agent
                </p>
                <div class="mb-2">
                    <span class="badge bg-primary"><?php echo $agent['property_count']; ?> Properties</span>
                </div>
                <p class="small text-muted"><?php echo htmlspecialchars(substr($agent['bio'] ?? '', 0, 80)); ?></p>
                <div class="d-flex gap-2 justify-content-center">
                    <?php if($agent['facebook_link']): ?>
                        <a href="<?php echo $agent['facebook_link']; ?>" class="btn btn-sm btn-outline-primary rounded-circle">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>
                    <?php if($agent['twitter_link']): ?>
                        <a href="<?php echo $agent['twitter_link']; ?>" class="btn btn-sm btn-outline-info rounded-circle">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                    <?php if($agent['instagram_link']): ?>
                        <a href="<?php echo $agent['instagram_link']; ?>" class="btn btn-sm btn-outline-danger rounded-circle">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                    <a href="messages.php?user=<?php echo $agent['id']; ?>" class="btn btn-sm btn-primary rounded-circle">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if(count($agents) == 0): ?>
        <div class="col-12 text-center py-5">
            <i class="fas fa-users fa-4x text-muted mb-3"></i>
            <h4>No Agents Yet</h4>
            <p class="text-muted">Check back soon for verified agents</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>