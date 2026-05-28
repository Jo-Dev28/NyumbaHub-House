<?php
// Start output buffering to prevent header issues
ob_start();
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

// Handle user actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    if($_GET['action'] == 'suspend') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "User suspended successfully";
        header("Location: users.php");
        exit();
    } elseif($_GET['action'] == 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "User activated successfully";
        header("Location: users.php");
        exit();
    } elseif($_GET['action'] == 'delete') {
        // Check if user is not super admin
        $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $check->execute([$user_id]);
        $user_check = $check->fetch();
        if($user_check['role'] != 'super_admin') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "User deleted successfully";
        } else {
            $_SESSION['error'] = "Cannot delete Super Admin user";
        }
        header("Location: users.php");
        exit();
    }
}

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : 'all';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if($search) {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($role_filter != 'all') {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

if($status_filter != 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'User Management';
?>

<style>
    .search-filters {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 20px;
    }
    .user-avatar-sm {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    .role-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .role-user { background: #e7f3ff; color: #0d6efd; }
    .role-admin { background: #d4edda; color: #155724; }
    .role-super { background: #f8d7da; color: #721c24; }
    .badge-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-active { background: #d4edda; color: #155724; }
    .badge-suspended { background: #f8d7da; color: #721c24; }
    .table > :not(caption) > * > * {
        vertical-align: middle;
    }
</style>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0"><i class="fas fa-users text-primary"></i> User Management</h5>
        </div>
    </div>
    <div class="card-body">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
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
                        <input type="text" name="search" class="form-control" placeholder="Name, email or phone..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="all" <?php echo $role_filter == 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>Property Seeker</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Property Owner</option>
                        <option value="super_admin" <?php echo $role_filter == 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
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
                        <th>User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Properties</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($users) > 0): ?>
                        <?php foreach($users as $user): 
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM properties WHERE owner_id = ?");
                            $stmt->execute([$user['id']]);
                            $prop_count = $stmt->fetch()['count'];
                            
                            $roleClass = '';
                            $roleText = '';
                            if($user['role'] == 'user') {
                                $roleClass = 'role-user';
                                $roleText = 'Property Seeker';
                            } elseif($user['role'] == 'admin') {
                                $roleClass = 'role-admin';
                                $roleText = 'Property Owner';
                            } else {
                                $roleClass = 'role-super';
                                $roleText = 'Super Admin';
                            }
                        ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($user['profile_image'] ?? 'default-avatar.png'); ?>" 
                                         class="user-avatar-sm">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['phone'] ?? '-'; ?></td>
                            <td><span class="role-badge <?php echo $roleClass; ?>"><?php echo $roleText; ?></span></td>
                            <td><?php echo $prop_count; ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <span class="badge-status <?php echo $user['status'] == 'active' ? 'badge-active' : 'badge-suspended'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary" title="View User">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if($user['status'] == 'active' && $user['role'] != 'super_admin'): ?>
                                        <a href="?action=suspend&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning" title="Suspend User" onclick="return confirm('Suspend this user?')">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php elseif($user['status'] == 'suspended' && $user['role'] != 'super_admin'): ?>
                                        <a href="?action=activate&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-success" title="Activate User" onclick="return confirm('Activate this user?')">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if($user['role'] != 'super_admin'): ?>
                                        <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete User" onclick="return confirm('Delete this user? This action cannot be undone!')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5>No users found</h5>
                                <p class="text-muted">Try adjusting your search criteria</p>
                            <tr>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>