<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT p.*, c.name as county_name, t.name as town_name,
                       (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
                       f.created_at as favorited_at
                       FROM favorites f
                       JOIN properties p ON f.property_id = p.id
                       LEFT JOIN counties c ON p.county_id = c.id
                       LEFT JOIN towns t ON p.town_id = t.id
                       WHERE f.user_id = ?
                       ORDER BY f.created_at DESC");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();

$page_title = 'My Favorites';
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
                    <h4 class="mb-0"><i class="fas fa-heart text-danger"></i> My Favorites</h4>
                </div>
                <div class="card-body">
                    <?php if(count($favorites) > 0): ?>
                        <div class="row g-4">
                            <?php foreach($favorites as $property): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="property-card h-100">
                                    <div class="card-img-wrapper position-relative" style="height: 200px;">
                                        <img src="<?php echo $property['primary_image'] ? SITE_URL . $property['primary_image'] : SITE_URL . 'assets/images/placeholder.jpg'; ?>" 
                                             alt="<?php echo $property['title']; ?>">
                                        <button class="btn btn-danger btn-sm remove-favorite position-absolute top-0 end-0 m-2" 
                                                data-id="<?php echo $property['id']; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-2"><?php echo htmlspecialchars(substr($property['title'], 0, 40)); ?></h6>
                                        <p class="card-text text-muted small mb-2">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo $property['town_name'] . ', ' . $property['county_name']; ?>
                                        </p>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> beds</span>
                                            <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> baths</span>
                                        </div>
                                        <h6 class="text-primary mb-2">
                                            KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?>
                                        </h6>
                                        <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary w-100 btn-sm">
                                            View Property
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="far fa-heart fa-4x text-muted mb-3"></i>
                            <h4>No Favorites Yet</h4>
                            <p class="text-muted">Start saving properties you like to see them here.</p>
                            <a href="properties.php" class="btn btn-primary">Browse Properties</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.remove-favorite').click(function() {
        var property_id = $(this).data('id');
        var btn = $(this);
        
        $.ajax({
            url: 'ajax/save-property.php',
            method: 'POST',
            data: {property_id: property_id},
            success: function(response) {
                var data = JSON.parse(response);
                if(data.success) {
                    Swal.fire('Removed!', 'Property removed from favorites', 'success');
                    btn.closest('.col-md-6').fadeOut();
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>