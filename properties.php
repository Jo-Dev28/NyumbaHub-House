<?php
require_once 'includes/config.php';
$page_title = 'Browse Properties - Find Your Dream Home';

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$county = isset($_GET['county']) ? (int)$_GET['county'] : 0;
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;
$bedrooms = isset($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : 0;
$property_type = isset($_GET['property_type']) ? $_GET['property_type'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["p.status = 'approved'"];
$params = [];

if($category) {
    $where_conditions[] = "p.category = ?";
    $params[] = $category;
}
if($county) {
    $where_conditions[] = "p.county_id = ?";
    $params[] = $county;
}
if($min_price) {
    $where_conditions[] = "(p.price_rent >= ? OR p.price_sale >= ?)";
    $params[] = $min_price;
    $params[] = $min_price;
}
if($max_price) {
    $where_conditions[] = "(p.price_rent <= ? OR p.price_sale <= ?)";
    $params[] = $max_price;
    $params[] = $max_price;
}
if($bedrooms) {
    $where_conditions[] = "p.bedrooms >= ?";
    $params[] = $bedrooms;
}
if($property_type) {
    $where_conditions[] = "p.property_type = ?";
    $params[] = $property_type;
}

$where_clause = implode(" AND ", $where_conditions);

// Sort order
$order_by = "p.created_at DESC";
switch($sort) {
    case 'price_low':
        $order_by = "CASE WHEN p.category = 'sale' THEN p.price_sale ELSE p.price_rent END ASC";
        break;
    case 'price_high':
        $order_by = "CASE WHEN p.category = 'sale' THEN p.price_sale ELSE p.price_rent END DESC";
        break;
    case 'oldest':
        $order_by = "p.created_at ASC";
        break;
}

// Get total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM properties p WHERE $where_clause");
$count_stmt->execute($params);
$total_properties = $count_stmt->fetch()['total'];
$total_pages = ceil($total_properties / $limit);

// Get properties with proper image handling
$query = "SELECT p.*, c.name as county_name, t.name as town_name,
          (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
          (SELECT COUNT(*) FROM property_images WHERE property_id = p.id) as image_count
          FROM properties p
          LEFT JOIN counties c ON p.county_id = c.id
          LEFT JOIN towns t ON p.town_id = t.id
          WHERE $where_clause
          ORDER BY $order_by LIMIT $offset, $limit";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Process images to ensure correct paths
foreach($properties as &$property) {
    // Fix image path
    if(!empty($property['primary_image'])) {
        // Remove any duplicate SITE_URL
        $property['primary_image'] = str_replace(SITE_URL, '', $property['primary_image']);
    }
}

require_once 'includes/header.php';
?>

<style>
    /* Page Header */
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 60px 0 40px;
        margin-top: -20px;
        margin-bottom: 2rem;
    }
    
    /* Property Card Styles */
    .property-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
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
    
    .card-price .price-amount {
        font-size: 1.1rem;
        font-weight: 700;
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
    
    .property-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 8px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .property-location {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 10px;
    }
    
    .property-location i {
        margin-right: 4px;
        font-size: 0.7rem;
    }
    
    /* Filter Sidebar */
    .filter-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
    }
    
    .filter-card .card-header {
        background: white;
        border-bottom: 1px solid #f0f0f0;
        padding: 20px 25px 15px;
        border-radius: 20px 20px 0 0;
    }
    
    .filter-card .card-body {
        padding: 20px 25px;
    }
    
    /* Pagination */
    .pagination .page-link {
        border-radius: 10px;
        margin: 0 3px;
        border: none;
        padding: 8px 15px;
        color: #475569;
        transition: all 0.3s ease;
    }
    
    .pagination .page-link:hover {
        background: #0d6efd;
        color: white;
        transform: translateY(-2px);
    }
    
    .pagination .active .page-link {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
    }
    
    /* Buttons */
    .btn-view {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-view:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(13,110,253,0.3);
        color: white;
    }
    
    .btn-save {
        background: transparent;
        border: 1px solid #dc3545;
        color: #dc3545;
        padding: 8px 12px;
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .btn-save:hover {
        background: #dc3545;
        color: white;
        transform: translateY(-2px);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            padding: 40px 0 30px;
        }
        .page-header h1 {
            font-size: 1.8rem;
        }
        .card-img-wrapper {
            height: 180px;
        }
        .property-features {
            font-size: 0.7rem;
        }
    }
</style>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center text-white">
                <h1 class="fw-bold display-5">Browse Properties</h1>
                <p class="lead mb-0 opacity-75">Find your perfect property from thousands of listings across Kenya</p>
            </div>
        </div>
    </div>
</section>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4 mb-lg-0">
            <div class="filter-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter text-primary me-2"></i> Filter Properties</h5>
                </div>
                <div class="card-body">
                    <form method="GET" id="filterForm">
                        <?php if(isset($_GET['page'])): ?>
                            <input type="hidden" name="page" value="<?php echo $page; ?>">
                        <?php endif; ?>
                        
                        <!-- Category Filter -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <option value="rent" <?php echo $category == 'rent' ? 'selected' : ''; ?>>🏠 For Rent</option>
                                <option value="sale" <?php echo $category == 'sale' ? 'selected' : ''; ?>>💰 For Sale</option>
                                <option value="airbnb" <?php echo $category == 'airbnb' ? 'selected' : ''; ?>>🏖️ Airbnb</option>
                            </select>
                        </div>
                        
                        <!-- Location Filter -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2">Location</label>
                            <select name="county" class="form-select" id="county_filter">
                                <option value="0">All Counties</option>
                                <?php
                                $counties = $pdo->query("SELECT * FROM counties ORDER BY name")->fetchAll();
                                foreach($counties as $county_item): ?>
                                    <option value="<?php echo $county_item['id']; ?>" <?php echo $county == $county_item['id'] ? 'selected' : ''; ?>>
                                        <?php echo $county_item['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2">Price Range (KES)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo $min_price ?: ''; ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo $max_price ?: ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bedrooms -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2">Bedrooms</label>
                            <select name="bedrooms" class="form-select">
                                <option value="0">Any</option>
                                <option value="1" <?php echo $bedrooms == 1 ? 'selected' : ''; ?>>1+ Bedroom</option>
                                <option value="2" <?php echo $bedrooms == 2 ? 'selected' : ''; ?>>2+ Bedrooms</option>
                                <option value="3" <?php echo $bedrooms == 3 ? 'selected' : ''; ?>>3+ Bedrooms</option>
                                <option value="4" <?php echo $bedrooms == 4 ? 'selected' : ''; ?>>4+ Bedrooms</option>
                                <option value="5" <?php echo $bedrooms == 5 ? 'selected' : ''; ?>>5+ Bedrooms</option>
                            </select>
                        </div>
                        
                        <!-- Property Type -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2">Property Type</label>
                            <select name="property_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="house" <?php echo $property_type == 'house' ? 'selected' : ''; ?>>🏠 House</option>
                                <option value="apartment" <?php echo $property_type == 'apartment' ? 'selected' : ''; ?>>🏢 Apartment</option>
                                <option value="bedsitter" <?php echo $property_type == 'bedsitter' ? 'selected' : ''; ?>>🛏️ Bedsitter</option>
                                <option value="commercial" <?php echo $property_type == 'commercial' ? 'selected' : ''; ?>>🏪 Commercial</option>
                                <option value="land" <?php echo $property_type == 'land' ? 'selected' : ''; ?>>🌾 Land</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="properties.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Properties List -->
        <div class="col-lg-9">
            <!-- Results Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0"><?php echo number_format($total_properties); ?> Properties Found</h4>
                    <small class="text-muted">
                        Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_properties); ?> of <?php echo number_format($total_properties); ?>
                    </small>
                </div>
                <div class="mt-2 mt-sm-0">
                    <select class="form-select" id="sortBy" style="width: 200px;">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>📅 Newest First</option>
                        <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>📅 Oldest First</option>
                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>💰 Price: Low to High</option>
                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>💰 Price: High to Low</option>
                    </select>
                </div>
            </div>
            
            <?php if(count($properties) > 0): ?>
                <div class="row g-4">
                    <?php foreach($properties as $index => $property): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="property-card">
                            <div class="card-img-wrapper">
                                <?php
                                // Get image path with proper fallback
                                $imagePath = !empty($property['primary_image']) ? SITE_URL . $property['primary_image'] : SITE_URL . 'assets/images/placeholder.jpg';
                                ?>
                                <img src="<?php echo $imagePath; ?>" 
                                     alt="<?php echo htmlspecialchars($property['title']); ?>"
                                     onerror="this.src='<?php echo SITE_URL; ?>assets/images/placeholder.jpg'">
                                     
                                <?php if($property['is_featured']): ?>
                                    <span class="featured-badge">
                                        <i class="fas fa-star"></i> Featured
                                    </span>
                                <?php endif; ?>
                                
                                <?php if($property['is_verified']): ?>
                                    <span class="verified-badge">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                <?php endif; ?>
                                
                                <div class="card-price">
                                    <span class="price-amount">
                                        KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?>
                                    </span>
                                    <?php if($property['category'] != 'sale'): ?>
                                        <small>/month</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body p-3 d-flex flex-column flex-grow-1">
                                <h6 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h6>
                                <p class="property-location">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $property['town_name'] . ', ' . $property['county_name']; ?>
                                </p>
                                <div class="property-features">
                                    <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> beds</span>
                                    <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> baths</span>
                                    <span><i class="fas fa-car"></i> <?php echo $property['parking_slots']; ?> cars</span>
                                </div>
                                <div class="d-flex gap-2 mt-auto">
                                    <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn-view flex-grow-1 text-center">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <?php if(isLoggedIn()): ?>
                                        <button class="btn-save save-property" data-id="<?php echo $property['id']; ?>">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for($i = $start; $i <= $end; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- No Results -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-home fa-5x text-muted"></i>
                    </div>
                    <h4 class="fw-bold">No Properties Found</h4>
                    <p class="text-muted mb-4">Try adjusting your filters or search criteria</p>
                    <a href="properties.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-sync-alt"></i> Clear All Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Sort functionality
    $('#sortBy').change(function() {
        var url = new URL(window.location.href);
        url.searchParams.set('sort', $(this).val());
        window.location.href = url.toString();
    });
    
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
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php require_once 'includes/footer.php'; ?>