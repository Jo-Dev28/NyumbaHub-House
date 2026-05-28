<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

// If user is admin or super admin, redirect to their dashboard
if(isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'super_admin')) {
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get recent properties
$stmt = $pdo->prepare("SELECT p.*, c.name as county_name, t.name as town_name,
                        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                        FROM properties p
                        LEFT JOIN counties c ON p.county_id = c.id
                        LEFT JOIN towns t ON p.town_id = t.id
                        WHERE p.status = 'approved'
                        ORDER BY p.created_at DESC LIMIT 6");
$stmt->execute();
$recent_properties = $stmt->fetchAll();

// Process images
foreach($recent_properties as &$property) {
    if(!empty($property['primary_image'])) {
        $property['primary_image'] = str_replace(SITE_URL, '', $property['primary_image']);
    }
}

// Get favorites count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorites_count = $stmt->fetch()['count'];

// Get inquiries count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM property_inquiries WHERE user_id = ?");
$stmt->execute([$user_id]);
$inquiries_count = $stmt->fetch()['count'];

// Get messages count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetch()['count'];

$page_title = 'Dashboard';
require_once 'includes/header.php';
?>

<style>
    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 1.8rem;
    }
    .property-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        height: 100%;
    }
    .property-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .card-img-wrapper {
        height: 200px;
        overflow: hidden;
        background: #f0f2f5;
    }
    .card-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    .property-card:hover .card-img-wrapper img {
        transform: scale(1.1);
    }
    .welcome-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
    }
    .quick-link-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
        text-decoration: none;
        display: block;
    }
    .quick-link-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .quick-link-card i {
        font-size: 2rem;
        margin-bottom: 1rem;
        display: block;
    }
</style>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <?php include 'includes/user-sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="fw-bold mb-2">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
                        <p class="mb-0 opacity-75">Find your dream property from thousands of listings across Kenya</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="properties.php" class="btn btn-light">
                            <i class="fas fa-search"></i> Browse Properties
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3 class="mb-0"><?php echo $favorites_count; ?></h3>
                        <p class="text-muted mb-0">Saved Properties</p>
                        <a href="favorites.php" class="btn btn-sm btn-link mt-2">View Favorites</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3 class="mb-0"><?php echo $inquiries_count; ?></h3>
                        <p class="text-muted mb-0">Inquiries Sent</p>
                        <a href="my-inquiries.php" class="btn btn-sm btn-link mt-2">View Inquiries</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <h3 class="mb-0"><?php echo $unread_count; ?></h3>
                        <p class="text-muted mb-0">Unread Messages</p>
                        <a href="messages.php" class="btn btn-sm btn-link mt-2">View Messages</a>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <a href="favorites.php" class="quick-link-card">
                        <i class="fas fa-heart text-danger"></i>
                        <h5>My Favorites</h5>
                        <p class="text-muted small">View your saved properties</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="my-inquiries.php" class="quick-link-card">
                        <i class="fas fa-envelope text-primary"></i>
                        <h5>My Inquiries</h5>
                        <p class="text-muted small">Track your property inquiries</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="messages.php" class="quick-link-card">
                        <i class="fas fa-comment-dots text-success"></i>
                        <h5>Messages</h5>
                        <p class="text-muted small">Chat with property owners</p>
                    </a>
                </div>
            </div>
            
            <!-- Recent Properties -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold">Recently Added Properties</h4>
                <a href="properties.php" class="btn btn-outline-primary btn-sm">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <?php if(count($recent_properties) > 0): ?>
                <div class="row g-4">
                    <?php foreach($recent_properties as $property): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="property-card">
                            <div class="card-img-wrapper">
                                <img src="<?php echo !empty($property['primary_image']) ? SITE_URL . $property['primary_image'] : SITE_URL . 'assets/images/placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($property['title']); ?>"
                                     onerror="this.src='<?php echo SITE_URL; ?>assets/images/placeholder.jpg'">
                            </div>
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-2"><?php echo htmlspecialchars(substr($property['title'], 0, 40)); ?></h6>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $property['town_name'] . ', ' . $property['county_name']; ?>
                                </p>
                                <div class="d-flex justify-content-between mb-3">
                                    <small><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> beds</small>
                                    <small><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> baths</small>
                                    <small><i class="fas fa-car"></i> <?php echo $property['parking_slots']; ?> cars</small>
                                </div>
                                <h6 class="text-primary mb-3">
                                    KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?>
                                    <?php echo $property['category'] != 'sale' ? '<small>/month</small>' : ''; ?>
                                </h6>
                                <div class="d-flex gap-2">
                                    <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-sm flex-grow-1">
                                        View Details
                                    </a>
                                    <button class="btn btn-outline-danger btn-sm save-property" data-id="<?php echo $property['id']; ?>">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4">
                    <i class="fas fa-home fa-4x text-muted mb-3"></i>
                    <h5>No Properties Yet</h5>
                    <p class="text-muted">Check back later for new listings</p>
                </div>
            <?php endif; ?>
            
            <!-- Help Section -->
            <div class="row g-4 mt-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5><i class="fas fa-question-circle text-primary"></i> Need Help Finding a Property?</h5>
                            <p class="text-muted">Our team is here to help you find your dream home. Contact us for personalized assistance.</p>
                            <a href="contact.php" class="btn btn-outline-primary">Contact Support</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5><i class="fas fa-bell text-primary"></i> Property Alerts</h5>
                            <p class="text-muted">Get notified when new properties matching your criteria are listed.</p>
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#alertModal">Set Alert Preferences</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Modal -->
<div class="modal fade" id="alertModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title"><i class="fas fa-bell text-primary"></i> Property Alert Preferences</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="alertForm">
                    <div class="mb-3">
                        <label class="form-label">Property Type</label>
                        <select class="form-select">
                            <option>All Properties</option>
                            <option>For Rent</option>
                            <option>For Sale</option>
                            <option>Airbnb</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select class="form-select">
                            <option>All Kenya</option>
                            <option>Nairobi</option>
                            <option>Mombasa</option>
                            <option>Kisumu</option>
                            <option>Nakuru</option>
                            <option>Kiambu</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price Range (KES)</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="number" class="form-control" placeholder="Min">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control" placeholder="Max">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Alert Preferences</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Save property functionality
    $('.save-property').click(function() {
        var property_id = $(this).data('id');
        var btn = $(this);
        
        $.ajax({
            url: 'ajax/save-property.php',
            method: 'POST',
            data: {property_id: property_id},
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    if(data.action === 'saved') {
                        btn.html('<i class="fas fa-heart"></i>');
                        btn.css('background', '#dc3545');
                        btn.css('color', 'white');
                    } else {
                        btn.html('<i class="far fa-heart"></i>');
                        btn.css('background', 'transparent');
                        btn.css('color', '#dc3545');
                    }
                } else {
                    if(data.redirect) {
                        window.location.href = 'login.php';
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                }
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>