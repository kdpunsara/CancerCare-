<?php
require_once __DIR__ . '/../patient_data.php';
$search = trim(isset($_GET['q']) ? $_GET['q'] : '');

$countSql = "
    SELECT
        COUNT(*) AS total_medications,
    SUM(CASE WHEN stock_quantity > 20 THEN 1 ELSE 0 END) AS in_stock,
    SUM(CASE WHEN stock_quantity BETWEEN 1 AND 20 THEN 1 ELSE 0 END) AS low_stock,
    SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock
    FROM Medicine
";
$countResult = $conn->query($countSql);
$counts = $countResult->fetch_assoc();

$total_medications = (int)(isset($counts['total_medications']) ? $counts['total_medications'] : 0);
$in_stock = (int)(isset($counts['in_stock']) ? $counts['in_stock'] : 0);
$low_stock = (int)(isset($counts['low_stock']) ? $counts['low_stock'] : 0);
$out_of_stock = (int)(isset($counts['out_of_stock']) ? $counts['out_of_stock'] : 0);

if ($search !== '') {
    $stmt = $conn->prepare(
      "SELECT medicine_id, medicine_name, category,
        stock_quantity AS quantity,
        'Main Pharmacy' AS pharmacy_location,
        NULL AS last_updated
         FROM Medicine
         WHERE medicine_name LIKE ?
         ORDER BY medicine_name"
    );
    $like = "%" . $search . "%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $medicines = $stmt->get_result();
} else {
    $medicines = $conn->query(
      "SELECT medicine_id, medicine_name, category,
        stock_quantity AS quantity,
        'Main Pharmacy' AS pharmacy_location,
        NULL AS last_updated
         FROM Medicine
         ORDER BY medicine_name"
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Drug Availability — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css?v=3">
<link rel="stylesheet" href="../../../public/css/dashboard.css?v=3">
</head>
<body>

<div class="app">

  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark">AO</div>
      <div class="brand-text">
        <h1>Apeksha<br>OncoCare</h1>
        <p>Cancer Patient Care</p>
      </div>
    </div>

    <nav class="nav">
      <p class="nav-label">Navigation</p>
      <ul>
        <li><a href="patient_dashboard.php" class="nav-item" title="My Dashboard">
          <span class="icon icon-dashboard" aria-hidden="true"></span>
          <span class="nav-text">My Dashboard</span>
        </a></li>
        <li><a href="patient_profile.php" class="nav-item" title="My Profile">
          <span class="icon icon-profile" aria-hidden="true"></span>
          <span class="nav-text">My Profile</span>
        </a></li>
        <li><a href="patient_appointments.php" class="nav-item" title="Appointments">
          <span class="icon icon-appointments" aria-hidden="true"></span>
          <span class="nav-text">Appointments</span>
        </a></li>
        <li><a href="patient_records.php" class="nav-item" title="Medical Records">
          <span class="icon icon-records" aria-hidden="true"></span>
          <span class="nav-text">Medical Records</span>
        </a></li>
        <li><a href="patient_prescriptions.php" class="nav-item" title="Prescriptions">
          <span class="icon icon-prescriptions" aria-hidden="true"></span>
          <span class="nav-text">Prescriptions</span>
        </a></li>
        <li><a href="patient_wellness.php" class="nav-item" title="Wellness & Meals">
          <span class="icon icon-wellness" aria-hidden="true"></span>
          <span class="nav-text">Wellness & Meals</span>
        </a></li>
        <li><a href="patient_transport.php" class="nav-item" title="Transport">
          <span class="icon icon-transport" aria-hidden="true"></span>
          <span class="nav-text">Transport</span>
        </a></li>
        <li><a href="patient_drug_availability.php" class="nav-item active" title="Drug Availability">
          <span class="icon icon-drug" aria-hidden="true"></span>
          <span class="nav-text">Drug Availability</span>
        </a></li>
        <li><a href="patient_motivation.php" class="nav-item" title="Motivation">
          <span class="icon icon-motivation" aria-hidden="true"></span>
          <span class="nav-text">Motivation</span>
        </a></li>
      </ul>
    </nav>

    <div class="sidebar-user">
      <div class="avatar">SJ</div>
      <div>
        <p class="user-name">Sandun Jayasekara</p>
        <p class="user-role">Patient</p>
      </div>
    </div>
  </aside>

  <main class="main">

    <header class="topbar">
      <div class="topbar-left">
        <div>
          <h2>Drug Availability</h2>
          <p class="date">Check current medication stock at the hospital pharmacy</p>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="icon-btn" aria-label="Notifications">
          <span class="icon icon-bell" aria-hidden="true"></span>
          <span class="dot"></span>
        </button>
        <a href="../../../logout.php" class="signout-btn">Sign Out</a>
      </div>
    </header>

    <section class="content">

      <div class="stat-grid">
        <div class="card stat-card">
          <div class="stat-icon stat-icon-blue">
            <span class="icon icon-drug" aria-hidden="true"></span>
          </div>
          <p class="stat-label">Total Medications</p>
          <p class="stat-value"><?= $total_medications ?></p>
          <p class="stat-sub">Tracked at this pharmacy</p>
        </div>
        <div class="card stat-card">
          <div class="stat-icon stat-icon-teal">
            <span class="icon icon-drug" aria-hidden="true"></span>
          </div>
          <p class="stat-label">In Stock</p>
          <p class="stat-value"><?= $in_stock ?></p>
          <p class="stat-sub">Ready for dispensing</p>
        </div>
        <div class="card stat-card">
          <div class="stat-icon stat-icon-amber">
            <span class="icon icon-drug" aria-hidden="true"></span>
          </div>
          <p class="stat-label">Low Stock</p>
          <p class="stat-value"><?= $low_stock ?></p>
          <p class="stat-sub">Reorder in progress</p>
        </div>
        <div class="card stat-card">
          <div class="stat-icon stat-icon-yellow">
            <span class="icon icon-drug" aria-hidden="true"></span>
          </div>
          <p class="stat-label">Out of Stock</p>
          <p class="stat-value"><?= $out_of_stock ?></p>
          <p class="stat-sub">Expected 20 Jul 2026</p>
        </div>
      </div>

      <div class="card list-card wide-card">
        <div class="header-actions-row">
          <h3>Drug Availability</h3>
        </div>

        <form class="search-bar" action="patient_drug_availability.php" method="get">
          <div class="search-field">
            <span class="icon icon-search" aria-hidden="true"></span>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search medication by name…">
          </div>
          <button type="submit" class="search-btn">Search</button>
        </form>
        <p class="search-note">Search looks up medications by name from the pharmacy list below.</p>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Medication</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Pharmacy Location</th>
                <th>Last Updated</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($medicines->num_rows === 0): ?>
                <tr>
                  <td colspan="6">No medications found.</td>
                </tr>
              <?php else: ?>
                <?php while ($medicine = $medicines->fetch_assoc()): ?>
                  <?php
                    $quantity = (int)$medicine['quantity'];
                    if ($quantity <= 0) {
                        $status = 'Out of Stock';
                        $badge = 'badge-cancelled';
                    } elseif ($quantity <= 20) {
                        $status = 'Low Stock';
                        $badge = 'badge-pending';
                    } else {
                        $status = 'In Stock';
                        $badge = 'badge-active';
                    }
                  ?>
                  <tr>
                    <td class="med-name"><?= htmlspecialchars($medicine['medicine_name']) ?></td>
                    <td><?= htmlspecialchars(isset($medicine['category']) ? $medicine['category'] : '') ?></td>
                    <td><?= $quantity ?></td>
                    <td><?= htmlspecialchars(isset($medicine['pharmacy_location']) ? $medicine['pharmacy_location'] : '') ?></td>
                    <td><?= htmlspecialchars(isset($medicine['last_updated']) ? $medicine['last_updated'] : '') ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $status ?></span></td>
                  </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card panel">
        <h4 class="panel-title">Prescription Pickup</h4>
        <p class="panel-note">Medications marked <strong>Low Stock</strong> or <strong>Out of Stock</strong> may take an extra day to dispense.
        Call the Main Pharmacy at <strong>+94 37 222 4420</strong> before your visit to confirm availability.</p>
      </div>
    </section>
  </main>
</div>

</body>
</html>
