<?php
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

// Add this to your config.php file to ensure default avatar exists
function getDefaultAvatar() {
    return 'default-avatar.png';
}

// Update getUserById function to handle missing fields
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if($user) {
        // Set default values for any missing fields
        $defaults = [
            'profile_image' => 'default-avatar.png',
            'bio' => '',
            'facebook_link' => '',
            'twitter_link' => '',
            'instagram_link' => '',
            'user_type' => 'owner',
            'subscription_plan' => 'bronze',
            'email_verified' => 0,
            'verification_badge' => 0,
            'phone' => ''
        ];
        
        foreach($defaults as $key => $value) {
            if(!isset($user[$key]) || $user[$key] === null) {
                $user[$key] = $value;
            }
        }
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
    $stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC");
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

?>