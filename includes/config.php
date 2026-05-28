<?php
// Start output buffering to prevent header issues
ob_start();
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nyumbahub_db');

// Site Configuration
define('SITE_URL', 'http://localhost/nyumbahub/');
define('SITE_NAME', 'NyumbaHub Kenya');
define('SITE_EMAIL', 'info@nyumbahub.co.ke');
define('SITE_PHONE', '+254700000000');

// Package Prices
define('BASIC_PRICE', 1000);
define('PREMIUM_PRICE', 2500);
define('VIP_PRICE', 5000);

// Subscription Prices
define('BRONZE_PRICE', 2000);
define('SILVER_PRICE', 5000);
define('GOLD_PRICE', 10000);

// Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if($user) {
        // Set default values for any missing fields
        if(!isset($user['profile_image']) || empty($user['profile_image'])) {
            $user['profile_image'] = 'default-avatar.png';
        }
        if(!isset($user['bio'])) $user['bio'] = '';
        if(!isset($user['phone'])) $user['phone'] = '';
        if(!isset($user['subscription_plan'])) $user['subscription_plan'] = 'bronze';
        if(!isset($user['user_type'])) $user['user_type'] = 'user';
        if(!isset($user['role'])) $user['role'] = 'user';
        if(!isset($user['email_verified'])) $user['email_verified'] = 0;
        if(!isset($user['verification_badge'])) $user['verification_badge'] = 0;
    }
    
    return $user;
}

function getPropertyById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, u.full_name as owner_name, u.phone as owner_phone, 
                           u.profile_image as owner_image, u.is_verified as owner_verified,
                           c.name as county_name, t.name as town_name
                           FROM properties p 
                           LEFT JOIN users u ON p.owner_id = u.id
                           LEFT JOIN counties c ON p.county_id = c.id
                           LEFT JOIN towns t ON p.town_id = t.id
                           WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getPropertyImages($property_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
    $stmt->execute([$property_id]);
    return $stmt->fetchAll();
}

function getPropertyFeatures($property_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$property_id]);
    return $stmt->fetch();
}

function isFavorite($user_id, $property_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND property_id = ?");
    $stmt->execute([$user_id, $property_id]);
    return $stmt->rowCount() > 0;
}

function addNotification($user_id, $title, $message, $type = 'system') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $type]);
}

function getUnreadNotifications($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function redirect($url) {
    // Clean output buffer before redirect
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Location: " . SITE_URL . $url);
    exit();
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}

function getSetting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : '';
}

function updateSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

function getTotalPropertyViews($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT SUM(views_count) as total FROM properties WHERE owner_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function getUnreadMessagesCount($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['count'];
}
?>