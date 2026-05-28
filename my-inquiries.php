<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT pi.*, p.title as property_title, p.id as property_id,
                       (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as property_image
                       FROM property_inquiries pi
                       JOIN properties p ON pi.property_id = p.id
                       WHERE pi.user_id = ?
                       ORDER BY pi.created_at DESC");
$stmt->execute([$user_id]);
$inquiries = $stmt->fetchAll();

$page_title = 'My Inquiries';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'includes/user-sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h4 class="mb-0"><i class="fas fa-envelope text-primary"></i> My Inquiries</h4>
                </div>
                <div class="card-body">
                    <?php if(count($inquiries) > 0): ?>
                        <?php foreach($inquiries as $inquiry): ?>
                        <div class="inquiry-item border-bottom pb-3 mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <img src="<?php echo !empty($inquiry['property_image']) ? SITE_URL . $inquiry['property_image'] : SITE_URL . 'assets/images/placeholder.jpg'; ?>" 
                                         class="img-fluid rounded-3" style="height: 80px; width: 100%; object-fit: cover;">
                                </div>
                                <div class="col-md-7">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($inquiry['property_title']); ?></h6>
                                    <p class="small text-muted mb-1"><?php echo htmlspecialchars(substr($inquiry['message'], 0, 100)); ?></p>
                                    <small class="text-muted">Sent: <?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?></small>
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <a href="property-details.php?id=<?php echo $inquiry['property_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View Property
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-envelope fa-4x text-muted mb-3"></i>
                            <h5>No Inquiries Yet</h5>
                            <p class="text-muted">You haven't sent any inquiries to property owners.</p>
                            <a href="properties.php" class="btn btn-primary">Browse Properties</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>