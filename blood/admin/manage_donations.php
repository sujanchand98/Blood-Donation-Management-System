<?php
session_start();
require_once '../config/database.php';



$database = new Database();
$db = $database->getConnection();




// Handle donation actions
$message = '';
$error = '';

if (isset($_POST['action'])) {
    $donation_id = $_POST['donation_id'] ?? '';
    
    switch ($_POST['action']) {
        case 'approve':
            $stmt = $db->prepare("UPDATE donations SET status = 'approved' WHERE id = ?");
            if ($stmt->execute([$donation_id])) {
                // Update blood inventory
                $donation = $db->prepare("SELECT blood_group, quantity_ml FROM donations WHERE id = ?");
                $donation->execute([$donation_id]);
                $donation_data = $donation->fetch(PDO::FETCH_ASSOC);
                
                $update_inventory = $db->prepare("UPDATE blood_inventory SET quantity_ml = quantity_ml + ? WHERE blood_group = ?");
                $update_inventory->execute([$donation_data['quantity_ml'], $donation_data['blood_group']]);
                
                $message = "Donation approved and inventory updated";
            } else {
                $error = "Failed to approve donation";
            }
            break;
            
        case 'reject':
            $stmt = $db->prepare("UPDATE donations SET status = 'rejected' WHERE id = ?");
            if ($stmt->execute([$donation_id])) {
                $message = "Donation rejected";
            } else {
                $error = "Failed to reject donation";
            }
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM donations WHERE id = ?");
            if ($stmt->execute([$donation_id])) {
                $message = "Donation record deleted";
            } else {
                $error = "Failed to delete donation";
            }
            break;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$blood_group_filter = $_GET['blood_group'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for donations
$query = "SELECT d.*, u.full_name, u.email, u.phone 
          FROM donations d 
          JOIN users u ON d.donor_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND d.status = ?";
    $params[] = $status_filter;
}

if (!empty($blood_group_filter)) {
    $query .= " AND d.blood_group = ?";
    $params[] = $blood_group_filter;
}

if (!empty($date_from)) {
    $query .= " AND d.donation_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND d.donation_date <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY d.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get donation statistics
$stats_query = "SELECT 
    COUNT(*) as total_donations,
    SUM(quantity_ml) as total_blood,
    AVG(quantity_ml) as avg_donation,
    COUNT(DISTINCT donor_id) as unique_donors
    FROM donations 
    WHERE status = 'approved'";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$donation_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Donations - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reuse styles from manage_users.php and add donation-specific styles */
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
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        /* Include all other styles from manage_users.php */
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
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #c00;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
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
            grid-template-columns: 1fr 1fr 1fr 1fr auto;
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

        .donations-table {
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

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
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
                <a href="manage_donations.php" class="active"><i class="fas fa-hand-holding-medical"></i> Manage Donations</a>
                <a href="manage_blood_requests.php"><i class="fas fa-blood"></i> Blood Requests</a>
                <a href="blood_inventory.php"><i class="fas fa-warehouse"></i> Inventory</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-hand-holding-medical"></i> Manage Donations</h1>
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

        <!-- Donation Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $donation_stats['total_donations'] ?? '0'; ?></div>
                <div class="stat-label">Total Donations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $donation_stats['total_blood'] ?? '0'; ?> ml</div>
                <div class="stat-label">Blood Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo round($donation_stats['avg_donation'] ?? '0'); ?> ml</div>
                <div class="stat-label">Average Donation</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $donation_stats['unique_donors'] ?? '0'; ?></div>
                <div class="stat-label">Unique Donors</div>
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
                    <a href="manage_donations.php" class="btn btn-secondary" style="margin-top: 0.5rem;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Donations Table -->
        <div class="donations-table">
            <div class="table-header">
                <h3>Donation Records (<?php echo count($donations); ?> donations)</h3>
            </div>
            <div class="table-container">
                <?php if (count($donations) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Donor</th>
                                <th>Blood Details</th>
                                <th>Donation Date</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donations as $donation): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <div class="user-avatar" style="width: 40px; height: 40px; background: #c00; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                <?php echo strtoupper(substr($donation['full_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($donation['full_name']); ?></div>
                                                <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($donation['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="blood-group-badge"><?php echo $donation['blood_group']; ?></span>
                                            <div class="quantity-display" style="margin-top: 0.5rem;"><?php echo $donation['quantity_ml']; ?> ml</div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($donation['donation_date'])); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $donation['status']; ?>">
                                            <?php echo ucfirst($donation['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($donation['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($donation['status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this donation? This will update blood inventory.')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Reject this donation?')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this donation record permanently?')">
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
                        <i class="fas fa-hand-holding-medical"></i>
                        <h3>No Donations Found</h3>
                        <p>No donations match your current filters.</p>
                        <a href="manage_donations.php" class="btn btn-primary">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add confirmation for all actions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to perform this action?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>