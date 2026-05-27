<?php
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

// Get all subscriptions
$stmt = $pdo->query("SELECT s.*, u.full_name as user_name, u.email as user_email 
                     FROM subscriptions s 
                     JOIN users u ON s.user_id = u.id 
                     ORDER BY s.created_at DESC");
$subscriptions = $stmt->fetchAll();

$page_title = 'Subscription Management';
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4">
        <h5 class="mb-0"><i class="fas fa-crown text-primary"></i> Subscription Management</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($subscriptions as $sub): ?>
                    <tr>
                        <td>#<?php echo $sub['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($sub['user_name']); ?></strong><br>
                            <small><?php echo $sub['user_email']; ?></small>
                        </td>
                        <td><span class="badge bg-<?php echo $sub['plan'] == 'gold' ? 'warning' : ($sub['plan'] == 'silver' ? 'secondary' : 'info'); ?>">
                            <?php echo ucfirst($sub['plan']); ?>
                        </span></td>
                        <td>KES <?php echo number_format($sub['amount']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($sub['start_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($sub['end_date'])); ?></td>
                        <td>
                            <span class="badge-status badge-<?php echo $sub['status'] == 'active' ? 'approved' : 'pending'; ?>">
                                <?php echo ucfirst($sub['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="cancelSubscription(<?php echo $sub['id']; ?>)">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function cancelSubscription(id) {
    Swal.fire({
        title: 'Cancel Subscription?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, cancel it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax/cancel-subscription.php',
                method: 'POST',
                data: {subscription_id: id},
                success: function(response) {
                    var data = JSON.parse(response);
                    if(data.success) {
                        Swal.fire('Cancelled!', data.message, 'success');
                        location.reload();
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

<?php require_once '../includes/admin-footer.php'; ?>