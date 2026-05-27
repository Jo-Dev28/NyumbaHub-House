<?php
require_once 'includes/config.php';
$page_title = 'Find Your Dream Property in Kenya';

// Get featured properties
$stmt = $pdo->prepare("SELECT p.*, c.name as county_name, t.name as town_name, u.full_name as owner_name,
                        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                        FROM properties p
                        LEFT JOIN counties c ON p.county_id = c.id
                        LEFT JOIN towns t ON p.town_id = t.id
                        LEFT JOIN users u ON p.owner_id = u.id
                        WHERE p.status = 'approved' AND p.is_featured = 1
                        ORDER BY p.created_at DESC LIMIT 6");
$stmt->execute();
$featured_properties = $stmt->fetchAll();

// Get latest properties
$stmt = $pdo->prepare("SELECT p.*, c.name as county_name, t.name as town_name,
                        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                        FROM properties p
                        LEFT JOIN counties c ON p.county_id = c.id
                        LEFT JOIN towns t ON p.town_id = t.id
                        WHERE p.status = 'approved'
                        ORDER BY p.created_at DESC LIMIT 8");
$stmt->execute();
$latest_properties = $stmt->fetchAll();

// Process images for all properties - FIX IMAGE PATHS
foreach($featured_properties as &$property) {
    if(!empty($property['primary_image'])) {
        $property['primary_image'] = str_replace(SITE_URL, '', $property['primary_image']);
    }
}
foreach($latest_properties as &$property) {
    if(!empty($property['primary_image'])) {
        $property['primary_image'] = str_replace(SITE_URL, '', $property['primary_image']);
    }
}

// Get property stats
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM properties WHERE status = 'approved'");
$stats['total'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'owner'");
$stats['agents'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['users'] = $stmt->fetch()['total'];

// Get counties for search
$counties = $pdo->query("SELECT * FROM counties ORDER BY name LIMIT 10")->fetchAll();

require_once 'includes/header.php';
?>

<style>
    /* Hero Section */
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 85vh;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
        background-size: cover;
        opacity: 0.2;
    }
    
    .hero-content {
        position: relative;
        z-index: 1;
    }
    
    /* Category Cards */
    .category-card {
        background: white;
        border-radius: 20px;
        padding: 30px 20px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        cursor: pointer;
        height: 100%;
    }
    
    .category-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }
    
    .icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        transition: all 0.3s ease;
    }
    
    .category-card:hover .icon-wrapper {
        transform: scale(1.1);
        border-radius: 30px;
    }
    
    /* Property Card */
    .property-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .property-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .card-img-wrapper {
        position: relative;
        overflow: hidden;
        height: 220px;
        background: #f0f2f5;
    }
    
    .card-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .property-card:hover .card-img-wrapper img {
        transform: scale(1.1);
    }
    
    .card-price {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        padding: 20px 15px 10px;
        color: white;
    }
    
    .featured-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: linear-gradient(135deg, #ffc107, #ff9800);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        z-index: 1;
    }
    
    .verified-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(13, 110, 253, 0.9);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        z-index: 1;
    }
    
    .property-features {
        display: flex;
        justify-content: space-between;
        margin: 15px 0;
        padding: 10px 0;
        border-top: 1px solid #f0f0f0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .property-features span {
        color: #6c757d;
        font-size: 12px;
    }
    
    .property-features i {
        margin-right: 5px;
        color: #0d6efd;
    }
    
    /* Testimonial Card */
    .testimonial-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .testimonial-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    
    /* Step Icon */
    .step-icon {
        width: 80px;
        height: 80px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        transition: all 0.3s ease;
    }
    
    .step-icon:hover {
        transform: scale(1.1);
        background: rgba(255,255,255,0.2);
    }
    
    /* Search Box */
    .search-box {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .hero-section {
            min-height: auto;
            padding: 60px 0;
        }
        .display-3 {
            font-size: 2rem;
        }
        .search-box {
            padding: 20px;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content text-white" data-aos="fade-right">
                <h1 class="display-3 fw-bold mb-4">Find Your <span class="text-warning">Dream Property</span> in Kenya</h1>
                <p class="lead mb-4">Discover thousands of properties for rent, sale, and Airbnb across Kenya. Connect directly with owners and agents.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="properties.php" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-search"></i> Browse Properties
                    </a>
                    <?php if(!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-user-plus"></i> List Your Property
                        </a>
                    <?php endif; ?>
                </div>
                <div class="row mt-5">
                    <div class="col-4">
                        <h3 class="fw-bold mb-0"><?php echo number_format($stats['total']); ?>+</h3>
                        <p class="text-white-50">Properties</p>
                    </div>
                    <div class="col-4">
                        <h3 class="fw-bold mb-0"><?php echo number_format($stats['agents']); ?>+</h3>
                        <p class="text-white-50">Agents</p>
                    </div>
                    <div class="col-4">
                        <h3 class="fw-bold mb-0"><?php echo number_format($stats['users']); ?>+</h3>
                        <p class="text-white-50">Happy Clients</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mt-5 mt-lg-0" data-aos="fade-left">
                <div class="search-box">
                    <h4 class="mb-3"><i class="fas fa-search text-primary"></i> Quick Search</h4>
                    <form action="properties.php" method="GET">
                        <div class="mb-3">
                            <label class="form-label">What are you looking for?</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="rent">🏠 For Rent</option>
                                <option value="sale">💰 For Sale</option>
                                <option value="airbnb">🏖️ Airbnb / Short Stay</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <select name="county" class="form-select">
                                <option value="0">All Kenya</option>
                                <?php foreach($counties as $county): ?>
                                    <option value="<?php echo $county['id']; ?>"><?php echo $county['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Min Price (KES)</label>
                                <input type="number" name="min_price" class="form-control" placeholder="Min">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Max Price (KES)</label>
                                <input type="number" name="max_price" class="form-control" placeholder="Max">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bedrooms</label>
                            <select name="bedrooms" class="form-select">
                                <option value="0">Any</option>
                                <option value="1">1+ Bedroom</option>
                                <option value="2">2+ Bedrooms</option>
                                <option value="3">3+ Bedrooms</option>
                                <option value="4">4+ Bedrooms</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search Properties
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Property Categories -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Browse by <span class="text-primary">Property Type</span></h2>
            <p class="text-muted">Find exactly what you're looking for</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="0">
                <div class="category-card">
                    <div class="icon-wrapper bg-primary bg-opacity-10">
                        <i class="fas fa-home fa-2x text-primary"></i>
                    </div>
                    <h5>Houses for Rent</h5>
                    <p class="text-muted small">Browse Listings →</p>
                    <a href="properties.php?category=rent" class="stretched-link"></a>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="100">
                <div class="category-card">
                    <div class="icon-wrapper bg-success bg-opacity-10">
                        <i class="fas fa-hand-holding-usd fa-2x text-success"></i>
                    </div>
                    <h5>Houses for Sale</h5>
                    <p class="text-muted small">Browse Listings →</p>
                    <a href="properties.php?category=sale" class="stretched-link"></a>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="200">
                <div class="category-card">
                    <div class="icon-wrapper bg-info bg-opacity-10">
                        <i class="fas fa-umbrella-beach fa-2x text-info"></i>
                    </div>
                    <h5>Airbnb</h5>
                    <p class="text-muted small">Browse Listings →</p>
                    <a href="properties.php?category=airbnb" class="stretched-link"></a>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="300">
                <div class="category-card">
                    <div class="icon-wrapper bg-warning bg-opacity-10">
                        <i class="fas fa-store fa-2x text-warning"></i>
                    </div>
                    <h5>Commercial</h5>
                    <p class="text-muted small">Browse Listings →</p>
                    <a href="properties.php?property_type=commercial" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Properties -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">Featured <span class="text-primary">Properties</span></h2>
            <p class="text-muted">Hand-picked premium properties just for you</p>
        </div>
        
        <?php if(count($featured_properties) > 0): ?>
            <div class="row g-4">
                <?php foreach($featured_properties as $index => $property): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="property-card">
                        <div class="card-img-wrapper">
                            <?php 
                            $imagePath = !empty($property['primary_image']) ? SITE_URL . $property['primary_image'] : SITE_URL . 'assets/images/placeholder.jpg';
                            ?>
                            <img src="<?php echo $imagePath; ?>" 
                                 alt="<?php echo htmlspecialchars($property['title']); ?>"
                                 onerror="this.src='<?php echo SITE_URL; ?>assets/images/placeholder.jpg'">
                            <span class="featured-badge">
                                <i class="fas fa-star"></i> Featured
                            </span>
                            <div class="card-price">
                                <span class="fw-bold">
                                    KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?>
                                </span>
                                <?php echo $property['category'] != 'sale' ? '<small>/month</small>' : ''; ?>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-2"><?php echo htmlspecialchars(substr($property['title'], 0, 45)); ?></h6>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-map-marker-alt"></i> <?php echo $property['town_name'] . ', ' . $property['county_name']; ?>
                            </p>
                            <div class="property-features">
                                <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> beds</span>
                                <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> baths</span>
                                <span><i class="fas fa-car"></i> <?php echo $property['parking_slots']; ?> cars</span>
                            </div>
                            <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary w-100">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="properties.php" class="btn btn-outline-primary btn-lg">
                    View All Properties <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-home fa-4x text-muted mb-3"></i>
                <h5>No featured properties yet</h5>
                <p class="text-muted">Check back soon for premium listings</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Latest Properties -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5" data-aos="fade-up">
            <div>
                <h2 class="fw-bold mb-0">Latest <span class="text-primary">Properties</span></h2>
                <p class="text-muted">Recently added properties across Kenya</p>
            </div>
            <a href="properties.php" class="btn btn-outline-primary">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <?php if(count($latest_properties) > 0): ?>
            <div class="row g-4">
                <?php foreach($latest_properties as $index => $property): ?>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="property-card">
                        <div class="card-img-wrapper" style="height: 200px;">
                            <?php 
                            $imagePath = !empty($property['primary_image']) ? SITE_URL . $property['primary_image'] : SITE_URL . 'assets/images/placeholder.jpg';
                            ?>
                            <img src="<?php echo $imagePath; ?>" 
                                 alt="<?php echo htmlspecialchars($property['title']); ?>"
                                 onerror="this.src='<?php echo SITE_URL; ?>assets/images/placeholder.jpg'">
                            <div class="card-price">
                                <span class="fw-bold">
                                    KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-2"><?php echo htmlspecialchars(substr($property['title'], 0, 35)); ?></h6>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-map-marker-alt"></i> <?php echo $property['town_name']; ?>
                            </p>
                            <div class="d-flex justify-content-between mb-3 small">
                                <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?></span>
                                <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?></span>
                                <span><i class="fas fa-car"></i> <?php echo $property['parking_slots']; ?></span>
                            </div>
                            <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                View Property
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-home fa-4x text-muted mb-3"></i>
                <h5>No properties yet</h5>
                <p class="text-muted">Be the first to list a property</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- How It Works -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">How <span class="text-warning">NyumbaHub</span> Works</h2>
            <p class="text-white-50">Simple process to find or list your property</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3 text-center" data-aos="fade-up" data-aos-delay="0">
                <div class="step-icon mx-auto">
                    <i class="fas fa-search fa-2x"></i>
                </div>
                <h5>1. Search Property</h5>
                <p class="text-white-50">Search thousands of properties by location, price, and type</p>
            </div>
            <div class="col-md-3 text-center" data-aos="fade-up" data-aos-delay="100">
                <div class="step-icon mx-auto">
                    <i class="fas fa-eye fa-2x"></i>
                </div>
                <h5>2. View Details</h5>
                <p class="text-white-50">Browse photos, videos, amenities, and location details</p>
            </div>
            <div class="col-md-3 text-center" data-aos="fade-up" data-aos-delay="200">
                <div class="step-icon mx-auto">
                    <i class="fas fa-comment-dots fa-2x"></i>
                </div>
                <h5>3. Contact Owner</h5>
                <p class="text-white-50">Chat directly with property owners or agents</p>
            </div>
            <div class="col-md-3 text-center" data-aos="fade-up" data-aos-delay="300">
                <div class="step-icon mx-auto">
                    <i class="fas fa-handshake fa-2x"></i>
                </div>
                <h5>4. Make a Deal</h5>
                <p class="text-white-50">Schedule viewing and complete your property deal</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="fw-bold">What Our <span class="text-primary">Clients Say</span></h2>
            <p class="text-muted">Trusted by thousands of happy customers</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="testimonial-card">
                    <div class="mb-3 text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="mb-3">"Found my dream apartment in just 3 days! The platform is easy to use and the agents are very responsive."</p>
                    <div class="d-flex align-items-center">
                        <img src="assets/images/avatar1.jpg" class="rounded-circle me-3" width="50" height="50" alt="User" onerror="this.src='assets/images/placeholder-avatar.jpg'">
                        <div>
                            <h6 class="mb-0">John Mwangi</h6>
                            <small class="text-muted">Property Buyer</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="testimonial-card">
                    <div class="mb-3 text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="mb-3">"Listing my property was very easy. Got multiple inquiries within hours. Highly recommended!"</p>
                    <div class="d-flex align-items-center">
                        <img src="assets/images/avatar2.jpg" class="rounded-circle me-3" width="50" height="50" alt="User" onerror="this.src='assets/images/placeholder-avatar.jpg'">
                        <div>
                            <h6 class="mb-0">Sarah Kimani</h6>
                            <small class="text-muted">Property Owner</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="testimonial-card">
                    <div class="mb-3 text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="mb-3">"Best real estate platform in Kenya! Found tenants for my rental property within a week."</p>
                    <div class="d-flex align-items-center">
                        <img src="assets/images/avatar3.jpg" class="rounded-circle me-3" width="50" height="50" alt="User" onerror="this.src='assets/images/placeholder-avatar.jpg'">
                        <div>
                            <h6 class="mb-0">Michael Otieno</h6>
                            <small class="text-muted">Landlord</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 text-white text-center text-lg-start mb-4 mb-lg-0" data-aos="fade-right">
                <h2 class="fw-bold">Ready to Find Your Dream Property?</h2>
                <p class="lead mb-0 opacity-75">Join thousands of happy homeowners and tenants on NyumbaHub Kenya today!</p>
            </div>
            <div class="col-lg-4 text-center text-lg-end" data-aos="fade-left">
                <a href="register.php" class="btn btn-light btn-lg">
                    <i class="fas fa-user-plus"></i> Get Started Now
                </a>
            </div>
        </div>
    </div>
</section>

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
            },
            error: function() {
                Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>