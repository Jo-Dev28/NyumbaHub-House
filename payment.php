<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
$package = isset($_GET['package']) ? $_GET['package'] : 'basic';

// If property_id is provided, get from database
if($property_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND owner_id = ?");
    $stmt->execute([$property_id, $_SESSION['user_id']]);
    $property = $stmt->fetch();
    
    if($property) {
        $package = $property['listing_package'];
        $_SESSION['pending_property_id'] = $property_id;
        $_SESSION['pending_package'] = $package;
    }
}

// Get package price
$price = BASIC_PRICE;
if($package == 'premium') $price = PREMIUM_PRICE;
if($package == 'vip') $price = VIP_PRICE;

$package_name = ucfirst($package);

// Handle payment form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $payment_method = $_POST['payment_method'];
    $transaction_id = $_POST['transaction_id'] ?? null;
    $mpesa_code = $_POST['mpesa_code'] ?? null;
    
    try {
        // Record payment
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, property_id, amount, payment_method, package_type, transaction_id, mpesa_code, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')");
        $stmt->execute([$_SESSION['user_id'], $property_id, $price, $payment_method, $package, $transaction_id, $mpesa_code]);
        
        // Update property status to pending_approval
        $is_featured = ($package == 'premium' || $package == 'vip') ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE properties SET status = 'pending_approval', is_featured = ? WHERE id = ?");
        $stmt->execute([$is_featured, $property_id]);
        
        // Add notification
        addNotification($_SESSION['user_id'], 'Payment Successful', 'Your property has been submitted for approval.');
        
        // Clear session
        unset($_SESSION['pending_property_id']);
        unset($_SESSION['pending_package']);
        
        $success = "Payment successful! Your property has been submitted for admin review.";
        
        // Redirect after 3 seconds
        header("Refresh: 3; url=my-properties.php");
        
    } catch(Exception $e) {
        $error = "Payment failed: " . $e->getMessage();
    }
}

$page_title = 'Complete Payment';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow rounded-4">
                <div class="card-header bg-primary text-white text-center rounded-top-4">
                    <h4 class="mb-0"><i class="fas fa-credit-card"></i> Complete Payment</h4>
                </div>
                <div class="card-body p-4">
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h5><?php echo $success; ?></h5>
                            <p>Redirecting to your properties...</p>
                        </div>
                    <?php else: ?>
                    
                    <div class="alert alert-info">
                        <strong>Property:</strong> <?php echo isset($property) ? htmlspecialchars($property['title']) : 'New Property'; ?><br>
                        <strong>Package:</strong> <?php echo $package_name; ?><br>
                        <strong>Amount:</strong> <span class="h5 text-primary">KES <?php echo number_format($price); ?></span>
                    </div>
                    
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Select Payment Method</label>
                            <select name="payment_method" class="form-select" id="paymentMethod" required>
                                <option value="">Select Payment Method</option>
                                <option value="mpesa">M-Pesa STK Push</option>
                                <option value="paypal">PayPal</option>
                                <option value="card">Debit/Credit Card</option>
                            </select>
                        </div>
                        
                        <!-- M-Pesa Fields -->
                        <div id="mpesa_fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">M-Pesa Phone Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="07XXXXXXXX">
                                <small class="text-muted">You will receive a prompt on your phone</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">M-Pesa Transaction Code (After payment)</label>
                                <input type="text" name="mpesa_code" class="form-control" placeholder="Enter M-Pesa confirmation code">
                            </div>
                            <button type="submit" name="process_payment" class="btn btn-success w-100">
                                <i class="fas fa-money-bill-wave"></i> Pay with M-Pesa
                            </button>
                        </div>
                        
                        <!-- PayPal Fields -->
                        <div id="paypal_fields" style="display: none;">
                            <div id="paypal-button-container" class="mb-3"></div>
                            <input type="hidden" name="transaction_id" id="paypal_transaction_id">
                            <button type="submit" name="process_payment" class="btn btn-primary w-100" id="paypalSubmit" style="display: none;">
                                Confirm PayPal Payment
                            </button>
                        </div>
                        
                        <!-- Card Fields -->
                        <div id="card_fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" placeholder="1234 5678 9012 3456">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="text" class="form-control" placeholder="MM/YY">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control" placeholder="123">
                                </div>
                            </div>
                            <button type="submit" name="process_payment" class="btn btn-primary w-100 mt-3">
                                Pay with Card
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="my-properties.php" class="btn btn-link">Cancel</a>
                        </div>
                    </form>
                    
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow rounded-4 mt-4">
                <div class="card-body">
                    <h6><i class="fas fa-shield-alt text-primary"></i> Secure Payment</h6>
                    <p class="small text-muted">Your payment is secured with industry-standard encryption. We accept M-Pesa, PayPal, and all major credit cards.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#paymentMethod').change(function() {
        var method = $(this).val();
        $('#mpesa_fields, #paypal_fields, #card_fields').hide();
        if(method === 'mpesa') {
            $('#mpesa_fields').show();
        } else if(method === 'paypal') {
            $('#paypal_fields').show();
        } else if(method === 'card') {
            $('#card_fields').show();
        }
    });
});

// PayPal Integration (simulated - replace with actual PayPal SDK)
// For actual PayPal integration, uncomment and add your client ID
/*
paypal.Buttons({
    createOrder: function(data, actions) {
        return actions.order.create({
            purchase_units: [{
                amount: {
                    value: '<?php echo $price; ?>'
                }
            }]
        });
    },
    onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
            $('#paypal_transaction_id').val(details.id);
            $('#paypalSubmit').click();
        });
    }
}).render('#paypal-button-container');
*/
</script>

<!-- Uncomment for PayPal SDK -->
<!-- <script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=KES"></script> -->

<?php require_once 'includes/footer.php'; ?>