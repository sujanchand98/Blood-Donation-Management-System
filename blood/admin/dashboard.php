<?php
session_start();
require_once '../config/database.php';


$database = new Database();
$db = $database->getConnection();


// Get real statistics from database
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'donor') as total_donors,
    (SELECT COUNT(*) FROM users WHERE role = 'seeker') as total_seekers,
    (SELECT COUNT(*) FROM donations WHERE status = 'approved') as total_donations,
    (SELECT COUNT(*) FROM blood_requests) as total_requests,
    (SELECT COUNT(*) FROM blood_requests WHERE status = 'pending') as pending_requests,
    (SELECT COUNT(*) FROM donations WHERE status = 'pending') as pending_donations,
    (SELECT SUM(quantity_ml) FROM blood_inventory) as total_blood_ml,
    (SELECT COUNT(*) FROM blood_inventory WHERE quantity_ml > 0) as available_blood_groups";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent activities
$activities_query = "SELECT 
    'donation' as type, d.created_at, u.full_name, d.blood_group, d.quantity_ml,
    CONCAT('New donation: ', d.blood_group, ' (', d.quantity_ml, 'ml)') as description
    FROM donations d 
    JOIN users u ON d.donor_id = u.id 
    WHERE d.status = 'approved'
    UNION ALL
    SELECT 
    'request' as type, br.created_at, u.full_name, br.blood_group, br.quantity_ml,
    CONCAT('Blood request: ', br.blood_group, ' (', br.quantity_ml, 'ml)') as description
    FROM blood_requests br 
    JOIN users u ON br.seeker_id = u.id 
    WHERE br.status = 'pending'
    ORDER BY created_at DESC 
    LIMIT 6";

$activities_stmt = $db->prepare($activities_query);
$activities_stmt->execute();
$recent_activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get blood inventory status
$inventory_query = "SELECT blood_group, quantity_ml FROM blood_inventory ORDER BY 
    CASE blood_group 
        WHEN 'A+' THEN 1 WHEN 'A-' THEN 2 WHEN 'B+' THEN 3 WHEN 'B-' THEN 4
        WHEN 'AB+' THEN 5 WHEN 'AB-' THEN 6 WHEN 'O+' THEN 7 WHEN 'O-' THEN 8
    END";
$inventory_stmt = $db->prepare($inventory_query);
$inventory_stmt->execute();
$inventory = $inventory_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blood Donation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #c00;
            --primary-dark: #a00;
            --secondary: #2c3e50;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --border: #dee2e6;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: var(--dark);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Sidebar Styles */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, var(--secondary) 0%, #34495e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .sidebar-brand i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .menu-section {
            margin-bottom: 1rem;
        }

        .menu-title {
            padding: 0.75rem 1.5rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: rgba(255,255,255,0.6);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary);
        }

        .menu-item i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: all 0.3s ease;
        }

        .top-nav {
            background: white;
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--dark);
            cursor: pointer;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark);
        }

        .user-role {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .logout-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 2rem;
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.donors { background: linear-gradient(135deg, var(--success), #20c997); }
        .stat-icon.seekers { background: linear-gradient(135deg, var(--info), #6f42c1); }
        .stat-icon.donations { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); }
        .stat-icon.requests { background: linear-gradient(135deg, var(--warning), #fd7e14); }
        .stat-icon.pending { background: linear-gradient(135deg, var(--danger), #e83e8c); }
        .stat-icon.inventory { background: linear-gradient(135deg, #6610f2, #6f42c1); }

        .stat-content {
            flex: 1;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-trend {
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.25rem;
        }

        .trend-up { color: var(--success); }
        .trend-down { color: var(--danger); }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .grid-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-content {
            padding: 1.5rem;
        }

        /* Quick Actions */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            background: white;
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 1.5rem 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }

        .action-btn:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .action-text {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Activity List */
        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            transition: background 0.3s ease;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item:hover {
            background: var(--light);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .activity-icon.donation { background: var(--success); }
        .activity-icon.request { background: var(--info); }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--gray);
            white-space: nowrap;
        }

        /* Blood Inventory Status */
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 0.75rem;
        }

        .blood-group-item {
            text-align: center;
            padding: 1rem 0.5rem;
            border-radius: 8px;
            background: var(--light);
            transition: all 0.3s ease;
        }

        .blood-group-item:hover {
            transform: scale(1.05);
        }

        .blood-group {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .blood-quantity {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark);
        }

        .blood-status {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            margin-top: 0.5rem;
        }

        .status-critical { background: var(--danger); color: white; }
        .status-low { background: var(--warning); color: var(--dark); }
        .status-adequate { background: var(--success); color: white; }
        .status-good { background: var(--info); color: white; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .nav-toggle {
                display: block;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-content {
                padding: 1rem;
            }

            .welcome-banner {
                padding: 1.5rem;
            }

            .welcome-title {
                font-size: 1.5rem;
            }

            .actions-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .stat-card {
                flex-direction: column;
                text-align: center;
            }

            .user-info {
                display: none;
            }

            .top-nav {
                padding: 1rem;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="fas fa-tint"></i>
                    <span>BloodDonor</span>
                </div>
            </div>
            
            <nav class="sidebar-menu">
                <div class="menu-section">
                    <div class="menu-title">Main Menu</div>
                    <a href="admin_dashboard.php" class="menu-item active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-title">Management</div>
                    <a href="manage_users.php" class="menu-item">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                    <a href="manage_donations.php" class="menu-item">
                        <i class="fas fa-hand-holding-medical"></i>
                        <span>Manage Donations</span>
                    </a>
                    <a href="manage_blood_requests.php" class="menu-item">
                        <i class="fas fa-blood"></i>
                        <span>Blood Requests</span>
                    </a>
                    <a href="blood_inventory.php" class="menu-item">
                        <i class="fas fa-warehouse"></i>
                        <span>Blood Inventory</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-title">System</div>
                    <a href="setting.php" class="menu-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    
                    <a href="../homepage.php" class="menu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <header class="top-nav">
                <button class="nav-toggle" id="navToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="user-menu">
                    

                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Welcome Banner -->
                <div class="welcome-banner fade-in">
                    <h1 class="welcome-title">Welcome back, </h1>
                    <p class="welcome-subtitle">Here's what's happening with your blood donation system today.</p>
                </div>

                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card fade-in">
                        <div class="stat-icon donors">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['total_donors'] ?? '0'; ?></div>
                            <div class="stat-label">Total Donors</div>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i> 12% from last month
                            </div>
                        </div>
                    </div>

                    <div class="stat-card fade-in">
                        <div class="stat-icon seekers">
                            <i class="fas fa-hospital-user"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['total_seekers'] ?? '0'; ?></div>
                            <div class="stat-label">Blood Seekers</div>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i> 8% from last month
                            </div>
                        </div>
                    </div>

                    <div class="stat-card fade-in">
                        <div class="stat-icon donations">
                            <i class="fas fa-tint"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['total_donations'] ?? '0'; ?></div>
                            <div class="stat-label">Total Donations</div>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i> 15% from last month
                            </div>
                        </div>
                    </div>

                    <div class="stat-card fade-in">
                        <div class="stat-icon requests">
                            <i class="fas fa-hand-holding-medical"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['total_requests'] ?? '0'; ?></div>
                            <div class="stat-label">Blood Requests</div>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i> 5% from last month
                            </div>
                        </div>
                    </div>

                    <div class="stat-card fade-in">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo ($stats['pending_requests'] ?? 0) + ($stats['pending_donations'] ?? 0); ?></div>
                            <div class="stat-label">Pending Actions</div>
                            <div class="stat-trend trend-down">
                                <i class="fas fa-arrow-down"></i> 3% from last week
                            </div>
                        </div>
                    </div>

                    <div class="stat-card fade-in">
                        <div class="stat-icon inventory">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['available_blood_groups'] ?? '0'; ?>/8</div>
                            <div class="stat-label">Available Blood Groups</div>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i> 2 more than last week
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Quick Actions -->
                    <div class="grid-card fade-in">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-bolt"></i>
                                Quick Actions
                            </h2>
                        </div>
                        <div class="card-content">
                            <div class="actions-grid">
                                <a href="manage_users.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-users-cog"></i>
                                    </div>
                                    <div class="action-text">Manage Users</div>
                                </a>
                                <a href="manage_donations.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-hand-holding-medical"></i>
                                    </div>
                                    <div class="action-text">Manage Donations</div>
                                </a>
                                <a href="manage_blood_requests.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-blood"></i>
                                    </div>
                                    <div class="action-text">Blood Requests</div>
                                </a>
                                <a href="blood_inventory.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-warehouse"></i>
                                    </div>
                                    <div class="action-text">Inventory</div>
                                </a>
                                <a href="setting.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div class="action-text">Settings</div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="grid-card fade-in">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-history"></i>
                                Recent Activity
                            </h2>
                        </div>
                        <div class="card-content">
                            <div class="activity-list">
                                <?php if (count($recent_activities) > 0): ?>
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon <?php echo $activity['type']; ?>">
                                                <i class="fas fa-<?php echo $activity['type'] == 'donation' ? 'tint' : 'hand-holding-medical'; ?>"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title"><?php echo $activity['description']; ?></div>
                                                <div class="activity-meta">By <?php echo htmlspecialchars($activity['full_name']); ?></div>
                                            </div>
                                            <div class="activity-time">
                                                <?php 
                                                    $time_ago = time() - strtotime($activity['created_at']);
                                                    if ($time_ago < 3600) {
                                                        echo ceil($time_ago / 60) . 'm ago';
                                                    } elseif ($time_ago < 86400) {
                                                        echo ceil($time_ago / 3600) . 'h ago';
                                                    } else {
                                                        echo date('M j', strtotime($activity['created_at']));
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 2rem; color: var(--gray);">
                                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                        <p>No recent activity</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blood Inventory Status -->
                <div class="grid-card fade-in">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-vial"></i>
                            Blood Inventory Status
                        </h2>
                        <a href="blood_inventory.php" class="logout-btn" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                            <i class="fas fa-external-link-alt"></i>
                            View Details
                        </a>
                    </div>
                    <div class="card-content">
                        <div class="inventory-grid">
                            <?php foreach ($inventory as $item): 
                                $quantity = $item['quantity_ml'];
                                
                                // Determine stock status
                                if ($quantity == 0) {
                                    $status = 'critical';
                                    $status_text = 'Out of Stock';
                                } elseif ($quantity < 500) {
                                    $status = 'low';
                                    $status_text = 'Low';
                                } elseif ($quantity < 1000) {
                                    $status = 'adequate';
                                    $status_text = 'Adequate';
                                } else {
                                    $status = 'good';
                                    $status_text = 'Good';
                                }
                            ?>
                                <div class="blood-group-item">
                                    <div class="blood-group"><?php echo $item['blood_group']; ?></div>
                                    <div class="blood-quantity"><?php echo $quantity; ?>ml</div>
                                    <div class="blood-status status-<?php echo $status; ?>">
                                        <?php echo $status_text; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile Navigation Toggle
        document.getElementById('navToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const navToggle = document.getElementById('navToggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !navToggle.contains(event.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        // Add animation delay to stat cards
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Auto-refresh dashboard every 30 seconds
        setInterval(() => {
            // You can add AJAX calls here to refresh data without page reload
            console.log('Dashboard auto-refresh triggered');
        }, 30000);
    </script>
</body>
</html>