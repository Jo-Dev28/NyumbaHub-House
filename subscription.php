<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get current subscription
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE() ORDER BY end_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$current_subscription = $stmt->fetch();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subscribe'])) {
    $plan = $_POST['plan'];
    
    $prices = [
        'bronze' => 2000,
        'silver' => 5000,
        'gold' => 10000
    ];
    
    $amount = $prices[$plan];
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+30 days'));
    
    // Record payment
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method, package_type, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $amount, 'mpesa', $plan]);
    $payment_id = $pdo->lastInsertId();
    
    // Create subscription
    $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan, amount, start_date, end_date, payment_id) VALUES (?, ?, ?, ?, ?, ?)");
    if($stmt->execute([$user_id, $plan, $amount, $start_date, $end_date, $payment_id])) {
        // Update user subscription
        $stmt = $pdo->prepare("UPDATE users SET subscription_plan = ?, subscription_expiry = ? WHERE id = ?");
        $stmt->execute([$plan, $end_date, $user_id]);
        
        $_SESSION['subscription_plan'] = $plan;
        
        // Update payment status
        $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
        $stmt->execute([$payment_id]);
        
        $success = "Successfully subscribed to " . ucfirst($plan) . " plan!";
        $current_subscription = ['plan' => $plan, 'end_date' => $end_date];
    }
}

$page_title = 'Subscription';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'includes/user-sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Current Subscription -->
            <?php if($current_subscription): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0"><i class="fas fa-crown text-warning"></i> Current Subscription</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h3 class="text-primary mb-0"><?php echo ucfirst($current_subscription['plan']); ?> Plan</h3>
                            <p class="text-muted">Expires: <?php echo date('F d, Y', strtotime($current_subscription['end_date'])); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="progress" style="height: 10px;">
                                <?php
                                $total_days = 30;
                                $days_left = (strtotime($current_subscription['end_date']) - time()) / 86400;
                                $percentage = ($days_left / $total_days) * 100;
                                ?>
                                <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo round($days_left); ?> days remaining</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Subscription Plans -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0"><i class="fas fa-gem text-primary"></i> Choose Your Plan</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Bronze Plan -->
                        <div class="col-md-4">
                            <div class="pricing-card text-center p-4 border rounded-4 h-100">
                                <div class="pricing-icon mb-3">
                                    <i class="fas fa-medal fa-3x text-bronze"></i>
                                </div>
                                <h3>Bronze Plan</h3>
                                <div class="price mb-3">
                                    <h2 class="text-primary">KES 2,000</h2>
                                    <small class="text-muted">per month</small>
                                </div>
                                <ul class="list-unstyled text-start mb-4">
                                    <li><i class="fas fa-check text-success me-2"></i> Up to 5 Active Listings</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Basic Support</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Standard Listing Position</li>
                                    <li><i class="fas fa-times text-danger me-2"></i> Featured Listings</li>
                                    <li><i class="fas fa-times text-danger me-2"></i> Priority Support</li>
                                </ul>
                                <?php if(!$current_subscription || $current_subscription['plan'] != 'bronze'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="plan" value="bronze">
                                        <button type="submit" name="subscribe" class="btn btn-outline-primary w-100">
                                            Select Plan
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-success w-100" disabled>Current Plan</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Silver Plan -->
                        <div class="col-md-4">
                            <div class="pricing-card text-center p-4 border rounded-4 h-100 bg-primary text-white">
                                <div class="pricing-icon mb-3">
                                    <i class="fas fa-gem fa-3x"></i>
                                </div>
                                <h3>Silver Plan</h3>
                                <div class="price mb-3">
                                    <h2>KES 5,000</h2>
                                    <small>per month</small>
                                </div>
                                <ul class="list-unstyled text-start mb-4">
                                    <li><i class="fas fa-check me-2"></i> Up to 15 Active Listings</li>
                                    <li><i class="fas fa-check me-2"></i> Priority Support</li>
                                    <li><i class="fas fa-check me-2"></i> Premium Listing Position</li>
                                    <li><i class="fas fa-check me-2"></i> 5 Featured Listings</li>
                                    <li><i class="fas fa-times me-2"></i> VIP Support</li>
                                </ul>
                                <?php if(!$current_subscription || $current_subscription['plan'] != 'silver'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="plan" value="silver">
                                        <button type="submit" name="subscribe" class="btn btn-light w-100">
                                            Select Plan
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-light w-100" disabled>Current Plan</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Gold Plan -->
                        <div class="col-md-4">
                            <div class="pricing-card text-center p-4 border rounded-4 h-100">
                                <div class="pricing-icon mb-3">
                                    <i class="fas fa-crown fa-3x text-warning"></i>
                                </div>
                                <h3>Gold Plan</h3>
                                <div class="price mb-3">
                                    <h2 class="text-warning">KES 10,000</h2>
                                    <small class="text-muted">per month</small>
                                </div>
                                <ul class="list-unstyled text-start mb-4">
                                    <li><i class="fas fa-check text-success me-2"></i> Unlimited Listings</li>
                                    <li><i class="fas fa-check text-success me-2"></i> VIP Support 24/7</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Top Search Priority</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Unlimited Featured Listings</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Homepage Banner</li>
                                </ul>
                                <?php if(!$current_subscription || $current_subscription['plan'] != 'gold'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="plan" value="gold">
                                        <button type="submit" name="subscribe" class="btn btn-warning w-100">
                                            Select Plan
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-success w-100" disabled>Current Plan</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Plan Benefits -->
            <div class="card border-0 shadow-sm rounded-4 mt-4">
                <div class="card-body p-4">
                    <h5 class="mb-3"><i class="fas fa-info-circle text-primary"></i> Plan Benefits</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-chart-line text-success me-2"></i> Increased property visibility</li>
                                <li><i class="fas fa-rocket text-success me-2"></i> Faster property approval</li>
                                <li><i class="fas fa-headset text-success me-2"></i> Priority customer support</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-badge-check text-success me-2"></i> Verified owner badge</li>
                                <li><i class="fas fa-chart-simple text-success me-2"></i> Advanced analytics</li>
                                <li><i class="fas fa-ad text-success me-2"></i> Free property promotion</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pricing-card {
    transition: all 0.3s ease;
}
.pricing-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}
.text-bronze {
    color: #cd7f32;
}
</style>

<?php require_once 'includes/footer.php'; ?>