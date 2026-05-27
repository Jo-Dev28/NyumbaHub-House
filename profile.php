<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
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
        
        // Handle profile image upload
        $profile_image = $user['profile_image'];
        if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_dir = 'uploads/profiles/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . $user_id . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $filepath)) {
                $profile_image = $filename;
            }
        }
        
        // Check if columns exist before updating
        $sql = "UPDATE users SET full_name = ?, phone = ?";
        $params = [$full_name, $phone];
        
        // Only include bio if column exists
        try {
            $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'bio'");
            if($checkCol->rowCount() > 0) {
                $sql .= ", bio = ?";
                $params[] = $bio;
            }
        } catch(Exception $e) {}
        
        try {
            $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'facebook_link'");
            if($checkCol->rowCount() > 0) {
                $sql .= ", facebook_link = ?";
                $params[] = $facebook_link;
            }
        } catch(Exception $e) {}
        
        try {
            $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'twitter_link'");
            if($checkCol->rowCount() > 0) {
                $sql .= ", twitter_link = ?";
                $params[] = $twitter_link;
            }
        } catch(Exception $e) {}
        
        try {
            $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'instagram_link'");
            if($checkCol->rowCount() > 0) {
                $sql .= ", instagram_link = ?";
                $params[] = $instagram_link;
            }
        } catch(Exception $e) {}
        
        $sql .= ", profile_image = ? WHERE id = ?";
        $params[] = $profile_image;
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
                                     class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;" id="profilePreview">
                                <div class="mt-2">
                                    <label for="profile_image" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-camera"></i> Change Photo
                                    </label>
                                    <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*">
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
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary px-4">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card border-0 shadow-sm rounded-4">
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
            <div class="card border-0 shadow-sm rounded-4 mt-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0"><i class="fas fa-shield-alt text-primary"></i> Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Account Type:</strong> 
                                <span class="badge bg-info"><?php echo ucfirst($user['user_type'] ?? 'Owner'); ?></span>
                            </p>
                            <p><strong>Subscription Plan:</strong> 
                                <span class="badge bg-warning"><?php echo ucfirst($user['subscription_plan'] ?? 'Bronze'); ?></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Email Verified:</strong> 
                                <?php echo ($user['email_verified'] ?? 0) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?>
                            </p>
                            <p><strong>Verification Badge:</strong> 
                                <?php echo ($user['verification_badge'] ?? 0) ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>' : '<span class="badge bg-secondary">Not Verified</span>'; ?>
                            </p>
                        </div>
                    </div>
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