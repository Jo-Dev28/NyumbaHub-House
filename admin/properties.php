<?php
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

// Handle property actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $property_id = (int)$_GET['id'];
    
    if($_GET['action'] == 'approve') {
        $stmt = $pdo->prepare("UPDATE properties SET status = 'approved', is_featured = 1 WHERE id = ?");
        $stmt->execute([$property_id]);
        $_SESSION['success'] = "Property approved successfully";
    } elseif($_GET['action'] == 'reject') {
        $stmt = $pdo->prepare("UPDATE properties SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$property_id]);
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
    redirect('properties.php');
}

// Get all properties
$stmt = $pdo->query("SELECT p.*, u.full_name as owner_name 
                     FROM properties p 
                     JOIN users u ON p.owner_id = u.id 
                     ORDER BY p.created_at DESC");
$properties = $stmt->fetchAll();

$page_title = 'Property Management';
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4">
        <h5 class="mb-0"><i class="fas fa-building text-primary"></i> Property Management</h5>
    </div>
    <div class="card-body">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
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
                    <?php foreach($properties as $property): 
                        $stmt = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ? AND is_primary = 1 LIMIT 1");
                        $stmt->execute([$property['id']]);
                        $image = $stmt->fetch();
                    ?>
                    <tr>
                        <td>#<?php echo $property['id']; ?></td>
                        <td>
                            <img src="<?php echo $image ? SITE_URL . $image['image_path'] : SITE_URL . 'assets/images/placeholder.jpg'; ?>" 
                                 width="50" height="50" style="object-fit: cover; border-radius: 8px;">
                        </td>
                        <td><?php echo htmlspecialchars(substr($property['title'], 0, 30)); ?></td>
                        <td><?php echo htmlspecialchars($property['owner_name']); ?></td>
                        <td>KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?></td>
                        <td>
                            <span class="badge-status badge-<?php echo $property['status'] == 'approved' ? 'approved' : ($property['status'] == 'pending_approval' ? 'pending' : 'rejected'); ?>">
                                <?php echo str_replace('_', ' ', $property['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if($property['is_featured']): ?>
                                <span class="badge bg-warning">Featured</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="../property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($property['status'] == 'pending_approval'): ?>
                                    <a href="?action=approve&id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="?action=reject&id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if($property['is_featured']): ?>
                                    <a href="?action=unfeature&id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-star"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?action=feature&id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-info">
                                        <i class="far fa-star"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this property?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>