<?php
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

// Handle user actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    if($_GET['action'] == 'suspend') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "User suspended successfully";
    } elseif($_GET['action'] == 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "User activated successfully";
    } elseif($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "User deleted successfully";
    }
    redirect('users.php');
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$page_title = 'User Management';
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4">
        <h5 class="mb-0"><i class="fas fa-users text-primary"></i> User Management</h5>
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
                        <th>User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Properties</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): 
                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM properties WHERE owner_id = ?");
                        $stmt->execute([$user['id']]);
                        $prop_count = $stmt->fetch()['count'];
                    ?>
                    <tr>
                        <td>#<?php echo $user['id']; ?></td>
                        <td>
                            <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($user['profile_image'] ?? 'default-avatar.png'); ?>" 
                                 width="40" height="40" class="rounded-circle me-2">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['phone'] ?? '-'; ?></td>
                        <td><?php echo $prop_count; ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <span class="badge-status badge-<?php echo $user['status'] == 'active' ? 'active' : 'suspended'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="view-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($user['status'] == 'active'): ?>
                                    <a href="?action=suspend&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Suspend this user?')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?action=activate&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Activate this user?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user? This action cannot be undone!')">
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