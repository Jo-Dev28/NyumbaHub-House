<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where = "owner_id = ?";
$params = [$user_id];

if($status_filter != 'all') {
    $where .= " AND status = ?";
    $params[] = $status_filter;
}

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM properties WHERE $where");
$stmt->execute($params);
$total_properties = $stmt->fetch()['total'];
$total_pages = ceil($total_properties / $limit);

// Get properties
$stmt = $pdo->prepare("SELECT p.*, 
                       (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                       FROM properties p 
                       WHERE $where 
                       ORDER BY p.created_at DESC LIMIT $offset, $limit");
$stmt->execute($params);
$properties = $stmt->fetchAll();

$page_title = 'My Properties';
require_once 'includes/header.php';
?>

<style>
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
    .btn-payment {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        transition: all 0.3s ease;
    }
    .btn-payment:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        color: white;
    }
    .property-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 10px;
    }
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    @media (max-width: 768px) {
        .property-img { width: 45px; height: 45px; }
        .action-buttons { flex-direction: column; }
    }
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'includes/user-sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h4 class="mb-0"><i class="fas fa-home text-primary"></i> My Properties</h4>
                        <a href="add-property.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add New Property
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Status Filter -->
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status_filter == 'all' ? 'active' : ''; ?>" href="?status=all">
                                All <span class="badge bg-secondary ms-1"><?php echo $total_properties; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status_filter == 'approved' ? 'active' : ''; ?>" href="?status=approved">
                                Approved
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status_filter == 'pending_approval' ? 'active' : ''; ?>" href="?status=pending_approval">
                                Pending Review
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status_filter == 'pending_payment' ? 'active' : ''; ?>" href="?status=pending_payment">
                                Payment Pending
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>" href="?status=rejected">
                                Rejected
                            </a>
                        </li>
                    </ul>
                    
                    <?php if(count($properties) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Views</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($properties as $property): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo !empty($property['primary_image']) ? SITE_URL . $property['primary_image'] : SITE_URL . 'assets/images/placeholder.jpg'; ?>" 
                                                 class="property-img" alt="<?php echo htmlspecialchars($property['title']); ?>">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(substr($property['title'], 0, 40)); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo ucfirst($property['property_type']); ?></small>
                                         </td>
                                        <td>
                                            <strong>KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?></strong>
                                            <?php echo $property['category'] != 'sale' ? '<small class="text-muted">/month</small>' : ''; ?>
                                         </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            $showPaymentButton = false;
                                            
                                            switch($property['status']) {
                                                case 'approved':
                                                    $statusClass = 'status-approved';
                                                    $statusText = '✓ Approved';
                                                    break;
                                                case 'pending_approval':
                                                    $statusClass = 'status-pending';
                                                    $statusText = '⏳ Pending Review';
                                                    break;
                                                case 'pending_payment':
                                                    $statusClass = 'status-payment';
                                                    $statusText = '💳 Payment Pending';
                                                    $showPaymentButton = true;
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'status-rejected';
                                                    $statusText = '✗ Rejected';
                                                    break;
                                                default:
                                                    $statusClass = 'status-pending';
                                                    $statusText = ucfirst(str_replace('_', ' ', $property['status']));
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                         </td>
                                        <td>
                                            <i class="fas fa-eye text-muted"></i> <?php echo number_format($property['views_count']); ?>
                                         </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($property['created_at'])); ?>
                                         </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="property-details.php?id=<?php echo $property['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   target="_blank" title="View Property">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if($property['status'] == 'pending_payment'): ?>
                                                    <a href="payment.php?property_id=<?php echo $property['id']; ?>&package=<?php echo $property['listing_package']; ?>" 
                                                       class="btn btn-sm btn-payment" title="Complete Payment">
                                                        <i class="fas fa-credit-card"></i> Pay Now
                                                    </a>
                                                <?php elseif($property['status'] == 'pending_approval'): ?>
                                                    <button class="btn btn-sm btn-warning" disabled title="Awaiting Admin Approval">
                                                        <i class="fas fa-clock"></i> Waiting
                                                    </button>
                                                <?php elseif($property['status'] == 'approved'): ?>
                                                    <a href="edit-property.php?id=<?php echo $property['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary" title="Edit Property">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-success" disabled title="Property Active">
                                                        <i class="fas fa-check-circle"></i> Active
                                                    </button>
                                                <?php elseif($property['status'] == 'rejected'): ?>
                                                    <button class="btn btn-sm btn-danger" disabled title="Property Rejected">
                                                        <i class="fas fa-times-circle"></i> Rejected
                                                    </button>
                                                    <a href="edit-property.php?id=<?php echo $property['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary" title="Edit & Resubmit">
                                                        <i class="fas fa-edit"></i> Resubmit
                                                    </a>
                                                <?php else: ?>
                                                    <a href="edit-property.php?id=<?php echo $property['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary" title="Edit Property">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-outline-danger delete-property" 
                                                        data-id="<?php echo $property['id']; ?>" 
                                                        title="Delete Property">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                         </td>
                                     </tr>
                                    <?php endforeach; ?>
                                </tbody>
                             </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        
                        <!-- Info Alert -->
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Property Status Guide:</strong>
                            <ul class="mb-0 mt-2">
                                <li><span class="status-badge status-payment">💳 Payment Pending</span> - Complete payment to submit for review</li>
                                <li><span class="status-badge status-pending">⏳ Pending Review</span> - Your property is being reviewed by admin</li>
                                <li><span class="status-badge status-approved">✓ Approved</span> - Your property is live on the platform</li>
                                <li><span class="status-badge status-rejected">✗ Rejected</span> - Please check and resubmit your property</li>
                            </ul>
                        </div>
                        
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-home fa-4x text-muted mb-3"></i>
                            <h4>No Properties Found</h4>
                            <p class="text-muted">You haven't listed any properties yet.</p>
                            <a href="add-property.php" class="btn btn-primary">List Your First Property</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Delete property
    $('.delete-property').click(function() {
        var property_id = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/delete-property.php',
                    method: 'POST',
                    data: {property_id: property_id},
                    success: function(response) {
                        var data = JSON.parse(response);
                        if(data.success) {
                            Swal.fire('Deleted!', data.message, 'success');
                            location.reload();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>