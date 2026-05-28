<?php
require_once 'includes/config.php';

if(isLoggedIn()) {
    if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
        redirect('dashboard.php');
    } elseif(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'super_admin') {
        redirect('admin/dashboard.php');
    } else {
        redirect('buyer-dashboard.php');
    }
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']); // 'user' or 'admin'
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
    
    // Validation
    if(empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill all required fields';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif(strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif($password != $confirm_password) {
        $error = 'Passwords do not match';
    } elseif($role == 'admin' && empty($phone)) {
        $error = 'Phone number is required for property owners/admins';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->rowCount() > 0) {
            $error = 'Email already registered';
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, user_type, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            if($stmt->execute([$full_name, $email, $phone, $hashed_password, $role, $role])) {
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_type'] = $role;
                
                // Redirect based on role
                if($role == 'admin') {
                    redirect('dashboard.php');
                } elseif($role == 'super_admin') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('buyer-dashboard.php');
                }
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$page_title = 'Register';
require_once 'includes/header.php';
?>

<style>
    .register-wrapper {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 0;
    }
    .register-card {
        background: white;
        border-radius: 30px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        overflow: hidden;
        max-width: 500px;
        width: 100%;
        margin: 0 auto;
    }
    .register-header {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        padding: 30px;
        text-align: center;
        color: white;
    }
    .register-body {
        padding: 30px;
    }
    /* Toggle Switch */
    .user-type-toggle {
        background: #f0f2f5;
        border-radius: 60px;
        padding: 5px;
        display: flex;
        margin-bottom: 25px;
    }
    .toggle-option {
        flex: 1;
        text-align: center;
        padding: 12px 20px;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
        background: transparent;
        font-size: 14px;
    }
    .toggle-option i {
        margin-right: 8px;
    }
    .toggle-option.active {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
        box-shadow: 0 5px 15px rgba(13,110,253,0.3);
    }
    .btn-register {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
        border: none;
        padding: 12px;
        border-radius: 15px;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
    }
    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(13,110,253,0.3);
    }
    .input-group-text {
        border-radius: 12px 0 0 12px;
        background: #f8f9fa;
        width: 45px;
        justify-content: center;
    }
    .form-control {
        border-radius: 0 12px 12px 0;
    }
    .info-text {
        background: #e7f3ff;
        padding: 12px;
        border-radius: 12px;
        font-size: 13px;
        margin-top: 15px;
    }
    .phone-field {
        transition: all 0.3s ease;
    }
</style>

<div class="register-wrapper">
    <div class="register-card">
        <div class="register-header">
            <i class="fas fa-building fa-3x mb-3"></i>
            <h2 class="fw-bold mb-2">Create Account</h2>
            <p class="mb-0 opacity-75">Join NyumbaHub Kenya today</p>
        </div>
        
        <div class="register-body">
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Role Toggle Button -->
            <div class="user-type-toggle" id="roleToggle">
                <button type="button" class="toggle-option active" data-role="user">
                    <i class="fas fa-search"></i> Property Seeker
                </button>
                <button type="button" class="toggle-option" data-role="admin">
                    <i class="fas fa-home"></i> Property Owner/Agent
                </button>
            </div>
            
            <form method="POST" id="registerForm">
                <input type="hidden" name="role" id="userRole" value="user">
                
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                
                <!-- Phone Field (Only for Property Owners/Admins) -->
                <div class="mb-3 phone-field" id="phoneField" style="display: none;">
                    <label class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="tel" name="phone" id="phoneInput" class="form-control" placeholder="e.g., 0712345678">
                    </div>
                    <small class="text-muted">Required for property listing and contact purposes</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                
                <!-- Info Message based on selection -->
                <div class="info-text" id="infoMessage">
                    <i class="fas fa-info-circle text-primary"></i> 
                    <span>As a property seeker, you'll be able to browse properties, save favorites, and contact owners.</span>
                </div>
                
                <button type="submit" class="btn-register mt-3">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="text-center mt-4">
                <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none fw-bold">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle functionality
    $('.toggle-option').click(function() {
        var role = $(this).data('role');
        
        // Update active state
        $('.toggle-option').removeClass('active');
        $(this).addClass('active');
        
        // Update hidden input
        $('#userRole').val(role);
        
        // Show/hide phone field for property owners/admins
        if(role === 'admin') {
            $('#phoneField').slideDown();
            $('#phoneInput').prop('required', true);
            $('#infoMessage').html('<i class="fas fa-info-circle text-primary"></i> <span>As a property owner/agent, you\'ll be able to list properties, manage listings, receive inquiries, and connect with potential buyers.</span>');
        } else {
            $('#phoneField').slideUp();
            $('#phoneInput').prop('required', false);
            $('#infoMessage').html('<i class="fas fa-info-circle text-primary"></i> <span>As a property seeker, you\'ll be able to browse properties, save favorites, and contact owners.</span>');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>