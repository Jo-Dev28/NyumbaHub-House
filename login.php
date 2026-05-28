<?php
require_once 'includes/config.php';

if(isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'user';
    if($role == 'super_admin') {
        redirect('admin/dashboard.php');
    } elseif($role == 'admin') {
        redirect('dashboard.php');
    } else {
        redirect('buyer-dashboard.php');
    }
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['profile_image'] ?? 'default-avatar.png';
            
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Redirect based on role
            if($user['role'] == 'super_admin') {
                redirect('admin/dashboard.php');
            } elseif($user['role'] == 'admin') {
                redirect('dashboard.php');
            } else {
                redirect('buyer-dashboard.php');
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
        padding: 40px 0;
    }
    .login-card {
        background: white;
        border-radius: 30px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        overflow: hidden;
        max-width: 450px;
        width: 100%;
        margin: 0 auto;
    }
    .login-header {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        padding: 30px;
        text-align: center;
        color: white;
    }
    .login-body {
        padding: 30px;
    }
    .btn-login {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
        border: none;
        padding: 12px;
        border-radius: 15px;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
    }
    .btn-login:hover {
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
</style>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-building fa-3x mb-3"></i>
            <h2 class="fw-bold mb-2">Welcome Back</h2>
            <p class="mb-0 opacity-75">Login to your NyumbaHub account</p>
        </div>
        
        <div class="login-body">
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="text-center mt-4">
                <p class="mb-0">Don't have an account? <a href="register.php" class="text-decoration-none fw-bold">Register here</a></p>
                <p class="mt-2"><a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>