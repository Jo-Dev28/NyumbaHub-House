<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$error = '';
$success = '';

// Check user subscription limits
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM properties WHERE owner_id = ? AND status != 'deleted'");
$stmt->execute([$user_id]);
$property_count = $stmt->fetch()['count'];

// Get active subscription
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$subscription = $stmt->fetch();

// Set limits based on subscription
$max_properties = 5;
$max_images = 10;
if($subscription) {
    switch($subscription['plan']) {
        case 'silver':
            $max_properties = 15;
            $max_images = 30;
            break;
        case 'gold':
            $max_properties = 999;
            $max_images = 50;
            break;
    }
}

if($property_count >= $max_properties && !$subscription) {
    $error = "You've reached your property limit. <a href='subscription.php'>Upgrade your plan</a> to list more properties.";
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_property'])) {
    try {
        // Basic Information
        $title = sanitize($_POST['title']);
        $property_type = sanitize($_POST['property_type']);
        $category = sanitize($_POST['category']);
        $price_rent = !empty($_POST['price_rent']) ? (float)$_POST['price_rent'] : null;
        $price_sale = !empty($_POST['price_sale']) ? (float)$_POST['price_sale'] : null;
        $service_charge = !empty($_POST['service_charge']) ? (float)$_POST['service_charge'] : null;
        $county_id = (int)$_POST['county_id'];
        
        // Handle custom town
        $town_id = isset($_POST['town_id']) ? (int)$_POST['town_id'] : 0;
        $custom_town = isset($_POST['custom_town']) ? sanitize($_POST['custom_town']) : '';
        
        if($town_id == 0 && !empty($custom_town)) {
            // Check if town already exists in this county
            $stmt = $pdo->prepare("SELECT id FROM towns WHERE county_id = ? AND LOWER(name) = LOWER(?)");
            $stmt->execute([$county_id, $custom_town]);
            $existing = $stmt->fetch();
            if($existing) {
                $town_id = $existing['id'];
            } else {
                // Insert custom town
                $stmt = $pdo->prepare("INSERT INTO towns (county_id, name) VALUES (?, ?)");
                $stmt->execute([$county_id, $custom_town]);
                $town_id = $pdo->lastInsertId();
            }
        }
        
        $estate = sanitize($_POST['estate']);
        $street = sanitize($_POST['street']);
        $bedrooms = (int)$_POST['bedrooms'];
        $bathrooms = (int)$_POST['bathrooms'];
        $parking_slots = (int)$_POST['parking_slots'];
        $square_feet = !empty($_POST['square_feet']) ? (int)$_POST['square_feet'] : null;
        $description = sanitize($_POST['description']);
        $listing_package = sanitize($_POST['listing_package']);
        
        // Validation
        if(empty($title)) {
            throw new Exception("Property title is required");
        }
        if(empty($category)) {
            throw new Exception("Please select a category");
        }
        if($category == 'rent' && empty($price_rent)) {
            throw new Exception("Rent price is required for rental properties");
        }
        if($category == 'sale' && empty($price_sale)) {
            throw new Exception("Sale price is required for properties for sale");
        }
        if($county_id == 0) {
            throw new Exception("Please select county");
        }
        if($town_id == 0) {
            throw new Exception("Please select or enter town");
        }
        
        // Amenities
        $water_available = isset($_POST['water_available']) ? 1 : 0;
        $electricity_available = isset($_POST['electricity_available']) ? 1 : 0;
        $wifi_available = isset($_POST['wifi_available']) ? 1 : 0;
        $security_available = isset($_POST['security_available']) ? 1 : 0;
        $cctv = isset($_POST['cctv']) ? 1 : 0;
        $borehole = isset($_POST['borehole']) ? 1 : 0;
        $swimming_pool = isset($_POST['swimming_pool']) ? 1 : 0;
        $gym = isset($_POST['gym']) ? 1 : 0;
        $backup_generator = isset($_POST['backup_generator']) ? 1 : 0;
        $balcony = isset($_POST['balcony']) ? 1 : 0;
        $furnished = isset($_POST['furnished']) ? 1 : 0;
        $pets_allowed = isset($_POST['pets_allowed']) ? 1 : 0;
        
        // Calculate expiry date based on package
        $expiry_days = 30;
        if($listing_package == 'premium') $expiry_days = 60;
        if($listing_package == 'vip') $expiry_days = 90;
        $listing_expiry = date('Y-m-d', strtotime("+$expiry_days days"));
        
        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = $slug . '-' . time();
        
        // Insert property
        $stmt = $pdo->prepare("INSERT INTO properties (
            owner_id, title, slug, property_type, category, price_rent, price_sale, 
            service_charge, county_id, town_id, estate, street, bedrooms, bathrooms, 
            parking_slots, square_feet, description, water_available, electricity_available, 
            wifi_available, security_available, cctv, borehole, swimming_pool, gym, 
            backup_generator, balcony, furnished, pets_allowed, listing_package, 
            listing_expiry, status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_payment', NOW()
        )");
        
        if($stmt->execute([
            $user_id, $title, $slug, $property_type, $category, $price_rent, $price_sale,
            $service_charge, $county_id, $town_id, $estate, $street, $bedrooms, $bathrooms,
            $parking_slots, $square_feet, $description, $water_available, $electricity_available,
            $wifi_available, $security_available, $cctv, $borehole, $swimming_pool, $gym,
            $backup_generator, $balcony, $furnished, $pets_allowed, $listing_package, $listing_expiry
        ])) {
            $property_id = $pdo->lastInsertId();
            
            // Handle image uploads based on package
            $max_images_per_package = [
                'basic' => 10,
                'premium' => 30,
                'vip' => 50
            ];
            $max_allowed = $max_images_per_package[$listing_package];
            
            $upload_dir = 'uploads/properties/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $uploaded_images = 0;
            if(isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                foreach($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if($uploaded_images >= $max_allowed) break;
                    if($_FILES['images']['error'][$key] == 0) {
                        $filename = time() . '_' . $key . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['images']['name'][$key]);
                        $filepath = $upload_dir . $filename;
                        if(move_uploaded_file($tmp_name, $filepath)) {
                            $is_primary = ($uploaded_images == 0) ? 1 : 0;
                            $img_stmt = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_primary, image_order) VALUES (?, ?, ?, ?)");
                            $img_stmt->execute([$property_id, $filepath, $is_primary, $uploaded_images]);
                            $uploaded_images++;
                        }
                    }
                }
            }
            
            // Handle video URL
            if(!empty($_POST['video_url'])) {
                $video_url = sanitize($_POST['video_url']);
                $stmt = $pdo->prepare("INSERT INTO property_videos (property_id, video_url, video_type) VALUES (?, ?, 'youtube')");
                $stmt->execute([$property_id, $video_url]);
            }
            
            // Store in session for payment
            $_SESSION['pending_property_id'] = $property_id;
            $_SESSION['pending_package'] = $listing_package;
            
            // Redirect to payment
            redirect('payment.php');
        } else {
            throw new Exception("Failed to add property. Please try again.");
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all counties from database
$counties = $pdo->query("SELECT * FROM counties ORDER BY name")->fetchAll();

$page_title = 'Add New Property';
require_once 'includes/header.php';
?>

<!-- jQuery UI for Autocomplete -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .form-section {
        background: white;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }
    .form-section-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f0f0f0;
        color: #0d6efd;
    }
    .form-section-title i {
        margin-right: 10px;
    }
    .package-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid #e0e0e0;
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        height: 100%;
    }
    .package-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .package-card.selected {
        border-color: #0d6efd;
        background: #f0f8ff;
    }
    .package-price {
        font-size: 24px;
        font-weight: bold;
        color: #0d6efd;
        margin: 15px 0;
    }
    .package-features {
        list-style: none;
        padding: 0;
        margin: 15px 0 0;
        text-align: left;
    }
    .package-features li {
        padding: 5px 0;
        font-size: 13px;
    }
    .package-features i.fa-check { color: #28a745; margin-right: 8px; }
    .package-features i.fa-times { color: #dc3545; margin-right: 8px; }
    .amenities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }
    .amenity-item {
        display: flex;
        align-items: center;
    }
    .amenity-item input {
        margin-right: 10px;
        width: 18px;
        height: 18px;
    }
    .image-preview {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    .preview-item {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e0e0e0;
        background: #f8f9fa;
    }
    .preview-item img {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }
    .preview-item .remove-image {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(220,53,69,0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s;
    }
    .preview-item .remove-image:hover {
        transform: scale(1.1);
    }
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        position: relative;
        background: white;
        padding: 20px;
        border-radius: 15px;
    }
    .step {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    .step .step-number {
        width: 35px;
        height: 35px;
        background: #e0e0e0;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 8px;
        font-size: 14px;
    }
    .step.active .step-number { background: #0d6efd; color: white; }
    .step.completed .step-number { background: #28a745; color: white; }
    .step-label { font-size: 12px; color: #666; }
    .step.active .step-label { color: #0d6efd; font-weight: 600; }
    .step-line {
        position: absolute;
        top: 28px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e0e0e0;
        z-index: 0;
    }
    .btn-submit {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
        padding: 12px 35px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        border-radius: 50px;
        transition: all 0.3s ease;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(13,110,253,0.3);
        color: white;
    }
    .required:after { content: "*"; color: #dc3545; margin-left: 5px; }
    .dropzone-area {
        transition: all 0.3s ease;
        cursor: pointer;
        background: #f8f9fa;
    }
    .dropzone-area:hover {
        border-color: #0d6efd !important;
        background: #e9ecef;
    }
    .town-input-group { position: relative; }
    .custom-town-input { display: none; margin-top: 10px; }
    .custom-town-input.active { display: block; }
    .select2-container--default .select2-selection--single {
        border-radius: 12px;
        height: 45px;
        padding: 5px;
    }
    .upload-info {
        background: #e7f3ff;
        border-left: 4px solid #0d6efd;
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 8px;
    }
    @media (max-width: 768px) {
        .form-section { padding: 20px; }
        .amenities-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
    }
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'includes/user-sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="mb-4">
                <h2 class="fw-bold mb-2"><i class="fas fa-plus-circle text-primary"></i> Add New Property</h2>
                <p class="text-muted">Fill in the details below to list your property</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if($property_count >= $max_properties): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> You have reached your property limit. 
                    <a href="subscription.php">Upgrade your plan</a> to list more properties.
                </div>
            <?php else: ?>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active" id="step1"><div class="step-number">1</div><div class="step-label">Package</div></div>
                <div class="step" id="step2"><div class="step-number">2</div><div class="step-label">Details</div></div>
                <div class="step" id="step3"><div class="step-number">3</div><div class="step-label">Amenities</div></div>
                <div class="step" id="step4"><div class="step-number">4</div><div class="step-label">Photos</div></div>
                <div class="step" id="step5"><div class="step-number">5</div><div class="step-label">Review</div></div>
                <div class="step-line"></div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="propertyForm">
                <!-- Step 1: Package Selection -->
                <div id="step1Content" class="form-section">
                    <div class="form-section-title"><i class="fas fa-gem"></i> Choose Your Listing Package</div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="package-card" data-package="basic" data-max-photos="10">
                                <i class="fas fa-home fa-3x text-secondary"></i>
                                <h5 class="mt-3">Basic Package</h5>
                                <div class="package-price">KES 1,000</div>
                                <ul class="package-features">
                                    <li><i class="fas fa-check"></i> 30 Days Listing</li>
                                    <li><i class="fas fa-check"></i> Up to 10 Photos</li>
                                    <li><i class="fas fa-check"></i> Standard Listing</li>
                                    <li><i class="fas fa-times text-danger"></i> Featured</li>
                                </ul>
                                <div class="form-check mt-3">
                                    <input class="form-check-input package-radio" type="radio" name="listing_package" value="basic" checked>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="package-card" data-package="premium" data-max-photos="30">
                                <i class="fas fa-crown fa-3x text-warning"></i>
                                <h5 class="mt-3">Premium Package</h5>
                                <div class="package-price">KES 2,500</div>
                                <ul class="package-features">
                                    <li><i class="fas fa-check"></i> 60 Days Listing</li>
                                    <li><i class="fas fa-check"></i> Up to 30 Photos</li>
                                    <li><i class="fas fa-check"></i> Featured Listing</li>
                                    <li><i class="fas fa-check"></i> Homepage Display</li>
                                </ul>
                                <div class="form-check mt-3">
                                    <input class="form-check-input package-radio" type="radio" name="listing_package" value="premium">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="package-card" data-package="vip" data-max-photos="50">
                                <i class="fas fa-gem fa-3x text-primary"></i>
                                <h5 class="mt-3">VIP Package</h5>
                                <div class="package-price">KES 5,000</div>
                                <ul class="package-features">
                                    <li><i class="fas fa-check"></i> 90 Days Listing</li>
                                    <li><i class="fas fa-check"></i> Up to 50 Photos</li>
                                    <li><i class="fas fa-check"></i> Featured + Top Priority</li>
                                    <li><i class="fas fa-check"></i> Homepage Banner</li>
                                    <li><i class="fas fa-check"></i> Verified Badge</li>
                                </ul>
                                <div class="form-check mt-3">
                                    <input class="form-check-input package-radio" type="radio" name="listing_package" value="vip">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-primary px-4 next-step">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                
                <!-- Step 2: Property Details -->
                <div id="step2Content" style="display: none;">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-info-circle"></i> Basic Information</div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label required">Property Title</label>
                                <input type="text" name="title" id="propertyTitle" class="form-control" placeholder="e.g., Modern 3-Bedroom Villa in Karen" required>
                                <small class="text-muted">Be descriptive - include location and key features</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Property Type</label>
                                <select name="property_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="house">🏠 House</option>
                                    <option value="apartment">🏢 Apartment</option>
                                    <option value="bedsitter">🛏️ Bedsitter</option>
                                    <option value="single_room">🚪 Single Room</option>
                                    <option value="hostel">🏘️ Hostel</option>
                                    <option value="commercial">🏪 Commercial</option>
                                    <option value="land">🌾 Land</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Category</label>
                                <select name="category" class="form-select" id="categorySelect" required>
                                    <option value="">Select Category</option>
                                    <option value="rent">🏠 For Rent</option>
                                    <option value="sale">💰 For Sale</option>
                                    <option value="airbnb">🏖️ Airbnb / Short Stay</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="rentPriceField" style="display: none;">
                                <label class="form-label">Rent Price (KES/month)</label>
                                <input type="number" name="price_rent" class="form-control" placeholder="e.g., 50000">
                            </div>
                            <div class="col-md-6" id="salePriceField" style="display: none;">
                                <label class="form-label">Sale Price (KES)</label>
                                <input type="number" name="price_sale" class="form-control" placeholder="e.g., 5000000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Service Charge (KES/month)</label>
                                <input type="number" name="service_charge" class="form-control" placeholder="If applicable">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-map-marker-alt"></i> Location Details</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">County</label>
                                <select name="county_id" class="form-select" id="countySelect" required>
                                    <option value="">Select County</option>
                                    <?php foreach($counties as $county): ?>
                                        <option value="<?php echo $county['id']; ?>"><?php echo htmlspecialchars($county['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Town/Area</label>
                                <div class="town-input-group">
                                    <select name="town_id" class="form-select" id="townSelect">
                                        <option value="">First select a county</option>
                                    </select>
                                    <div class="custom-town-input" id="customTownInput">
                                        <input type="text" name="custom_town" id="customTown" class="form-control" placeholder="Enter your town/area name">
                                        <small class="text-muted">This town will be added to our database for future use</small>
                                    </div>
                                    <button type="button" id="addCustomTownBtn" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-plus"></i> Can't find your area? Add it here
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estate/Neighborhood</label>
                                <input type="text" name="estate" id="estateInput" class="form-control" placeholder="e.g., Kilimani, Westlands">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Street Address</label>
                                <input type="text" name="street" class="form-control" placeholder="Street name or building">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-home"></i> Property Specifications</div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Bedrooms</label>
                                <input type="number" name="bedrooms" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bathrooms</label>
                                <input type="number" name="bathrooms" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Parking Slots</label>
                                <input type="number" name="parking_slots" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Square Feet</label>
                                <input type="number" name="square_feet" class="form-control" placeholder="Area in sqft">
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Description</label>
                                <textarea name="description" class="form-control" rows="5" placeholder="Describe your property in detail..."></textarea>
                                <small class="text-muted">Include information about the property, nearby amenities, and unique features</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="btn btn-primary px-4 next-step">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                
                <!-- Step 3: Amenities -->
                <div id="step3Content" style="display: none;">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-wifi"></i> Amenities & Features</div>
                        <div class="amenities-grid">
                            <div class="amenity-item"><input type="checkbox" name="water_available" id="water"> <label for="water">💧 Water Available</label></div>
                            <div class="amenity-item"><input type="checkbox" name="electricity_available" id="electricity"> <label for="electricity">⚡ Electricity</label></div>
                            <div class="amenity-item"><input type="checkbox" name="wifi_available" id="wifi"> <label for="wifi">📶 WiFi</label></div>
                            <div class="amenity-item"><input type="checkbox" name="security_available" id="security"> <label for="security">🛡️ Security</label></div>
                            <div class="amenity-item"><input type="checkbox" name="cctv" id="cctv"> <label for="cctv">📹 CCTV</label></div>
                            <div class="amenity-item"><input type="checkbox" name="borehole" id="borehole"> <label for="borehole">🚰 Borehole</label></div>
                            <div class="amenity-item"><input type="checkbox" name="swimming_pool" id="pool"> <label for="pool">🏊 Swimming Pool</label></div>
                            <div class="amenity-item"><input type="checkbox" name="gym" id="gym"> <label for="gym">💪 Gym</label></div>
                            <div class="amenity-item"><input type="checkbox" name="backup_generator" id="generator"> <label for="generator">🔌 Backup Generator</label></div>
                            <div class="amenity-item"><input type="checkbox" name="balcony" id="balcony"> <label for="balcony">🏞️ Balcony</label></div>
                            <div class="amenity-item"><input type="checkbox" name="furnished" id="furnished"> <label for="furnished">🛋️ Furnished</label></div>
                            <div class="amenity-item"><input type="checkbox" name="pets_allowed" id="pets"> <label for="pets">🐾 Pets Allowed</label></div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="btn btn-primary px-4 next-step">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                
                <!-- Step 4: Photos -->
                <div id="step4Content" style="display: none;">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-images"></i> Photos & Media</div>
                        <div class="upload-info" id="uploadInfo">
                            <i class="fas fa-info-circle"></i> 
                            <span id="photoLimitText">You can upload up to 10 photos</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Property Photos</label>
                                <div class="dropzone-area border rounded-3 p-4 text-center" style="border: 2px dashed #ccc; cursor: pointer;" id="dropzoneArea">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p>Click or drag photos here to upload</p>
                                    <small class="text-muted">First image will be the cover photo</small>
                                    <input type="file" name="images[]" id="imageInput" multiple accept="image/jpeg,image/png,image/jpg,image/webp" style="display: none;">
                                </div>
                                <div class="image-preview" id="imagePreview"></div>
                            </div>
                            <div class="col-12 mt-3">
                                <label class="form-label">Video Tour (YouTube URL)</label>
                                <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                                <small class="text-muted">Add a video tour of your property</small>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="btn btn-primary px-4 next-step">Review Property <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                
                <!-- Step 5: Review -->
                <div id="step5Content" style="display: none;">
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-check-circle"></i> Review Your Listing</div>
                        <div id="reviewContent" class="mb-4"></div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Important:</strong> After submitting, you'll be redirected to payment. 
                            Your property will be listed only after successful payment.
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-secondary px-4 prev-step"><i class="fas fa-arrow-left"></i> Back</button>
                            <button type="submit" name="submit_property" class="btn btn-submit px-5" id="submitBtn">
                                <i class="fas fa-credit-card"></i> Proceed to Payment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Maximum photos based on package
    let maxPhotos = 10;
    let selectedFiles = [];
    
    // Update photo limit based on selected package
    function updatePhotoLimit() {
        const package = $('input[name="listing_package"]:checked').val();
        if(package === 'basic') {
            maxPhotos = 10;
            $('#photoLimitText').text('You can upload up to 10 photos');
        } else if(package === 'premium') {
            maxPhotos = 30;
            $('#photoLimitText').text('You can upload up to 30 photos');
        } else {
            maxPhotos = 50;
            $('#photoLimitText').text('You can upload up to 50 photos');
        }
        
        // Check if current files exceed new limit
        if(selectedFiles.length > maxPhotos) {
            Swal.fire('Warning', `Your package only allows ${maxPhotos} photos. Extra photos will be removed.`, 'warning');
            selectedFiles = selectedFiles.slice(0, maxPhotos);
            displayImages();
            updateFileInput();
        }
    }
    
    // Update file input with current files
    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        $('#imageInput')[0].files = dataTransfer.files;
    }
    
    // Package selection with photo limit update
    $('.package-card').click(function() {
        $('.package-card').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('.package-radio').prop('checked', true);
        updatePhotoLimit();
    });
    
    $('input[name="listing_package"]').change(function() {
        updatePhotoLimit();
    });
    
    // Property Title Autocomplete
    const titleSuggestions = [
        "Modern 3 Bedroom Villa with Pool", "Spacious 2 Bedroom Apartment", "Luxury 4 Bedroom Mansion",
        "Cozy 1 Bedroom Bedsitter", "Executive 5 Bedroom Home", "Studio Apartment in City Center",
        "Penthouse with Ocean View", "Townhouse with Garden", "Commercial Office Space",
        "Retail Shop for Rent", "Warehouse for Lease", "Agricultural Land for Sale",
        "Residential Plot in Prime Area", "Beachfront Villa", "Furnished 2 Bedroom House"
    ];
    
    $("#propertyTitle").autocomplete({
        source: titleSuggestions,
        minLength: 1
    });
    
    // Estate Autocomplete
    const estateSuggestions = [
        "CBD", "Town Center", "Industrial Area", "Residential Area", "Gated Community",
        "Kilimani", "Westlands", "Karen", "Lang'ata", "Runda", "Kileleshwa", "Lavington",
        "Nyali", "Bamburi", "Milimani", "Kondele", "Kisumu CBD", "Eldoret CBD", "Nakuru CBD"
    ];
    
    $("#estateInput").autocomplete({
        source: estateSuggestions,
        minLength: 1
    });
    
    // Category change handler
    $('#categorySelect').change(function() {
        var category = $(this).val();
        $('#rentPriceField, #salePriceField').hide();
        if(category === 'rent' || category === 'airbnb') {
            $('#rentPriceField').show();
        } else if(category === 'sale') {
            $('#salePriceField').show();
        }
    });
    
    // County change - load towns via AJAX
    $('#countySelect').change(function() {
        var countyId = $(this).val();
        var townSelect = $('#townSelect');
        
        if(countyId) {
            townSelect.html('<option value="">Loading towns...</option>');
            townSelect.prop('disabled', true);
            
            $.ajax({
                url: 'ajax/get-towns.php',
                method: 'POST',
                data: {county_id: countyId},
                dataType: 'json',
                success: function(data) {
                    townSelect.empty();
                    townSelect.append('<option value="">Select Town</option>');
                    if(data.length > 0) {
                        $.each(data, function(index, town) {
                            townSelect.append('<option value="' + town.id + '">' + town.name + '</option>');
                        });
                    } else {
                        townSelect.append('<option value="">No towns found</option>');
                    }
                    townSelect.prop('disabled', false);
                },
                error: function() {
                    townSelect.html('<option value="">Error loading towns</option>');
                    townSelect.prop('disabled', false);
                }
            });
        } else {
            townSelect.html('<option value="">First select a county</option>');
        }
        
        $('#townSelect').show();
        $('#customTownInput').removeClass('active');
        $('#addCustomTownBtn').html('<i class="fas fa-plus"></i> Can\'t find your area? Add it here');
        $('#customTown').val('');
    });
    
    // Add custom town button
    let customMode = false;
    $('#addCustomTownBtn').click(function() {
        var countyId = $('#countySelect').val();
        if(!countyId) {
            Swal.fire('Error', 'Please select a county first', 'error');
            return;
        }
        
        if(!customMode) {
            $('#townSelect').hide();
            $('#customTownInput').addClass('active');
            $(this).html('<i class="fas fa-undo"></i> Back to select from list');
            customMode = true;
        } else {
            $('#townSelect').show();
            $('#customTownInput').removeClass('active');
            $(this).html('<i class="fas fa-plus"></i> Can\'t find your area? Add it here');
            customMode = false;
            $('#customTown').val('');
        }
    });
    
    // Image upload with preview and limit enforcement
    $('#dropzoneArea').click(function() {
        if(selectedFiles.length >= maxPhotos) {
            Swal.fire('Limit Reached', `You can only upload up to ${maxPhotos} photos with your current package`, 'warning');
            return;
        }
        $('#imageInput').click();
    });
    
    $('#imageInput').change(function(e) {
        const files = Array.from(e.target.files);
        const remainingSlots = maxPhotos - selectedFiles.length;
        
        if(files.length > remainingSlots) {
            Swal.fire('Limit Exceeded', `You can only add ${remainingSlots} more photo(s) with your current package`, 'warning');
            const allowedFiles = files.slice(0, remainingSlots);
            selectedFiles = [...selectedFiles, ...allowedFiles];
        } else {
            selectedFiles = [...selectedFiles, ...files];
        }
        
        displayImages();
        updateFileInput();
    });
    
    function displayImages() {
        const preview = $('#imagePreview');
        preview.empty();
        
        if(selectedFiles.length === 0) {
            preview.html('<div class="text-center text-muted p-3">No photos selected</div>');
            return;
        }
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewHtml = `
                    <div class="preview-item">
                        <img src="${e.target.result}" alt="Preview ${index + 1}">
                        <button type="button" class="remove-image" data-index="${index}" title="Remove image">
                            <i class="fas fa-times"></i>
                        </button>
                        ${index === 0 ? '<div class="badge bg-primary position-absolute bottom-0 start-0 m-1">Cover Photo</div>' : ''}
                        <div class="badge bg-secondary position-absolute bottom-0 end-0 m-1">${index + 1}/${selectedFiles.length}</div>
                    </div>
                `;
                preview.append(previewHtml);
            };
            reader.readAsDataURL(file);
        });
    }
    
    $(document).on('click', '.remove-image', function() {
        const index = $(this).data('index');
        selectedFiles.splice(index, 1);
        displayImages();
        updateFileInput();
    });
    
    // Drag and drop functionality
    const dropzone = document.getElementById('dropzoneArea');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight(e) {
        dropzone.style.borderColor = '#0d6efd';
        dropzone.style.background = '#e7f3ff';
    }
    
    function unhighlight(e) {
        dropzone.style.borderColor = '#ccc';
        dropzone.style.background = '#f8f9fa';
    }
    
    dropzone.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = Array.from(dt.files);
        const imageFiles = files.filter(file => file.type.startsWith('image/'));
        const remainingSlots = maxPhotos - selectedFiles.length;
        
        if(imageFiles.length > remainingSlots) {
            Swal.fire('Limit Exceeded', `You can only add ${remainingSlots} more photo(s) with your current package`, 'warning');
            const allowedFiles = imageFiles.slice(0, remainingSlots);
            selectedFiles = [...selectedFiles, ...allowedFiles];
        } else {
            selectedFiles = [...selectedFiles, ...imageFiles];
        }
        
        displayImages();
        updateFileInput();
    }
    
    // Step navigation
    let currentStep = 1;
    const totalSteps = 5;
    
    function updateSteps() {
        for(let i = 1; i <= totalSteps; i++) {
            if(i < currentStep) {
                $(`#step${i}`).addClass('completed').removeClass('active');
            } else if(i === currentStep) {
                $(`#step${i}`).addClass('active').removeClass('completed');
            } else {
                $(`#step${i}`).removeClass('active completed');
            }
        }
        $(`#step1Content, #step2Content, #step3Content, #step4Content, #step5Content`).hide();
        $(`#step${currentStep}Content`).fadeIn();
        if(currentStep === totalSteps) { generateReview(); }
        $('html, body').animate({scrollTop: 0}, 300);
    }
    
    function generateReview() {
        const title = $('input[name="title"]').val() || 'Not specified';
        const category = $('#categorySelect option:selected').text() || 'Not selected';
        const propertyType = $('select[name="property_type"] option:selected').text() || 'Not selected';
        const county = $('#countySelect option:selected').text() || 'Not selected';
        let town = $('#townSelect option:selected').text();
        if(town === 'Select Town' || town === 'First select a county' || town === 'Loading towns...') {
            town = $('#customTown').val() || 'Not specified';
        }
        const estate = $('input[name="estate"]').val() || 'Not specified';
        const bedrooms = $('input[name="bedrooms"]').val() || '0';
        const bathrooms = $('input[name="bathrooms"]').val() || '0';
        const parking = $('input[name="parking_slots"]').val() || '0';
        const package = $('input[name="listing_package"]:checked').val();
        
        let packagePrice = package === 'basic' ? 'KES 1,000' : (package === 'premium' ? 'KES 2,500' : 'KES 5,000');
        let packageName = package === 'basic' ? 'Basic' : (package === 'premium' ? 'Premium' : 'VIP');
        
        let price = '';
        const catVal = $('#categorySelect').val();
        if(catVal === 'rent' || catVal === 'airbnb') {
            const rentPrice = $('input[name="price_rent"]').val();
            price = rentPrice ? `KES ${parseInt(rentPrice).toLocaleString()}/month` : 'Not specified';
        } else if(catVal === 'sale') {
            const salePrice = $('input[name="price_sale"]').val();
            price = salePrice ? `KES ${parseInt(salePrice).toLocaleString()}` : 'Not specified';
        }
        
        const reviewHtml = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100"><div class="card-body">
                        <h6><i class="fas fa-home text-primary"></i> Property Details</h6><hr>
                        <table class="table table-sm">
                            <tr><td width="40%"><strong>Title:</strong></td><td>${escapeHtml(title)}</td></tr>
                            <tr><td><strong>Category:</strong></td><td>${escapeHtml(category)}</td></tr>
                            <tr><td><strong>Type:</strong></td><td>${escapeHtml(propertyType)}</td></tr>
                            <tr><td><strong>Price:</strong></td><td class="text-primary fw-bold">${escapeHtml(price)}</td></tr>
                            <tr><td><strong>Bedrooms:</strong></td><td>${bedrooms}</td></tr>
                            <tr><td><strong>Bathrooms:</strong></td><td>${bathrooms}</td></tr>
                            <tr><td><strong>Parking:</strong></td><td>${parking}</td></tr>
                        </table>
                    </div></div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100"><div class="card-body">
                        <h6><i class="fas fa-map-marker-alt text-primary"></i> Location & Package</h6><hr>
                        <table class="table table-sm">
                            <tr><td width="40%"><strong>County:</strong></td><td>${escapeHtml(county)}</td></tr>
                            <tr><td><strong>Town:</strong></td><td>${escapeHtml(town)}</td></tr>
                            <tr><td><strong>Estate:</strong></td><td>${escapeHtml(estate)}</td></tr>
                            <tr><td><strong>Package:</strong></td><td class="text-warning fw-bold">${packageName}</td></tr>
                            <tr><td><strong>Package Price:</strong></td><td class="text-primary fw-bold">${packagePrice}</td></tr>
                            <tr><td><strong>Photos:</strong></td><td>${selectedFiles.length} / ${maxPhotos} selected</td></tr>
                        </table>
                    </div></div>
                </div>
            </div>
        `;
        $('#reviewContent').html(reviewHtml);
    }
    
    function escapeHtml(text) {
        if(!text) return 'Not specified';
        return text.replace(/[&<>]/g, function(m) {
            if(m === '&') return '&amp;';
            if(m === '<') return '&lt;';
            if(m === '>') return '&gt;';
            return m;
        });
    }
    
    $('.next-step').click(function() {
        if(currentStep === 1) {
            if(!$('input[name="listing_package"]:checked').val()) {
                Swal.fire('Error', 'Please select a package', 'error');
                return;
            }
        } else if(currentStep === 2) {
            if(!$('input[name="title"]').val()) {
                Swal.fire('Error', 'Please enter property title', 'error');
                return;
            }
            if(!$('#categorySelect').val()) {
                Swal.fire('Error', 'Please select category', 'error');
                return;
            }
            if(!$('#countySelect').val()) {
                Swal.fire('Error', 'Please select county', 'error');
                return;
            }
            const townSelected = $('#townSelect').val();
            const customTown = $('#customTown').val();
            if(!townSelected && !customTown) {
                Swal.fire('Error', 'Please select or enter a town/area', 'error');
                return;
            }
            const cat = $('#categorySelect').val();
            if((cat === 'rent' || cat === 'airbnb') && !$('input[name="price_rent"]').val()) {
                Swal.fire('Error', 'Please enter rent price', 'error');
                return;
            }
            if(cat === 'sale' && !$('input[name="price_sale"]').val()) {
                Swal.fire('Error', 'Please enter sale price', 'error');
                return;
            }
        } else if(currentStep === 4) {
            if(selectedFiles.length === 0) {
                Swal.fire('Error', 'Please upload at least one property photo', 'error');
                return;
            }
        }
        currentStep++;
        updateSteps();
    });
    
    $('.prev-step').click(function() {
        currentStep--;
        updateSteps();
    });
    
    updateSteps();
});
</script>

<?php require_once 'includes/footer.php'; ?>