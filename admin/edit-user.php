<?php
ob_start();
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($user_id == 0) {
    header("Location: users.php");
    exit();
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if(!$user) {
    header("Location: users.php");
    exit();
}

// Check permissions
$current_user_role = $_SESSION['user_role'];
$current_user_id = $_SESSION['user_id'];
$is_editing_self = ($current_user_id == $user_id);
$is_editing_superadmin = ($user['role'] == 'super_admin');
$is_current_user_superadmin = ($current_user_role == 'super_admin');

// Prevent non-superadmin from editing superadmin
if($is_editing_superadmin && !$is_current_user_superadmin) {
    $_SESSION['error'] = "You don't have permission to edit a Super Admin user";
    header("Location: users.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_user'])) {
        // Only superadmin can change role and status
        if($is_current_user_superadmin) {
            $role = sanitize($_POST['role']);
            $status = sanitize($_POST['status']);
        } else {
            // Keep original values for non-superadmin
            $role = $user['role'];
            $status = $user['status'];
        }
        
        // Prevent role change to superadmin by non-superadmin
        if(!$is_current_user_superadmin && $role == 'super_admin') {
            $role = $user['role'];
        }
        
        // Personal info fields - ONLY the user themselves can change these
        if($is_editing_self) {
            $full_name = sanitize($_POST['full_name']);
            $phone = sanitize($_POST['phone']);
            $bio = sanitize($_POST['bio']);
        } else {
            // Admin editing another user - cannot change personal info
            $full_name = $user['full_name'];
            $phone = $user['phone'];
            $bio = $user['bio'];
        }
        
        // Admin specific fields (can be changed by superadmin or when editing self if user is admin)
        $company_name = isset($_POST['company_name']) ? sanitize($_POST['company_name']) : '';
        $business_reg_no = isset($_POST['business_reg_no']) ? sanitize($_POST['business_reg_no']) : '';
        $experience_years = isset($_POST['experience_years']) ? (int)$_POST['experience_years'] : 0;
        $license_number = isset($_POST['license_number']) ? sanitize($_POST['license_number']) : '';
        
        // Handle profile image upload (only user themselves or superadmin can change)
        $profile_image = $user['profile_image'];
        if(($is_editing_self || $is_current_user_superadmin) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_dir = '../uploads/profiles/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            if($profile_image != 'default-avatar.png' && file_exists($upload_dir . $profile_image)) {
                unlink($upload_dir . $profile_image);
            }
            
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . $user_id . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $filepath)) {
                $profile_image = $filename;
            }
        }
        
        $sql = "UPDATE users SET full_name = ?, phone = ?, bio = ?, profile_image = ?";
        $params = [$full_name, $phone, $bio, $profile_image];
        
        // Only superadmin can update role and status
        if($is_current_user_superadmin) {
            $sql .= ", role = ?, status = ?";
            $params[] = $role;
            $params[] = $status;
        }
        
        // Add admin fields if role is admin (or if user is admin being edited)
        if($role == 'admin' || $user['role'] == 'admin') {
            $sql .= ", company_name = ?, business_reg_no = ?, experience_years = ?, license_number = ?";
            $params[] = $company_name;
            $params[] = $business_reg_no;
            $params[] = $experience_years;
            $params[] = $license_number;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $user_id;
        
        $stmt = $pdo->prepare($sql);
        if($stmt->execute($params)) {
            $success = "User updated successfully!";
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $error = "Failed to update user.";
        }
    }
    
    // Handle password reset
    if(isset($_POST['reset_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if(strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters";
        } elseif($new_password != $confirm_password) {
            $error = "Passwords do not match";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if($stmt->execute([$hashed_password, $user_id])) {
                $success = "Password reset successfully! The user can now login with the new password.";
                // Add notification for the user
                addNotification($user_id, "Password Changed", "Your password has been reset by an administrator.");
            } else {
                $error = "Failed to reset password.";
            }
        }
    }
}

$page_title = 'Edit User - ' . htmlspecialchars($user['full_name']);
$is_superadmin = ($user['role'] == 'super_admin');
$can_edit_role = ($is_current_user_superadmin && !$is_superadmin) || ($is_current_user_superadmin && $_SESSION['user_id'] != $user_id);
?>

<style>
    .info-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }
    .role-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .role-user { background: #e7f3ff; color: #0d6efd; }
    .role-admin { background: #d4edda; color: #155724; }
    .role-super { background: #f8d7da; color: #721c24; }
    .restricted-field {
        background-color: #f0f0f0;
        cursor: not-allowed;
    }
    .info-note {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .password-reset-box {
        background: #e7f3ff;
        border: 1px solid #0d6efd;
        border-radius: 15px;
        padding: 20px;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-user-edit text-primary"></i> Edit User</h4>
    <div>
        <a href="users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        <a href="view-user.php?id=<?php echo $user_id; ?>" class="btn btn-info">
            <i class="fas fa-eye"></i> View User
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
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
        
        <!-- Restriction Note -->
        <?php if($is_superadmin && !$is_current_user_superadmin): ?>
        <div class="info-note">
            <i class="fas fa-shield-alt text-warning"></i> 
            <strong>Restricted Access:</strong> You cannot edit Super Admin users. Only another Super Admin can modify this account.
        </div>
        <?php endif; ?>
        
        <?php if(!$is_editing_self && !$is_current_user_superadmin): ?>
        <div class="info-note">
            <i class="fas fa-info-circle text-info"></i> 
            <strong>Personal Information Locked:</strong> Name, phone, and bio can only be edited by the user themselves.
        </div>
        <?php endif; ?>
        
        <?php if($_SESSION['user_id'] == $user_id): ?>
        <div class="info-note">
            <i class="fas fa-info-circle text-info"></i> 
            <strong>Editing Your Own Account:</strong> You can update your personal information here.
        </div>
        <?php endif; ?>
        
        <!-- Edit User Form -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="mb-0"><i class="fas fa-user-edit text-primary"></i> User Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-3 text-center mb-4">
                            <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($user['profile_image'] ?? 'default-avatar.png'); ?>" 
                                 class="rounded-circle mb-3" width="120" height="120" style="object-fit: cover;" id="profilePreview">
                            <?php if($is_editing_self || $is_current_user_superadmin): ?>
                            <div>
                                <label for="profile_image" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-camera"></i> Change Photo
                                </label>
                                <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*">
                            </div>
                            <?php else: ?>
                            <div class="text-muted small">Photo locked (only user can change)</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <?php if($is_editing_self): ?>
                                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    <?php else: ?>
                                        <input type="text" class="form-control restricted-field" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>
                                        <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                                        <small class="text-muted">Only user can change their name</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control restricted-field" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <?php if($is_editing_self): ?>
                                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    <?php else: ?>
                                        <input type="text" class="form-control restricted-field" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" disabled>
                                        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        <small class="text-muted">Only user can change their phone number</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Role</label>
                                    <?php if($is_current_user_superadmin && !$is_superadmin): ?>
                                        <select name="role" class="form-select" id="roleSelect">
                                            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Property Seeker</option>
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Property Owner/Agent</option>
                                            <option value="super_admin" <?php echo $user['role'] == 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" class="form-control restricted-field" value="<?php 
                                            if($user['role'] == 'user') echo 'Property Seeker';
                                            elseif($user['role'] == 'admin') echo 'Property Owner/Agent';
                                            else echo 'Super Admin';
                                        ?>" disabled>
                                        <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                                        <small class="text-muted">Role can only be changed by Super Admin</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <?php if($is_current_user_superadmin && !$is_superadmin): ?>
                                        <select name="status" class="form-select">
                                            <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="suspended" <?php echo $user['status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" class="form-control restricted-field" value="<?php echo ucfirst($user['status']); ?>" disabled>
                                        <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                        <small class="text-muted">Status can only be changed by Super Admin</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Bio</label>
                                    <?php if($is_editing_self): ?>
                                        <textarea name="bio" class="form-control" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    <?php else: ?>
                                        <textarea class="form-control restricted-field" rows="3" disabled><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                        <input type="hidden" name="bio" value="<?php echo htmlspecialchars($user['bio'] ?? ''); ?>">
                                        <small class="text-muted">Only user can change their bio</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Admin Business Fields (only for admin role) -->
                    <div id="adminFields" style="display: <?php echo ($user['role'] == 'admin') ? 'block' : 'none'; ?>;">
                        <hr class="my-4">
                        <h6 class="mb-3"><i class="fas fa-building text-primary"></i> Business Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Company/Agency Name</label>
                                <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Business Registration Number</label>
                                <input type="text" name="business_reg_no" class="form-control" value="<?php echo htmlspecialchars($user['business_reg_no'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Years of Experience</label>
                                <input type="number" name="experience_years" class="form-control" value="<?php echo $user['experience_years'] ?? 0; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">License Number</label>
                                <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($user['license_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Verification Status</label>
                                <div class="mt-2">
                                    <span class="badge bg-<?php echo ($user['is_verified_seller'] ?? 0) ? 'success' : 'warning'; ?>">
                                        <?php echo ($user['is_verified_seller'] ?? 0) ? 'Verified' : 'Pending Verification'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(!($is_superadmin && !$is_current_user_superadmin)): ?>
                    <div class="text-end mt-4">
                        <button type="submit" name="update_user" class="btn btn-primary px-4">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Reset Password Section -->
        <div class="card border-0 shadow-sm rounded-4 mt-4">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="mb-0"><i class="fas fa-key text-primary"></i> Reset User Password</h5>
            </div>
            <div class="card-body">
                <?php if($is_superadmin && !$is_current_user_superadmin): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-lock"></i> You cannot reset password for Super Admin users.
                    </div>
                <?php else: ?>
                    <div class="password-reset-box">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Reset Password for: <?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                    This will generate a new password. The user will need to use this new password to login.
                                </div>
                            </div>
                        </div>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" name="reset_password" class="btn btn-warning w-100" onclick="return confirm('Are you sure you want to reset this user\'s password? They will need to use the new password to login.')">
                                        <i class="fas fa-sync-alt"></i> Reset Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Stats Sidebar -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-chart-line text-primary"></i> User Statistics</h6>
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM properties WHERE owner_id = ?");
                $stmt->execute([$user_id]);
                $prop_count = $stmt->fetch()['count'];
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $fav_count = $stmt->fetch()['count'];
                
                $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE user_id = ? AND status = 'completed'");
                $stmt->execute([$user_id]);
                $total_spent = $stmt->fetch()['total'] ?? 0;
                ?>
                <div class="mb-3">
                    <strong>Total Properties:</strong> <?php echo $prop_count; ?>
                </div>
                <div class="mb-3">
                    <strong>Total Favorites:</strong> <?php echo $fav_count; ?>
                </div>
                <div class="mb-3">
                    <strong>Total Spent:</strong> KES <?php echo number_format($total_spent); ?>
                </div>
                <div>
                    <strong>Member Since:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                </div>
            </div>
        </div>
        
        <!-- Permission Info -->
        <div class="card border-0 shadow-sm rounded-4 mt-4">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-shield-alt text-primary"></i> Permission Info</h6>
                <ul class="small text-muted">
                    <li><i class="fas fa-check-circle text-success"></i> Email cannot be changed</li>
                    <li><i class="fas fa-user-lock text-warning"></i> Name, phone, bio can only be changed by the user</li>
                    <?php if(!$is_current_user_superadmin): ?>
                    <li><i class="fas fa-lock text-warning"></i> Role and status can only be changed by Super Admin</li>
                    <?php endif; ?>
                    <li><i class="fas fa-key text-info"></i> Password can be reset by admins</li>
                    <?php if($is_superadmin): ?>
                    <li><i class="fas fa-shield-alt text-danger"></i> Super Admin account has full privileges</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm rounded-4 mt-4">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-bolt text-primary"></i> Quick Actions</h6>
                <div class="d-grid gap-2">
                    <a href="view-user.php?id=<?php echo $user_id; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-eye"></i> View User Profile
                    </a>
                    <?php if($user['role'] == 'admin'): ?>
                    <a href="../my-properties.php?user=<?php echo $user_id; ?>" class="btn btn-outline-success">
                        <i class="fas fa-home"></i> View User's Properties
                    </a>
                    <?php endif; ?>
                    <?php if($user['role'] == 'admin' && $user['status'] == 'active'): ?>
                    <a href="users.php?action=suspend&id=<?php echo $user_id; ?>" class="btn btn-outline-warning" onclick="return confirm('Suspend this user?')">
                        <i class="fas fa-ban"></i> Suspend User
                    </a>
                    <?php elseif($user['role'] == 'admin' && $user['status'] == 'suspended'): ?>
                    <a href="users.php?action=activate&id=<?php echo $user_id; ?>" class="btn btn-outline-success" onclick="return confirm('Activate this user?')">
                        <i class="fas fa-check"></i> Activate User
                    </a>
                    <?php endif; ?>
                    <?php if($user['role'] != 'super_admin'): ?>
                    <a href="users.php?action=delete&id=<?php echo $user_id; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this user? This action cannot be undone!')">
                        <i class="fas fa-trash"></i> Delete User
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('profile_image')?.addEventListener('change', function(e) {
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('profilePreview').src = e.target.result;
    }
    if(e.target.files[0]) {
        reader.readAsDataURL(e.target.files[0]);
    }
});

document.getElementById('roleSelect')?.addEventListener('change', function() {
    const adminFields = document.getElementById('adminFields');
    if(this.value === 'admin') {
        adminFields.style.display = 'block';
    } else {
        adminFields.style.display = 'none';
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>