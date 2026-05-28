<?php
ob_start();
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($property_id == 0) {
    header("Location: properties.php");
    exit();
}

// Get property details
$stmt = $pdo->prepare("SELECT p.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone,
                       u.profile_image as owner_image, c.name as county_name, t.name as town_name
                       FROM properties p 
                       JOIN users u ON p.owner_id = u.id
                       LEFT JOIN counties c ON p.county_id = c.id
                       LEFT JOIN towns t ON p.town_id = t.id
                       WHERE p.id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if(!$property) {
    header("Location: properties.php");
    exit();
}

// Get property images
$stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->execute([$property_id]);
$images = $stmt->fetchAll();

// Get payment info
$stmt = $pdo->prepare("SELECT * FROM payments WHERE property_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$property_id]);
$payment = $stmt->fetch();

// Get inquiries
$stmt = $pdo->prepare("SELECT * FROM property_inquiries WHERE property_id = ? ORDER BY created_at DESC");
$stmt->execute([$property_id]);
$inquiries = $stmt->fetchAll();

$page_title = 'View Property - ' . htmlspecialchars($property['title']);
?>

<style>
    .info-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }
    .property-image {
        width: 100%;
        height: 300px;
        object-fit: cover;
        border-radius: 15px;
    }
    .thumbnail-img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .thumbnail-img:hover {
        transform: scale(1.05);
        opacity: 0.8;
    }
    .badge-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .badge-approved { background: #d4edda; color: #155724; }
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-rejected { background: #f8d7da; color: #721c24; }
    .badge-payment { background: #cce5ff; color: #004085; }
    .amenity-badge {
        display: inline-block;
        background: #f0f0f0;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        margin: 3px;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-building text-primary"></i> Property Details</h4>
    <div>
        <a href="properties.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Properties
        </a>
        <a href="../property-details.php?id=<?php echo $property_id; ?>" class="btn btn-info" target="_blank">
            <i class="fas fa-external-link-alt"></i> View on Site
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Property Images -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-images text-primary"></i> Property Images</h6>
            <?php if(count($images) > 0): ?>
                <div class="mb-3">
                    <img src="<?php echo SITE_URL . $images[0]['image_path']; ?>" class="property-image" id="mainImage" alt="Main Image">
                </div>
                <div class="row g-2">
                    <?php foreach($images as $index => $image): ?>
                    <div class="col-3">
                        <img src="<?php echo SITE_URL . $image['image_path']; ?>" 
                             class="thumbnail-img w-100" 
                             onclick="document.getElementById('mainImage').src = this.src"
                             alt="Thumbnail <?php echo $index + 1; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <img src="<?php echo SITE_URL; ?>assets/images/placeholder-large.jpg" class="property-image" alt="No Image">
            <?php endif; ?>
        </div>
        
        <!-- Property Description -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-file-alt text-primary"></i> Description</h6>
            <p class="text-muted"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
        </div>
        
        <!-- Property Details -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-info-circle text-primary"></i> Property Specifications</h6>
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="text-center p-2 bg-light rounded-3">
                        <i class="fas fa-bed text-primary"></i>
                        <strong><?php echo $property['bedrooms']; ?></strong>
                        <small class="d-block">Bedrooms</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-center p-2 bg-light rounded-3">
                        <i class="fas fa-bath text-primary"></i>
                        <strong><?php echo $property['bathrooms']; ?></strong>
                        <small class="d-block">Bathrooms</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-center p-2 bg-light rounded-3">
                        <i class="fas fa-car text-primary"></i>
                        <strong><?php echo $property['parking_slots']; ?></strong>
                        <small class="d-block">Parking</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-center p-2 bg-light rounded-3">
                        <i class="fas fa-vector-square text-primary"></i>
                        <strong><?php echo $property['square_feet'] ? number_format($property['square_feet']) : 'N/A'; ?></strong>
                        <small class="d-block">Sq Feet</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Amenities -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-wifi text-primary"></i> Amenities</h6>
            <?php
            $amenities = [
                'water_available' => '💧 Water',
                'electricity_available' => '⚡ Electricity',
                'wifi_available' => '📶 WiFi',
                'security_available' => '🛡️ Security',
                'cctv' => '📹 CCTV',
                'borehole' => '🚰 Borehole',
                'swimming_pool' => '🏊 Pool',
                'gym' => '💪 Gym',
                'backup_generator' => '🔌 Generator',
                'balcony' => '🏞️ Balcony',
                'furnished' => '🛋️ Furnished',
                'pets_allowed' => '🐾 Pets'
            ];
            $hasAmenities = false;
            foreach($amenities as $key => $label):
                if($property[$key]): 
                    $hasAmenities = true;
            ?>
                <span class="amenity-badge"><?php echo $label; ?></span>
            <?php endif; endforeach; 
            if(!$hasAmenities): ?>
                <p class="text-muted">No amenities listed</p>
            <?php endif; ?>
        </div>
        
        <!-- Inquiries -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-envelope text-primary"></i> Inquiries (<?php echo count($inquiries); ?>)</h6>
            <?php if(count($inquiries) > 0): ?>
                <?php foreach($inquiries as $inquiry): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong><?php echo htmlspecialchars($inquiry['name'] ?? 'Guest'); ?></strong>
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?></small>
                    </div>
                    <p class="small mb-1"><?php echo htmlspecialchars($inquiry['message']); ?></p>
                    <?php if($inquiry['email']): ?>
                        <small class="text-muted"><i class="fas fa-envelope"></i> <?php echo $inquiry['email']; ?></small>
                    <?php endif; ?>
                    <?php if($inquiry['phone']): ?>
                        <small class="text-muted ms-2"><i class="fas fa-phone"></i> <?php echo $inquiry['phone']; ?></small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-3">No inquiries for this property</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Status Card -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-chart-line text-primary"></i> Property Status</h6>
            <table class="table table-sm">
                <tr><td width="45%"><strong>Status:</strong></td>
                    <td>
                        <span class="badge-status <?php 
                            if($property['status'] == 'approved') echo 'badge-approved';
                            elseif($property['status'] == 'pending_approval') echo 'badge-pending';
                            elseif($property['status'] == 'pending_payment') echo 'badge-payment';
                            else echo 'badge-rejected';
                        ?>">
                            <?php echo str_replace('_', ' ', $property['status']); ?>
                        </span>
                    </td
                </tr>
                <tr><td><strong>Featured:</strong></td><td><?php echo $property['is_featured'] ? '<span class="badge bg-warning">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td></tr>
                <tr><td><strong>Package:</strong></td><td><?php echo ucfirst($property['listing_package']); ?></td></tr>
                <tr><td><strong>Views:</strong></td><td><?php echo number_format($property['views_count']); ?></td></tr>
                <tr><td><strong>Listed:</strong></td><td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td></tr>
                <?php if($property['listing_expiry']): ?>
                <tr><td><strong>Expires:</strong></td><td><?php echo date('M d, Y', strtotime($property['listing_expiry'])); ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Payment Info -->
        <?php if($payment): ?>
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-credit-card text-primary"></i> Payment Information</h6>
            <table class="table table-sm">
                <tr><td><strong>Amount:</strong></td><td>KES <?php echo number_format($payment['amount']); ?></td></tr>
                <tr><td><strong>Package:</strong></td><td><?php echo ucfirst($payment['package_type']); ?></td></tr>
                <tr><td><strong>Method:</strong></td><td><?php echo strtoupper($payment['payment_method']); ?></td></tr>
                <tr><td><strong>Status:</strong></td>
                    <td>
                        <span class="badge-status <?php echo $payment['status'] == 'completed' ? 'badge-approved' : 'badge-pending'; ?>">
                            <?php echo ucfirst($payment['status']); ?>
                        </span>
                    </td
                </tr>
                <tr><td><strong>Paid on:</strong></td><td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td></tr>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Owner Info -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-user-circle text-primary"></i> Owner Information</h6>
            <div class="text-center mb-3">
                <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($property['owner_image'] ?? 'default-avatar.png'); ?>" 
                     class="rounded-circle" width="80" height="80" style="object-fit: cover;">
                <h5 class="mt-2 mb-1"><?php echo htmlspecialchars($property['owner_name']); ?></h5>
                <p class="text-muted small">Property Owner</p>
            </div>
            <table class="table table-sm">
                <tr><td><i class="fas fa-envelope"></i></td><td><?php echo htmlspecialchars($property['owner_email']); ?></td></tr>
                <?php if($property['owner_phone']): ?>
                <tr><td><i class="fas fa-phone"></i></td><td><?php echo $property['owner_phone']; ?></td></tr>
                <?php endif; ?>
            </table>
            <div class="d-grid mt-3">
                <a href="edit-user.php?id=<?php echo $property['owner_id']; ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-user-edit"></i> View Owner Profile
                </a>
            </div>
        </div>
        
        <!-- Location Info -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-map-marker-alt text-primary"></i> Location</h6>
            <p class="mb-1"><strong>County:</strong> <?php echo $property['county_name']; ?></p>
            <p class="mb-1"><strong>Town:</strong> <?php echo $property['town_name']; ?></p>
            <?php if($property['estate']): ?>
            <p class="mb-1"><strong>Estate:</strong> <?php echo $property['estate']; ?></p>
            <?php endif; ?>
            <?php if($property['street']): ?>
            <p class="mb-1"><strong>Street:</strong> <?php echo $property['street']; ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <div class="info-card">
            <h6 class="mb-3"><i class="fas fa-cog text-primary"></i> Actions</h6>
            <div class="d-grid gap-2">
                <?php if($property['status'] == 'pending_approval'): ?>
                    <a href="properties.php?action=approve&id=<?php echo $property_id; ?>" class="btn btn-success" onclick="return confirm('Approve this property?')">
                        <i class="fas fa-check"></i> Approve Property
                    </a>
                    <a href="properties.php?action=reject&id=<?php echo $property_id; ?>" class="btn btn-danger" onclick="return confirm('Reject this property?')">
                        <i class="fas fa-times"></i> Reject Property
                    </a>
                <?php endif; ?>
                <?php if($property['status'] == 'approved'): ?>
                    <?php if($property['is_featured']): ?>
                        <a href="properties.php?action=unfeature&id=<?php echo $property_id; ?>" class="btn btn-warning" onclick="return confirm('Remove featured status?')">
                            <i class="fas fa-star"></i> Remove Featured
                        </a>
                    <?php else: ?>
                        <a href="properties.php?action=feature&id=<?php echo $property_id; ?>" class="btn btn-info" onclick="return confirm('Make this property featured?')">
                            <i class="far fa-star"></i> Make Featured
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="properties.php?action=delete&id=<?php echo $property_id; ?>" class="btn btn-danger" onclick="return confirm('Delete this property? This action cannot be undone!')">
                    <i class="fas fa-trash"></i> Delete Property
                </a>
                <a href="../property-details.php?id=<?php echo $property_id; ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View on Website
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>