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

// Update view count (only for approved properties)
if($property['status'] == 'approved') {
    $stmt = $pdo->prepare("UPDATE properties SET views_count = views_count + 1 WHERE id = ?");
    $stmt->execute([$property_id]);
}

// Get property images - FIXED: removed image_order reference
$stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->execute([$property_id]);
$images = $stmt->fetchAll();

// Get property video
$stmt = $pdo->prepare("SELECT * FROM property_videos WHERE property_id = ? LIMIT 1");
$stmt->execute([$property_id]);
$video = $stmt->fetch();

// Get reviews (only show for approved properties or if logged in as owner)
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

// Get similar properties (only approved ones)
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
    .property-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 60px 0 40px;
        margin-top: -20px;
    }
    .property-gallery {
        margin-top: -40px;
    }
    .gallery-main {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .gallery-main img {
        width: 100%;
        height: 500px;
        object-fit: cover;
    }
    .gallery-thumb {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-top: 15px;
    }
    .gallery-thumb img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .gallery-thumb img:hover {
        opacity: 0.8;
        transform: scale(1.02);
    }
    .info-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }
    .info-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    .info-title i {
        color: #0d6efd;
        margin-right: 10px;
    }
    .amenity-badge {
        display: inline-block;
        background: #f0f0f0;
        padding: 8px 15px;
        border-radius: 30px;
        margin: 5px;
        font-size: 13px;
    }
    .amenity-badge i {
        margin-right: 5px;
        color: #28a745;
    }
    .price-tag {
        font-size: 2rem;
        font-weight: 700;
        color: #0d6efd;
    }
    .owner-card {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 15px;
    }
    .owner-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 10px;
    }
    .rating-stars {
        color: #ffc107;
        font-size: 1.2rem;
    }
    .review-item {
        border-bottom: 1px solid #e0e0e0;
        padding: 15px 0;
    }
    .review-item:last-child {
        border-bottom: none;
    }
    .similar-property-card {
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .similar-property-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .contact-btn {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
        border: none;
        padding: 12px;
        border-radius: 50px;
        font-weight: 600;
        width: 100%;
        margin-bottom: 10px;
    }
    .contact-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(13,110,253,0.3);
        color: white;
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }
    .status-approved { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-payment { background: #cce5ff; color: #004085; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-expired { background: #e2e3e5; color: #383d41; }
    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.2);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 50px;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    .back-button:hover {
        background: rgba(255,255,255,0.3);
        color: white;
        transform: translateX(-5px);
    }
    @media (max-width: 768px) {
        .gallery-main img { height: 300px; }
        .gallery-thumb img { height: 70px; }
        .price-tag { font-size: 1.5rem; }
    }
</style>

<!-- Property Header -->
<div class="property-header">
    <div class="container">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="my-properties.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <h1 class="text-white fw-bold mb-2"><?php echo htmlspecialchars($property['title']); ?></h1>
                <p class="text-white-50 mb-0">
                    <i class="fas fa-map-marker-alt"></i> <?php echo $property['town_name'] . ', ' . $property['county_name']; ?>
                    <?php if($property['estate']): ?> - <?php echo $property['estate']; ?><?php endif; ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <div class="price-tag text-white">KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?></div>
                <?php if($property['category'] != 'sale'): ?>
                    <small class="text-white-50">per month</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Status Alert for Non-Approved Properties -->
            <?php if($property['status'] != 'approved'): ?>
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-info-circle"></i>
                    <strong>Property Status:</strong> 
                    <?php
                    $status_messages = [
                        'pending_payment' => 'This property is awaiting payment. Complete payment to list your property.',
                        'pending_approval' => 'This property is pending admin approval. You will be notified once approved.',
                        'rejected' => 'This property has been rejected. Please check your email for details and resubmit.',
                        'expired' => 'This listing has expired. Please renew your subscription.'
                    ];
                    echo $status_messages[$property['status']] ?? 'This property is currently not available for public view.';
                    ?>
                    <?php if($property['status'] == 'pending_payment' && $property['owner_id'] == $_SESSION['user_id']): ?>
                        <a href="payment.php?property_id=<?php echo $property['id']; ?>" class="btn btn-sm btn-primary ms-3">
                            Complete Payment
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Image Gallery -->
            <div class="property-gallery mb-4">
                <div class="gallery-main">
                    <?php if(count($images) > 0): ?>
                        <a href="<?php echo SITE_URL . $images[0]['image_path']; ?>" data-lightbox="property-gallery">
                            <img src="<?php echo SITE_URL . $images[0]['image_path']; ?>" alt="Main Image">
                        </a>
                    <?php else: ?>
                        <img src="<?php echo SITE_URL; ?>assets/images/placeholder-large.jpg" alt="No Image">
                    <?php endif; ?>
                </div>
                <?php if(count($images) > 1): ?>
                <div class="gallery-thumb">
                    <?php for($i = 1; $i < min(4, count($images)); $i++): ?>
                        <a href="<?php echo SITE_URL . $images[$i]['image_path']; ?>" data-lightbox="property-gallery">
                            <img src="<?php echo SITE_URL . $images[$i]['image_path']; ?>" alt="Thumbnail">
                        </a>
                    <?php endfor; ?>
                    <?php if(count($images) > 4): ?>
                        <div class="position-relative">
                            <img src="<?php echo SITE_URL . $images[4]['image_path']; ?>" alt="Thumbnail">
                            <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center rounded-3">
                                <span class="text-white fw-bold">+<?php echo count($images) - 4; ?> more</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Description -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-file-alt"></i> Description
                </div>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
            </div>
            
            <!-- Property Details -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-info-circle"></i> Property Details
                </div>
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 bg-light rounded-3">
                            <i class="fas fa-bed fa-2x text-primary mb-2"></i>
                            <h5 class="mb-0"><?php echo $property['bedrooms']; ?></h5>
                            <small class="text-muted">Bedrooms</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 bg-light rounded-3">
                            <i class="fas fa-bath fa-2x text-primary mb-2"></i>
                            <h5 class="mb-0"><?php echo $property['bathrooms']; ?></h5>
                            <small class="text-muted">Bathrooms</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 bg-light rounded-3">
                            <i class="fas fa-car fa-2x text-primary mb-2"></i>
                            <h5 class="mb-0"><?php echo $property['parking_slots']; ?></h5>
                            <small class="text-muted">Parking</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 bg-light rounded-3">
                            <i class="fas fa-vector-square fa-2x text-primary mb-2"></i>
                            <h5 class="mb-0"><?php echo $property['square_feet'] ? number_format($property['square_feet']) : 'N/A'; ?></h5>
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
                        <span class="amenity-badge">
                            <i class="fas fa-check-circle"></i> <?php echo $label; ?>
                        </span>
                    <?php endif; endforeach; 
                    if(!$hasAmenities): ?>
                        <p class="text-muted mb-0">No amenities listed</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reviews - Only show for approved properties -->
            <?php if($show_reviews && count($reviews) >= 0): ?>
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-star"></i> Reviews & Ratings
                </div>
                <div class="d-flex align-items-center mb-4">
                    <div class="me-4 text-center">
                        <div class="display-4 fw-bold text-primary"><?php echo $avg_rating; ?></div>
                        <div class="rating-stars">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= $avg_rating ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <small class="text-muted"><?php echo count($reviews); ?> reviews</small>
                    </div>
                    <div>
                        <?php if(isLoggedIn() && $property['status'] == 'approved'): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                <i class="fas fa-star"></i> Write a Review
                            </button>
                        <?php elseif(!isLoggedIn()): ?>
                            <a href="login.php" class="btn btn-outline-primary">Login to Review</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if(count($reviews) > 0): ?>
                    <?php foreach($reviews as $review): ?>
                    <div class="review-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex">
                                <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($review['profile_image'] ?? 'default-avatar.png'); ?>" 
                                     class="rounded-circle me-3" width="40" height="40" alt="User">
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
                    <p class="text-muted text-center py-3">No reviews yet. Be the first to review!</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <!-- Status Card -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-chart-line"></i> Property Status
                </div>
                <div class="mb-3">
                    <strong>Listing Status:</strong><br>
                    <?php
                    $statusClass = '';
                    $statusText = '';
                    switch($property['status']) {
                        case 'approved':
                            $statusClass = 'status-approved';
                            $statusText = '✓ Approved - Property is Live';
                            break;
                        case 'pending_approval':
                            $statusClass = 'status-pending';
                            $statusText = '⏳ Pending Admin Review';
                            break;
                        case 'pending_payment':
                            $statusClass = 'status-payment';
                            $statusText = '💳 Payment Required';
                            break;
                        case 'rejected':
                            $statusClass = 'status-rejected';
                            $statusText = '✗ Rejected';
                            break;
                        case 'expired':
                            $statusClass = 'status-expired';
                            $statusText = '📅 Listing Expired';
                            break;
                        default:
                            $statusClass = 'status-pending';
                            $statusText = ucfirst(str_replace('_', ' ', $property['status']));
                    }
                    ?>
                    <span class="status-badge <?php echo $statusClass; ?> mt-1 d-inline-block"><?php echo $statusText; ?></span>
                </div>
                
                <?php if($payment): ?>
                <div class="mb-3">
                    <strong>Payment Status:</strong><br>
                    <?php
                    $paymentClass = $payment['status'] == 'completed' ? 'status-approved' : 'status-pending';
                    $paymentText = $payment['status'] == 'completed' ? '✓ Payment Completed' : '⏳ Payment Pending';
                    ?>
                    <span class="status-badge <?php echo $paymentClass; ?> mt-1 d-inline-block"><?php echo $paymentText; ?></span>
                    <?php if($payment['status'] == 'completed'): ?>
                        <small class="text-muted d-block mt-1">
                            Paid on: <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                        </small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <strong>Listing Package:</strong><br>
                    <span class="badge bg-primary mt-1"><?php echo ucfirst($property['listing_package']); ?> Package</span>
                </div>
                
                <?php if($property['listing_expiry']): ?>
                <div class="mb-3">
                    <strong>Listing Expiry:</strong><br>
                    <small><?php echo date('F d, Y', strtotime($property['listing_expiry'])); ?></small>
                    <?php if(strtotime($property['listing_expiry']) < time()): ?>
                        <span class="text-danger d-block">Expired</span>
                    <?php else: ?>
                        <span class="text-success d-block">
                            <?php echo ceil((strtotime($property['listing_expiry']) - time()) / 86400); ?> days remaining
                        </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <strong>Total Views:</strong><br>
                    <i class="fas fa-eye"></i> <?php echo number_format($property['views_count']); ?> views
                </div>
            </div>
            
            <!-- Owner Contact Card -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-user-circle"></i> Property Owner
                </div>
                <div class="owner-card">
                    <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($property['owner_image'] ?? 'default-avatar.png'); ?>" 
                         class="owner-avatar" alt="Owner">
                    <h5 class="mb-1"><?php echo htmlspecialchars($property['owner_name']); ?></h5>
                    <?php if($property['owner_verified']): ?>
                        <span class="badge bg-info mb-2"><i class="fas fa-check-circle"></i> Verified Owner</span>
                    <?php endif; ?>
                    
                    <?php if(isLoggedIn() && $property['status'] == 'approved'): ?>
                        <button class="contact-btn" id="revealPhoneBtn">
                            <i class="fas fa-phone"></i> Reveal Phone Number
                        </button>
                        <button class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#inquiryModal">
                            <i class="fas fa-envelope"></i> Send Message
                        </button>
                        <button class="btn btn-outline-success w-100 mb-2" id="chatBtn">
                            <i class="fas fa-comment-dots"></i> Chat with Owner
                        </button>
                        <button class="btn btn-outline-danger w-100 save-property" data-id="<?php echo $property['id']; ?>">
                            <i class="fas fa-heart"></i> <?php echo $is_favorite ? 'Saved' : 'Save Property'; ?>
                        </button>
                        <div id="ownerPhone" class="mt-3 text-center" style="display: none;">
                            <hr>
                            <strong>Phone Number:</strong>
                            <p class="h5 text-primary"><?php echo $property['owner_phone']; ?></p>
                        </div>
                    <?php elseif($property['status'] == 'approved'): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-lock"></i> Login to view contact info
                        </div>
                        <a href="login.php" class="btn btn-primary w-100">Login to Contact</a>
                        <a href="register.php" class="btn btn-outline-primary w-100 mt-2">Create Account</a>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center">
                            <i class="fas fa-info-circle"></i> Property not yet available
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Info Card -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-info-circle"></i> Quick Info
                </div>
                <table class="table table-borderless">
                    <tr>
                        <td><i class="fas fa-tag"></i> Property ID:</td>
                        <td><strong>#<?php echo str_pad($property['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-calendar"></i> Listed:</td>
                        <td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-eye"></i> Views:</td>
                        <td><?php echo number_format($property['views_count']); ?> views</td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-building"></i> Type:</td>
                        <td><?php echo ucfirst($property['property_type']); ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-tag"></i> Category:</td>
                        <td><?php echo ucfirst($property['category']); ?></td>
                    </tr>
                </table>
            </div>
            
            <!-- Share Card -->
            <?php if($property['status'] == 'approved'): ?>
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-share-alt"></i> Share Property
                </div>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . 'property-details.php?id=' . $property_id); ?>" 
                       target="_blank" class="btn btn-outline-primary rounded-circle" style="width: 45px; height: 45px;">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . 'property-details.php?id=' . $property_id); ?>&text=<?php echo urlencode($property['title']); ?>" 
                       target="_blank" class="btn btn-outline-info rounded-circle" style="width: 45px; height: 45px;">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://wa.me/?text=<?php echo urlencode($property['title'] . ' - ' . SITE_URL . 'property-details.php?id=' . $property_id); ?>" 
                       target="_blank" class="btn btn-outline-success rounded-circle" style="width: 45px; height: 45px;">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Similar Properties - Only show approved ones -->
    <?php if(count($similar_properties) > 0 && $property['status'] == 'approved'): ?>
    <div class="mt-5">
        <h3 class="fw-bold mb-4">You May Also Like</h3>
        <div class="row g-4">
            <?php foreach($similar_properties as $similar): ?>
            <div class="col-md-3">
                <div class="similar-property-card">
                    <img src="<?php echo !empty($similar['primary_image']) ? SITE_URL . $similar['primary_image'] : SITE_URL . 'assets/images/placeholder.jpg'; ?>" 
                         class="img-fluid" style="height: 180px; width: 100%; object-fit: cover;" alt="">
                    <div class="p-3">
                        <h6><?php echo htmlspecialchars(substr($similar['title'], 0, 40)); ?></h6>
                        <p class="text-muted small mb-1"><?php echo $similar['town_name']; ?></p>
                        <strong class="text-primary">KES <?php echo number_format($similar['category'] == 'sale' ? $similar['price_sale'] : $similar['price_rent']); ?></strong>
                        <div class="mt-2">
                            <a href="property-details.php?id=<?php echo $similar['id']; ?>" class="btn btn-sm btn-outline-primary w-100">View Property</a>
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
        <div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title">Send Message to Owner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="inquiryForm">
                    <div class="mb-3">
                        <label class="form-label">Your Message</label>
                        <textarea class="form-control" name="message" rows="5" required 
                                  placeholder="Hello, I'm interested in this property. Is it still available?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title">Write a Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reviewForm">
                    <div class="mb-3 text-center">
                        <label class="form-label d-block">Your Rating</label>
                        <div class="rating-stars" id="ratingStars">
                            <i class="far fa-star fa-2x" data-rating="1"></i>
                            <i class="far fa-star fa-2x" data-rating="2"></i>
                            <i class="far fa-star fa-2x" data-rating="3"></i>
                            <i class="far fa-star fa-2x" data-rating="4"></i>
                            <i class="far fa-star fa-2x" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="ratingValue" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Your Review</label>
                        <textarea class="form-control" name="comment" rows="4" required 
                                  placeholder="Share your experience with this property..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Submit Review</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Reveal phone number
    $('#revealPhoneBtn').click(function() {
        $.ajax({
            url: 'ajax/reveal-phone.php',
            method: 'POST',
            data: {property_id: <?php echo $property_id; ?>},
            success: function(response) {
                var data = JSON.parse(response);
                if(data.success) {
                    $('#ownerPhone').show();
                    $('#ownerPhone p').text(data.phone);
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
    
    // Send inquiry
    $('#inquiryForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax/send-inquiry.php',
            method: 'POST',
            data: {
                property_id: <?php echo $property_id; ?>,
                message: $('textarea[name="message"]').val()
            },
            success: function(response) {
                var data = JSON.parse(response);
                if(data.success) {
                    Swal.fire('Success!', data.message, 'success');
                    $('#inquiryModal').modal('hide');
                    $('#inquiryForm')[0].reset();
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            }
        });
    });
    
    // Submit review
    $('#reviewForm').submit(function(e) {
        e.preventDefault();
        if(!$('#ratingValue').val()) {
            Swal.fire('Error', 'Please select a rating', 'error');
            return;
        }
        $.ajax({
            url: 'ajax/submit-review.php',
            method: 'POST',
            data: {
                property_id: <?php echo $property_id; ?>,
                rating: $('#ratingValue').val(),
                comment: $('textarea[name="comment"]').val()
            },
            success: function(response) {
                var data = JSON.parse(response);
                if(data.success) {
                    Swal.fire('Success!', data.message, 'success');
                    location.reload();
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            }
        });
    });
    
    // Chat button
    $('#chatBtn').click(function() {
        window.location.href = 'messages.php?property=<?php echo $property_id; ?>&user=<?php echo $property['owner_id']; ?>';
    });
    
    // Save property
    $('.save-property').click(function() {
        var property_id = $(this).data('id');
        var btn = $(this);
        
        $.ajax({
            url: 'ajax/save-property.php',
            method: 'POST',
            data: {property_id: property_id},
            success: function(response) {
                var data = JSON.parse(response);
                if(data.success) {
                    Swal.fire('Success!', data.message, 'success');
                    if(data.action === 'saved') {
                        btn.html('<i class="fas fa-heart"></i> Saved');
                    } else {
                        btn.html('<i class="fas fa-heart"></i> Save Property');
                    }
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>