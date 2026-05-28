<?php
ob_start();
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

// Handle property actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $property_id = (int)$_GET['id'];
    
    if($_GET['action'] == 'approve') {
        $stmt = $pdo->prepare("UPDATE properties SET status = 'approved', is_featured = 1 WHERE id = ?");
        $stmt->execute([$property_id]);
        // Add notification to owner
        $stmt2 = $pdo->prepare("SELECT owner_id, title FROM properties WHERE id = ?");
        $stmt2->execute([$property_id]);
        $prop = $stmt2->fetch();
        addNotification($prop['owner_id'], "Property Approved", "Your property '{$prop['title']}' has been approved and is now live.");
        $_SESSION['success'] = "Property approved successfully";
    } elseif($_GET['action'] == 'reject') {
        $stmt = $pdo->prepare("UPDATE properties SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$property_id]);
        // Add notification to owner
        $stmt2 = $pdo->prepare("SELECT owner_id, title FROM properties WHERE id = ?");
        $stmt2->execute([$property_id]);
        $prop = $stmt2->fetch();
        addNotification($prop['owner_id'], "Property Rejected", "Your property '{$prop['title']}' has been rejected. Please check your listing and resubmit.");
        $_SESSION['success'] = "Property rejected";
    } elseif($_GET['action'] == 'feature') {
        $stmt = $pdo->prepare("UPDATE properties SET is_featured = 1 WHERE id = ?");
        $stmt->execute([$property_id]);
        $_SESSION['success'] = "Property featured";
    } elseif($_GET['action'] == 'unfeature') {
        $stmt = $pdo->prepare("UPDATE properties SET is_featured = 0 WHERE id = ?");
        $stmt->execute([$property_id]);
        $_SESSION['success'] = "Property unfeatured";
    } elseif($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
        $stmt->execute([$property_id]);
        $_SESSION['success'] = "Property deleted";
    }
    header("Location: properties.php");
    exit();
}

// Search and filters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : 'all';

// Build query
$query = "SELECT p.*, u.full_name as owner_name 
          FROM properties p 
          JOIN users u ON p.owner_id = u.id 
          WHERE 1=1";
$params = [];

if($search) {
    $query .= " AND (p.title LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($status_filter != 'all') {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
}

if($category_filter != 'all') {
    $query .= " AND p.category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll();

$page_title = 'Property Management';
?>

<style>
    .search-filters {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 20px;
    }
    .badge-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
    }
    .badge-approved { background: #d4edda; color: #155724; }
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-rejected { background: #f8d7da; color: #721c24; }
    .badge-payment { background: #cce5ff; color: #004085; }
    .property-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
    }
</style>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0"><i class="fas fa-building text-primary"></i> Property Management</h5>
            <?php if($_SESSION['user_role'] == 'super_admin'): ?>
            <a href="add-property.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus-circle"></i> Add Property
            </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Search and Filters -->
        <div class="search-filters">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Property title or owner name..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="pending_approval" <?php echo $status_filter == 'pending_approval' ? 'selected' : ''; ?>>Pending Approval</option>
                        <option value="pending_payment" <?php echo $status_filter == 'pending_payment' ? 'selected' : ''; ?>>Payment Pending</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="all" <?php echo $category_filter == 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <option value="rent" <?php echo $category_filter == 'rent' ? 'selected' : ''; ?>>For Rent</option>
                        <option value="sale" <?php echo $category_filter == 'sale' ? 'selected' : ''; ?>>For Sale</option>
                        <option value="airbnb" <?php echo $category_filter == 'airbnb' ? 'selected' : ''; ?>>Airbnb</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Owner</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($properties) > 0): ?>
                        <?php foreach($properties as $property): 
                            $stmt = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ? AND is_primary = 1 LIMIT 1");
                            $stmt->execute([$property['id']]);
                            $image = $stmt->fetch();
                            
                            $statusClass = '';
                            if($property['status'] == 'approved') $statusClass = 'badge-approved';
                            elseif($property['status'] == 'pending_approval') $statusClass = 'badge-pending';
                            elseif($property['status'] == 'pending_payment') $statusClass = 'badge-payment';
                            else $statusClass = 'badge-rejected';
                        ?>
                        <tr>
                            <td>#<?php echo $property['id']; ?></td>
                            <td>
                                <img src="<?php echo $image ? SITE_URL . $image['image_path'] : SITE_URL . 'assets/images/placeholder.jpg'; ?>" 
                                     class="property-img" 
                                     onerror="this.src='<?php echo SITE_URL; ?>assets/images/placeholder.jpg'">
                            </td>
                            <td>
                                <a href="view-property.php?id=<?php echo $property['id']; ?>" class="text-decoration-none fw-bold">
                                    <?php echo htmlspecialchars(substr($property['title'], 0, 35)); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($property['owner_name']); ?></td>
                            <td>KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?> <?php echo $property['category'] != 'sale' ? '<small>/mo</small>' : ''; ?></td>
                            <td>
                                <span class="badge-status <?php echo $statusClass; ?>">
                                    <?php echo str_replace('_', ' ', $property['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($property['is_featured']): ?>
                                    <span class="badge bg-warning"><i class="fas fa-star"></i> Featured</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="view-property.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Property">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($property['status'] == 'pending_approval'): ?>
                                        <a href="?action=approve&id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-success" title="Approve" onclick="return confirm('Approve this property?')">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="?action=reject&id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-danger" title="Reject" onclick="return confirm('Reject this property?')">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if($property['is_featured']): ?>
                                        <a href="?action=unfeature&id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-warning" title="Remove Featured">
                                            <i class="fas fa-star"></i>
                                        </a>
                                    <?php else: ?>
                                        <?php if($property['status'] == 'approved'): ?>
                                        <a href="?action=feature&id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-info" title="Make Featured">
                                            <i class="far fa-star"></i>
                                        </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this property? This action cannot be undone!')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <h5>No properties found</h5>
                                <p class="text-muted">Try adjusting your search criteria</p>
                             </td
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>