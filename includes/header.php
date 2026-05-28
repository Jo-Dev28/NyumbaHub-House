<?php
if(!isset($_SESSION)) session_start();

// Check if user is logged in and get user data
$loggedInUser = null;
if(isLoggedIn() && isset($_SESSION['user_id'])) {
    $loggedInUser = getUserById($_SESSION['user_id']);
}

// Check user role
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user';
$is_admin = ($user_role == 'admin');
$is_super_admin = ($user_role == 'super_admin');
$is_regular_user = (!$is_admin && !$is_super_admin);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?>NyumbaHub Kenya</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0a58ca;
            --secondary: #6c757d;
            --dark: #1e293b;
            --light: #f8fafc;
            --gradient: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f0f2f5;
        }
        
        /* Modern Navbar */
        .modern-navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
            padding: 0.8rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            transition: all 0.3s ease;
        }
        
        .modern-navbar.scrolled {
            padding: 0.5rem 0;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .navbar-brand i {
            background: none;
            -webkit-text-fill-color: var(--primary);
            margin-right: 5px;
        }
        
        /* Nav Links */
        .nav-link {
            font-weight: 600;
            color: #475569;
            padding: 0.6rem 1.2rem;
            margin: 0 0.2rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .nav-link:hover {
            color: var(--primary);
            background: rgba(13, 110, 253, 0.08);
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            color: var(--primary);
            background: rgba(13, 110, 253, 0.1);
        }
        
        /* Dropdown Styling */
        .dropdown-menu {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
            padding: 0.7rem;
            margin-top: 0.5rem;
            min-width: 250px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .dropdown-item {
            padding: 0.7rem 1rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .dropdown-item i {
            width: 28px;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            color: white;
            transform: translateX(5px);
        }
        
        .dropdown-divider {
            margin: 0.5rem 0;
        }
        
        /* Role Badges */
        .user-badge {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
            vertical-align: middle;
        }
        
        .admin-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
            vertical-align: middle;
        }
        
        .super-badge {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
            vertical-align: middle;
        }
        
        /* User Dropdown Button */
        .user-dropdown-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 60px;
            padding: 0.4rem 1rem 0.4rem 0.6rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .user-dropdown-btn:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(13,110,253,0.15);
        }
        
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1e293b;
        }
        
        /* Buttons */
        .btn-custom-primary {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13,110,253,0.3);
            color: white;
        }
        
        .btn-custom-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 0.5rem 1.5rem;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Spacer */
        .navbar-spacer {
            height: 80px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 991px) {
            .navbar-nav {
                padding: 1rem 0;
            }
            
            .nav-link {
                padding: 0.8rem 1rem;
            }
            
            .dropdown-menu {
                border: none;
                box-shadow: none;
                padding-left: 1.5rem;
                background: transparent;
            }
            
            .dropdown-item:hover {
                transform: none;
            }
            
            .user-dropdown-btn {
                margin: 1rem 0;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dropdown-menu {
            animation: fadeIn 0.2s ease;
        }
        
        /* Back to top */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            background: var(--gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 999;
            border: none;
        }
        
        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }
        
        .back-to-top:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>

<!-- Modern Navbar -->
<nav class="modern-navbar" id="mainNav">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <!-- Logo -->
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-building"></i> NyumbaHub
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler d-lg-none" type="button" id="mobileToggle" style="background: none; border: none; font-size: 1.5rem;">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Nav Menu -->
            <div class="d-none d-lg-flex align-items-center" id="navMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 d-flex flex-row">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'properties.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>properties.php">
                            <i class="fas fa-search me-1"></i> Explore
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="propertyDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-building me-1"></i> Property Type
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="propertyDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?category=rent"><i class="fas fa-home"></i> Houses for Rent</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?category=sale"><i class="fas fa-hand-holding-usd"></i> Houses for Sale</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?category=airbnb"><i class="fas fa-umbrella-beach"></i> Airbnb</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?property_type=apartment"><i class="fas fa-building"></i> Apartments</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?property_type=bedsitter"><i class="fas fa-bed"></i> Bedsitters</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?property_type=commercial"><i class="fas fa-store"></i> Commercial</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?property_type=land"><i class="fas fa-mountain"></i> Land</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>agents.php">
                            <i class="fas fa-users me-1"></i> Agents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>contact.php">
                            <i class="fas fa-envelope me-1"></i> Contact
                        </a>
                    </li>
                </ul>
                
                <div class="ms-3">
                    <?php if(isLoggedIn() && isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <div class="user-dropdown-btn" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php 
                                $profileImg = 'default-avatar.png';
                                if(isset($loggedInUser['profile_image']) && $loggedInUser['profile_image'] && $loggedInUser['profile_image'] != 'default-avatar.png') {
                                    $profileImg = $loggedInUser['profile_image'];
                                }
                                ?>
                                <img src="<?php echo SITE_URL . 'uploads/profiles/' . $profileImg; ?>" class="user-avatar" alt="Avatar">
                                <span class="user-name">
                                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                                    <?php if($is_super_admin): ?>
                                        <span class="super-badge"><i class="fas fa-shield-alt"></i> Super Admin</span>
                                    <?php elseif($is_admin): ?>
                                        <span class="admin-badge"><i class="fas fa-home"></i> Owner</span>
                                    <?php else: ?>
                                        <span class="user-badge"><i class="fas fa-user"></i> Seeker</span>
                                    <?php endif; ?>
                                </span>
                                <i class="fas fa-chevron-down" style="font-size: 0.8rem;"></i>
                            </div>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <!-- SUPER ADMIN ONLY -->
                                <?php if($is_super_admin): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/dashboard.php">
                                        <i class="fas fa-shield-alt"></i> Admin Panel
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                
                                <!-- REGULAR USER (Property Seeker) Menu -->
                                <?php if($is_regular_user): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>buyer-dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>favorites.php">
                                        <i class="fas fa-heart"></i> Favorites
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>my-inquiries.php">
                                        <i class="fas fa-envelope"></i> My Inquiries
                                    </a></li>
                                <?php endif; ?>
                                
                                <!-- ADMIN (Property Owner) Menu -->
                                <?php if($is_admin): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>my-properties.php">
                                        <i class="fas fa-home"></i> My Properties
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>add-property.php">
                                        <i class="fas fa-plus-circle"></i> Add Property
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>subscription.php">
                                        <i class="fas fa-crown"></i> Subscription
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>payments.php">
                                        <i class="fas fa-credit-card"></i> Payments
                                    </a></li>
                                <?php endif; ?>
                                
                                <!-- SUPER ADMIN also gets these -->
                                <?php if($is_super_admin): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Owner Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>my-properties.php">
                                        <i class="fas fa-home"></i> My Properties
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>add-property.php">
                                        <i class="fas fa-plus-circle"></i> Add Property
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>payments.php">
                                        <i class="fas fa-credit-card"></i> Payments
                                    </a></li>
                                <?php endif; ?>
                                
                                <!-- COMMON MENU - Everyone -->
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>messages.php">
                                    <i class="fas fa-comment-dots"></i> Messages
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>profile.php">
                                    <i class="fas fa-user-edit"></i> Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="d-flex gap-2">
                            <a href="<?php echo SITE_URL; ?>login.php" class="btn-custom-outline">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                            <a href="<?php echo SITE_URL; ?>register.php" class="btn-custom-primary">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div class="mobile-menu d-none" id="mobileMenu" style="margin-top: 1rem;">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>properties.php">Explore</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="mobilePropertyDropdown" role="button" data-bs-toggle="dropdown">
                        Property Type
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?category=rent">Houses for Rent</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?category=sale">Houses for Sale</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?category=airbnb">Airbnb</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?property_type=apartment">Apartments</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?property_type=commercial">Commercial</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>properties.php?property_type=land">Land</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>agents.php">Agents</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>contact.php">Contact</a>
                </li>
            </ul>
            
            <?php if(isLoggedIn()): ?>
                <div class="mt-3">
                    <?php if($is_super_admin): ?>
                        <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="btn-custom-primary d-block text-center mb-2">
                            <i class="fas fa-shield-alt"></i> Admin Panel
                        </a>
                    <?php endif; ?>
                    <?php if($is_regular_user): ?>
                        <a href="<?php echo SITE_URL; ?>buyer-dashboard.php" class="btn-custom-outline d-block text-center mb-2">
                            Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>dashboard.php" class="btn-custom-outline d-block text-center mb-2">
                            Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>logout.php" class="btn-custom-outline d-block text-center text-danger">
                        Logout
                    </a>
                </div>
            <?php else: ?>
                <div class="mt-3">
                    <a href="<?php echo SITE_URL; ?>login.php" class="btn-custom-outline d-block text-center mb-2">Login</a>
                    <a href="<?php echo SITE_URL; ?>register.php" class="btn-custom-primary d-block text-center">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="navbar-spacer"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('mainNav');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Mobile menu toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if(mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('d-none');
        });
    }
    
    // Back to top button
    const backToTop = document.createElement('button');
    backToTop.className = 'back-to-top';
    backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
    document.body.appendChild(backToTop);
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    });
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Fix dropdown on mobile
    if (window.innerWidth <= 991) {
        $('.dropdown-toggle').on('click', function(e) {
            if ($(this).parent().hasClass('dropdown')) {
                e.preventDefault();
                var dropdownMenu = $(this).siblings('.dropdown-menu');
                dropdownMenu.toggleClass('show');
            }
        });
    }
</script>

</body>
</html>