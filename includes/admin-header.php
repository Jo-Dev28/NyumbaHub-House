<?php
// Start output buffering if not already started
if (ob_get_level() == 0) {
    ob_start();
}

if(!isset($_SESSION)) session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'super_admin')) {
    // Clean buffer before redirect
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Location: ../login.php");
    exit();
}

// Get admin info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// Get unread notifications count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_notifications = $stmt->fetch()['count'];

// Get recent notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?>Admin Panel - NyumbaHub Kenya</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            overflow-x: hidden;
        }
        
        /* Modern Sidebar */
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .admin-sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 5px;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .sidebar-header .logo-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .sidebar-header h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .sidebar-header p {
            font-size: 0.7rem;
            opacity: 0.6;
            margin-top: 5px;
        }
        
        /* Sidebar Navigation */
        .sidebar-nav {
            padding: 0 15px;
        }
        
        .nav-section {
            margin-bottom: 20px;
        }
        
        .nav-section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.4);
            padding: 10px 15px;
            margin-bottom: 5px;
        }
        
        .nav-item {
            margin: 3px 0;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 12px 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .nav-link:hover {
            background: rgba(13,110,253,0.2);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            box-shadow: 0 5px 15px rgba(13,110,253,0.3);
        }
        
        .nav-link i {
            width: 24px;
            font-size: 1.2rem;
        }
        
        .nav-link .badge {
            margin-left: auto;
            background: #dc3545;
        }
        
        /* Main Content */
        .admin-main {
            margin-left: 280px;
            transition: all 0.3s ease;
        }
        
        /* Top Navbar */
        .admin-topbar {
            background: white;
            padding: 12px 25px;
            margin: 20px 20px 0 20px;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
        }
        
        .page-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
            color: #1a1a2e;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        /* Notifications */
        .notifications-dropdown {
            position: relative;
        }
        
        .notifications-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #666;
            cursor: pointer;
            position: relative;
            padding: 8px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .notifications-btn:hover {
            background: #f0f2f5;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .notifications-menu {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: none;
            z-index: 1000;
            margin-top: 10px;
        }
        
        .notifications-menu.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .notifications-header {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 600;
        }
        
        .notifications-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #e7f3ff;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .notification-message {
            font-size: 0.8rem;
            color: #666;
        }
        
        .notification-time {
            font-size: 0.7rem;
            color: #999;
            margin-top: 5px;
        }
        
        .notifications-footer {
            padding: 12px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        
        /* User Dropdown */
        .user-dropdown {
            position: relative;
        }
        
        .user-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .user-btn:hover {
            background: #f0f2f5;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info {
            text-align: left;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .user-role {
            font-size: 0.7rem;
            color: #666;
        }
        
        .user-menu {
            position: absolute;
            top: 100%;
            right: 0;
            width: 250px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: none;
            z-index: 1000;
            margin-top: 10px;
        }
        
        .user-menu.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        .user-menu-header {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            text-align: center;
        }
        
        .user-menu-item {
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .user-menu-item:hover {
            background: #f8f9fa;
            padding-left: 20px;
        }
        
        .user-menu-item.text-danger:hover {
            background: #fee;
        }
        
        .switch-user-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 8px 15px;
            border-radius: 12px;
            color: white;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .switch-user-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
            color: white;
        }
        
        /* Content Area */
        .admin-content {
            padding: 20px;
        }
        
        /* Cards */
        .admin-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
            border: none;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        /* Badge Styles */
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-suspended { background: #f8d7da; color: #721c24; }
        
        /* Mobile Responsive */
        @media (max-width: 991px) {
            .admin-sidebar {
                transform: translateX(-100%);
                z-index: 1050;
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
            }
            .notifications-menu {
                width: 300px;
                right: -50px;
            }
            .user-menu {
                right: -20px;
            }
        }
        
        /* Utility Classes */
        .text-gradient {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>

<!-- Modern Sidebar -->
<div class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <div class="logo-icon">
            <i class="fas fa-building"></i>
        </div>
        <h3>NyumbaHub</h3>
        <p>Admin Dashboard</p>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">MAIN</div>
            <div class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i> Users
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'properties.php' ? 'active' : ''; ?>" href="properties.php">
                    <i class="fas fa-building"></i> Properties
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">FINANCIAL</div>
            <div class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>" href="payments.php">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'subscriptions.php' ? 'active' : ''; ?>" href="subscriptions.php">
                    <i class="fas fa-crown"></i> Subscriptions
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-line"></i> Reports
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">SYSTEM</div>
            <div class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" href="../">
                    <i class="fas fa-globe"></i> View Website
                </a>
            </div>
        </div>
        
        <div class="nav-section mt-4">
            <div class="nav-item">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="admin-main">
    <!-- Top Navbar -->
    <div class="admin-topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4 class="page-title"><?php echo $page_title ?? 'Dashboard'; ?></h4>
        </div>
        
        <div class="topbar-right">
            <!-- Switch to User Side Button -->
            <a href="../dashboard.php" class="switch-user-btn">
                <i class="fas fa-user"></i> Switch to User Side
            </a>
            
            <!-- Notifications Dropdown -->
            <div class="notifications-dropdown">
                <button class="notifications-btn" id="notificationsBtn">
                    <i class="fas fa-bell"></i>
                    <?php if($unread_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </button>
                <div class="notifications-menu" id="notificationsMenu">
                    <div class="notifications-header">
                        <i class="fas fa-bell"></i> Notifications
                        <?php if($unread_notifications > 0): ?>
                            <span class="badge bg-primary ms-2"><?php echo $unread_notifications; ?> new</span>
                        <?php endif; ?>
                    </div>
                    <div class="notifications-list">
                        <?php if(count($notifications) > 0): ?>
                            <?php foreach($notifications as $notif): ?>
                            <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>" data-id="<?php echo $notif['id']; ?>">
                                <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                <div class="notification-message"><?php echo htmlspecialchars(substr($notif['message'], 0, 60)); ?></div>
                                <div class="notification-time">
                                    <i class="far fa-clock"></i> <?php echo timeAgo($notif['created_at']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No notifications yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="notifications-footer">
                        <a href="notifications.php" class="text-decoration-none">View All Notifications</a>
                    </div>
                </div>
            </div>
            
            <!-- User Dropdown -->
            <div class="user-dropdown">
                <button class="user-btn" id="userBtn">
                    <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($admin['profile_image'] ?? 'default-avatar.png'); ?>" 
                         class="user-avatar" alt="Admin">
                    <div class="user-info d-none d-sm-block">
                        <div class="user-name"><?php echo htmlspecialchars($admin['full_name']); ?></div>
                        <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?> Admin</div>
                    </div>
                    <i class="fas fa-chevron-down d-none d-sm-block"></i>
                </button>
                <div class="user-menu" id="userMenu">
                    <div class="user-menu-header">
                        <img src="<?php echo SITE_URL . 'uploads/profiles/' . ($admin['profile_image'] ?? 'default-avatar.png'); ?>" 
                             class="rounded-circle mb-2" width="50" height="50" style="object-fit: cover;">
                        <h6 class="mb-0"><?php echo htmlspecialchars($admin['full_name']); ?></h6>
                        <small class="text-muted"><?php echo $admin['email']; ?></small>
                    </div>
                    <a href="profile.php" class="user-menu-item">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <a href="settings.php" class="user-menu-item">
                        <i class="fas fa-cog"></i> Account Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="../dashboard.php" class="user-menu-item">
                        <i class="fas fa-eye"></i> View Website
                    </a>
                    <a href="../logout.php" class="user-menu-item text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Area -->
    <div class="admin-content">

<script>
    // Mobile menu toggle
    document.getElementById('menuToggle')?.addEventListener('click', function() {
        document.getElementById('adminSidebar').classList.toggle('show');
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('adminSidebar');
        const toggle = document.getElementById('menuToggle');
        if (window.innerWidth <= 991) {
            if (sidebar && toggle && !sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
    
    // Notifications dropdown
    const notificationsBtn = document.getElementById('notificationsBtn');
    const notificationsMenu = document.getElementById('notificationsMenu');
    
    if(notificationsBtn) {
        notificationsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsMenu.classList.toggle('show');
            // Mark notifications as read when opened
            if(notificationsMenu.classList.contains('show')) {
                $.ajax({
                    url: 'ajax/mark-notifications-read.php',
                    method: 'POST',
                    success: function() {
                        $('.notification-badge').hide();
                        $('.notification-item').removeClass('unread');
                    }
                });
            }
        });
    }
    
    // User dropdown
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');
    
    if(userBtn) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        if(notificationsMenu) notificationsMenu.classList.remove('show');
        if(userMenu) userMenu.classList.remove('show');
    });
    
    // Notification item click
    $('.notification-item').click(function() {
        var id = $(this).data('id');
        $.ajax({
            url: 'ajax/mark-notification-read.php',
            method: 'POST',
            data: {id: id},
            success: function() {
                location.reload();
            }
        });
    });
</script>