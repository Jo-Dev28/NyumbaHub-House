<?php
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

// Get statistics
// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$total_users = $stmt->fetch()['count'];

// Total properties
$stmt = $pdo->query("SELECT COUNT(*) as count FROM properties");
$total_properties = $stmt->fetch()['count'];

// Total payments
$stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
$total_revenue = $stmt->fetch()['total'] ?? 0;

// Pending approvals
$stmt = $pdo->query("SELECT COUNT(*) as count FROM properties WHERE status = 'pending_approval'");
$pending_properties = $stmt->fetch()['count'];

// Recent users
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

// Recent properties
$stmt = $pdo->query("SELECT p.*, u.full_name as owner_name 
                     FROM properties p 
                     JOIN users u ON p.owner_id = u.id 
                     ORDER BY p.created_at DESC LIMIT 5");
$recent_properties = $stmt->fetchAll();

// Monthly revenue data for chart
$stmt = $pdo->query("SELECT 
                     DATE_FORMAT(created_at, '%M') as month,
                     SUM(amount) as total
                     FROM payments 
                     WHERE status = 'completed' 
                     AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY MONTH(created_at)
                     ORDER BY created_at ASC");
$revenue_data = $stmt->fetchAll();

$months = [];
$revenues = [];
foreach($revenue_data as $data) {
    $months[] = $data['month'];
    $revenues[] = $data['total'];
}

$page_title = 'Dashboard';
?>

<style>
    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-icon {
        width: 70px;
        height: 70px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 2rem;
    }
    .stat-value { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
    .stat-label { color: #6c757d; font-size: 0.9rem; }
</style>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?php echo number_format($total_users); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-value"><?php echo number_format($total_properties); ?></div>
            <div class="stat-label">Total Properties</div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto">
                <i class="fas fa-credit-card"></i>
            </div>
            <div class="stat-value">KES <?php echo number_format($total_revenue); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning mx-auto">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-value"><?php echo number_format($pending_properties); ?></div>
            <div class="stat-label">Pending Approval</div>
        </div>
    </div>
</div>

<!-- Revenue Chart -->
<div class="row mb-4">
    <div class="col-12">
        <div class="admin-card">
            <h5 class="mb-3"><i class="fas fa-chart-line text-primary"></i> Revenue Overview (Last 6 Months)</h5>
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Users -->
    <div class="col-md-6 mb-4">
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-users text-primary"></i> Recent Users</h5>
                <a href="users.php" class="btn btn-sm btn-link">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>User</th><th>Email</th><th>Joined</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td><span class="badge bg-success">Active</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Recent Properties -->
    <div class="col-md-6 mb-4">
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-building text-primary"></i> Recent Properties</h5>
                <a href="properties.php" class="btn btn-sm btn-link">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>Title</th><th>Owner</th><th>Price</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_properties as $property): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(substr($property['title'], 0, 25)); ?></td>
                            <td><?php echo htmlspecialchars($property['owner_name']); ?></td>
                            <td>KES <?php echo number_format($property['category'] == 'sale' ? $property['price_sale'] : $property['price_rent']); ?></td>
                            <td>
                                <span class="badge-status badge-<?php echo $property['status'] == 'approved' ? 'approved' : ($property['status'] == 'pending_approval' ? 'pending' : 'rejected'); ?>">
                                    <?php echo str_replace('_', ' ', $property['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Revenue (KES)',
            data: <?php echo json_encode($revenues); ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' }
        }
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>