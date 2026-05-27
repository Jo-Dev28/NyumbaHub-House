<?php
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

// Get date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get report data
$stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users WHERE role = 'user' AND created_at BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$new_users = $stmt->fetch()['total_users'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total_properties FROM properties WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$new_properties = $stmt->fetch()['total_properties'];

$stmt = $pdo->prepare("SELECT SUM(amount) as total_revenue FROM payments WHERE status = 'completed' AND created_at BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_revenue = $stmt->fetch()['total_revenue'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as total_payments FROM payments WHERE status = 'completed' AND created_at BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_payments = $stmt->fetch()['total_payments'];

// Get top properties by views
$stmt = $pdo->query("SELECT p.title, p.views_count, u.full_name as owner 
                     FROM properties p 
                     JOIN users u ON p.owner_id = u.id 
                     ORDER BY p.views_count DESC LIMIT 10");
$top_properties = $stmt->fetchAll();

// Get payment method distribution
$stmt = $pdo->query("SELECT payment_method, COUNT(*) as count, SUM(amount) as total 
                     FROM payments WHERE status = 'completed' 
                     GROUP BY payment_method");
$payment_methods = $stmt->fetchAll();

$page_title = 'Reports';
?>

<style>
    .report-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .report-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
</style>

<div class="row mb-4">
    <div class="col-12">
        <div class="admin-card">
            <h5 class="mb-3"><i class="fas fa-calendar text-primary"></i> Date Range Report</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary form-control">
                        <i class="fas fa-chart-line"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="report-card">
            <i class="fas fa-users fa-2x text-primary mb-2"></i>
            <div class="report-value"><?php echo number_format($new_users); ?></div>
            <div>New Users</div>
            <small class="text-muted"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="report-card">
            <i class="fas fa-building fa-2x text-success mb-2"></i>
            <div class="report-value"><?php echo number_format($new_properties); ?></div>
            <div>New Properties</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="report-card">
            <i class="fas fa-credit-card fa-2x text-info mb-2"></i>
            <div class="report-value"><?php echo number_format($total_payments); ?></div>
            <div>Transactions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="report-card">
            <i class="fas fa-chart-line fa-2x text-warning mb-2"></i>
            <div class="report-value">KES <?php echo number_format($total_revenue); ?></div>
            <div>Total Revenue</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="admin-card">
            <h5 class="mb-3"><i class="fas fa-eye text-primary"></i> Most Viewed Properties</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>Property</th><th>Owner</th><th>Views</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($top_properties as $prop): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(substr($prop['title'], 0, 30)); ?></td>
                            <td><?php echo htmlspecialchars($prop['owner']); ?></td>
                            <td><strong><?php echo number_format($prop['views_count']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="admin-card">
            <h5 class="mb-3"><i class="fas fa-chart-pie text-primary"></i> Payment Methods</h5>
            <canvas id="paymentChart" height="250"></canvas>
            <div class="mt-3">
                <?php foreach($payment_methods as $method): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span><i class="fas fa-<?php echo $method['payment_method'] == 'mpesa' ? 'mobile-alt' : ($method['payment_method'] == 'paypal' ? 'fa-paypal' : 'credit-card'); ?>"></i> <?php echo strtoupper($method['payment_method']); ?></span>
                    <span><strong><?php echo number_format($method['count']); ?></strong> transactions</span>
                    <span><strong>KES <?php echo number_format($method['total']); ?></strong></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="admin-card">
            <h5 class="mb-3"><i class="fas fa-download text-primary"></i> Export Reports</h5>
            <div class="row">
                <div class="col-md-3">
                    <a href="export-report.php?type=users&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-file-excel"></i> Export Users
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="export-report.php?type=properties&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" class="btn btn-outline-success w-100 mb-2">
                        <i class="fas fa-file-excel"></i> Export Properties
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="export-report.php?type=payments&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" class="btn btn-outline-info w-100 mb-2">
                        <i class="fas fa-file-excel"></i> Export Payments
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="export-report.php?type=revenue&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" class="btn btn-outline-warning w-100 mb-2">
                        <i class="fas fa-file-pdf"></i> Export Revenue
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('paymentChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($payment_methods, 'payment_method')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($payment_methods, 'total')); ?>,
            backgroundColor: ['#0d6efd', '#28a745', '#ffc107']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>