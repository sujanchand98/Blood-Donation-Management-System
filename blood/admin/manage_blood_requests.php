<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle blood request actions
$message = '';
$error = '';

if (isset($_POST['action'])) {
    $request_id = $_POST['request_id'] ?? '';
    
    switch ($_POST['action']) {
        case 'approve':
            $stmt = $db->prepare("UPDATE blood_requests SET status = 'approved' WHERE id = ?");
            if ($stmt->execute([$request_id])) {
                $message = "Blood request approved";
            } else {
                $error = "Failed to approve blood request";
            }
            break;
            
        case 'reject':
            $stmt = $db->prepare("UPDATE blood_requests SET status = 'rejected' WHERE id = ?");
            if ($stmt->execute([$request_id])) {
                $message = "Blood request rejected";
            } else {
                $error = "Failed to reject blood request";
            }
            break;
            
        case 'fulfill':
            // Check if enough blood is available
            $request = $db->prepare("SELECT blood_group, quantity_ml FROM blood_requests WHERE id = ?");
            $request->execute([$request_id]);
            $request_data = $request->fetch(PDO::FETCH_ASSOC);
            
            $inventory = $db->prepare("SELECT quantity_ml FROM blood_inventory WHERE blood_group = ?");
            $inventory->execute([$request_data['blood_group']]);
            $inventory_data = $inventory->fetch(PDO::FETCH_ASSOC);
            
            if ($inventory_data && $inventory_data['quantity_ml'] >= $request_data['quantity_ml']) {
                // Update inventory
                $update_inventory = $db->prepare("UPDATE blood_inventory SET quantity_ml = quantity_ml - ? WHERE blood_group = ?");
                $update_inventory->execute([$request_data['quantity_ml'], $request_data['blood_group']]);
                
                // Mark request as fulfilled
                $stmt = $db->prepare("UPDATE blood_requests SET status = 'fulfilled' WHERE id = ?");
                if ($stmt->execute([$request_id])) {
                    $message = "Blood request fulfilled and inventory updated";
                } else {
                    $error = "Failed to mark request as fulfilled";
                }
            } else {
                $error = "Insufficient blood in inventory to fulfill this request";
            }
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM blood_requests WHERE id = ?");
            if ($stmt->execute([$request_id])) {
                $message = "Blood request deleted";
            } else {
                $error = "Failed to delete blood request";
            }
            break;
    }
}

// Get detailed request data if viewing a specific request
$view_request = null;
if (isset($_GET['view'])) {
    $view_id = $_GET['view'];
    $stmt = $db->prepare("SELECT br.*, u.full_name, u.email, u.phone, u.address 
                         FROM blood_requests br 
                         JOIN users u ON br.seeker_id = u.id 
                         WHERE br.id = ?");
    $stmt->execute([$view_id]);
    $view_request = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$blood_group_filter = $_GET['blood_group'] ?? '';
$urgency_filter = $_GET['urgency'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for blood requests
$query = "SELECT br.*, u.full_name, u.email, u.phone 
          FROM blood_requests br 
          JOIN users u ON br.seeker_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND br.status = ?";
    $params[] = $status_filter;
}

if (!empty($blood_group_filter)) {
    $query .= " AND br.blood_group = ?";
    $params[] = $blood_group_filter;
}

if (!empty($urgency_filter)) {
    $query .= " AND br.urgency_level = ?";
    $params[] = $urgency_filter;
}

if (!empty($date_from)) {
    $query .= " AND br.created_at >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND br.created_at <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY 
    CASE br.urgency_level 
        WHEN 'critical' THEN 1
        WHEN 'high' THEN 2
        WHEN 'medium' THEN 3
        WHEN 'low' THEN 4
    END, br.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$blood_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get separate lists for each status
$pending_requests = array_filter($blood_requests, function($request) {
    return $request['status'] === 'pending';
});

$approved_requests = array_filter($blood_requests, function($request) {
    return $request['status'] === 'approved';
});

$rejected_requests = array_filter($blood_requests, function($request) {
    return $request['status'] === 'rejected';
});

$fulfilled_requests = array_filter($blood_requests, function($request) {
    return $request['status'] === 'fulfilled';
});

// Get blood request statistics
$stats_query = "SELECT 
    COUNT(*) as total_requests,
    SUM(quantity_ml) as total_blood_requested,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_requests,
    COUNT(CASE WHEN status = 'fulfilled' THEN 1 END) as fulfilled_requests
    FROM blood_requests";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$request_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blood Requests - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Urgency level styles */
        .urgency-critical { background: #dc3545; color: white; }
        .urgency-high { background: #fd7e14; color: white; }
        .urgency-medium { background: #ffc107; color: #212529; }
        .urgency-low { background: #28a745; color: white; }
        
        .urgency-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #c00 0%, #a00 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .request-details {
            display: grid;
            gap: 1.5rem;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            border-left: 4px solid #c00;
        }

        .detail-section h3 {
            margin: 0 0 1rem 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            color: #333;
            font-size: 1rem;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        /* Status Tabs */
        .status-tabs {
            display: flex;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .status-tab {
            flex: 1;
            padding: 1rem 1.5rem;
            text-align: center;
            cursor: pointer;
            border: none;
            background: #f8f9fa;
            transition: all 0.3s;
            font-weight: 600;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .status-tab:hover {
            background: #e9ecef;
        }

        .status-tab.active {
            background: #c00;
            color: white;
        }

        .status-tab.pending.active { background: #ffc107; color: #212529; }
        .status-tab.approved.active { background: #17a2b8; color: white; }
        .status-tab.rejected.active { background: #dc3545; color: white; }
        .status-tab.fulfilled.active { background: #28a745; color: white; }

        .tab-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-tab-content {
            display: none;
        }

        .status-tab-content.active {
            display: block;
        }

        /* Include all other styles from previous pages */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-brand i {
            font-size: 1.8rem;
        }

        .admin-brand h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 1rem;
        }

        .user-role {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .admin-menu {
            background: #2c3e50;
            padding: 1rem 0;
        }

        .menu-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .menu-links {
            display: flex;
            gap: 2rem;
        }

        .menu-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .menu-links a:hover, .menu-links a.active {
            background: rgba(255,255,255,0.1);
        }

        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            color: #2c3e50;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #c00;
        }

        .stat-card.pending { border-top-color: #ffc107; }
        .stat-card.approved { border-top-color: #17a2b8; }
        .stat-card.rejected { border-top-color: #dc3545; }
        .stat-card.fulfilled { border-top-color: #28a745; }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #c00;
            margin-bottom: 0.5rem;
        }

        .stat-card.pending .stat-number { color: #ffc107; }
        .stat-card.approved .stat-number { color: #17a2b8; }
        .stat-card.rejected .stat-number { color: #dc3545; }
        .stat-card.fulfilled .stat-number { color: #28a745; }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .filters-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group select, .form-group input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #c00;
            color: white;
        }

        .btn-primary:hover {
            background: #a00;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .requests-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .blood-group-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: bold;
            background: #c00;
            color: white;
        }

        .quantity-display {
            font-weight: bold;
            color: #c00;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1edff; color: #004085; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-fulfilled { background: #d4edda; color: #155724; }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.25rem;
        }

        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-view { background: #6c757d; color: white; }
        .btn-view:hover { background: #545b62; }

        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .admin-nav {
                flex-direction: column;
                gap: 1rem;
            }
            
            .menu-links {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .status-tabs {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
        </nav>
    </header>

    <nav class="admin-menu">
        <div class="menu-container">
            <div class="menu-links">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="manage_donations.php"><i class="fas fa-hand-holding-medical"></i> Manage Donations</a>
                <a href="manage_blood_requests.php" class="active"><i class="fas fa-blood"></i> Blood Requests</a>
                <a href="blood_inventory.php"><i class="fas fa-warehouse"></i> Inventory</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-blood"></i> Manage Blood Requests</h1>
        </div>

        <?php if ($message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Blood Request Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $request_stats['total_requests'] ?? '0'; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $request_stats['total_blood_requested'] ?? '0'; ?> ml</div>
                <div class="stat-label">Blood Requested</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?php echo $request_stats['pending_requests'] ?? '0'; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-number"><?php echo $request_stats['approved_requests'] ?? '0'; ?></div>
                <div class="stat-label">Approved Requests</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-number"><?php echo $request_stats['rejected_requests'] ?? '0'; ?></div>
                <div class="stat-label">Rejected Requests</div>
            </div>
            <div class="stat-card fulfilled">
                <div class="stat-number"><?php echo $request_stats['fulfilled_requests'] ?? '0'; ?></div>
                <div class="stat-label">Fulfilled Requests</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="fulfilled" <?php echo $status_filter == 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Blood Group</label>
                    <select name="blood_group">
                        <option value="">All Blood Groups</option>
                        <option value="A+" <?php echo $blood_group_filter == 'A+' ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo $blood_group_filter == 'A-' ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo $blood_group_filter == 'B+' ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo $blood_group_filter == 'B-' ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo $blood_group_filter == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo $blood_group_filter == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        <option value="O+" <?php echo $blood_group_filter == 'O+' ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo $blood_group_filter == 'O-' ? 'selected' : ''; ?>>O-</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Urgency</label>
                    <select name="urgency">
                        <option value="">All Urgency</option>
                        <option value="critical" <?php echo $urgency_filter == 'critical' ? 'selected' : ''; ?>>Critical</option>
                        <option value="high" <?php echo $urgency_filter == 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $urgency_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $urgency_filter == 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="manage_blood_requests.php" class="btn btn-secondary" style="margin-top: 0.5rem;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Status Tabs -->
        <div class="status-tabs">
            <button class="status-tab all active" onclick="showTab('all')">
                <i class="fas fa-list"></i> All Requests
                <span class="tab-badge"><?php echo count($blood_requests); ?></span>
            </button>
            <button class="status-tab pending" onclick="showTab('pending')">
                <i class="fas fa-clock"></i> Pending
                <span class="tab-badge"><?php echo count($pending_requests); ?></span>
            </button>
            <button class="status-tab approved" onclick="showTab('approved')">
                <i class="fas fa-check-circle"></i> Approved
                <span class="tab-badge"><?php echo count($approved_requests); ?></span>
            </button>
            <button class="status-tab rejected" onclick="showTab('rejected')">
                <i class="fas fa-times-circle"></i> Rejected
                <span class="tab-badge"><?php echo count($rejected_requests); ?></span>
            </button>
            <button class="status-tab fulfilled" onclick="showTab('fulfilled')">
                <i class="fas fa-check-double"></i> Fulfilled
                <span class="tab-badge"><?php echo count($fulfilled_requests); ?></span>
            </button>
        </div>

        <!-- All Requests Tab -->
        <div id="all-tab" class="status-tab-content active">
            <div class="requests-table">
                <div class="table-header">
                    <h3>All Blood Requests (<?php echo count($blood_requests); ?> requests)</h3>
                </div>
                <div class="table-container">
                    <?php if (count($blood_requests) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Requester</th>
                                    <th>Blood Details</th>
                                    <th>Hospital/Patient</th>
                                    <th>Urgency</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blood_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div class="user-avatar" style="width: 40px; height: 40px; background: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                    <?php echo strtoupper(substr($request['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($request['full_name']); ?></div>
                                                    <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($request['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="blood-group-badge"><?php echo $request['blood_group']; ?></span>
                                                <div class="quantity-display" style="margin-top: 0.5rem;"><?php echo $request['quantity_ml']; ?> ml</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($request['hospital_name'] ?? 'N/A'); ?></div>
                                            <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($request['patient_name'] ?? 'N/A'); ?></div>
                                            <?php if ($request['reason']): ?>
                                                <div style="color: #666; font-size: 0.8rem; margin-top: 0.25rem;"><?php echo htmlspecialchars($request['reason']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="urgency-badge urgency-<?php echo $request['urgency_level']; ?>">
                                                <?php echo ucfirst($request['urgency_level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $request['status']; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <!-- View Button -->
                                                <a href="?view=<?php echo $request['id']; ?>" class="btn btn-view btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>

                                                <?php if ($request['status'] == 'pending'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this blood request?')">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Reject this blood request?')">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </form>
                                                <?php elseif ($request['status'] == 'approved'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        <input type="hidden" name="action" value="fulfill">
                                                        <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Fulfill this blood request? This will deduct from inventory.')">
                                                            <i class="fas fa-check-double"></i> Fulfill
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this blood request permanently?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-tint"></i>
                            <h3>No Blood Requests Found</h3>
                            <p>No blood requests match your current filters.</p>
                            <a href="manage_blood_requests.php" class="btn btn-primary">Clear Filters</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pending Requests Tab -->
        <div id="pending-tab" class="status-tab-content">
            <div class="requests-table">
                <div class="table-header">
                    <h3>Pending Blood Requests (<?php echo count($pending_requests); ?> requests)</h3>
                </div>
                <div class="table-container">
                    <?php if (count($pending_requests) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Requester</th>
                                    <th>Blood Details</th>
                                    <th>Hospital/Patient</th>
                                    <th>Urgency</th>
                                    <th>Requested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div class="user-avatar" style="width: 40px; height: 40px; background: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                    <?php echo strtoupper(substr($request['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($request['full_name']); ?></div>
                                                    <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($request['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="blood-group-badge"><?php echo $request['blood_group']; ?></span>
                                                <div class="quantity-display" style="margin-top: 0.5rem;"><?php echo $request['quantity_ml']; ?> ml</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($request['hospital_name'] ?? 'N/A'); ?></div>
                                            <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($request['patient_name'] ?? 'N/A'); ?></div>
                                            <?php if ($request['reason']): ?>
                                                <div style="color: #666; font-size: 0.8rem; margin-top: 0.25rem;"><?php echo htmlspecialchars($request['reason']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="urgency-badge urgency-<?php echo $request['urgency_level']; ?>">
                                                <?php echo ucfirst($request['urgency_level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?view=<?php echo $request['id']; ?>" class="btn btn-view btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this blood request?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Reject this blood request?')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this blood request permanently?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clock"></i>
                            <h3>No Pending Requests</h3>
                            <p>There are no pending blood requests at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Approved Requests Tab -->
        <div id="approved-tab" class="status-tab-content">
            <div class="requests-table">
                <div class="table-header">
                    <h3>Approved Blood Requests (<?php echo count($approved_requests); ?> requests)</h3>
                </div>
                <div class="table-container">
                    <?php if (count($approved_requests) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Requester</th>
                                    <th>Blood Details</th>
                                    <th>Hospital/Patient</th>
                                    <th>Urgency</th>
                                    <th>Approved Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approved_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div class="user-avatar" style="width: 40px; height: 40px; background: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                    <?php echo strtoupper(substr($request['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($request['full_name']); ?></div>
                                                    <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($request['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="blood-group-badge"><?php echo $request['blood_group']; ?></span>
                                                <div class="quantity-display" style="margin-top: 0.5rem;"><?php echo $request['quantity_ml']; ?> ml</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($request['hospital_name'] ?? 'N/A'); ?></div>
                                            <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($request['patient_name'] ?? 'N/A'); ?></div>
                                            <?php if ($request['reason']): ?>
                                                <div style="color: #666; font-size: 0.8rem; margin-top: 0.25rem;"><?php echo htmlspecialchars($request['reason']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="urgency-badge urgency-<?php echo $request['urgency_level']; ?>">
                                                <?php echo ucfirst($request['urgency_level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($request['updated_at'] ?? $request['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?view=<?php echo $request['id']; ?>" class="btn btn-view btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="fulfill">
                                                    <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Fulfill this blood request? This will deduct from inventory.')">
                                                        <i class="fas fa-check-double"></i> Fulfill
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this blood request permanently?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h3>No Approved Requests</h3>
                            <p>There are no approved blood requests at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Rejected Requests Tab -->
        <div id="rejected-tab" class="status-tab-content">
            <div class="requests-table">
                <div class="table-header">
                    <h3>Rejected Blood Requests (<?php echo count($rejected_requests); ?> requests)</h3>
                </div>
                <div class="table-container">
                    <?php if (count($rejected_requests) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Requester</th>
                                    <th>Blood Details</th>
                                    <th>Hospital/Patient</th>
                                    <th>Urgency</th>
                                    <th>Rejected Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rejected_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div class="user-avatar" style="width: 40px; height: 40px; background: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                    <?php echo strtoupper(substr($request['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($request['full_name']); ?></div>
                                                    <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($request['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="blood-group-badge"><?php echo $request['blood_group']; ?></span>
                                                <div class="quantity-display" style="margin-top: 0.5rem;"><?php echo $request['quantity_ml']; ?> ml</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($request['hospital_name'] ?? 'N/A'); ?></div>
                                            <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($request['patient_name'] ?? 'N/A'); ?></div>
                                            <?php if ($request['reason']): ?>
                                                <div style="color: #666; font-size: 0.8rem; margin-top: 0.25rem;"><?php echo htmlspecialchars($request['reason']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="urgency-badge urgency-<?php echo $request['urgency_level']; ?>">
                                                <?php echo ucfirst($request['urgency_level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($request['updated_at'] ?? $request['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?view=<?php echo $request['id']; ?>" class="btn btn-view btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this blood request permanently?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-times-circle"></i>
                            <h3>No Rejected Requests</h3>
                            <p>There are no rejected blood requests at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Fulfilled Requests Tab -->
        <div id="fulfilled-tab" class="status-tab-content">
            <div class="requests-table">
                <div class="table-header">
                    <h3>Fulfilled Blood Requests (<?php echo count($fulfilled_requests); ?> requests)</h3>
                </div>
                <div class="table-container">
                    <?php if (count($fulfilled_requests) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Requester</th>
                                    <th>Blood Details</th>
                                    <th>Hospital/Patient</th>
                                    <th>Urgency</th>
                                    <th>Fulfilled Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fulfilled_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div class="user-avatar" style="width: 40px; height: 40px; background: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                    <?php echo strtoupper(substr($request['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($request['full_name']); ?></div>
                                                    <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($request['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="blood-group-badge"><?php echo $request['blood_group']; ?></span>
                                                <div class="quantity-display" style="margin-top: 0.5rem;"><?php echo $request['quantity_ml']; ?> ml</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($request['hospital_name'] ?? 'N/A'); ?></div>
                                            <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($request['patient_name'] ?? 'N/A'); ?></div>
                                            <?php if ($request['reason']): ?>
                                                <div style="color: #666; font-size: 0.8rem; margin-top: 0.25rem;"><?php echo htmlspecialchars($request['reason']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="urgency-badge urgency-<?php echo $request['urgency_level']; ?>">
                                                <?php echo ucfirst($request['urgency_level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($request['updated_at'] ?? $request['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?view=<?php echo $request['id']; ?>" class="btn btn-view btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this blood request permanently?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-double"></i>
                            <h3>No Fulfilled Requests</h3>
                            <p>There are no fulfilled blood requests at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- View Request Modal -->
    <?php if ($view_request): ?>
    <div id="viewModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-medical"></i> Blood Request Details</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="request-details">
                    <!-- Requester Information -->
                    <div class="detail-section">
                        <h3>Requester Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Full Name</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['full_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Address</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['address'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Blood Request Details -->
                    <div class="detail-section">
                        <h3>Blood Request Details</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Blood Group</span>
                                <span class="detail-value">
                                    <span class="blood-group-badge"><?php echo $view_request['blood_group']; ?></span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Quantity</span>
                                <span class="detail-value quantity-display"><?php echo $view_request['quantity_ml']; ?> ml</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Urgency Level</span>
                                <span class="detail-value">
                                    <span class="urgency-badge urgency-<?php echo $view_request['urgency_level']; ?>">
                                        <?php echo ucfirst($view_request['urgency_level']); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value">
                                    <span class="status-badge status-<?php echo $view_request['status']; ?>">
                                        <?php echo ucfirst($view_request['status']); ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Patient & Hospital Information -->
                    <div class="detail-section">
                        <h3>Patient & Hospital Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Patient Name</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['patient_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Patient Age</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['patient_age'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Patient Gender</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['patient_gender'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Hospital Name</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['hospital_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item full-width">
                                <span class="detail-label">Hospital Address</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['hospital_address'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="detail-section">
                        <h3>Additional Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item full-width">
                                <span class="detail-label">Reason for Request</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['reason'] ?? 'No reason provided'); ?></span>
                            </div>
                            <div class="detail-item full-width">
                                <span class="detail-label">Additional Notes</span>
                                <span class="detail-value"><?php echo htmlspecialchars($view_request['additional_notes'] ?? 'No additional notes'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Information -->
                    <div class="detail-section">
                        <h3>Timeline</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Request Date</span>
                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($view_request['created_at'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Required Date</span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($view_request['required_date'] ?? $view_request['created_at'])); ?></span>
                            </div>
                            <?php if ($view_request['updated_at'] && $view_request['updated_at'] != $view_request['created_at']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Last Updated</span>
                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($view_request['updated_at'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Add confirmation for all actions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to perform this action?')) {
                    e.preventDefault();
                }
            });
        });

        // Modal functions
        function closeModal() {
            window.location.href = 'manage_blood_requests.php';
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('viewModal');
            if (modal && e.target === modal) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.status-tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.status-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>