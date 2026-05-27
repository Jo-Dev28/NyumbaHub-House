<?php
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

$admin_id = $_SESSION['user_id'];
$admin = getUserById($admin_id);
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        
        // Handle profile image upload
        $profile_image = $admin['profile_image'];
        if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_dir = '../uploads/profiles/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            if($profile_image != 'default-avatar.png' && file_exists($upload_dir . $profile_image)) {
                unlink($upload_dir . $profile_image);
            }
            
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . $admin_id . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $filepath)) {
                $profile_image = $filename;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, profile_image = ? WHERE id = ?");
        if($stmt->execute([$full_name, $phone, $profile_image, $admin_id])) {
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_avatar'] = $profile_image;
            $success = 'Profile updated successfully!';
            $admin = getUserById($admin_id);
        } else {
            $error = 'Failed to update profile.';
        }
    }
    
    if(isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if(password_verify($current_password, $admin['password'])) {
            if($new_password == $confirm_password) {
                if(strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if($stmt->execute([$hashed_password, $admin_id])) {
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

$page_title = 'Admin Profile';
?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="admin-card text-center">
            <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($admin['profile_image'] ?? 'default-avatar.png'); ?>" 
                 class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;" id="profilePreview">
            <h4><?php echo htmlspecialchars($admin['full_name']); ?></h4>
            <p class="text-muted"><?php echo ucfirst($_SESSION['user_role']); ?> Administrator</p>
            <p><i class="fas fa-envelope"></i> <?php echo $admin['email']; ?></p>
            <p><i class="fas fa-phone"></i> <?php echo $admin['phone'] ?? 'Not provided'; ?></p>
            <p><i class="fas fa-calendar"></i> Member since <?php echo date('F d, Y', strtotime($admin['created_at'])); ?></p>
        </div>
    </div>
    
    <div class="col-lg-8">
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="admin-card">
            <h5 class="mb-3"><i class="fas fa-user-edit text-primary"></i> Edit Profile</h5>
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profile Image</label>
                        <input type="file" name="profile_image" class="form-control" accept="image/*" id="profileImage">
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
        
        <div class="admin-card mt-4">
            <h5 class="mb-3"><i class="fas fa-lock text-primary"></i> Change Password</h5>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('profileImage')?.addEventListener('change', function(e) {
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('profilePreview').src = e.target.result;
    }
    if(e.target.files[0]) {
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>