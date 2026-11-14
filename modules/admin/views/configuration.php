<?php
require_once __DIR__ . '/../admin_init.php';
$current_page = 'configuration';
$settings = ['site_name' => 'CancerCare', 'maintenance_mode' => 'off', 'default_currency' => 'LKR'];
$result = $conn->query("SELECT setting_key, setting_value FROM AdminSetting");
while ($row = $result->fetch_assoc()) if (array_key_exists($row['setting_key'], $settings)) $settings[$row['setting_key']] = $row['setting_value'];
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Configuration - CancerCare</title><link rel="stylesheet" href="../../../public/css/base.css"></head>
<body><div class="app"><?php require_once __DIR__ . '/../../../includes/admin_sidebar.php'; ?><main class="main">
<header class="topbar"><div><h2>System Configuration</h2><p class="date">Manage basic system settings</p></div><a href="../../../logout.php" class="signout-btn">Sign Out</a></header>
<div class="content"><div class="card form-card"><div class="list-card-header"><h3>Application Settings</h3></div>
<?php if (isset($_GET['saved'])): ?><p class="panel-note" style="color:var(--teal);">Settings saved.</p><?php endif; ?>
<form method="post" action="../admin_actions.php" class="form-grid"><input type="hidden" name="action" value="save_settings">
<div class="form-field"><label>System Name</label><input name="site_name" required value="<?php echo admin_escape($settings['site_name']); ?>"></div>
<div class="form-field"><label>Default Donation Currency</label><select name="default_currency"><option <?php echo $settings['default_currency'] === 'LKR' ? 'selected' : ''; ?>>LKR</option><option <?php echo $settings['default_currency'] === 'USD' ? 'selected' : ''; ?>>USD</option></select></div>
<div class="form-field"><label>Maintenance Mode</label><select name="maintenance_mode"><option value="off" <?php echo $settings['maintenance_mode'] === 'off' ? 'selected' : ''; ?>>Off</option><option value="on" <?php echo $settings['maintenance_mode'] === 'on' ? 'selected' : ''; ?>>On</option></select></div>
<div class="form-actions form-field-wide"><button class="btn-primary" type="submit">Save Settings</button></div></form></div></div></main></div></body></html>
