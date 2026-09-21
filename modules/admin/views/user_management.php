<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

// ---- Fetch All Users ----
$sql = "SELECT u.user_id, u.username, u.email, u.role, u.status, u.created_at,
               COALESCE(p.first_name, d.first_name, ms.first_name, ph.first_name, b.first_name, a.first_name) AS first_name,
               COALESCE(p.last_name, d.last_name, ms.last_name, ph.last_name, b.last_name, a.last_name) AS last_name,
               COALESCE(d.specialization, ms.department, ph.pharmacy_name, 'General') AS department
        FROM User u
        LEFT JOIN Patient p ON u.user_id = p.user_id
        LEFT JOIN Doctor d ON u.user_id = d.user_id
        LEFT JOIN Medical_Staff ms ON u.user_id = ms.user_id
        LEFT JOIN Pharmacist ph ON u.user_id = ph.user_id
        LEFT JOIN Benefactor b ON u.user_id = b.user_id
        LEFT JOIN Admin a ON u.user_id = a.user_id
        ORDER BY u.user_id DESC";

$all_users = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// ---- Filters (GET) ----
$search      = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

$users = $all_users;
if ($search !== '' || $role_filter !== '') {
    $users = array_values(array_filter($users, function ($u) use ($search, $role_filter) {
        if ($role_filter !== '' && $u['role'] !== $role_filter) return false;
        if ($search !== '') {
            $hay = strtolower($u['first_name'] . ' ' . $u['last_name'] . ' ' . $u['email'] . ' ' . $u['user_id']);
            if (strpos($hay, strtolower($search)) === false) return false;
        }
        return true;
    }));
}

$total_users = count($users);
$users = array_slice($users, 0, 5); // Show up to 5 rows at a time

function statusBadge($status) {
    switch ($status) {
        case 'active':    return 'badge-active';
        case 'inactive':  return 'badge-pending';
        case 'suspended': return 'badge-done';
        default:          return 'badge-pending';
    }
}

// ---- Modal State & Role Selection ----
$modal_open   = isset($_GET['add']);
$valid_roles  = ['doctor', 'patient', 'staff', 'pharmacist', 'benefactor', 'admin'];
$selected_role = isset($_GET['add_role']) && in_array($_GET['add_role'], $valid_roles) ? $_GET['add_role'] : 'doctor';
$edit_user = null;

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt_edit = $conn->prepare("SELECT u.user_id, u.username, u.email, u.phone, u.role,
                                        COALESCE(p.first_name, d.first_name, ms.first_name, ph.first_name, b.first_name, a.first_name) AS first_name,
                                        COALESCE(p.last_name, d.last_name, ms.last_name, ph.last_name, b.last_name, a.last_name) AS last_name,
                                        p.nic, p.dob, p.gender, p.address AS patient_address, p.city, p.blood_group, p.allergies,
                                        d.specialization, d.qualification, d.license_no AS doctor_license,
                                        ms.designation, ms.department, ms.employee_id,
                                        ph.pharmacy_name, ph.address AS pharmacist_address, ph.license_no AS pharmacist_license,
                                        b.benefactor_type, b.organization_name, b.address AS benefactor_address, b.country,
                                        a.admin_level
                                 FROM User u
                                 LEFT JOIN Patient p ON u.user_id = p.user_id
                                 LEFT JOIN Doctor d ON u.user_id = d.user_id
                                 LEFT JOIN Medical_Staff ms ON u.user_id = ms.user_id
                                 LEFT JOIN Pharmacist ph ON u.user_id = ph.user_id
                                 LEFT JOIN Benefactor b ON u.user_id = b.user_id
                                 LEFT JOIN Admin a ON u.user_id = a.user_id
                                 WHERE u.user_id = ?");
    $stmt_edit->bind_param("i", $edit_id);
    $stmt_edit->execute();
    $edit_user = $stmt_edit->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
    <link rel="stylesheet" href="../../../public/css/user-management.css">
</head>
<body class="<?php echo ($modal_open || $edit_user) ? 'modal-active' : ''; ?>">
    <div class="app">
        <?php $current_page = 'users'; require_once __DIR__ . '/../../../includes/admin_sidebar.php'; ?>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>System User Management</h2>
                        <p class="date">Manage patients, doctors, and staff credentials</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a href="../../../logout.php" class="signout-btn">Sign Out</a>
                </div>
            </header>

            <div class="content">

                <?php if (isset($_GET['msg']) && in_array($_GET['msg'], ['user_added', 'user_updated'], true)): ?>
                    <div class="card panel">
                        <p class="panel-note" style="color: var(--teal);">
                            <?php echo $_GET['msg'] === 'user_updated' ? 'User information updated successfully.' : 'User account created successfully.'; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Search & Filters -->
                <div class="card wide-card">
                    <form method="GET" action="user_management.php" class="form-grid">
                        <div class="form-field form-field-wide">
                            <label>Search Users</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email or User ID...">
                        </div>
                        <div class="form-field">
                            <label>System Role</label>
                            <select name="role">
                                <option value="">All Roles</option>
                                <option value="doctor"     <?php echo $role_filter === 'doctor' ? 'selected' : ''; ?>>Doctors</option>
                                <option value="patient"    <?php echo $role_filter === 'patient' ? 'selected' : ''; ?>>Patients</option>
                                <option value="staff"      <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                <option value="pharmacist" <?php echo $role_filter === 'pharmacist' ? 'selected' : ''; ?>>Pharmacists</option>
                                <option value="benefactor" <?php echo $role_filter === 'benefactor' ? 'selected' : ''; ?>>Benefactors</option>
                                <option value="admin"      <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                            </select>
                        </div>
                        <div class="form-field" style="align-items: flex-end;">
                            <button type="submit" class="btn-primary">Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="card wide-card">
                    <div class="list-card-header">
                        <h3>All Registered System Accounts (<?php echo $total_users; ?>)</h3>
                        <a href="user_management.php?add=1&add_role=doctor" class="btn-primary">Add User</a>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Name &amp; Email</th>
                                    <th>System Role</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">No users found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td class="med-name">USR-<?php echo str_pad($u['user_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></strong><br>
                                                <span style="font-size: 12.5px; color: var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></span>
                                            </td>
                                            <td><?php echo ucfirst($u['role']); ?></td>
                                            <td><?php echo htmlspecialchars($u['department']); ?></td>
                                            <td><span class="badge <?php echo statusBadge($u['status']); ?>"><?php echo ucfirst($u['status']); ?></span></td>
                                            <td>
                                                <a href="user_management.php?edit=<?php echo $u['user_id']; ?>" class="link-action" style="margin-right: 12px;">Edit</a>
                                                <a href="../admin_actions.php?action=toggle_status&user_id=<?php echo $u['user_id']; ?>&current_status=<?php echo $u['status']; ?>" class="link-action" style="color: var(--red);">
                                                    <?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- ========================================== -->
    <!-- ADD USER DIALOG (Dynamic Role Fields)      -->
    <!-- ========================================== -->
    <div class="modal-overlay <?php echo $modal_open ? 'open' : ''; ?>">
        <div class="modal-card">
            <div class="modal-head">
                <h3>Add New System User</h3>
                <a href="user_management.php" class="modal-close" title="Close">&times;</a>
            </div>
            <p class="form-subtitle" style="margin: 0 0 18px;">Select a role below to reveal the specific fields required for that account type.</p>

            <?php if (isset($_GET['error'])): ?>
                <p class="panel-note" style="color: var(--red); margin-bottom: 14px;">
                    <?php echo $_GET['error'] === 'invalid_user' ? 'Could not save: please fill all required fields.' : 'Database error while creating the user.'; ?>
                </p>
            <?php endif; ?>

            <!-- Role Selector (Pure CSS/HTML, no JS needed) -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
                <?php foreach ($valid_roles as $r): ?>
                    <a href="user_management.php?add=1&add_role=<?php echo $r; ?>" 
                       style="text-decoration: none; padding: 8px 16px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; border: 1px solid var(--border); background: <?php echo $selected_role === $r ? 'var(--blue)' : '#fff'; ?>; color: <?php echo $selected_role === $r ? '#fff' : 'var(--text)'; ?>;">
                        <?php echo ucfirst($r); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="POST" action="../admin_actions.php">
                <input type="hidden" name="action" value="add_user">
                <input type="hidden" name="role" value="<?php echo $selected_role; ?>">
                
                <p class="form-section-label">Account Credentials (Common for all)</p>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-field">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-field">
                        <label>Password *</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-field">
                        <label>Phone</label>
                        <input type="text" name="phone">
                    </div>
                </div>

                <p class="form-section-label">Profile Information (<?php echo ucfirst($selected_role); ?>)</p>
                <div class="form-grid">
                    <div class="form-field">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-field">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>

                    <!-- DOCTOR SPECIFIC -->
                    <?php if ($selected_role === 'doctor'): ?>
                        <div class="form-field">
                            <label>Specialization</label>
                            <input type="text" name="specialization" placeholder="e.g. Medical Oncology">
                        </div>
                        <div class="form-field">
                            <label>Qualification</label>
                            <input type="text" name="qualification" placeholder="e.g. MBBS, MD">
                        </div>
                        <div class="form-field">
                            <label>License No *</label>
                            <input type="text" name="license_no" required>
                        </div>

                    <!-- PATIENT SPECIFIC -->
                    <?php elseif ($selected_role === 'patient'): ?>
                        <div class="form-field">
                            <label>NIC *</label>
                            <input type="text" name="nic" required placeholder="e.g. 198510201234">
                        </div>
                        <div class="form-field">
                            <label>Date of Birth *</label>
                            <input type="date" name="dob" required>
                        </div>
                        <div class="form-field">
                            <label>Gender *</label>
                            <select name="gender" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Blood Group</label>
                            <select name="blood_group">
                                <option value="">Select</option>
                                <option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option>
                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                <option value="O+">O+</option><option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="form-field form-field-wide">
                            <label>Address</label>
                            <input type="text" name="address">
                        </div>
                        <div class="form-field">
                            <label>City</label>
                            <input type="text" name="city">
                        </div>
                        <div class="form-field form-field-wide">
                            <label>Allergies</label>
                            <input type="text" name="allergies" placeholder="e.g. Penicillin, Peanuts">
                        </div>

                    <!-- STAFF SPECIFIC -->
                    <?php elseif ($selected_role === 'staff'): ?>
                        <div class="form-field">
                            <label>Designation</label>
                            <input type="text" name="designation" placeholder="e.g. Head Nurse">
                        </div>
                        <div class="form-field">
                            <label>Department</label>
                            <input type="text" name="department" placeholder="e.g. Oncology Ward">
                        </div>
                        <div class="form-field">
                            <label>Employee ID</label>
                            <input type="text" name="employee_id" placeholder="e.g. EMP-001">
                        </div>

                    <!-- PHARMACIST SPECIFIC -->
                    <?php elseif ($selected_role === 'pharmacist'): ?>
                        <div class="form-field form-field-wide">
                            <label>Pharmacy Name *</label>
                            <input type="text" name="pharmacy_name" required>
                        </div>
                        <div class="form-field form-field-wide">
                            <label>Address</label>
                            <input type="text" name="address">
                        </div>
                        <div class="form-field">
                            <label>License No *</label>
                            <input type="text" name="license_no" required>
                        </div>

                    <!-- BENEFACTOR SPECIFIC -->
                    <?php elseif ($selected_role === 'benefactor'): ?>
                        <div class="form-field">
                            <label>Benefactor Type *</label>
                            <select name="benefactor_type" required>
                                <option value="local">Local</option>
                                <option value="international">International</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Organization Name</label>
                            <input type="text" name="organization_name">
                        </div>
                        <div class="form-field">
                            <label>Country</label>
                            <input type="text" name="country">
                        </div>
                        <div class="form-field form-field-wide">
                            <label>Address</label>
                            <input type="text" name="address">
                        </div>

                    <!-- ADMIN SPECIFIC -->
                    <?php elseif ($selected_role === 'admin'): ?>
                        <div class="form-field">
                            <label>Admin Level *</label>
                            <select name="admin_level" required>
                                <option value="regular">Regular</option>
                                <option value="super">Super</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <a href="user_management.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($edit_user): ?>
        <div class="modal-overlay open">
            <div class="modal-card">
                <div class="modal-head">
                    <h3>Edit System User</h3>
                    <a href="user_management.php" class="modal-close" title="Close">&times;</a>
                </div>
                <?php if (isset($_GET['error'])): ?>
                    <p class="panel-note" style="color: var(--red); margin-bottom: 14px;">Could not update this user. Check the values and try again.</p>
                <?php endif; ?>

                <form method="POST" action="../admin_actions.php">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($edit_user['role']); ?>">

                    <p class="form-section-label">Account Credentials</p>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Username *</label>
                            <input type="text" name="username" required value="<?php echo htmlspecialchars($edit_user['username']); ?>">
                        </div>
                        <div class="form-field">
                            <label>Email *</label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($edit_user['email']); ?>">
                        </div>
                        <div class="form-field">
                            <label>New Password</label>
                            <input type="password" name="password" placeholder="Leave blank to keep current password">
                        </div>
                        <div class="form-field">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-field">
                            <label>Role</label>
                            <input type="text" value="<?php echo ucfirst(htmlspecialchars($edit_user['role'])); ?>" disabled>
                        </div>
                    </div>

                    <p class="form-section-label">Profile Information</p>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>First Name *</label>
                            <input type="text" name="first_name" required value="<?php echo htmlspecialchars($edit_user['first_name'] ?? ''); ?>">
                        </div>
                        <div class="form-field">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" required value="<?php echo htmlspecialchars($edit_user['last_name'] ?? ''); ?>">
                        </div>

                        <?php if ($edit_user['role'] === 'doctor'): ?>
                            <div class="form-field">
                                <label>Specialization</label>
                                <input type="text" name="specialization" value="<?php echo htmlspecialchars($edit_user['specialization'] ?? ''); ?>">
                            </div>
                            <div class="form-field">
                                <label>Qualification</label>
                                <input type="text" name="qualification" value="<?php echo htmlspecialchars($edit_user['qualification'] ?? ''); ?>">
                            </div>
                            <div class="form-field">
                                <label>License No *</label>
                                <input type="text" name="license_no" required value="<?php echo htmlspecialchars($edit_user['doctor_license'] ?? ''); ?>">
                            </div>
                        <?php elseif ($edit_user['role'] === 'patient'): ?>
                            <div class="form-field">
                                <label>NIC *</label>
                                <input type="text" name="nic" required value="<?php echo htmlspecialchars($edit_user['nic'] ?? ''); ?>">
                            </div>
                            <div class="form-field">
                                <label>Date of Birth *</label>
                                <input type="date" name="dob" required value="<?php echo htmlspecialchars($edit_user['dob'] ?? ''); ?>">
                            </div>
                            <div class="form-field">
                                <label>Gender</label>
                                <select name="gender">
                                    <?php foreach (['male', 'female', 'other'] as $gender): ?>
                                        <option value="<?php echo $gender; ?>" <?php echo ($edit_user['gender'] ?? '') === $gender ? 'selected' : ''; ?>><?php echo ucfirst($gender); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Blood Group</label>
                                <input type="text" name="blood_group" value="<?php echo htmlspecialchars($edit_user['blood_group'] ?? ''); ?>">
                            </div>
                            <div class="form-field form-field-wide">
                                <label>Address</label>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($edit_user['patient_address'] ?? ''); ?>">
                            </div>
                            <div class="form-field">
                                <label>City</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($edit_user['city'] ?? ''); ?>">
                            </div>
                            <div class="form-field form-field-wide">
                                <label>Allergies</label>
                                <input type="text" name="allergies" value="<?php echo htmlspecialchars($edit_user['allergies'] ?? ''); ?>">
                            </div>
                        <?php elseif ($edit_user['role'] === 'staff'): ?>
                            <div class="form-field">
                                <label>Designation</label>
                                <input type="text" name="designation" value="<?php echo htmlspecialchars($edit_user['designation'] ?? ''); ?>">
                            </div>
                            <div class="form-field">
                                <label>Department</label>
                                <input type="text" name="department" value="<?php echo htmlspecialchars($edit_user['department'] ?? ''); ?>">
                            </div>
                            <div class="form-field">
                                <label>Employee ID</label>
                                <input type="text" name="employee_id" value="<?php echo htmlspecialchars($edit_user['employee_id'] ?? ''); ?>">
                            </div>
                        <?php elseif ($edit_user['role'] === 'pharmacist'): ?>
                            <div class="form-field form-field-wide">
                                <label>Pharmacy Name *</label>
                                <input type="text" name="pharmacy_name" required value="<?php echo htmlspecialchars($edit_user['pharmacy_name'] ?? ''); ?>">
                            </div>
                            <div class="form-field form-field-wide">
                                <label>Address</label>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($edit_user['pharmacist_address'] ?? ''); ?>">
                            </div>
                            <div class="form-field">
                                <label>License No *</label>
                                <input type="text" name="license_no" required value="<?php echo htmlspecialchars($edit_user['pharmacist_license'] ?? ''); ?>">
                            </div>
                        <?php elseif ($edit_user['role'] === 'benefactor'): ?>
                            <div class="form-field">
                                <label>Benefactor Type *</label>
                                <select name="benefactor_type" required>
                                    <option value="local" <?php echo ($edit_user['benefactor_type'] ?? '') === 'local' ? 'selected' : ''; ?>>Local</option>
                                    <option value="international" <?php echo ($edit_user['benefactor_type'] ?? '') === 'international' ? 'selected' : ''; ?>>International</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Organization Name</label>
                                <input type="text" name="organization_name" value="<?php echo htmlspecialchars($edit_user['organization_name'] ?? ''); ?>">
                            </div>
                            <div class="form-field">
                                <label>Country</label>
                                <input type="text" name="country" value="<?php echo htmlspecialchars($edit_user['country'] ?? ''); ?>">
                            </div>
                            <div class="form-field form-field-wide">
                                <label>Address</label>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($edit_user['benefactor_address'] ?? ''); ?>">
                            </div>
                        <?php elseif ($edit_user['role'] === 'admin'): ?>
                            <div class="form-field">
                                <label>Admin Level *</label>
                                <select name="admin_level" required>
                                    <option value="regular" <?php echo ($edit_user['admin_level'] ?? '') === 'regular' ? 'selected' : ''; ?>>Regular</option>
                                    <option value="super" <?php echo ($edit_user['admin_level'] ?? '') === 'super' ? 'selected' : ''; ?>>Super</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <a href="user_management.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>