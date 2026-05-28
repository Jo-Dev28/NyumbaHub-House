<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$user_role = $_SESSION['user_role'] ?? 'user';
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $bio = isset($_POST['bio']) ? sanitize($_POST['bio']) : '';
        $facebook_link = isset($_POST['facebook_link']) ? sanitize($_POST['facebook_link']) : '';
        $twitter_link = isset($_POST['twitter_link']) ? sanitize($_POST['twitter_link']) : '';
        $instagram_link = isset($_POST['instagram_link']) ? sanitize($_POST['instagram_link']) : '';
        
        // Admin/Agent specific fields
        $company_name = isset($_POST['company_name']) ? sanitize($_POST['company_name']) : '';
        $business_reg_no = isset($_POST['business_reg_no']) ? sanitize($_POST['business_reg_no']) : '';
        $experience_years = isset($_POST['experience_years']) ? (int)$_POST['experience_years'] : 0;
        $license_number = isset($_POST['license_number']) ? sanitize($_POST['license_number']) : '';
        
        // Handle profile image upload
        $profile_image = $user['profile_image'];
        if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_dir = 'uploads/profiles/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            // Delete old image if not default
            if($profile_image != 'default-avatar.png' && file_exists($upload_dir . $profile_image)) {
                unlink($upload_dir . $profile_image);
            }
            
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . $user_id . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $filepath)) {
                $profile_image = $filename;
                $_SESSION['user_avatar'] = $profile_image;
            }
        }
        
        // Build update query based on role
        $sql = "UPDATE users SET full_name = ?, phone = ?, bio = ?, facebook_link = ?, twitter_link = ?, instagram_link = ?, profile_image = ?";
        $params = [$full_name, $phone, $bio, $facebook_link, $twitter_link, $instagram_link, $profile_image];
        
        // Add admin-specific fields if user is admin or super admin
        if($user_role == 'admin' || $user_role == 'super_admin') {
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
            $_SESSION['user_name'] = $full_name;
            $success = 'Profile updated successfully!';
            $user = getUserById($user_id);
        } else {
            $error = 'Failed to update profile.';
        }
    }
    
    if(isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if(password_verify($current_password, $user['password'])) {
            if($new_password == $confirm_password) {
                if(strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if($stmt->execute([$hashed_password, $user_id])) {
                        $success = 'Password changed successfully!';
                    } else {
                        $error = 'Failed to change password.';
                    }
                } else {
                    $error = 'New password must be at least 6 characters.';
                }
            } else {
                $error = 'New passwords do not match.';
            }
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

$page_title = 'My Profile';
require_once 'includes/header.php';
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
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'includes/user-sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <?php if($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Profile Information -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0"><i class="fas fa-user-edit text-primary"></i> Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-3 text-center mb-4">
                                <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($user['profile_image'] ?? 'default-avatar.png'); ?>" 
                                     class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover; border: 3px solid #0d6efd;" id="profilePreview">
                                <div class="mt-2">
                                    <label for="profile_image" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-camera"></i> Change Photo
                                    </label>
                                    <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*">
                                </div>
                                <div class="mt-2">
                                    <?php if($user_role == 'user'): ?>
                                        <span class="role-badge role-user"><i class="fas fa-user"></i> Property Seeker</span>
                                    <?php elseif($user_role == 'admin'): ?>
                                        <span class="role-badge role-admin"><i class="fas fa-home"></i> Property Owner/Agent</span>
                                    <?php else: ?>
                                        <span class="role-badge role-super"><i class="fas fa-shield-alt"></i> Super Administrator</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Member Since</label>
                                        <input type="text" class="form-control" value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>" disabled>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Bio</label>
                                        <textarea name="bio" class="form-control" rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    </div>
                                    <?php if($user_role == 'admin'): ?>
                                    <div class="col-md-4">
                                        <label class="form-label"><i class="fab fa-facebook text-primary"></i> Facebook</label>
                                        <input type="url" name="facebook_link" class="form-control" value="<?php echo htmlspecialchars($user['facebook_link'] ?? ''); ?>" placeholder="https://facebook.com/username">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><i class="fab fa-twitter text-info"></i> Twitter</label>
                                        <input type="url" name="twitter_link" class="form-control" value="<?php echo htmlspecialchars($user['twitter_link'] ?? ''); ?>" placeholder="https://twitter.com/username">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><i class="fab fa-instagram text-danger"></i> Instagram</label>
                                        <input type="url" name="instagram_link" class="form-control" value="<?php echo htmlspecialchars($user['instagram_link'] ?? ''); ?>" placeholder="https://instagram.com/username">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Admin/Agent Business Information (Only for Admin and Super Admin) -->
                        <?php if($user_role == 'admin' || $user_role == 'super_admin'): ?>
                        <hr class="my-4">
                        <h6 class="mb-3"><i class="fas fa-building text-primary"></i> Business Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Company/Agency Name</label>
                                <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>" placeholder="Your company or agency name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Business Registration Number</label>
                                <input type="text" name="business_reg_no" class="form-control" value="<?php echo htmlspecialchars($user['business_reg_no'] ?? ''); ?>" placeholder="e.g., PVT-2024-001234">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Years of Experience</label>
                                <input type="number" name="experience_years" class="form-control" value="<?php echo $user['experience_years'] ?? 0; ?>" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">License Number</label>
                                <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($user['license_number'] ?? ''); ?>" placeholder="Professional license #">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Verification Status</label>
                                <div class="mt-2">
                                    <?php if($user['is_verified_seller'] ?? 0): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified Agent</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="fas fa-clock"></i> Pending Verification</span>
                                        <small class="d-block text-muted mt-1">Submit your documents for verification</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-end mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary px-4">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0"><i class="fas fa-lock text-primary"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" name="change_password" class="btn btn-warning px-4">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0"><i class="fas fa-shield-alt text-primary"></i> Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Account Type:</strong> 
                                <?php if($user_role == 'user'): ?>
                                    <span class="badge bg-info">Property Seeker</span>
                                <?php elseif($user_role == 'admin'): ?>
                                    <span class="badge bg-primary">Property Owner/Agent</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Super Administrator</span>
                                <?php endif; ?>
                            </p>
                            <?php if($user_role == 'admin'): ?>
                            <p><strong>Subscription Plan:</strong> 
                                <span class="badge bg-warning"><?php echo ucfirst($user['subscription_plan'] ?? 'Bronze'); ?></span>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Email Verified:</strong> 
                                <?php echo ($user['email_verified'] ?? 0) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?>
                            </p>
                            <p><strong>Last Login:</strong> 
                                <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if($user_role == 'admin'): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Total Properties Listed:</strong> 
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM properties WHERE owner_id = ?");
                                $stmt->execute([$user_id]);
                                $prop_count = $stmt->fetch()['count'];
                                echo '<strong>' . $prop_count . '</strong>';
                                ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Views on Properties:</strong> 
                                <?php
                                $stmt = $pdo->prepare("SELECT SUM(views_count) as total FROM properties WHERE owner_id = ?");
                                $stmt->execute([$user_id]);
                                $total_views = $stmt->fetch()['total'] ?? 0;
                                echo '<strong>' . number_format($total_views) . '</strong>';
                                ?>
                            </p>
                        </div>
                    </div>
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

document.querySelector('label[for="profile_image"]')?.addEventListener('click', function() {
    document.getElementById('profile_image').click();
});
</script>

<?php require_once 'includes/footer.php'; ?>