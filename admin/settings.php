<?php
require_once '../includes/config.php';
require_once '../includes/admin-header.php';

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $settings = [
            'site_name' => sanitize($_POST['site_name']),
            'site_email' => sanitize($_POST['site_email']),
            'admin_email' => sanitize($_POST['admin_email']),
            'currency' => sanitize($_POST['currency']),
            'tax_rate' => (float)$_POST['tax_rate'],
            'commission_rate' => (float)$_POST['commission_rate'],
            'facebook_url' => sanitize($_POST['facebook_url']),
            'twitter_url' => sanitize($_POST['twitter_url']),
            'instagram_url' => sanitize($_POST['instagram_url']),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
        ];
        
        foreach($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $success = "Settings updated successfully!";
    } catch(Exception $e) {
        $error = "Failed to update settings: " . $e->getMessage();
    }
}

// Get current settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM admin_settings");
while($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$page_title = 'System Settings';
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4">
        <h5 class="mb-0"><i class="fas fa-cog text-primary"></i> System Settings</h5>
    </div>
    <div class="card-body">
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-3">General Settings</h6>
                    <div class="mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?php echo $settings['site_name'] ?? 'NyumbaHub Kenya'; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Site Email</label>
                        <input type="email" name="site_email" class="form-control" value="<?php echo $settings['site_email'] ?? 'info@nyumbahub.co.ke'; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Email</label>
                        <input type="email" name="admin_email" class="form-control" value="<?php echo $settings['admin_email'] ?? 'admin@nyumbahub.co.ke'; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Currency</label>
                        <select name="currency" class="form-select">
                            <option value="KES" <?php echo ($settings['currency'] ?? 'KES') == 'KES' ? 'selected' : ''; ?>>Kenyan Shilling (KES)</option>
                            <option value="USD" <?php echo ($settings['currency'] ?? 'KES') == 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h6 class="mb-3">Payment Settings</h6>
                    <div class="mb-3">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" class="form-control" step="0.01" value="<?php echo $settings['tax_rate'] ?? 0; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Commission Rate (%)</label>
                        <input type="number" name="commission_rate" class="form-control" step="0.01" value="<?php echo $settings['commission_rate'] ?? 5; ?>">
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="maintenance_mode" class="form-check-input" id="maintenanceMode" <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenanceMode">Maintenance Mode</label>
                        </div>
                    </div>
                </div>
                
                <div class="col-12">
                    <h6 class="mb-3 mt-3">Social Media Links</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><i class="fab fa-facebook text-primary"></i> Facebook</label>
                                <input type="url" name="facebook_url" class="form-control" value="<?php echo $settings['facebook_url'] ?? ''; ?>" placeholder="https://facebook.com/...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><i class="fab fa-twitter text-info"></i> Twitter</label>
                                <input type="url" name="twitter_url" class="form-control" value="<?php echo $settings['twitter_url'] ?? ''; ?>" placeholder="https://twitter.com/...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label"><i class="fab fa-instagram text-danger"></i> Instagram</label>
                                <input type="url" name="instagram_url" class="form-control" value="<?php echo $settings['instagram_url'] ?? ''; ?>" placeholder="https://instagram.com/...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?>