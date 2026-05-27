<?php
require_once 'includes/config.php';

if(isLoggedIn()) {
    // Redirect based on role
    if(isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'super_admin')) {
        redirect('admin/dashboard.php');
    } else {
        redirect('dashboard.php');
    }
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    if(empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['user_avatar'] = $user['profile_image'] ?? 'default-avatar.png';
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Set remember me cookie
            if($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), "/");
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $user['id']]);
            }
            
            // Redirect based on role
            if($user['role'] == 'admin' || $user['role'] == 'super_admin') {
                redirect('admin/dashboard.php');
            } else {
                redirect('dashboard.php');
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}

$page_title = 'Login';
require_once 'includes/header.php';
?>

<style>
    .login-wrapper {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        overflow: hidden;
    }
    .login-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
        background-size: cover;
        opacity: 0.3;
    }
    .login-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        padding: 40px;
        width: 100%;
        max-width: 450px;
        position: relative;
        z-index: 1;
        border: 1px solid rgba(255,255,255,0.2);
    }
    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .login-header h2 {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .form-control {
        border-radius: 15px;
        padding: 12px 20px;
        border: 2px solid #e0e0e0;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.25);
    }
    .btn-login {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
        border: none;
        padding: 12px;
        border-radius: 15px;
        font-weight: 600;
        font-size: 1rem;
        width: 100%;
        transition: all 0.3s ease;
    }
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(13,110,253,0.3);
    }
    .divider {
        text-align: center;
        position: relative;
        margin: 20px 0;
    }
    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #e0e0e0;
    }
    .divider span {
        background: white;
        padding: 0 15px;
        position: relative;
        color: #999;
    }
</style>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-building fa-3x text-primary mb-3"></i>
            <h2>Welcome Back</h2>
            <p class="text-muted">Login to your NyumbaHub account</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0 rounded-start-3">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0 rounded-start-3">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    <button type="button" class="btn btn-outline-secondary toggle-password" style="border-radius: 0 15px 15px 0;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="divider">
            <span>Or</span>
        </div>
        
        <div class="text-center">
            <p class="mb-0">Don't have an account? <a href="register.php" class="text-decoration-none fw-bold">Create Account</a></p>
        </div>
        
        <hr class="my-4">
        
        <div class="text-center">
            <small class="text-muted">
                <i class="fas fa-shield-alt"></i> Secure Login • 256-bit SSL Encryption
            </small>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.toggle-password').click(function() {
        var passwordInput = $('input[name="password"]');
        var icon = $(this).find('i');
        if(passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>