<?php
require_once 'includes/config.php';

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get property details with owner info
$stmt = $pdo->prepare("SELECT p.*, u.full_name as owner_name, u.phone as owner_phone, 
                       u.email as owner_email, u.profile_image as owner_image, 
                       u.verification_badge as owner_verified,
                       c.name as county_name, t.name as town_name
                       FROM properties p 
                       LEFT JOIN users u ON p.owner_id = u.id
                       LEFT JOIN counties c ON p.county_id = c.id
                       LEFT JOIN towns t ON p.town_id = t.id
                       WHERE p.id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if(!$property) {
    redirect('index.php');
}

// Get payment info for this property
$stmt = $pdo->prepare("SELECT * FROM payments WHERE property_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$property_id]);
$payment = $stmt->fetch();

// Update view count
if($property['status'] == 'approved') {
    $stmt = $pdo->prepare("UPDATE properties SET views_count = views_count + 1 WHERE id = ?");
    $stmt->execute([$property_id]);
}

// Get property images
$stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->execute([$property_id]);
$images = $stmt->fetchAll();

// Get property video
$stmt = $pdo->prepare("SELECT * FROM property_videos WHERE property_id = ? LIMIT 1");
$stmt->execute([$property_id]);
$video = $stmt->fetch();

// Get reviews
$show_reviews = ($property['status'] == 'approved' || (isLoggedIn() && $property['owner_id'] == $_SESSION['user_id']));
if($show_reviews) {
    $stmt = $pdo->prepare("SELECT r.*, u.full_name, u.profile_image 
                           FROM reviews r 
                           JOIN users u ON r.user_id = u.id 
                           WHERE r.property_id = ? 
                           ORDER BY r.created_at DESC");
    $stmt->execute([$property_id]);
    $reviews = $stmt->fetchAll();
} else {
    $reviews = [];
}

// Calculate average rating
$avg_rating = 0;
if(count($reviews) > 0) {
    $total = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($total / count($reviews), 1);
}

// Get similar properties
$stmt = $pdo->prepare("SELECT p.*, c.name as county_name, t.name as town_name,
                       (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                       FROM properties p
                       LEFT JOIN counties c ON p.county_id = c.id
                       LEFT JOIN towns t ON p.town_id = t.id
                       WHERE p.status = 'approved' AND p.id != ? 
                       AND (p.county_id = ? OR p.property_type = ?)
                       LIMIT 4");
$stmt->execute([$property_id, $property['county_id'], $property['property_type']]);
$similar_properties = $stmt->fetchAll();

// Check if property is favorited
$is_favorite = false;
if(isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND property_id = ?");
    $stmt->execute([$_SESSION['user_id'], $property_id]);
    $is_favorite = $stmt->rowCount() > 0;
}

$page_title = $property['title'];
require_once 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

<style>
    :root {
        --primary: #0d6efd;
        --primary-dark: #0b5ed7;
        --gray-light: #f8f9fa;
        --gray-border: #e9ecef;
    }
    
    .property-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        padding: 50px 0 35px;
        margin-top: -20px;
    }
    
    .property-gallery {
        margin-top: -35px;
    }
    
    .gallery-main {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .gallery-main img {
        width: 100%;
        height: 420px;
        object-fit: cover;
    }
    
    .gallery-thumb {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 12px;
        margin-top: 15px;
    }
    
    .gallery-thumb img {
        width: 100%;
        height: 85px;
        object-fit: cover;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid transparent;
    }
    
    .gallery-thumb img:hover {
        transform: scale(1.02);
        border-color: var(--primary);
    }
    
    .info-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }
    
    .info-card:hover {
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .info-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--gray-border);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .info-title i {
        color: var(--primary);
        font-size: 1.2rem;
    }
    
    .amenity-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--gray-light);
        padding: 5px 14px;
        border-radius: 25px;
        font-size: 13px;
        margin: 4px;
    }
    
    .price-tag {
        font-size: 1.8rem;
        font-weight: 700;
        color: white;
        margin-bottom: 5px;
    }
    
    .owner-card {
        text-align: center;
        padding: 20px;
        background: var(--gray-light);
        border-radius: 16px;
    }
    
    .owner-avatar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 12px;
        border: 3px solid white;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .rating-stars {
        color: #ffc107;
        font-size: 14px;
    }
    
    .review-item {
        border-bottom: 1px solid var(--gray-border);
        padding: 15px 0;
    }
    
    .review-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .similar-property-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.25s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        height: 100%;
    }
    
    .similar-property-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .similar-property-card img {
        height: 180px;
        width: 100%;
        object-fit: cover;
    }
    
    .contact-btn {
        background: linear-gradient(135deg, var(--primary), #0dcaf0);
        color: white;
        border: none;
        padding: 10px;
        border-radius: 10px;
        font-weight: 500;
        width: 100%;
        transition: all 0.2s ease;
    }
    
    .contact-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(13,110,253,0.3);
        color: white;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }
    
    .status-approved { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-payment { background: #cce5ff; color: #004085; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    
    .detail-card {
        background: var(--gray-light);
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        transition: all 0.2s ease;
    }
    
    .detail-card:hover {
        background: white;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }
    
    .detail-card i {
        font-size: 1.5rem;
        margin-bottom: 5px;
    }
    
    .detail-card h4 {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 5px 0;
    }
    
    .btn-favorite {
        background: transparent;
        border: 1.5px solid #dc3545;
        color: #dc3545;
        padding: 8px;
        border-radius: 10px;
        font-weight: 500;
        width: 100%;
        transition: all 0.2s ease;
    }
    
    .btn-favorite.active {
        background: #dc3545;
        color: white;
    }
    
    .btn-favorite:hover:not(.active) {
        background: #dc3545;
        color: white;
    }
    
    .social-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .social-icon:hover {
        transform: translateY(-3px);
    }
    
    @media (max-width: 768px) {
        .gallery-main img { height: 280px; }
        .gallery-thumb img { height: 60px; }
        .price-tag { font-size: 1.4rem; }
        .property-header { padding: 35px 0 25px; }
        .property-gallery { margin-top: -25px; }
        .detail-card h4 { font-size: 1.1rem; }
    }
</style>

<!-- Property Header -->
<div class="property-header">
    <div class="container">
        <div class="row align-items-end">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-2 p-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item"><a href="properties.php" class="text-white-50 text-decoration-none">Properties</a></li>
                        <li class="breadcrumb-item active text-white"><?php echo htmlspecialchars(substr($property['title'], 0, 60)); ?></li>
                    </ol>
                </nav>
                <h1 class="text-white fw-bold mb-2"><?php echo htmlspecialchars($property['title']); ?></h1>
                <div class="d-flex flex-wrap gap-3 text-white-50 small">
                    <span><i class="fas fa-map-marker-alt me-1"></i> <?php echo $property['town_name'] . ', ' . $property['county_name']; ?></span>
                    <?php if($property['estate']): ?>
                        <span><i class="fas fa-location-dot me-1"></i> <?php echo $property['estate']; ?></span>
                    <?php endif; ?>
                    <span><i class="fas fa-eye me-1"></i> <?php echo number_format($property['views_count']); ?> views</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <div class="price-tag">KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?></div>
                <?php if($property['category'] != 'sale'): ?>
                    <div class="text-white-50">per month</div>
                <?php endif; ?>
                <?php if($property['is_featured']): ?>
                    <span class="badge bg-warning text-dark mt-2"><i class="fas fa-star"></i> Featured Property</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <!-- Status Alert -->
    <?php if($property['status'] != 'approved' && isLoggedIn() && $property['owner_id'] == $_SESSION['user_id']): ?>
        <div class="alert alert-warning mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Property Status: <?php echo ucfirst(str_replace('_', ' ', $property['status'])); ?></strong>
                </div>
                <?php if($property['status'] == 'pending_payment'): ?>
                    <a href="payment.php?property_id=<?php echo $property['id']; ?>" class="btn btn-primary btn-sm">Complete Payment</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Image Gallery -->
            <div class="property-gallery mb-4">
                <div class="gallery-main">
                    <?php if(count($images) > 0): ?>
                        <a href="<?php echo SITE_URL . $images[0]['image_path']; ?>" data-lightbox="property-gallery">
                            <img src="<?php echo SITE_URL . $images[0]['image_path']; ?>" alt="Main Image" id="mainGalleryImage">
                        </a>
                    <?php else: ?>
                        <img src="<?php echo SITE_URL; ?>assets/images/placeholder-large.jpg" alt="No Image">
                    <?php endif; ?>
                </div>
                <?php if(count($images) > 1): ?>
                <div class="gallery-thumb">
                    <?php foreach($images as $index => $image): ?>
                        <img src="<?php echo SITE_URL . $image['image_path']; ?>" alt="Thumbnail <?php echo $index + 1; ?>" 
                             onclick="document.getElementById('mainGalleryImage').src = this.src; $('.gallery-thumb img').css('border-color', 'transparent'); $(this).css('border-color', '#0d6efd')">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Description -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-file-alt"></i> Description
                </div>
                <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
            </div>
            
            <!-- Property Details -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-info-circle"></i> Property Details
                </div>
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="detail-card">
                            <i class="fas fa-bed text-primary"></i>
                            <h4 class="mb-0"><?php echo $property['bedrooms']; ?></h4>
                            <small class="text-muted">Bedrooms</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-card">
                            <i class="fas fa-bath text-primary"></i>
                            <h4 class="mb-0"><?php echo $property['bathrooms']; ?></h4>
                            <small class="text-muted">Bathrooms</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-card">
                            <i class="fas fa-car text-primary"></i>
                            <h4 class="mb-0"><?php echo $property['parking_slots']; ?></h4>
                            <small class="text-muted">Parking</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-card">
                            <i class="fas fa-vector-square text-primary"></i>
                            <h4 class="mb-0"><?php echo $property['square_feet'] ? number_format($property['square_feet']) : 'N/A'; ?></h4>
                            <small class="text-muted">Sq Feet</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Amenities -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-wifi"></i> Amenities & Features
                </div>
                <div>
                    <?php
                    $amenities_list = [
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
                    foreach($amenities_list as $key => $label):
                        if($property[$key]): 
                            $hasAmenities = true;
                    ?>
                        <span class="amenity-badge"><i class="fas fa-check-circle text-success me-1"></i> <?php echo $label; ?></span>
                    <?php endif; endforeach; 
                    if(!$hasAmenities): ?>
                        <p class="text-muted text-center py-3 mb-0">No amenities listed</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <?php if($property['status'] == 'approved'): ?>
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-star"></i> Reviews & Ratings
                </div>
                
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="text-center">
                        <div class="display-4 fw-bold text-primary"><?php echo $avg_rating; ?></div>
                        <div class="rating-stars">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= $avg_rating ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <small class="text-muted"><?php echo count($reviews); ?> reviews</small>
                    </div>
                    <div>
                        <?php if(isLoggedIn()): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                <i class="fas fa-star"></i> Write a Review
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-primary">Login to Review</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if(count($reviews) > 0): ?>
                    <?php foreach($reviews as $review): ?>
                    <div class="review-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex gap-2">
                                <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($review['profile_image'] ?? 'default-avatar.png'); ?>" 
                                     class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($review['full_name']); ?></h6>
                                    <div class="rating-stars small">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                        </div>
                        <p class="text-muted mt-2 mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3 mb-0">No reviews yet. Be the first to review!</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Owner Contact Card -->
            <div class="info-card sticky-top" style="top: 100px;">
                <div class="owner-card">
                    <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($property['owner_image'] ?? 'default-avatar.png'); ?>" 
                         class="owner-avatar" alt="Owner">
                    <h5 class="mb-1"><?php echo htmlspecialchars($property['owner_name']); ?></h5>
                    <?php if($property['owner_verified']): ?>
                        <span class="badge bg-info mb-2"><i class="fas fa-check-circle"></i> Verified Owner</span>
                    <?php endif; ?>
                    
                    <?php if(isLoggedIn() && $property['status'] == 'approved'): ?>
                        <button class="contact-btn mb-2" id="revealPhoneBtn">
                            <i class="fas fa-phone"></i> Reveal Phone Number
                        </button>
                        <div id="ownerPhone" class="mt-2 text-center" style="display: none;">
                            <hr class="my-2">
                            <p class="h5 text-primary mb-0"><?php echo $property['owner_phone']; ?></p>
                            <small class="text-muted">Click to call</small>
                        </div>
                        <button class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#inquiryModal">
                            <i class="fas fa-envelope"></i> Send Message
                        </button>
                        <button class="btn btn-outline-success w-100 mb-2" id="chatBtn">
                            <i class="fas fa-comment-dots"></i> Chat with Owner
                        </button>
                        <button class="btn-favorite <?php echo $is_favorite ? 'active' : ''; ?>" id="savePropertyBtn" data-id="<?php echo $property['id']; ?>">
                            <i class="fas fa-heart"></i> <?php echo $is_favorite ? 'Saved to Favorites' : 'Save Property'; ?>
                        </button>
                    <?php elseif($property['status'] == 'approved'): ?>
                        <div class="alert alert-warning text-center mb-2">
                            <i class="fas fa-lock"></i> Login to contact owner
                        </div>
                        <a href="login.php" class="btn btn-primary w-100">Login to Contact</a>
                        <a href="register.php" class="btn btn-outline-primary w-100 mt-2">Create Account</a>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center mb-0">
                            <i class="fas fa-info-circle"></i> Property not yet available
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Property Information -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-chart-line"></i> Property Information
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Listing Status:</span>
                        <span class="status-badge <?php 
                            echo $property['status'] == 'approved' ? 'status-approved' : 
                                ($property['status'] == 'pending_approval' ? 'status-pending' : 
                                ($property['status'] == 'pending_payment' ? 'status-payment' : 'status-rejected')); 
                        ?>">
                            <?php echo $property['status'] == 'approved' ? 'Active' : ucfirst(str_replace('_', ' ', $property['status'])); ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Property ID:</span>
                        <span><code>#<?php echo str_pad($property['id'], 6, '0', STR_PAD_LEFT); ?></code></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Listing Package:</span>
                        <span class="badge bg-primary"><?php echo ucfirst($property['listing_package']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Listed Date:</span>
                        <span><?php echo date('M d, Y', strtotime($property['created_at'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Total Views:</span>
                        <span><i class="fas fa-eye"></i> <?php echo number_format($property['views_count']); ?></span>
                    </div>
                </div>
                
                <?php if($property['listing_expiry']): ?>
                <div class="mt-3 pt-2 border-top">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Listing Expiry:</span>
                        <span><?php echo date('M d, Y', strtotime($property['listing_expiry'])); ?></span>
                    </div>
                    <?php if(strtotime($property['listing_expiry']) > time()): ?>
                        <div class="progress" style="height: 4px;">
                            <?php
                            $total_days = (strtotime($property['listing_expiry']) - strtotime($property['created_at'])) / 86400;
                            $days_left = (strtotime($property['listing_expiry']) - time()) / 86400;
                            $percentage = ($days_left / $total_days) * 100;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <small class="text-success"><?php echo ceil($days_left); ?> days remaining</small>
                    <?php else: ?>
                        <small class="text-danger">Expired</small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Share Property -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-share-alt"></i> Share Property
                </div>
                <div class="d-flex justify-content-center gap-2">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . 'property-details.php?id=' . $property_id); ?>" 
                       target="_blank" class="btn btn-primary social-icon">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . 'property-details.php?id=' . $property_id); ?>&text=<?php echo urlencode($property['title']); ?>" 
                       target="_blank" class="btn btn-info social-icon">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://wa.me/?text=<?php echo urlencode($property['title'] . ' - ' . SITE_URL . 'property-details.php?id=' . $property_id); ?>" 
                       target="_blank" class="btn btn-success social-icon">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode($property['title']); ?>&body=Check out this property: <?php echo urlencode(SITE_URL . 'property-details.php?id=' . $property_id); ?>" 
                       class="btn btn-secondary social-icon">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Similar Properties -->
    <?php if(count($similar_properties) > 0 && $property['status'] == 'approved'): ?>
    <div class="mt-5">
        <h4 class="fw-bold mb-4">🏠 You May Also Like</h4>
        <div class="row g-4">
            <?php foreach($similar_properties as $similar): ?>
            <div class="col-md-6 col-lg-3">
                <div class="similar-property-card">
                    <img src="<?php echo !empty($similar['primary_image']) ? SITE_URL . $similar['primary_image'] : SITE_URL . 'assets/images/placeholder.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($similar['title']); ?>">
                    <div class="p-3">
                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars(substr($similar['title'], 0, 40)); ?></h6>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-map-marker-alt"></i> <?php echo $similar['town_name']; ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-primary">KES <?php echo number_format($similar['category'] == 'sale' ? $similar['price_sale'] : $similar['price_rent']); ?></strong>
                            <a href="property-details.php?id=<?php echo $similar['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Inquiry Modal -->
<div class="modal fade" id="inquiryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title"><i class="fas fa-envelope text-primary"></i> Send Message to Owner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <textarea class="form-control" name="message" rows="4" required 
                                  placeholder="Hello, I'm interested in this property. Is it still available? I would like to schedule a viewing."></textarea>
                    </div>
                    <button type="submit" name="send_inquiry" class="btn btn-primary w-100">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3">
            <div class="modal-header border-0">
                <h5 class="modal-title"><i class="fas fa-star text-primary"></i> Write a Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3 text-center">
                        <label class="form-label d-block">Your Rating</label>
                        <div class="rating-stars" id="ratingStars" style="font-size: 1.5rem;">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="ratingValue" required>
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" name="comment" rows="4" required 
                                  placeholder="Share your experience with this property..."></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-primary w-100">Submit Review</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Gallery thumbnail click
    $('.gallery-thumb img').click(function() {
        var newSrc = $(this).attr('src');
        $('#mainGalleryImage').attr('src', newSrc);
        $('.gallery-thumb img').css('border-color', 'transparent');
        $(this).css('border-color', '#0d6efd');
    });
    
    // Reveal phone number
    $('#revealPhoneBtn').click(function() {
        $.ajax({
            url: 'ajax/reveal-phone.php',
            method: 'POST',
            data: {property_id: <?php echo $property_id; ?>},
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    $('#ownerPhone').slideDown();
                    $('#revealPhoneBtn').hide();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }
        });
    });
    
    // Rating stars
    $('#ratingStars i').click(function() {
        var rating = $(this).data('rating');
        $('#ratingValue').val(rating);
        $('#ratingStars i').removeClass('fas').addClass('far');
        for(var i = 1; i <= rating; i++) {
            $('#ratingStars i[data-rating="' + i + '"]').removeClass('far').addClass('fas');
        }
    });
    
    // Chat button
    $('#chatBtn').click(function() {
        window.location.href = 'messages.php?property=<?php echo $property_id; ?>&user=<?php echo $property['owner_id']; ?>';
    });
    
    // Save property
    $('#savePropertyBtn').click(function() {
        var btn = $(this);
        $.ajax({
            url: 'ajax/save-property.php',
            method: 'POST',
            data: {property_id: <?php echo $property_id; ?>},
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: data.action === 'saved' ? 'Saved!' : 'Removed!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    if(data.action === 'saved') {
                        btn.html('<i class="fas fa-heart"></i> Saved to Favorites').addClass('active');
                    } else {
                        btn.html('<i class="fas fa-heart"></i> Save Property').removeClass('active');
                    }
                } else if(data.redirect) {
                    window.location.href = 'login.php';
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>