<?php
session_start();
include '../config/database.php';

// Simple admin check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../admin_login.php?access_denied=1");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get admin stats
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'donor') as total_donors,
    (SELECT COUNT(*) FROM users WHERE role = 'seeker') as total_seekers,
    (SELECT COUNT(*) FROM donations) as total_donations,
    (SELECT COUNT(*) FROM blood_requests) as total_requests,
    (SELECT COUNT(*) FROM blood_requests WHERE status = 'pending') as pending_requests,
    (SELECT COUNT(*) FROM donations WHERE status = 'pending') as pending_donations";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blood Donation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
        }

        .admin-header {
            background: linear-gradient(135deg, #c00 0%, #a00 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-brand i {
            font-size: 1.5rem;
        }

        .admin-brand h1 {
            font-size: 1.5rem;
        }

        .admin-actions a {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .admin-actions a:hover {
            background: rgba(255,255,255,0.1);
        }

        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #c00;
        }

        .welcome-section h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #c00;
        }

        .stat-card i {
            font-size: 2rem;
            color: #c00;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .admin-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .action-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #c00;
        }

        .action-card h3 {
            color: #c00;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-card p {
            color: #666;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .admin-actions {
                display: flex;
                gap: 0.5rem;
            }

            .admin-actions a {
                margin-left: 0;
            }

            .admin-container {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="admin-brand">
                <i class="fas fa-shield-alt"></i>
                <h1>Admin Dashboard</h1>
            </div>
            <div class="admin-actions">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>

    <div class="admin-container">
        <div class="welcome-section">
            <h2><i class="fas fa-tachometer-alt"></i> System Overview</h2>
            <p>Welcome to the Blood Donation System Admin Panel</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-user-friends"></i>
                <div class="stat-number"><?php echo $stats['total_donors']; ?></div>
                <div class="stat-label">Total Donors</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-hospital-user"></i>
                <div class="stat-number"><?php echo $stats['total_seekers']; ?></div>
                <div class="stat-label">Blood Seekers</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-tint"></i>
                <div class="stat-number"><?php echo $stats['total_donations']; ?></div>
                <div class="stat-label">Total Donations</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-hand-holding-medical"></i>
                <div class="stat-number"><?php echo $stats['total_requests']; ?></div>
                <div class="stat-label">Blood Requests</div>
            </div>
        </div>

        <div class="admin-actions-grid">
            <a href="manage_users.php" class="action-card">
                <h3><i class="fas fa-users-cog"></i> Manage Users</h3>
                <p>View, edit, and manage all system users including donors and seekers.</p>
            </a>
            <a href="manage_donations.php" class="action-card">
                <h3><i class="fas fa-hand-holding-medical"></i> Manage Donations</h3>
                <p>Approve, reject, and track all blood donation records.</p>
            </a>
            <a href="blood_requests.php" class="action-card">
                <h3><i class="fas fa-blood"></i> Blood Requests</h3>
                <p>Manage and fulfill blood requests from hospitals and seekers.</p>
            </a>
            <a href="blood_inventory.php" class="action-card">
                <h3><i class="fas fa-warehouse"></i> Blood Inventory</h3>
                <p>Monitor and manage blood stock levels across all blood groups.</p>
            </a>
        </div>
    </div>
</body>
</html>