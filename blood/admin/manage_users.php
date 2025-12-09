<?php
session_start();
require_once '../config/database.php';


$database = new Database();
$db = $database->getConnection();




// Handle user actions
$message = '';
$error = '';

if (isset($_POST['action'])) {
    $user_id = $_POST['user_id'] ?? '';
    
    switch ($_POST['action']) {
        case 'activate':
            $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $message = "User activated successfully";
            } else {
                $error = "Failed to activate user";
            }
            break;
            
        case 'deactivate':
            $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $message = "User deactivated successfully";
            } else {
                $error = "Failed to deactivate user";
            }
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $message = "User deleted successfully";
            } else {
                $error = "Failed to delete user";
            }
            break;
    }
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query for users
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$stats_query = "SELECT 
    role,
    status,
    COUNT(*) as count 
    FROM users 
    GROUP BY role, status";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$user_stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            grid-template-columns: 1fr 1fr 2fr auto;
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

        .users-table {
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

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #c00;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #fff3cd; color: #856404; }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .role-admin { background: #dc3545; color: white; }
        .role-donor { background: #28a745; color: white; }
        .role-seeker { background: #007bff; color: white; }

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
                <a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a>
                <a href="manage_donations.php"><i class="fas fa-hand-holding-medical"></i> Manage Donations</a>
                <a href="manage_blood_requests.php"><i class="fas fa-blood"></i> Blood Requests</a>
                <a href="blood_inventory.php"><i class="fas fa-warehouse"></i> Inventory</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-users"></i> Manage Users</h1>
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

        <!-- User Statistics -->
        <div class="stats-grid">
            <?php
            $total_users = count($users);
            $active_users = array_filter($users, fn($user) => $user['status'] == 'active');
            $donors = array_filter($users, fn($user) => $user['role'] == 'donor');
            $seekers = array_filter($users, fn($user) => $user['role'] == 'seeker');
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($active_users); ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($donors); ?></div>
                <div class="stat-label">Blood Donors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($seekers); ?></div>
                <div class="stat-label">Blood Seekers</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="donor" <?php echo $role_filter == 'donor' ? 'selected' : ''; ?>>Donor</option>
                        <option value="seeker" <?php echo $role_filter == 'seeker' ? 'selected' : ''; ?>>Seeker</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="manage_users.php" class="btn btn-secondary" style="margin-top: 0.5rem;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="users-table">
            <div class="table-header">
                <h3>User List (<?php echo count($users); ?> users)</h3>
            </div>
            <div class="table-container">
                <?php if (count($users) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Blood Group</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                <div style="color: #666; font-size: 0.9rem;">@<?php echo htmlspecialchars($user['username']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($user['email']); ?></div>
                                        <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['blood_group']): ?>
                                            <span style="font-weight: bold; color: #c00;"><?php echo $user['blood_group']; ?></span>
                                        <?php else: ?>
                                            <span style="color: #666;">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($user['status'] == 'active'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Deactivate this user?')">
                                                        <i class="fas fa-pause"></i> Deactivate
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="activate">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Activate this user?')">
                                                        <i class="fas fa-play"></i> Activate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user permanently?')">
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
                        <i class="fas fa-users-slash"></i>
                        <h3>No Users Found</h3>
                        <p>No users match your current filters.</p>
                        <a href="manage_users.php" class="btn btn-primary">Clear Filters</a>
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