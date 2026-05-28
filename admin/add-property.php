<?php
ob_start();
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

// Only super admin can access
if($_SESSION['user_role'] != 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Get counties for dropdown
$counties = $pdo->query("SELECT * FROM counties ORDER BY name")->fetchAll();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_property'])) {
    try {
        // Basic Information
        $title = sanitize($_POST['title']);
        $property_type = sanitize($_POST['property_type']);
        $category = sanitize($_POST['category']);
        $price_rent = !empty($_POST['price_rent']) ? (float)$_POST['price_rent'] : null;
        $price_sale = !empty($_POST['price_sale']) ? (float)$_POST['price_sale'] : null;
        $service_charge = !empty($_POST['service_charge']) ? (float)$_POST['service_charge'] : null;
        $county_id = (int)$_POST['county_id'];
        $town_id = (int)$_POST['town_id'];
        $estate = sanitize($_POST['estate']);
        $street = sanitize($_POST['street']);
        $bedrooms = (int)$_POST['bedrooms'];
        $bathrooms = (int)$_POST['bathrooms'];
        $parking_slots = (int)$_POST['parking_slots'];
        $square_feet = !empty($_POST['square_feet']) ? (int)$_POST['square_feet'] : null;
        $description = sanitize($_POST['description']);
        $listing_package = sanitize($_POST['listing_package']);
        $status = sanitize($_POST['status']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $owner_id = (int)$_POST['owner_id'];
        
        // If owner_id is 0, use logged in admin
        if($owner_id == 0) {
            $owner_id = $_SESSION['user_id'];
        }
        
        // Validation
        if(empty($title)) {
            throw new Exception("Property title is required");
        }
        if(empty($category)) {
            throw new Exception("Please select a category");
        }
        if($county_id == 0 || $town_id == 0) {
            throw new Exception("Please select location");
        }
        
        // Calculate expiry date
        $expiry_days = 30;
        if($listing_package == 'premium') $expiry_days = 60;
        if($listing_package == 'vip') $expiry_days = 90;
        $listing_expiry = date('Y-m-d', strtotime("+$expiry_days days"));
        
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
            listing_expiry, is_featured, status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )");
        
        if($stmt->execute([
            $owner_id, $title, $slug, $property_type, $category, $price_rent, $price_sale,
            $service_charge, $county_id, $town_id, $estate, $street, $bedrooms, $bathrooms,
            $parking_slots, $square_feet, $description, $water_available, $electricity_available,
            $wifi_available, $security_available, $cctv, $borehole, $swimming_pool, $gym,
            $backup_generator, $balcony, $furnished, $pets_allowed, $listing_package, $listing_expiry,
            $is_featured, $status
        ])) {
            $property_id = $pdo->lastInsertId();
            
            // Handle image uploads
            $max_images_per_package = [
                'basic' => 10,
                'premium' => 30,
                'vip' => 50
            ];
            $max_allowed = $max_images_per_package[$listing_package];
            
            $upload_dir = '../uploads/properties/';
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
            
            // Record payment if status is approved (free listing)
            if($status == 'approved') {
                $stmt = $pdo->prepare("INSERT INTO payments (user_id, property_id, amount, payment_method, package_type, status) 
                                       VALUES (?, ?, ?, 'admin', ?, 'completed')");
                $stmt->execute([$owner_id, $property_id, 0, $listing_package]);
            }
            
            $success = "Property added successfully!";
            
            // Clear form
            $_POST = array();
        } else {
            throw new Exception("Failed to add property");
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Get users for owner selection
$users = $pdo->query("SELECT id, full_name, email, role FROM users WHERE role = 'admin' OR role = 'super_admin' ORDER BY full_name")->fetchAll();

$page_title = 'Add Property';
?>

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
    .amenities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-plus-circle text-primary"></i> Add New Property</h4>
    <a href="properties.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Properties
    </a>
</div>

<?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <!-- Basic Information -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-info-circle"></i> Basic Information</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Property Title *</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Owner</label>
                <select name="owner_id" class="form-select">
                    <option value="0">-- Select Owner (or leave as admin) --</option>
                    <?php foreach($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?> (<?php echo $user['role']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Select an existing property owner or leave blank for admin</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Property Type *</label>
                <select name="property_type" class="form-select" required>
                    <option value="house">House</option>
                    <option value="apartment">Apartment</option>
                    <option value="bedsitter">Bedsitter</option>
                    <option value="single_room">Single Room</option>
                    <option value="hostel">Hostel</option>
                    <option value="commercial">Commercial</option>
                    <option value="land">Land</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Category *</label>
                <select name="category" class="form-select" id="categorySelect" required>
                    <option value="rent">For Rent</option>
                    <option value="sale">For Sale</option>
                    <option value="airbnb">Airbnb</option>
                </select>
            </div>
            <div class="col-md-6" id="rentPriceField">
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
    
    <!-- Location Details -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-map-marker-alt"></i> Location Details</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">County *</label>
                <select name="county_id" class="form-select" id="countySelect" required>
                    <option value="">Select County</option>
                    <?php foreach($counties as $county): ?>
                        <option value="<?php echo $county['id']; ?>"><?php echo $county['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Town/Area *</label>
                <select name="town_id" class="form-select" id="townSelect" required>
                    <option value="">Select Town</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Estate/Neighborhood</label>
                <input type="text" name="estate" class="form-control" placeholder="e.g., Kilimani, Westlands">
            </div>
            <div class="col-md-6">
                <label class="form-label">Street Address</label>
                <input type="text" name="street" class="form-control" placeholder="Street name or building">
            </div>
        </div>
    </div>
    
    <!-- Property Specifications -->
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
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5" placeholder="Describe the property in detail..."></textarea>
            </div>
        </div>
    </div>
    
    <!-- Amenities -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-wifi"></i> Amenities & Features</div>
        <div class="amenities-grid">
            <div class="form-check"><input type="checkbox" name="water_available" class="form-check-input"> <label class="form-check-label">💧 Water Available</label></div>
            <div class="form-check"><input type="checkbox" name="electricity_available" class="form-check-input"> <label class="form-check-label">⚡ Electricity</label></div>
            <div class="form-check"><input type="checkbox" name="wifi_available" class="form-check-input"> <label class="form-check-label">📶 WiFi</label></div>
            <div class="form-check"><input type="checkbox" name="security_available" class="form-check-input"> <label class="form-check-label">🛡️ Security</label></div>
            <div class="form-check"><input type="checkbox" name="cctv" class="form-check-input"> <label class="form-check-label">📹 CCTV</label></div>
            <div class="form-check"><input type="checkbox" name="borehole" class="form-check-input"> <label class="form-check-label">🚰 Borehole</label></div>
            <div class="form-check"><input type="checkbox" name="swimming_pool" class="form-check-input"> <label class="form-check-label">🏊 Swimming Pool</label></div>
            <div class="form-check"><input type="checkbox" name="gym" class="form-check-input"> <label class="form-check-label">💪 Gym</label></div>
            <div class="form-check"><input type="checkbox" name="backup_generator" class="form-check-input"> <label class="form-check-label">🔌 Backup Generator</label></div>
            <div class="form-check"><input type="checkbox" name="balcony" class="form-check-input"> <label class="form-check-label">🏞️ Balcony</label></div>
            <div class="form-check"><input type="checkbox" name="furnished" class="form-check-input"> <label class="form-check-label">🛋️ Furnished</label></div>
            <div class="form-check"><input type="checkbox" name="pets_allowed" class="form-check-input"> <label class="form-check-label">🐾 Pets Allowed</label></div>
        </div>
    </div>
    
    <!-- Package & Status -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-gem"></i> Package & Status</div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Listing Package</label>
                <select name="listing_package" class="form-select">
                    <option value="basic">Basic (KES 1,000) - 30 days</option>
                    <option value="premium">Premium (KES 2,500) - 60 days, Featured</option>
                    <option value="vip">VIP (KES 5,000) - 90 days, Featured + Priority</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Property Status</label>
                <select name="status" class="form-select">
                    <option value="approved">Approved (Live)</option>
                    <option value="pending_approval">Pending Approval</option>
                    <option value="pending_payment">Payment Pending</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="col-md-4">
                <div class="form-check mt-4">
                    <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured">
                    <label class="form-check-label" for="isFeatured">Mark as Featured Property</label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Photos -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-images"></i> Photos & Media</div>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Property Photos</label>
                <input type="file" name="images[]" class="form-control" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
                <small class="text-muted">You can select multiple images. First image will be the cover.</small>
            </div>
            <div class="col-12">
                <label class="form-label">Video Tour (YouTube URL)</label>
                <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
            </div>
        </div>
    </div>
    
    <div class="text-center">
        <button type="submit" name="add_property" class="btn btn-primary btn-lg px-5">
            <i class="fas fa-save"></i> Add Property
        </button>
        <a href="properties.php" class="btn btn-secondary btn-lg px-5 ms-2">
            Cancel
        </a>
    </div>
</form>

<script>
$(document).ready(function() {
    // Category change handler
    $('#categorySelect').change(function() {
        var category = $(this).val();
        if(category === 'rent' || category === 'airbnb') {
            $('#rentPriceField').show();
            $('#salePriceField').hide();
        } else if(category === 'sale') {
            $('#rentPriceField').hide();
            $('#salePriceField').show();
        }
    });
    
    // County change - load towns
    $('#countySelect').change(function() {
        var county_id = $(this).val();
        if(county_id) {
            $.ajax({
                url: '../ajax/get-towns.php',
                method: 'POST',
                data: {county_id: county_id},
                dataType: 'json',
                success: function(data) {
                    var townSelect = $('#townSelect');
                    townSelect.empty();
                    townSelect.append('<option value="">Select Town</option>');
                    $.each(data, function(index, town) {
                        townSelect.append('<option value="' + town.id + '">' + town.name + '</option>');
                    });
                }
            });
        }
    });
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>