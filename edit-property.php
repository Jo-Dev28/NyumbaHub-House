<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get property details
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND owner_id = ?");
$stmt->execute([$property_id, $_SESSION['user_id']]);
$property = $stmt->fetch();

if(!$property) {
    redirect('my-properties.php');
}

// Get property images
$stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, image_order ASC");
$stmt->execute([$property_id]);
$images = $stmt->fetchAll();

// Get property video
$stmt = $pdo->prepare("SELECT * FROM property_videos WHERE property_id = ? LIMIT 1");
$stmt->execute([$property_id]);
$video = $stmt->fetch();

// Get counties
$counties = $pdo->query("SELECT * FROM counties ORDER BY name")->fetchAll();

// Get towns for selected county
$towns = [];
if($property['county_id']) {
    $stmt = $pdo->prepare("SELECT * FROM towns WHERE county_id = ? ORDER BY name");
    $stmt->execute([$property['county_id']]);
    $towns = $stmt->fetchAll();
}

$error = '';
$success = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_property'])) {
    try {
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
        
        // Update property
        $stmt = $pdo->prepare("UPDATE properties SET 
            title = ?, property_type = ?, category = ?, price_rent = ?, price_sale = ?,
            service_charge = ?, county_id = ?, town_id = ?, estate = ?, street = ?,
            bedrooms = ?, bathrooms = ?, parking_slots = ?, square_feet = ?, description = ?,
            water_available = ?, electricity_available = ?, wifi_available = ?, security_available = ?,
            cctv = ?, borehole = ?, swimming_pool = ?, gym = ?, backup_generator = ?,
            balcony = ?, furnished = ?, pets_allowed = ?, updated_at = NOW()
            WHERE id = ? AND owner_id = ?");
        
        if($stmt->execute([
            $title, $property_type, $category, $price_rent, $price_sale,
            $service_charge, $county_id, $town_id, $estate, $street,
            $bedrooms, $bathrooms, $parking_slots, $square_feet, $description,
            $water_available, $electricity_available, $wifi_available, $security_available,
            $cctv, $borehole, $swimming_pool, $gym, $backup_generator,
            $balcony, $furnished, $pets_allowed, $property_id, $_SESSION['user_id']
        ])) {
            // Handle new image uploads
            $upload_dir = 'uploads/properties/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            if(isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
                foreach($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                    if($_FILES['new_images']['error'][$key] == 0) {
                        $filename = time() . '_' . $key . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['new_images']['name'][$key]);
                        $filepath = $upload_dir . $filename;
                        if(move_uploaded_file($tmp_name, $filepath)) {
                            $img_stmt = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_primary, image_order) VALUES (?, ?, 0, ?)");
                            $img_stmt->execute([$property_id, $filepath, count($images) + $key]);
                        }
                    }
                }
            }
            
            // Handle video URL
            $video_url = sanitize($_POST['video_url']);
            if($video) {
                $stmt = $pdo->prepare("UPDATE property_videos SET video_url = ? WHERE property_id = ?");
                $stmt->execute([$video_url, $property_id]);
            } else if(!empty($video_url)) {
                $stmt = $pdo->prepare("INSERT INTO property_videos (property_id, video_url, video_type) VALUES (?, ?, 'youtube')");
                $stmt->execute([$property_id, $video_url]);
            }
            
            $success = "Property updated successfully!";
            
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
            $stmt->execute([$property_id]);
            $property = $stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, image_order ASC");
            $stmt->execute([$property_id]);
            $images = $stmt->fetchAll();
        } else {
            throw new Exception("Failed to update property");
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle image deletion
if(isset($_GET['delete_image'])) {
    $image_id = (int)$_GET['delete_image'];
    $stmt = $pdo->prepare("SELECT image_path FROM property_images WHERE id = ? AND property_id = ?");
    $stmt->execute([$image_id, $property_id]);
    $image = $stmt->fetch();
    if($image) {
        if(file_exists($image['image_path'])) {
            unlink($image['image_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM property_images WHERE id = ?");
        $stmt->execute([$image_id]);
        redirect("edit-property.php?id=$property_id&msg=image_deleted");
    }
}

// Handle set primary image
if(isset($_GET['set_primary'])) {
    $image_id = (int)$_GET['set_primary'];
    $stmt = $pdo->prepare("UPDATE property_images SET is_primary = 0 WHERE property_id = ?");
    $stmt->execute([$property_id]);
    $stmt = $pdo->prepare("UPDATE property_images SET is_primary = 1 WHERE id = ? AND property_id = ?");
    $stmt->execute([$image_id, $property_id]);
    redirect("edit-property.php?id=$property_id&msg=primary_set");
}

$page_title = 'Edit Property';
require_once 'includes/header.php';
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
    .existing-images {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    .image-item {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e0e0e0;
    }
    .image-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    .image-actions {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        padding: 5px;
        display: flex;
        justify-content: center;
        gap: 10px;
    }
    .image-actions a {
        color: white;
        font-size: 12px;
        padding: 5px;
    }
    .primary-badge {
        position: absolute;
        top: 5px;
        left: 5px;
        background: #28a745;
        color: white;
        padding: 3px 8px;
        border-radius: 15px;
        font-size: 10px;
    }
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
    @media (max-width: 768px) {
        .form-section { padding: 20px; }
    }
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'includes/user-sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="mb-4">
                <h2 class="fw-bold mb-2"><i class="fas fa-edit text-primary"></i> Edit Property</h2>
                <p class="text-muted">Update your property details</p>
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
                    <div class="form-section-title">
                        <i class="fas fa-info-circle"></i> Basic Information
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">Property Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($property['title']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Property Type</label>
                            <select name="property_type" class="form-select" required>
                                <option value="house" <?php echo $property['property_type'] == 'house' ? 'selected' : ''; ?>>🏠 House</option>
                                <option value="apartment" <?php echo $property['property_type'] == 'apartment' ? 'selected' : ''; ?>>🏢 Apartment</option>
                                <option value="bedsitter" <?php echo $property['property_type'] == 'bedsitter' ? 'selected' : ''; ?>>🛏️ Bedsitter</option>
                                <option value="single_room" <?php echo $property['property_type'] == 'single_room' ? 'selected' : ''; ?>>🚪 Single Room</option>
                                <option value="hostel" <?php echo $property['property_type'] == 'hostel' ? 'selected' : ''; ?>>🏘️ Hostel</option>
                                <option value="commercial" <?php echo $property['property_type'] == 'commercial' ? 'selected' : ''; ?>>🏪 Commercial</option>
                                <option value="land" <?php echo $property['property_type'] == 'land' ? 'selected' : ''; ?>>🌾 Land</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Category</label>
                            <select name="category" class="form-select" id="categorySelect" required>
                                <option value="rent" <?php echo $property['category'] == 'rent' ? 'selected' : ''; ?>>🏠 For Rent</option>
                                <option value="sale" <?php echo $property['category'] == 'sale' ? 'selected' : ''; ?>>💰 For Sale</option>
                                <option value="airbnb" <?php echo $property['category'] == 'airbnb' ? 'selected' : ''; ?>>🏖️ Airbnb</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="rentPriceField">
                            <label class="form-label">Rent Price (KES/month)</label>
                            <input type="number" name="price_rent" class="form-control" value="<?php echo $property['price_rent']; ?>" placeholder="e.g., 50000">
                        </div>
                        <div class="col-md-6" id="salePriceField">
                            <label class="form-label">Sale Price (KES)</label>
                            <input type="number" name="price_sale" class="form-control" value="<?php echo $property['price_sale']; ?>" placeholder="e.g., 5000000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service Charge (KES/month)</label>
                            <input type="number" name="service_charge" class="form-control" value="<?php echo $property['service_charge']; ?>" placeholder="If applicable">
                        </div>
                    </div>
                </div>
                
                <!-- Location Details -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-map-marker-alt"></i> Location Details
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">County</label>
                            <select name="county_id" class="form-select" id="countySelect" required>
                                <option value="">Select County</option>
                                <?php foreach($counties as $county): ?>
                                    <option value="<?php echo $county['id']; ?>" <?php echo $property['county_id'] == $county['id'] ? 'selected' : ''; ?>>
                                        <?php echo $county['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Town/Area</label>
                            <select name="town_id" class="form-select" id="townSelect" required>
                                <option value="">Select Town</option>
                                <?php foreach($towns as $town): ?>
                                    <option value="<?php echo $town['id']; ?>" <?php echo $property['town_id'] == $town['id'] ? 'selected' : ''; ?>>
                                        <?php echo $town['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estate/Neighborhood</label>
                            <input type="text" name="estate" class="form-control" value="<?php echo htmlspecialchars($property['estate']); ?>" placeholder="e.g., Kilimani, Westlands">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Street Address</label>
                            <input type="text" name="street" class="form-control" value="<?php echo htmlspecialchars($property['street']); ?>" placeholder="Street name or building">
                        </div>
                    </div>
                </div>
                
                <!-- Property Specifications -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-home"></i> Property Specifications
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Bedrooms</label>
                            <input type="number" name="bedrooms" class="form-control" value="<?php echo $property['bedrooms']; ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bathrooms</label>
                            <input type="number" name="bathrooms" class="form-control" value="<?php echo $property['bathrooms']; ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Parking Slots</label>
                            <input type="number" name="parking_slots" class="form-control" value="<?php echo $property['parking_slots']; ?>" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Square Feet</label>
                            <input type="number" name="square_feet" class="form-control" value="<?php echo $property['square_feet']; ?>" placeholder="Area in sqft">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Description</label>
                            <textarea name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Amenities -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-wifi"></i> Amenities & Features
                    </div>
                    <div class="amenities-grid">
                        <div class="amenity-item">
                            <input type="checkbox" name="water_available" id="water" <?php echo $property['water_available'] ? 'checked' : ''; ?>> 
                            <label for="water">💧 Water Available</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="electricity_available" id="electricity" <?php echo $property['electricity_available'] ? 'checked' : ''; ?>> 
                            <label for="electricity">⚡ Electricity</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="wifi_available" id="wifi" <?php echo $property['wifi_available'] ? 'checked' : ''; ?>> 
                            <label for="wifi">📶 WiFi</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="security_available" id="security" <?php echo $property['security_available'] ? 'checked' : ''; ?>> 
                            <label for="security">🛡️ Security</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="cctv" id="cctv" <?php echo $property['cctv'] ? 'checked' : ''; ?>> 
                            <label for="cctv">📹 CCTV</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="borehole" id="borehole" <?php echo $property['borehole'] ? 'checked' : ''; ?>> 
                            <label for="borehole">🚰 Borehole</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="swimming_pool" id="pool" <?php echo $property['swimming_pool'] ? 'checked' : ''; ?>> 
                            <label for="pool">🏊 Swimming Pool</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="gym" id="gym" <?php echo $property['gym'] ? 'checked' : ''; ?>> 
                            <label for="gym">💪 Gym</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="backup_generator" id="generator" <?php echo $property['backup_generator'] ? 'checked' : ''; ?>> 
                            <label for="generator">🔌 Backup Generator</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="balcony" id="balcony" <?php echo $property['balcony'] ? 'checked' : ''; ?>> 
                            <label for="balcony">🏞️ Balcony</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="furnished" id="furnished" <?php echo $property['furnished'] ? 'checked' : ''; ?>> 
                            <label for="furnished">🛋️ Furnished</label>
                        </div>
                        <div class="amenity-item">
                            <input type="checkbox" name="pets_allowed" id="pets" <?php echo $property['pets_allowed'] ? 'checked' : ''; ?>> 
                            <label for="pets">🐾 Pets Allowed</label>
                        </div>
                    </div>
                </div>
                
                <!-- Photos -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-images"></i> Photos & Media
                    </div>
                    
                    <?php if(count($images) > 0): ?>
                        <label class="form-label">Current Images</label>
                        <div class="existing-images">
                            <?php foreach($images as $image): ?>
                            <div class="image-item">
                                <img src="<?php echo SITE_URL . $image['image_path']; ?>" alt="Property Image">
                                <?php if($image['is_primary']): ?>
                                    <div class="primary-badge"><i class="fas fa-star"></i> Primary</div>
                                <?php endif; ?>
                                <div class="image-actions">
                                    <?php if(!$image['is_primary']): ?>
                                        <a href="?id=<?php echo $property_id; ?>&set_primary=<?php echo $image['id']; ?>" class="text-success">
                                            <i class="fas fa-star"></i> Set Primary
                                        </a>
                                    <?php endif; ?>
                                    <a href="?id=<?php echo $property_id; ?>&delete_image=<?php echo $image['id']; ?>" class="text-danger" onclick="return confirm('Are you sure you want to delete this image?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">No images uploaded yet.</div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <label class="form-label">Add More Images</label>
                        <input type="file" name="new_images[]" class="form-control" multiple accept="image/jpeg,image/png,image/jpg,image/webp">
                        <small class="text-muted">You can select multiple images</small>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label">Video Tour (YouTube URL)</label>
                        <input type="url" name="video_url" class="form-control" value="<?php echo $video['video_url'] ?? ''; ?>" placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="my-properties.php" class="btn btn-secondary px-4">Cancel</a>
                    <button type="submit" name="update_property" class="btn btn-primary px-4">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
    $('#categorySelect').trigger('change');
    
    // County change - load towns
    $('#countySelect').change(function() {
        var county_id = $(this).val();
        if(county_id) {
            $.ajax({
                url: 'ajax/get-towns.php',
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

<?php require_once 'includes/footer.php'; ?>