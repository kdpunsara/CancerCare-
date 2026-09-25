<?php
require_once __DIR__ . '/../patient_data.php';

$prescription_groups = [];
foreach ($prescriptions as $prescription) {
  $prescription_id = (int) $prescription['prescription_id'];

  if (!isset($prescription_groups[$prescription_id])) {
    $prescription_groups[$prescription_id] = [
      'prescription_id' => $prescription_id,
      'prescription_date' => $prescription['prescription_date'],
      'status' => $prescription['status'],
      'doctor_name' => 'Dr. ' . $prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name'],
      'items' => []
    ];
  }

  $prescription_groups[$prescription_id]['items'][] = $prescription;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prescriptions — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css?v=3">
<link rel="stylesheet" href="../../../public/css/prescription.css">
<style>
  .prescription-table th,
  .prescription-table td { white-space: nowrap; }
  .view-prescription-btn {
    border: 0;
    border-radius: 6px;
    background: var(--blue);
    color: #fff;
    cursor: pointer;
    font: inherit;
    font-size: 12px;
    font-weight: 700;
    padding: 8px 12px;
  }
  .prescription-modal {
    background: rgba(16, 26, 51, .55);
    display: none;
    inset: 0;
    padding: 24px 16px;
    position: fixed;
    z-index: 20;
  }
  .prescription-modal.open { display: flex; align-items: center; justify-content: center; }
  .prescription-modal-card {
    background: var(--card, #fff);
    border-radius: var(--radius-lg, 12px);
    max-height: 85vh;
    max-width: 720px;
    overflow-y: auto;
    padding: 24px;
    position: relative;
    width: 100%;
  }
  .prescription-modal-head { align-items: flex-start; display: flex; justify-content: space-between; }
  .prescription-modal-head h3 { margin: 0 0 6px; }
  .prescription-modal-meta { color: var(--text-muted, #6b7280); font-size: 13px; margin: 0 0 18px; }
  .prescription-modal-close { background: transparent; border: 0; cursor: pointer; font-size: 24px; line-height: 1; }
  .medication-detail { border-top: 1px solid var(--border, #e7e9f2); padding: 14px 0; }
  .medication-detail:first-child { border-top: 0; padding-top: 0; }
  .medication-detail p { margin: 4px 0 0; }
</style>
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
        <li><a href="patient_profile_edit.php" class="nav-item" title="My Profile">
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
        <li><a href="patient_prescriptions.php" class="nav-item active" title="Prescriptions">
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
        <li><a href="patient_drug_availability.php" class="nav-item" title="Drug Availability">
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
          <h2>Prescriptions</h2>
          <p class="date">Your current and past medications</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="../../../logout.php" class="signout-btn">Sign Out</a>
      </div>
    </header>

    <section class="content">

      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>My Prescriptions</h3>
        </div>

        <div class="table-wrap">
          <table class="data-table prescription-table">
            <thead>
              <tr>
                <th>Prescription ID</th>
                <th>Doctor</th>
                <th>Prescription Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$prescription_groups): ?>
                <tr><td colspan="5">No prescriptions found.</td></tr>
              <?php else: ?>
                <?php foreach ($prescription_groups as $prescription): ?>
                  <tr>
                    <td>#<?= (int) $prescription['prescription_id'] ?></td>
                    <td><?= htmlspecialchars($prescription['doctor_name']) ?></td>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($prescription['prescription_date']))) ?></td>
                    <td><span class="badge <?= $prescription['status'] === 'active' ? 'badge-active' : 'badge-done' ?>"><?= htmlspecialchars(ucfirst($prescription['status'])) ?></span></td>
                    <td><button type="button" class="view-prescription-btn" data-prescription-id="<?= (int) $prescription['prescription_id'] ?>">View</button></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php foreach ($prescription_groups as $prescription): ?>
        <div class="prescription-modal" id="prescription-<?= (int) $prescription['prescription_id'] ?>" aria-hidden="true">
          <div class="prescription-modal-card" role="dialog" aria-modal="true" aria-labelledby="prescription-title-<?= (int) $prescription['prescription_id'] ?>">
            <div class="prescription-modal-head">
              <div>
                <h3 id="prescription-title-<?= (int) $prescription['prescription_id'] ?>">Prescription #<?= (int) $prescription['prescription_id'] ?></h3>
                <p class="prescription-modal-meta"><?= htmlspecialchars($prescription['doctor_name']) ?> · <?= htmlspecialchars(date('d M Y', strtotime($prescription['prescription_date']))) ?></p>
              </div>
              <button type="button" class="prescription-modal-close" aria-label="Close">&times;</button>
            </div>

            <?php foreach ($prescription['items'] as $item): ?>
              <div class="medication-detail">
                <strong><?= htmlspecialchars($item['medicine_name']) ?></strong>
                <p><?= htmlspecialchars($item['dosage']) ?> · <?= htmlspecialchars($item['frequency']) ?> · <?= htmlspecialchars($item['duration']) ?></p>
                <?php if (!empty($item['instructions'])): ?>
                  <p><?= htmlspecialchars($item['instructions']) ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

    </section>
  </main>
</div>

<script>
  document.querySelectorAll('.view-prescription-btn').forEach((button) => {
    button.addEventListener('click', () => {
      const modal = document.getElementById('prescription-' + button.dataset.prescriptionId);
      if (modal) {
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
      }
    });
  });

  document.querySelectorAll('.prescription-modal').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      if (event.target === modal || event.target.classList.contains('prescription-modal-close')) {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
      }
    });
  });
</script>

</body>
</html>
