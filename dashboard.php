<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM properties WHERE owner_id = ?");
$stmt->execute([$user_id]);
$total_properties = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_favorites = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_messages = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT SUM(views_count) as total FROM properties WHERE owner_id = ?");
$stmt->execute([$user_id]);
$total_views = $stmt->fetch()['total'] ?? 0;

// Get pending properties count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM properties WHERE owner_id = ? AND status = 'pending_approval'");
$stmt->execute([$user_id]);
$pending_properties = $stmt->fetch()['total'];

// Get approved properties count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM properties WHERE owner_id = ? AND status = 'approved'");
$stmt->execute([$user_id]);
$approved_properties = $stmt->fetch()['total'];

// Get recent properties
$stmt = $pdo->prepare("SELECT p.*, 
                       (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                       FROM properties p 
                       WHERE p.owner_id = ? 
                       ORDER BY p.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_properties = $stmt->fetchAll();

// Get recent messages
$stmt = $pdo->prepare("SELECT m.*, u.full_name, u.profile_image 
                       FROM messages m 
                       JOIN users u ON m.sender_id = u.id 
                       WHERE m.receiver_id = ? 
                       ORDER BY m.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_messages = $stmt->fetchAll();

// Get recent inquiries
$stmt = $pdo->prepare("SELECT pi.*, p.title as property_title 
                       FROM property_inquiries pi
                       JOIN properties p ON pi.property_id = p.id
                       WHERE p.owner_id = ? AND pi.is_read = 0
                       ORDER BY pi.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_inquiries = $stmt->fetchAll();

// Get subscription info
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$subscription = $stmt->fetch();

$page_title = 'Dashboard';
require_once 'includes/header.php';
?>

<style>
    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        height: 100%;
        border: none;
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
        font-size: 1.8rem;
        margin-bottom: 1rem;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .welcome-banner {
        background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        border-radius: 20px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
    }
    .list-group-item {
        border: none;
        padding: 1rem;
        border-radius: 12px !important;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
    }
    .list-group-item:hover {
        background: #f8f9fa;
        transform: translateX(5px);
    }
    .badge-status {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
    }
    .badge-approved { background: #d4edda; color: #155724; }
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-rejected { background: #f8d7da; color: #721c24; }
    .message-item, .inquiry-item {
        transition: all 0.3s ease;
    }
    .message-item:hover, .inquiry-item:hover {
        background: #f8f9fa;
        transform: translateX(5px);
    }
    .quick-action-btn {
        padding: 1rem;
        border-radius: 15px;
        transition: all 0.3s ease;
        text-align: center;
        background: white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .quick-action-btn:hover {
        transform: translateY(-5px);
        background: #0d6efd;
        color: white;
    }
    .quick-action-btn i {
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    @media (max-width: 768px) {
        .stat-value { font-size: 1.5rem; }
        .welcome-banner { padding: 1.5rem; }
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
                        <h2 class="fw-bold mb-2">
                            Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!
                        </h2>
                        <p class="mb-0 opacity-75">
                            Here's what's happening with your properties today.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="add-property.php" class="btn btn-light">
                            <i class="fas fa-plus-circle"></i> List New Property
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Subscription Alert -->
            <?php if(!$subscription || ($subscription && strtotime($subscription['end_date']) < time())): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-4">
                <i class="fas fa-crown"></i> 
                <strong>Upgrade Your Plan!</strong> Get more listings and features by upgrading your subscription.
                <a href="subscription.php" class="btn btn-sm btn-warning ms-3">Upgrade Now</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php elseif($subscription && strtotime($subscription['end_date']) - time() < 86400 * 7): ?>
            <div class="alert alert-info alert-dismissible fade show mb-4">
                <i class="fas fa-clock"></i> 
                Your <strong><?php echo ucfirst($subscription['plan']); ?></strong> plan expires on 
                <?php echo date('F d, Y', strtotime($subscription['end_date'])); ?>.
                <a href="subscription.php" class="btn btn-sm btn-info ms-3">Renew Now</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card text-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_properties); ?></div>
                        <div class="stat-label">Total Properties</div>
                        <?php if($approved_properties > 0): ?>
                            <small class="text-success">
                                <i class="fas fa-check-circle"></i> <?php echo $approved_properties; ?> approved
                            </small>
                        <?php endif; ?>
                        <?php if($pending_properties > 0): ?>
                            <small class="text-warning d-block">
                                <i class="fas fa-clock"></i> <?php echo $pending_properties; ?> pending
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card text-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_favorites); ?></div>
                        <div class="stat-label">Favorites Saved</div>
                        <small class="text-muted">Properties you liked</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card text-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($unread_messages); ?></div>
                        <div class="stat-label">Unread Messages</div>
                        <?php if($unread_messages > 0): ?>
                            <small class="text-danger">
                                <i class="fas fa-bell"></i> New messages waiting
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card text-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning mx-auto">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_views); ?></div>
                        <div class="stat-label">Total Views</div>
                        <small class="text-muted">All properties combined</small>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <a href="add-property.php" class="text-decoration-none">
                        <div class="quick-action-btn">
                            <i class="fas fa-plus-circle text-primary"></i>
                            <span>Add Property</span>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="my-properties.php" class="text-decoration-none">
                        <div class="quick-action-btn">
                            <i class="fas fa-list text-success"></i>
                            <span>My Listings</span>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="messages.php" class="text-decoration-none">
                        <div class="quick-action-btn">
                            <i class="fas fa-comment-dots text-info"></i>
                            <span>Messages</span>
                            <?php if($unread_messages > 0): ?>
                                <span class="badge bg-danger position-absolute"><?php echo $unread_messages; ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="profile.php" class="text-decoration-none">
                        <div class="quick-action-btn">
                            <i class="fas fa-user-edit text-warning"></i>
                            <span>Edit Profile</span>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Recent Properties -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-clock text-primary"></i> Recent Properties</h5>
                        <a href="my-properties.php" class="btn btn-sm btn-link">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(count($recent_properties) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                    <?php foreach($recent_properties as $property): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo !empty($property['primary_image']) ? SITE_URL . $property['primary_image'] : SITE_URL . 'assets/images/placeholder.jpg'; ?>" 
                                                 width="50" height="50" style="object-fit: cover; border-radius: 10px;">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(substr($property['title'], 0, 35)); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo ucfirst($property['property_type']); ?></small>
                                        </td>
                                        <td>
                                            KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?>
                                            <?php echo $property['category'] != 'sale' ? '<small>/month</small>' : ''; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch($property['status']) {
                                                case 'approved':
                                                    $statusClass = 'badge-approved';
                                                    $statusText = 'Approved';
                                                    break;
                                                case 'pending_approval':
                                                    $statusClass = 'badge-pending';
                                                    $statusText = 'Pending';
                                                    break;
                                                case 'pending_payment':
                                                    $statusClass = 'badge-pending';
                                                    $statusText = 'Payment Pending';
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'badge-rejected';
                                                    $statusText = 'Rejected';
                                                    break;
                                                default:
                                                    $statusClass = 'badge-pending';
                                                    $statusText = ucfirst(str_replace('_', ' ', $property['status']));
                                            }
                                            ?>
                                            <span class="badge-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td><?php echo number_format($property['views_count']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-property.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-home fa-3x text-muted mb-3"></i>
                            <h6>No Properties Yet</h6>
                            <p class="text-muted">You haven't listed any properties yet.</p>
                            <a href="add-property.php" class="btn btn-primary">List Your First Property</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Recent Messages -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-comment-dots text-primary"></i> Recent Messages</h5>
                                <a href="messages.php" class="btn btn-sm btn-link">View All</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if(count($recent_messages) > 0): ?>
                                <?php foreach($recent_messages as $message): ?>
                                <div class="message-item d-flex align-items-start mb-3 pb-3 border-bottom">
                                    <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($message['profile_image'] ?? 'default-avatar.png'); ?>" 
                                         class="rounded-circle me-3" width="45" height="45" style="object-fit: cover;" alt="Sender">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($message['full_name']); ?></h6>
                                            <small class="text-muted"><?php echo timeAgo($message['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($message['message'], 0, 80)); ?></p>
                                        <?php if(!$message['is_read']): ?>
                                            <span class="badge bg-primary">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="messages.php?conversation=<?php echo $message['sender_id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-reply"></i>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No messages yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Inquiries -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-question-circle text-primary"></i> Recent Inquiries</h5>
                                <a href="inquiries.php" class="btn btn-sm btn-link">View All</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if(count($recent_inquiries) > 0): ?>
                                <?php foreach($recent_inquiries as $inquiry): ?>
                                <div class="inquiry-item d-flex align-items-start mb-3 pb-3 border-bottom">
                                    <div class="bg-light rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                        <i class="fas fa-user fa-lg text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($inquiry['name'] ?? 'Guest User'); ?></h6>
                                            <small class="text-muted"><?php echo timeAgo($inquiry['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small">
                                            <strong>Property:</strong> <?php echo htmlspecialchars(substr($inquiry['property_title'], 0, 40)); ?>
                                        </p>
                                        <p class="mb-0 small text-muted"><?php echo htmlspecialchars(substr($inquiry['message'], 0, 60)); ?></p>
                                        <?php if(!$inquiry['is_read']): ?>
                                            <span class="badge bg-warning mt-1">Unread</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="property-details.php?id=<?php echo $inquiry['property_id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No inquiries yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Subscription Info -->
            <?php if($subscription): ?>
            <div class="card border-0 shadow-sm rounded-4 mt-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1"><i class="fas fa-crown text-warning"></i> Current Subscription</h6>
                            <h4 class="mb-2"><?php echo ucfirst($subscription['plan']); ?> Plan</h4>
                            <div class="progress mb-2" style="height: 8px;">
                                <?php
                                $total_days = (strtotime($subscription['end_date']) - strtotime($subscription['start_date'])) / 86400;
                                $days_passed = (time() - strtotime($subscription['start_date'])) / 86400;
                                $percentage = min(100, max(0, ($days_passed / $total_days) * 100));
                                ?>
                                <div class="progress-bar bg-success" style="width: <?php echo 100 - $percentage; ?>%"></div>
                            </div>
                            <small class="text-muted">
                                Expires: <?php echo date('F d, Y', strtotime($subscription['end_date'])); ?>
                                (<?php echo ceil((strtotime($subscription['end_date']) - time()) / 86400); ?> days left)
                            </small>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="subscription.php" class="btn btn-outline-primary">
                                <i class="fas fa-sync-alt"></i> Upgrade / Renew
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>