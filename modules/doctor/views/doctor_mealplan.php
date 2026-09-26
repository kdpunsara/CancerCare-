<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Plans - Cancer Care</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
    <style>
        /* Custom badges for meal types */
        .meal-type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 8px;
        }
        .meal-type-badge.breakfast { background: #fdf1de; color: #d97706; }
        .meal-type-badge.lunch { background: #e8edfe; color: var(--blue-dark); }
        .meal-type-badge.dinner { background: #f3e8ff; color: #7c3aed; }
        .meal-type-badge.snack { background: #e2f7f3; color: #0d9488; }

        /* Day Card */
        .day-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px;
        }
        .day-card h4 {
            font-size: 14.5px;
            font-weight: 700;
            margin: 0 0 14px;
            color: var(--text);
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }
        .meal-item {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed var(--border);
        }
        .meal-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .meal-item p {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.4;
        }

        /* Cancer Template Cards */
        .cancer-type-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 22px;
            transition: all 0.2s ease;
            cursor: pointer;
            height: 100%;
        }
        .cancer-type-card:hover {
            border-color: var(--blue);
            box-shadow: 0 4px 12px rgba(79, 110, 247, 0.08);
            transform: translateY(-2px);
        }
        .cancer-type-card h4 {
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 8px;
            color: var(--text);
        }
        .cancer-type-card p {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0 0 16px;
            line-height: 1.5;
        }
        .cancer-type-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 12px;
            color: var(--text-faint);
            font-weight: 600;
        }

        /* Modal Styles (Not present in base.css) */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(16, 26, 51, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
            backdrop-filter: blur(3px);
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: var(--card);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 540px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px 34px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .modal-large { max-width: 960px; }

        .weekly-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 20px;
        }
        
        .radio-group {
            display: flex;
            gap: 16px;
            margin-top: 6px;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500 !important;
            font-size: 13px !important;
            cursor: pointer;
        }

        @media (max-width: 1024px) { .weekly-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .weekly-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="app">
        <?php
        $current_page = 'mealplan';
        require_once __DIR__ . '/../../../includes/doctor_sidebar.php';
        if (false):
        ?>
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">CC</div>
                <div class="brand-text">
                    <h1>Cancer Care</h1>
                    <p>Doctor Module</p>
                </div>
            </div>
            
            <nav class="nav">
                <p class="nav-label">Main Menu</p>
                <ul>
                    <li><a href="doctor_dashboard.html" class="nav-item">
                        <span class="icon icon-dashboard" aria-hidden="true"></span>
                        <span class="nav-text">Dashboard</span>
                    </a></li>
                    <li><a href="doctor_appointments.html" class="nav-item">
                        <span class="icon icon-appointments" aria-hidden="true"></span>
                        <span class="nav-text">Appointments</span>
                    </a></li>
                    <li><a href="doctor_mypatients.html" class="nav-item">
                        <span class="icon icon-profile" aria-hidden="true"></span>
                        <span class="nav-text">My Patients</span>
                    </a></li>
                    <li><a href="doctor_prescription.html" class="nav-item">
                        <span class="icon icon-prescriptions" aria-hidden="true"></span>
                        <span class="nav-text">Prescriptions</span>
                    </a></li>
                </ul>

                <p class="nav-label" style="margin-top: 16px;">Medical Records</p>
                <ul>
                    <li><a href="view_records.html" class="nav-item">
                        <span class="icon icon-records" aria-hidden="true"></span>
                        <span class="nav-text">View Records</span>
                    </a></li>
                </ul>

                <p class="nav-label" style="margin-top: 16px;">Patient Support</p>
                <ul>
                    <li><a href="#" class="nav-item active">
                        <span class="icon icon-wellness" aria-hidden="true"></span>
                        <span class="nav-text">Meal Plans</span>
                    </a></li>
                </ul>
                <p class="nav-label" style="margin-top: 16px;">Account</p>
                <ul>
                    <li><a href="doctor_profile.html" class="nav-item">
                        <span class="icon icon-profile" aria-hidden="true"></span>
                        <span class="nav-text">My Profile</span>
                    </a></li>
                </ul>
            </nav>
            
            <div class="sidebar-user">
                <div class="avatar">NF</div>
                <div>
                    <p class="user-name">Dr. Hemal Wijesinghe</p>
                    <p class="user-role">Senior Oncologist</p>
                </div>
            </div>
        </aside>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="main">
            <!-- Top Bar -->
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>Meal Plans</h2>
                        <p class="date">Manage patient nutrition plans and access pre-made templates by cancer type</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a href="../../../logout.php" class="signout-btn">Sign Out</a>
                </div>
            </header>

            <section class="content">
                <!-- Search Patients Section -->
                <div class="card form-card" style="padding-bottom: 24px;">
                    <div class="form-grid" style="align-items: end;">
                        <div class="form-field">
                            <label>Search by Patient Name or ID</label>
                            <input type="text" id="patientSearch" placeholder="e.g. Kamal Perera or P-1001">
                        </div>
                        <div class="form-field">
                            <label>Filter by Cancer Type</label>
                            <select id="cancerFilter">
                                <option value="">All Cancer Types</option>
                                <option value="colorectal">Colorectal Cancer</option>
                                <option value="breast">Breast Cancer</option>
                                <option value="lung">Lung Cancer</option>
                                <option value="prostate">Prostate Cancer</option>
                                <option value="lymphoma">Lymphoma</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Patient Meal Plans List -->
                <div class="card list-card wide-card">
                    <div class="list-card-header">
                        <h3>Patient Meal Plans (<span id="patientCount">6</span> patients)</h3>
                        <button class="btn-primary" onclick="openModal('assignModal')" style="padding: 8px 16px; font-size: 13px;">+ Assign Plan</button>
                    </div>
                    
                    <ul class="appointment-list" id="patientMealList">
                        <li class="appointment-item" data-search="kamal perera p-1001 colorectal" style="border-left-color: var(--teal);">
                            <div class="appointment-time">
                                <div class="avatar" style="background: var(--blue);">KP</div>
                            </div>
                            <div class="appointment-info">
                                <p class="appointment-name">Kamal Perera</p>
                                <p class="appointment-desc">P-1001 • Colorectal Cancer • Stage III</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                                <span class="badge badge-active">Active Plan</span>
                                <button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="openModal('weeklyModal')">View Plan</button>
                                <button class="btn-primary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                            </div>
                        </li>

                        <li class="appointment-item" data-search="nimali silva p-1002 breast" style="border-left-color: var(--teal);">
                            <div class="appointment-time">
                                <div class="avatar" style="background: #7c3aed;">NS</div>
                            </div>
                            <div class="appointment-info">
                                <p class="appointment-name">Nimali Silva</p>
                                <p class="appointment-desc">P-1002 • Breast Cancer • Stage II</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                                <span class="badge badge-active">Active Plan</span>
                                <button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="openModal('weeklyModal')">View Plan</button>
                                <button class="btn-primary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                            </div>
                        </li>

                        <li class="appointment-item pending" data-search="sunil bandara p-1003 lung">
                            <div class="appointment-time">
                                <div class="avatar" style="background: var(--amber);">SB</div>
                            </div>
                            <div class="appointment-info">
                                <p class="appointment-name">Sunil Bandara</p>
                                <p class="appointment-desc">P-1003 • Lung Cancer • Stage IV</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                                <span class="badge badge-pending">Pending Assignment</span>
                                <button class="btn-primary" style="padding: 6px 12px; font-size: 12px;" onclick="openModal('assignModal')">Assign Plan</button>
                            </div>
                        </li>

                        <li class="appointment-item" data-search="chamari jayawardena p-1004 colorectal" style="border-left-color: var(--teal);">
                            <div class="appointment-time">
                                <div class="avatar" style="background: var(--teal);">CJ</div>
                            </div>
                            <div class="appointment-info">
                                <p class="appointment-name">Chamari Jayawardena</p>
                                <p class="appointment-desc">P-1004 • Colorectal Cancer • Stage I</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                                <span class="badge badge-active">Active Plan</span>
                                <button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="openModal('weeklyModal')">View Plan</button>
                                <button class="btn-primary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                            </div>
                        </li>

                        <li class="appointment-item pending" data-search="prasanna silva p-1005 prostate">
                            <div class="appointment-time">
                                <div class="avatar" style="background: var(--navy-soft);">PS</div>
                            </div>
                            <div class="appointment-info">
                                <p class="appointment-name">Prasanna Silva</p>
                                <p class="appointment-desc">P-1005 • Prostate Cancer • Stage III</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                                <span class="badge badge-pending">Pending Assignment</span>
                                <button class="btn-primary" style="padding: 6px 12px; font-size: 12px;" onclick="openModal('assignModal')">Assign Plan</button>
                            </div>
                        </li>

                        <li class="appointment-item" data-search="ruwan jayasinghe p-1007 lymphoma" style="border-left-color: var(--teal);">
                            <div class="appointment-time">
                                <div class="avatar" style="background: #059669;">RJ</div>
                            </div>
                            <div class="appointment-info">
                                <p class="appointment-name">Ruwan Jayasinghe</p>
                                <p class="appointment-desc">P-1007 • Lymphoma • In Remission</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                                <span class="badge badge-active">Active Plan</span>
                                <button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="openModal('weeklyModal')">View Plan</button>
                                <button class="btn-primary" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Pre-made Meal Plan Templates -->
                <div class="card list-card wide-card">
                    <div class="list-card-header" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                        <h3>Pre-made Meal Plan Templates</h3>
                        <p class="table-caption" style="margin:0;">Select a template based on cancer type to quickly assign to patients</p>
                    </div>
                    
                    <div class="form-grid">
                        <div class="cancer-type-card" onclick="openModal('templateModal')">
                            <h4>Colorectal Cancer</h4>
                            <p>High-fiber, low-fat diet focused on digestive health. Emphasizes whole grains, lean proteins, and cooked vegetables.</p>
                            <div class="cancer-type-meta">
                                <span>📋 7-day plan</span>
                                <span>🥗 4 meals/day</span>
                                <span>👨‍⚕️ 24 patients using</span>
                            </div>
                        </div>
                        <div class="cancer-type-card" onclick="openModal('templateModal')">
                            <h4>Breast Cancer</h4>
                            <p>Antioxidant-rich, hormone-balancing nutrition plan. Includes cruciferous vegetables, omega-3 sources, and phytoestrogen foods.</p>
                            <div class="cancer-type-meta">
                                <span>📋 7-day plan</span>
                                <span>🥗 4 meals/day</span>
                                <span>👩‍⚕️ 18 patients using</span>
                            </div>
                        </div>
                        <div class="cancer-type-card" onclick="openModal('templateModal')">
                            <h4>Lung Cancer</h4>
                            <p>High-protein, calorie-dense plan for patients with reduced appetite. Focus on easy-to-swallow, nutrient-dense foods.</p>
                            <div class="cancer-type-meta">
                                <span>📋 7-day plan</span>
                                <span>🥗 4 meals/day</span>
                                <span>👨‍⚕️ 12 patients using</span>
                            </div>
                        </div>
                        <div class="cancer-type-card" onclick="openModal('templateModal')">
                            <h4>Prostate Cancer</h4>
                            <p>Low-fat, plant-based emphasis with lycopene-rich foods. Includes tomatoes, green tea, and soy-based proteins.</p>
                            <div class="cancer-type-meta">
                                <span>📋 7-day plan</span>
                                <span>🥗 4 meals/day</span>
                                <span>👨‍⚕️ 9 patients using</span>
                            </div>
                        </div>
                        <div class="cancer-type-card" onclick="openModal('templateModal')">
                            <h4>Lymphoma</h4>
                            <p>Immune-boosting, neutropenic-safe diet for chemotherapy patients. Focus on fully cooked foods and food safety.</p>
                            <div class="cancer-type-meta">
                                <span>📋 7-day plan</span>
                                <span>🥗 4 meals/day</span>
                                <span>👨‍⚕️ 7 patients using</span>
                            </div>
                        </div>
                        <div class="cancer-type-card" onclick="openModal('templateModal')">
                            <h4>Chemotherapy Support (General)</h4>
                            <p>Bland, low-sodium diet for patients undergoing active chemotherapy. Manages nausea and taste changes.</p>
                            <div class="cancer-type-meta">
                                <span>📋 7-day plan</span>
                                <span>🥗 4 meals/day</span>
                                <span>👨‍⚕️ 31 patients using</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- View Weekly Plan Modal -->
    <div class="modal-overlay" id="weeklyModal">
        <div class="modal modal-large">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h2 class="form-title">Weekly Meal Plan</h2>
                    <p class="form-subtitle" style="margin-bottom: 0;">Prepared by Dr. Noel Fernando • High-protein, low-sodium plan for Colorectal Cancer</p>
                </div>
                <button onclick="closeModal('weeklyModal')" style="font-size: 24px; color: var(--text-faint); background: none; border: none; cursor: pointer; padding: 0;">&times;</button>
            </div>
            
            <div class="weekly-grid">
                <div class="day-card">
                    <h4>Monday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Kola kanda with a boiled egg</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Red rice, dhal curry, steamed greens</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>Vegetable soup with grilled fish</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Papaya slices</p>
                    </div>
                </div>
                <div class="day-card">
                    <h4>Tuesday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Plain oats with banana</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Brown rice, chicken curry, beetroot salad</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>String hoppers with dhal curry</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Coconut water</p>
                    </div>
                </div>
                <div class="day-card">
                    <h4>Wednesday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Milk rice with jaggery</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Red rice, fish curry, sautéed cabbage</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>Vegetable soup with brown bread</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Sliced mango</p>
                    </div>
                </div>
                <div class="day-card">
                    <h4>Thursday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Roti with mild coconut sambol</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Brown rice, lentil curry, pumpkin curry</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>Rice porridge with vegetables</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Boiled chickpeas</p>
                    </div>
                </div>
                <div class="day-card">
                    <h4>Friday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Steamed idli with sambar</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Red rice, chicken curry, carrot salad</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>Noodle soup with tofu</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Yogurt with honey</p>
                    </div>
                </div>
                <div class="day-card">
                    <h4>Saturday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Vegetable kottu (light oil)</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Brown rice, fish curry, green beans</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>Clear soup with steamed vegetables</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Fresh fruit salad</p>
                    </div>
                </div>
                <div class="day-card">
                    <h4>Sunday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Wheat pittu with coconut milk</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Red rice, egg curry, mixed salad</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>Vegetable stew with rice</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Herbal tea with biscuits</p>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn-secondary" onclick="closeModal('weeklyModal')">Close</button>
                <button class="btn-primary">Edit This Plan</button>
            </div>
        </div>
    </div>

    <!-- Assign Meal Plan Modal -->
    <div class="modal-overlay" id="assignModal">
        <div class="modal">
            <h2 class="form-title">Assign Meal Plan</h2>
            <p class="form-subtitle">Select a patient and choose a pre-made template or create a custom plan</p>
            
            <div class="form-field" style="margin-bottom: 18px;">
                <label>Select Patient <span style="color:var(--red);">*</span></label>
                <select>
                    <option value="">-- Choose Patient --</option>
                    <option>Sunil Bandara (P-1003) - Lung Cancer, Stage IV</option>
                    <option>Prasanna Silva (P-1005) - Prostate Cancer, Stage III</option>
                </select>
            </div>
            
            <div class="form-field" style="margin-bottom: 18px;">
                <label>Choose Plan Type <span style="color:var(--red);">*</span></label>
                <div class="radio-group">
                    <label><input type="radio" name="planType" value="template" checked> Use Pre-made Template</label>
                    <label><input type="radio" name="planType" value="custom"> Create Custom Plan</label>
                </div>
            </div>

            <div class="form-field" style="margin-bottom: 18px;">
                <label>Select Template <span style="color:var(--red);">*</span></label>
                <select>
                    <option value="">-- Choose Cancer Type Template --</option>
                    <option>Colorectal Cancer - High-fiber, low-fat (7 days)</option>
                    <option>Breast Cancer - Antioxidant-rich (7 days)</option>
                    <option>Lung Cancer - High-protein, calorie-dense (7 days)</option>
                    <option>Prostate Cancer - Low-fat, plant-based (7 days)</option>
                    <option>Lymphoma - Immune-boosting, neutropenic-safe (7 days)</option>
                    <option>Chemotherapy Support - Bland, low-sodium (7 days)</option>
                </select>
            </div>

            <div class="form-field" style="margin-bottom: 18px;">
                <label>Dietary Restrictions / Notes</label>
                <textarea rows="3" placeholder="e.g. Patient has diabetes - limit sugar intake. Avoid spicy foods due to mouth sores."></textarea>
            </div>

            <div class="form-field">
                <label>Plan Duration</label>
                <select>
                    <option>1 Week (7 days)</option>
                    <option>2 Weeks (14 days)</option>
                    <option>4 Weeks (28 days)</option>
                    <option>Until next appointment</option>
                </select>
            </div>

            <div class="form-actions">
                <button class="btn-secondary" onclick="closeModal('assignModal')">Cancel</button>
                <button class="btn-primary" onclick="closeModal('assignModal')">Assign Plan</button>
            </div>
        </div>
    </div>

    <!-- Template Preview Modal -->
    <div class="modal-overlay" id="templateModal">
        <div class="modal modal-large">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h2 class="form-title">Colorectal Cancer Meal Plan Template</h2>
                    <p class="form-subtitle" style="margin-bottom: 20px;">High-fiber, low-fat diet • 7 days • 4 meals per day</p>
                </div>
                <button onclick="closeModal('templateModal')" style="font-size: 24px; color: var(--text-faint); background: none; border: none; cursor: pointer; padding: 0;">&times;</button>
            </div>

            <div class="card panel" style="background: #fafbfd; margin-bottom: 20px;">
                <h4 style="margin: 0 0 10px; font-size: 14.5px;">Nutritional Guidelines</h4>
                <ul style="font-size: 13px; color: var(--text-muted); padding-left: 20px; line-height: 1.8; margin: 0;">
                    <li>Emphasize whole grains (brown rice, oats, whole wheat)</li>
                    <li>Include lean proteins: fish, chicken, eggs, tofu</li>
                    <li>Cook vegetables thoroughly for easier digestion</li>
                    <li>Limit red meat and processed foods</li>
                    <li>Avoid spicy, fried, and high-fat foods</li>
                    <li>Encourage hydration: 8-10 glasses of water daily</li>
                </ul>
            </div>

            <div class="weekly-grid">
                <div class="day-card">
                    <h4>Monday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Kola kanda with a boiled egg</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Red rice, dhal curry, steamed greens</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>Vegetable soup with grilled fish</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Papaya slices</p>
                    </div>
                </div>
                <div class="day-card">
                    <h4>Tuesday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Plain oats with banana</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Brown rice, chicken curry, beetroot salad</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>String hoppers with dhal curry</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Coconut water</p>
                    </div>
                </div>
                <div class="day-card">
                    <h4>Wednesday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Milk rice with jaggery</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Red rice, fish curry, sautéed cabbage</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>Vegetable soup with brown bread</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Sliced mango</p>
                    </div>
                </div>
                <div class="day-card">
                    <h4>Thursday</h4>
                    <div class="meal-item">
                        <span class="meal-type-badge breakfast">Breakfast</span>
                        <p>Roti with mild coconut sambol</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge lunch">Lunch</span>
                        <p>Brown rice, lentil curry, pumpkin curry</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge dinner">Dinner</span>
                        <p>Rice porridge with vegetables</p>
                    </div>
                    <div class="meal-item">
                        <span class="meal-type-badge snack">Snack</span>
                        <p>Boiled chickpeas</p>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn-secondary" onclick="closeModal('templateModal')">Close</button>
                <button class="btn-primary" onclick="closeModal('templateModal'); openModal('assignModal');">Use This Template</button>
            </div>
        </div>
    </div>

    <script>
        // Patient search
        document.getElementById('patientSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#patientMealList .appointment-item');
            let count = 0;
            rows.forEach(row => {
                const data = row.getAttribute('data-search');
                if (data.includes(searchTerm)) {
                    row.style.display = 'flex';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });
            document.getElementById('patientCount').textContent = count;
        });

        // Cancer type filter
        document.getElementById('cancerFilter').addEventListener('change', function(e) {
            const filter = e.target.value;
            const rows = document.querySelectorAll('#patientMealList .appointment-item');
            let count = 0;
            rows.forEach(row => {
                const data = row.getAttribute('data-search');
                if (!filter || data.includes(filter)) {
                    row.style.display = 'flex';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });
            document.getElementById('patientCount').textContent = count;
        });

        // Modal functions
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.classList.remove('active');
            });
        });
    </script>
</body>
</html>