<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get all payments where user is either payer OR property owner
$stmt = $pdo->prepare("SELECT p.*, 
                       pr.title as property_title,
                       pr.owner_id as property_owner_id,
                       pr.listing_package as property_package,
                       u.full_name as payer_name,
                       u.email as payer_email,
                       CASE 
                           WHEN p.user_id = ? THEN 'sent'
                           WHEN pr.owner_id = ? THEN 'received'
                           ELSE 'other'
                       END as transaction_type
                       FROM payments p
                       LEFT JOIN properties pr ON p.property_id = pr.id
                       LEFT JOIN users u ON p.user_id = u.id
                       WHERE p.user_id = ? OR pr.owner_id = ?
                       ORDER BY p.created_at DESC");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$payments = $stmt->fetchAll();

// Get payment statistics
$stmt = $pdo->prepare("SELECT 
                       COUNT(*) as total_payments,
                       SUM(amount) as total_amount,
                       SUM(CASE WHEN status = 'completed' AND user_id = ? THEN amount ELSE 0 END) as total_sent,
                       SUM(CASE WHEN status = 'completed' AND property_id IN (SELECT id FROM properties WHERE owner_id = ?) THEN amount ELSE 0 END) as total_received
                       FROM payments WHERE user_id = ? OR property_id IN (SELECT id FROM properties WHERE owner_id = ?)");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$stats = $stmt->fetch();

// Get properties owned by user for filtering
$stmt = $pdo->prepare("SELECT id, title, listing_package, status FROM properties WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$user_properties = $stmt->fetchAll();

$page_title = 'Payment History';
require_once 'includes/header.php';
?>

<style>
    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        height: 100%;
        text-align: center;
    }
    .stat-card i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    .payment-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }
    .status-completed { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-failed { background: #f8d7da; color: #721c24; }
    .status-refunded { background: #cce5ff; color: #004085; }
    .transaction-type {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    .type-sent { background: #ffeaa7; color: #d63031; }
    .type-received { background: #d4edda; color: #00b894; }
    .filter-btn {
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 5px;
    }
    .filter-btn.active {
        background: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }
    .receipt-modal .modal-content {
        border-radius: 15px;
    }
    @media (max-width: 768px) {
        .stat-card { padding: 1rem; }
        .table-responsive { font-size: 12px; }
        .btn-group { flex-wrap: wrap; }
        .filter-btn { margin: 2px; }
    }
    /* Print styles for receipt */
    @media print {
        .modal-header, .modal-footer, .btn-close, .btn {
            display: none !important;
        }
        .modal-content {
            border: none !important;
            box-shadow: none !important;
        }
        .modal-dialog {
            margin: 0 !important;
            padding: 0 !important;
        }
        body * {
            visibility: hidden;
        }
        #receiptModal, #receiptModal * {
            visibility: visible;
        }
        #receiptModal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: white;
        }
    }
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'includes/user-sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="mb-4">
                <h2 class="fw-bold mb-2"><i class="fas fa-credit-card text-primary"></i> Payment History</h2>
                <p class="text-muted">View all your payment transactions (sent and received)</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <i class="fas fa-receipt text-primary"></i>
                        <h3 class="mb-0"><?php echo number_format($stats['total_payments'] ?? 0); ?></h3>
                        <small class="text-muted">Total Transactions</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <i class="fas fa-arrow-up text-danger"></i>
                        <h3 class="mb-0">KES <?php echo number_format($stats['total_sent'] ?? 0); ?></h3>
                        <small class="text-muted">Payments Sent</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <i class="fas fa-arrow-down text-success"></i>
                        <h3 class="mb-0">KES <?php echo number_format($stats['total_received'] ?? 0); ?></h3>
                        <small class="text-muted">Payments Received</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <i class="fas fa-chart-line text-info"></i>
                        <h3 class="mb-0">KES <?php echo number_format($stats['total_amount'] ?? 0); ?></h3>
                        <small class="text-muted">Total Volume</small>
                    </div>
                </div>
            </div>
            
            <!-- Filter Buttons -->
            <div class="mb-4">
                <div class="btn-group flex-wrap" role="group">
                    <button class="btn btn-outline-primary filter-btn active" data-filter="all">All Transactions</button>
                    <button class="btn btn-outline-primary filter-btn" data-filter="sent">Payments Sent</button>
                    <button class="btn btn-outline-primary filter-btn" data-filter="received">Payments Received</button>
                    <button class="btn btn-outline-primary filter-btn" data-filter="completed">Completed</button>
                    <button class="btn btn-outline-primary filter-btn" data-filter="pending">Pending</button>
                </div>
            </div>
            
            <!-- Payments Table -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0"><i class="fas fa-history text-primary"></i> Transaction History</h5>
                </div>
                <div class="card-body">
                    <?php if(count($payments) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="paymentsTable">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Date</th>
                                        <th>Property</th>
                                        <th>Type</th>
                                        <th>From/To</th>
                                        <th>Package</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($payments as $payment): ?>
                                    <tr data-type="<?php echo $payment['transaction_type']; ?>" data-status="<?php echo $payment['status']; ?>">
                                        <td>
                                            <code>#<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></code>
                                         </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></td>
                                        <td>
                                            <?php if($payment['property_title']): ?>
                                                <strong><?php echo htmlspecialchars(substr($payment['property_title'], 0, 30)); ?></strong>
                                                <?php if($payment['transaction_type'] == 'received'): ?>
                                                    <br><small class="text-muted">Your property</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                         </td>
                                        <td>
                                            <?php if($payment['transaction_type'] == 'sent'): ?>
                                                <span class="transaction-type type-sent">
                                                    <i class="fas fa-arrow-up"></i> Payment Sent
                                                </span>
                                            <?php else: ?>
                                                <span class="transaction-type type-received">
                                                    <i class="fas fa-arrow-down"></i> Payment Received
                                                </span>
                                            <?php endif; ?>
                                         </td>
                                        <td>
                                            <?php if($payment['transaction_type'] == 'sent'): ?>
                                                To: <strong><?php echo htmlspecialchars($payment['property_owner_id'] == $user_id ? 'Your Property' : ($payment['payer_name'] ?? 'Owner')); ?></strong>
                                            <?php else: ?>
                                                From: <strong><?php echo htmlspecialchars($payment['payer_name'] ?? 'Guest'); ?></strong>
                                                <br><small><?php echo $payment['payer_email']; ?></small>
                                            <?php endif; ?>
                                         </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo ucfirst($payment['package_type']); ?></span>
                                         </td>
                                        <td>
                                            <strong class="<?php echo $payment['transaction_type'] == 'sent' ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo $payment['transaction_type'] == 'sent' ? '- ' : '+ '; ?>
                                                KES <?php echo number_format($payment['amount']); ?>
                                            </strong>
                                         </td>
                                        <td>
                                            <?php
                                            $method_icon = '';
                                            switch($payment['payment_method']) {
                                                case 'mpesa':
                                                    $method_icon = '<i class="fas fa-mobile-alt text-success"></i> M-Pesa';
                                                    break;
                                                case 'paypal':
                                                    $method_icon = '<i class="fab fa-paypal text-primary"></i> PayPal';
                                                    break;
                                                case 'card':
                                                    $method_icon = '<i class="fas fa-credit-card text-info"></i> Card';
                                                    break;
                                                default:
                                                    $method_icon = ucfirst($payment['payment_method']);
                                            }
                                            echo $method_icon;
                                            ?>
                                         </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch($payment['status']) {
                                                case 'completed':
                                                    $statusClass = 'status-completed';
                                                    $statusText = '✓ Completed';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'status-pending';
                                                    $statusText = '⏳ Pending';
                                                    break;
                                                case 'failed':
                                                    $statusClass = 'status-failed';
                                                    $statusText = '✗ Failed';
                                                    break;
                                                case 'refunded':
                                                    $statusClass = 'status-refunded';
                                                    $statusText = '↺ Refunded';
                                                    break;
                                                default:
                                                    $statusClass = 'status-pending';
                                                    $statusText = ucfirst($payment['status']);
                                            }
                                            ?>
                                            <span class="payment-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                         </td>
                                        <td>
                                            <?php if($payment['status'] == 'completed'): ?>
                                                <button class="btn btn-sm btn-outline-primary view-receipt-btn" data-id="<?php echo $payment['id']; ?>">
                                                    <i class="fas fa-receipt"></i> Receipt
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                         </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                            <h5>No Payment History</h5>
                            <p class="text-muted">You haven't made any payments yet.</p>
                            <a href="add-property.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> List a Property
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Properties Summary -->
            <?php if(count($user_properties) > 0): ?>
            <div class="card border-0 shadow-sm rounded-4 mt-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h5 class="mb-0"><i class="fas fa-building text-primary"></i> Your Properties Payment Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Package</th>
                                    <th>Listing Fee</th>
                                    <th>Payment Status</th>
                                    <th>Paid Date</th>
                                    <th>Actions</th>
                                 </tr>
                            </thead>
                            <tbody>
                                <?php foreach($user_properties as $prop): 
                                    // Get payment for this property
                                    $stmt = $pdo->prepare("SELECT * FROM payments WHERE property_id = ? ORDER BY created_at DESC LIMIT 1");
                                    $stmt->execute([$prop['id']]);
                                    $prop_payment = $stmt->fetch();
                                    
                                    // Get package price safely
                                    $package_price = 1000;
                                    $package_name = isset($prop['listing_package']) ? $prop['listing_package'] : 'basic';
                                    if($package_name == 'premium') $package_price = 2500;
                                    if($package_name == 'vip') $package_price = 5000;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(substr($prop['title'], 0, 40)); ?></td>
                                    <td><span class="badge bg-primary"><?php echo ucfirst($package_name); ?></span></td>
                                    <td>KES <?php echo number_format($package_price); ?></td>
                                    <td>
                                        <?php if($prop_payment && $prop_payment['status'] == 'completed'): ?>
                                            <span class="payment-status status-completed">✓ Paid</span>
                                        <?php elseif($prop_payment && $prop_payment['status'] == 'pending'): ?>
                                            <span class="payment-status status-pending">⏳ Pending</span>
                                        <?php else: ?>
                                            <span class="payment-status status-pending">Not Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($prop_payment && $prop_payment['status'] == 'completed'): ?>
                                            <?php echo date('M d, Y', strtotime($prop_payment['created_at'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!$prop_payment || $prop_payment['status'] != 'completed'): ?>
                                            <a href="payment.php?property_id=<?php echo $prop['id']; ?>&package=<?php echo $package_name; ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-credit-card"></i> Pay Now
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary view-property-receipt" data-id="<?php echo $prop_payment['id']; ?>">
                                                <i class="fas fa-receipt"></i> View Receipt
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Payment Guide -->
            <div class="card border-0 shadow-sm rounded-4 mt-4">
                <div class="card-body p-4">
                    <h6><i class="fas fa-question-circle text-primary"></i> Payment Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="small text-muted">
                                <li><i class="fas fa-check-circle text-success"></i> M-Pesa payments are processed instantly</li>
                                <li><i class="fas fa-check-circle text-success"></i> PayPal and Card payments may take 24-48 hours</li>
                                <li><i class="fas fa-check-circle text-success"></i> All payments are securely processed</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="small text-muted">
                                <li><i class="fas fa-check-circle text-success"></i> Payment receipts are sent to your email</li>
                                <li><i class="fas fa-check-circle text-success"></i> Contact support for payment issues</li>
                                <li><i class="fas fa-check-circle text-success"></i> Refunds are processed within 7-14 days</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title"><i class="fas fa-receipt text-primary"></i> Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="receiptContent" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading receipt...</p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" id="printReceiptBtn">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Filter functionality
    $('.filter-btn').click(function() {
        var filter = $(this).data('filter');
        
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        $('#paymentsTable tbody tr').each(function() {
            var $row = $(this);
            var type = $row.data('type');
            var status = $row.data('status');
            
            if(filter === 'all') {
                $row.show();
            } else if(filter === 'sent' && type === 'sent') {
                $row.show();
            } else if(filter === 'received' && type === 'received') {
                $row.show();
            } else if(filter === 'completed' && status === 'completed') {
                $row.show();
            } else if(filter === 'pending' && (status === 'pending' || status === 'pending_payment')) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    });
    
    // View receipt for payments table
    $('.view-receipt-btn, .view-property-receipt').click(function() {
        var paymentId = $(this).data('id');
        loadReceipt(paymentId);
    });
    
    // Load receipt function
    function loadReceipt(paymentId) {
        $('#receiptContent').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading receipt...</p>
            </div>
        `);
        
        $.ajax({
            url: 'ajax/get-receipt.php',
            method: 'POST',
            data: {payment_id: paymentId},
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    var receiptHtml = `
                        <div style="font-family: Arial, sans-serif;">
                            <div style="text-align: center; padding-bottom: 20px; border-bottom: 2px solid #0d6efd;">
                                <h2 style="color: #0d6efd;">NyumbaHub Kenya</h2>
                                <p>Official Payment Receipt</p>
                            </div>
                            <div style="padding: 20px;">
                                <div class="row">
                                    <div class="col-6">
                                        <p><strong>Receipt No:</strong> ${data.receipt_no}</p>
                                        <p><strong>Transaction ID:</strong> ${data.transaction_id}</p>
                                        <p><strong>Date:</strong> ${data.date}</p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p><strong>Status:</strong> <span class="text-success">${data.status}</span></p>
                                        <p><strong>Payment Method:</strong> ${data.method}</p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-12">
                                        <p><strong>Property:</strong> ${data.property_title}</p>
                                        <p><strong>Package:</strong> ${data.package}</p>
                                        <p><strong>Amount Paid:</strong> <span style="font-size: 1.5rem; color: #0d6efd;">${data.amount}</span></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <p><strong>Paid By:</strong><br>
                                        Name: ${data.payer_name}<br>
                                        Email: ${data.payer_email}</p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p><strong>Paid To:</strong><br>
                                        NyumbaHub Kenya<br>
                                        info@nyumbahub.co.ke</p>
                                    </div>
                                </div>
                                <hr>
                                <div style="text-align: center; color: #666; font-size: 12px; margin-top: 20px;">
                                    <p>Thank you for using NyumbaHub Kenya!</p>
                                    <p>This is a computer generated receipt, no signature required.</p>
                                    <p>For any queries, contact support at support@nyumbahub.co.ke</p>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#receiptContent').html(receiptHtml);
                    $('#receiptModal').modal('show');
                } else {
                    $('#receiptContent').html(`
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle"></i> ${data.message || 'Could not load receipt'}
                        </div>
                    `);
                }
            },
            error: function() {
                $('#receiptContent').html(`
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle"></i> Error loading receipt. Please try again.
                    </div>
                `);
            }
        });
    }
    
    // Print receipt
    $('#printReceiptBtn').click(function() {
        var printContent = $('#receiptContent').html();
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Payment Receipt - NyumbaHub Kenya</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 40px; }
                    @media print {
                        body { padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                ${printContent}
                <div style="text-align: center; margin-top: 30px;" class="no-print">
                    <button onclick="window.print()">Print</button>
                    <button onclick="window.close()">Close</button>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>