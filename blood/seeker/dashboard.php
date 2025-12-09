<?php
// session_start();
include '../config/database.php';
include '../includes/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'seeker') {
    header("Location: ../login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get user data from database if session data is missing
if (!isset($_SESSION['full_name']) || empty($_SESSION['full_name'])) {
    $user_query = "SELECT full_name FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(":user_id", $user_id);
    $user_stmt->execute();
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data) {
        $_SESSION['full_name'] = $user_data['full_name'];
    } else {
        // Fallback if user not found
        $_SESSION['full_name'] = "User";
    }
}

// Get seeker's blood requests
$query = "SELECT * FROM blood_requests WHERE seeker_id = :user_id ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get blood inventory stats
$inventory_query = "SELECT blood_group, quantity_ml FROM blood_inventory ORDER BY blood_group";
$inventory_stmt = $db->prepare($inventory_query);
$inventory_stmt->execute();
$inventory = $inventory_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats for dashboard
$total_query = "SELECT COUNT(*) as total FROM blood_requests WHERE seeker_id = :user_id";
$total_stmt = $db->prepare($total_query);
$total_stmt->bindParam(":user_id", $user_id);
$total_stmt->execute();
$total_requests = $total_stmt->fetch()['total'];

$pending_query = "SELECT COUNT(*) as total FROM blood_requests WHERE seeker_id = :user_id AND status = 'pending'";
$pending_stmt = $db->prepare($pending_query);
$pending_stmt->bindParam(":user_id", $user_id);
$pending_stmt->execute();
$pending_requests = $pending_stmt->fetch()['total'];

$approved_query = "SELECT COUNT(*) as total FROM blood_requests WHERE seeker_id = :user_id AND status = 'approved'";
$approved_stmt = $db->prepare($approved_query);
$approved_stmt->bindParam(":user_id", $user_id);
$approved_stmt->execute();
$approved_requests = $approved_stmt->fetch()['total'];

$fulfilled_query = "SELECT COUNT(*) as total FROM blood_requests WHERE seeker_id = :user_id AND status = 'fulfilled'";
$fulfilled_stmt = $db->prepare($fulfilled_query);
$fulfilled_stmt->bindParam(":user_id", $user_id);
$fulfilled_stmt->execute();
$fulfilled_requests = $fulfilled_stmt->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seeker Dashboard - BloodLife</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #e53935;
            --primary-light: #ff6b6b;
            --primary-dark: #c62828;
            --secondary: #2c3e50;
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fce4e4 0%, #f5f7fa 100%);
            color: var(--secondary);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .logo i {
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            padding: 5px 10px;
            border-radius: 4px;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .user-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: white;
            color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--primary);
        }

        /* Dashboard Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .welcome-section h1 {
            font-size: 28px;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 5px;
        }

        .welcome-section p {
            color: var(--gray);
            font-size: 16px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-info h3 {
            font-size: 16px;
            font-weight: 500;
        }

        .profile-info p {
            font-size: 14px;
            color: var(--gray);
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
            border-left: 5px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.pending { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-icon.approved { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-icon.fulfilled { background: linear-gradient(135deg, #43e97b, #38f9d7); }

        .stat-info h3 {
            font-size: 14px;
            font-weight: 500;
            color: var(--gray);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--secondary);
        }

        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .dashboard-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--secondary);
        }

        .card-header a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .card-header a:hover {
            color: var(--primary-dark);
        }

        .card-body {
            padding: 25px;
        }

        .request-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .request-item:hover {
            background: #f8f9fa;
            border-radius: 8px;
            padding-left: 10px;
            padding-right: 10px;
        }

        .request-item:last-child {
            border-bottom: none;
        }

        .request-info h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--secondary);
        }

        .request-details {
            display: flex;
            gap: 15px;
            margin-top: 8px;
        }

        .request-detail {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: var(--gray);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1edff; color: #004085; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-fulfilled { background: #d4edda; color: #155724; }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .blood-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            background: var(--light);
            transition: var(--transition);
        }

        .blood-group-item:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }

        .blood-group-item.available { border-left: 4px solid var(--success); }
        .blood-group-item.low { border-left: 4px solid var(--warning); }
        .blood-group-item.critical { border-left: 4px solid var(--danger); }

        .blood-group {
            font-size: 18px;
            font-weight: 700;
            color: var(--secondary);
        }

        .blood-quantity {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .action-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border-color: var(--primary);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: white;
        }

        .action-card h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 10px;
        }

        .action-card p {
            font-size: 14px;
            color: var(--gray);
            line-height: 1.5;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--light-gray);
        }

        .empty-state p {
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Footer */
        footer {
            background: var(--secondary);
            color: white;
            padding: 3rem 0 1.5rem;
            margin-top: 4rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-column h4 {
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer-column h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary);
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column li {
            margin-bottom: 0.8rem;
        }

        .footer-column a {
            color: #b0b0b0;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-column a:hover {
            color: white;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .inventory-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            nav ul {
                gap: 1rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-tint"></i>
                    <span>BloodLife</span>
                </div>
                
                <nav>
                    <ul>
                        <li><a href="request_blood.php">Request Blood</a></li>
                        <li><a href="find_donor.php">Find Donors</a></li>
                        <li><a href="request_history.php">Request History</a></li>
                    </ul>
                </nav>
                
                <div class="user-actions">
                    <a href="profile.php" class="btn btn-outline">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="../homepage.php" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>!</h1>
                <p>Manage your blood requests and find donors</p>
            </div>
            <div class="user-profile">
                <div class="profile-avatar">
                    <?php 
                    $full_name = $_SESSION['full_name'] ?? 'User';
                    $name_parts = explode(' ', $full_name);
                    $initials = '';
                    foreach ($name_parts as $part) {
                        $initials .= strtoupper(substr($part, 0, 1));
                    }
                    echo $initials ?: 'U';
                    ?>
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($full_name); ?></h3>
                    <p>Blood Seeker</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-file-medical"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Requests</h3>
                    <div class="stat-number"><?php echo $total_requests; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Pending</h3>
                    <div class="stat-number"><?php echo $pending_requests; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Approved</h3>
                    <div class="stat-number"><?php echo $approved_requests; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon fulfilled">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-info">
                    <h3>Fulfilled</h3>
                    <div class="stat-number"><?php echo $fulfilled_requests; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="request_blood.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-tint"></i>
                </div>
                <h3>Request Blood</h3>
                <p>Submit a new blood request for patients in need</p>
            </a>
            
            <a href="find_donor.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Find Donors</h3>
                <p>Search for available blood donors in your area</p>
            </a>
            
            <a href="request_history.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3>Request History</h3>
                <p>View your previous blood requests and their status</p>
            </a>
        </div>
        
        <!-- Main Content -->
        <div class="dashboard-content">
            <!-- Recent Requests -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Recent Blood Requests</h2>
                    
                </div>
                <div class="card-body">
                    <?php if (count($recent_requests) > 0): ?>
                        <?php foreach ($recent_requests as $request): ?>
                            <div class="request-item">
                                <div class="request-info">
                                    <h4><?php echo htmlspecialchars($request['blood_group']); ?> Blood Request</h4>
                                    <div class="request-details">
                                        <div class="request-detail">
                                            <i class="fas fa-syringe"></i>
                                            <span><?php echo htmlspecialchars($request['quantity_ml']); ?> ml</span>
                                        </div>
                                        <div class="request-detail">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo ucfirst(htmlspecialchars($request['urgency_level'])); ?></span>
                                        </div>
                                        <div class="request-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('M j, Y', strtotime($request['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="request-status">
                                    <span class="status-badge status-<?php echo htmlspecialchars($request['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-medical"></i>
                            <p>No blood requests yet.</p>
                            <a href="request_blood.php" class="btn">Make Your First Request</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Blood Inventory -->
                   
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
            <div class="footer-bottom">
                <p>&copy; 2025 BloodLife. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Simple animation for stat cards on page load
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s, transform 0.5s';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
        });
    </script>
</body>
</html>