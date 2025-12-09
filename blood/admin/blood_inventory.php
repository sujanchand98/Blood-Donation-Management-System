<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();





// Handle inventory actions
$message = '';
$error = '';

if (isset($_POST['action'])) {
    $blood_group = $_POST['blood_group'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    
    switch ($_POST['action']) {
        case 'update':
            if ($quantity >= 0) {
                // Check if record exists
                $check_stmt = $db->prepare("SELECT id FROM blood_inventory WHERE blood_group = ?");
                $check_stmt->execute([$blood_group]);
                
                if ($check_stmt->rowCount() > 0) {
                    // Update existing record
                    $stmt = $db->prepare("UPDATE blood_inventory SET quantity_ml = ? WHERE blood_group = ?");
                    if ($stmt->execute([$quantity, $blood_group])) {
                        $message = "Inventory updated successfully for $blood_group";
                    } else {
                        $error = "Failed to update inventory";
                    }
                } else {
                    // Insert new record
                    $stmt = $db->prepare("INSERT INTO blood_inventory (blood_group, quantity_ml) VALUES (?, ?)");
                    if ($stmt->execute([$blood_group, $quantity])) {
                        $message = "Inventory record created for $blood_group";
                    } else {
                        $error = "Failed to create inventory record";
                    }
                }
            } else {
                $error = "Quantity cannot be negative";
            }
            break;
            
        case 'add_stock':
            $add_quantity = $_POST['add_quantity'] ?? 0;
            if ($add_quantity > 0) {
                $check_stmt = $db->prepare("SELECT quantity_ml FROM blood_inventory WHERE blood_group = ?");
                $check_stmt->execute([$blood_group]);
                
                if ($check_stmt->rowCount() > 0) {
                    $current = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    $new_quantity = $current['quantity_ml'] + $add_quantity;
                    
                    $stmt = $db->prepare("UPDATE blood_inventory SET quantity_ml = ? WHERE blood_group = ?");
                    if ($stmt->execute([$new_quantity, $blood_group])) {
                        $message = "Added $add_quantity ml to $blood_group inventory";
                    } else {
                        $error = "Failed to add stock";
                    }
                } else {
                    $stmt = $db->prepare("INSERT INTO blood_inventory (blood_group, quantity_ml) VALUES (?, ?)");
                    if ($stmt->execute([$blood_group, $add_quantity])) {
                        $message = "Created inventory with $add_quantity ml for $blood_group";
                    } else {
                        $error = "Failed to create inventory record";
                    }
                }
            } else {
                $error = "Add quantity must be positive";
            }
            break;
            
        case 'remove_stock':
            $remove_quantity = $_POST['remove_quantity'] ?? 0;
            if ($remove_quantity > 0) {
                $check_stmt = $db->prepare("SELECT quantity_ml FROM blood_inventory WHERE blood_group = ?");
                $check_stmt->execute([$blood_group]);
                
                if ($check_stmt->rowCount() > 0) {
                    $current = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($current['quantity_ml'] >= $remove_quantity) {
                        $new_quantity = $current['quantity_ml'] - $remove_quantity;
                        
                        $stmt = $db->prepare("UPDATE blood_inventory SET quantity_ml = ? WHERE blood_group = ?");
                        if ($stmt->execute([$new_quantity, $blood_group])) {
                            $message = "Removed $remove_quantity ml from $blood_group inventory";
                        } else {
                            $error = "Failed to remove stock";
                        }
                    } else {
                        $error = "Insufficient stock to remove $remove_quantity ml from $blood_group";
                    }
                } else {
                    $error = "No inventory found for $blood_group";
                }
            } else {
                $error = "Remove quantity must be positive";
            }
            break;
            
        case 'reset':
            $stmt = $db->prepare("UPDATE blood_inventory SET quantity_ml = 0 WHERE blood_group = ?");
            if ($stmt->execute([$blood_group])) {
                $message = "Inventory reset to 0 for $blood_group";
            } else {
                $error = "Failed to reset inventory";
            }
            break;
    }
}

// Get inventory data
$inventory_query = "SELECT * FROM blood_inventory ORDER BY 
    CASE blood_group 
        WHEN 'A+' THEN 1
        WHEN 'A-' THEN 2
        WHEN 'B+' THEN 3
        WHEN 'B-' THEN 4
        WHEN 'AB+' THEN 5
        WHEN 'AB-' THEN 6
        WHEN 'O+' THEN 7
        WHEN 'O-' THEN 8
    END";
$inventory_stmt = $db->prepare($inventory_query);
$inventory_stmt->execute();
$inventory = $inventory_stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure all blood groups exist in inventory
$all_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$current_groups = array_column($inventory, 'blood_group');
$missing_groups = array_diff($all_blood_groups, $current_groups);

// Insert missing blood groups with 0 quantity
foreach ($missing_groups as $blood_group) {
    $insert_stmt = $db->prepare("INSERT INTO blood_inventory (blood_group, quantity_ml) VALUES (?, 0)");
    $insert_stmt->execute([$blood_group]);
}

// Re-fetch inventory after ensuring all groups exist
$inventory_stmt->execute();
$inventory = $inventory_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get inventory statistics
$stats_query = "SELECT 
    SUM(quantity_ml) as total_blood,
    AVG(quantity_ml) as avg_stock,
    COUNT(*) as total_groups,
    COUNT(CASE WHEN quantity_ml > 0 THEN 1 END) as available_groups,
    COUNT(CASE WHEN quantity_ml < 500 THEN 1 END) as low_stock_groups,
    COUNT(CASE WHEN quantity_ml = 0 THEN 1 END) as out_of_stock_groups
    FROM blood_inventory";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$inventory_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent donations for stock updates
$recent_donations_query = "SELECT d.blood_group, d.quantity_ml, d.donation_date, u.full_name 
                          FROM donations d 
                          JOIN users u ON d.donor_id = u.id 
                          WHERE d.status = 'approved' 
                          ORDER BY d.donation_date DESC 
                          LIMIT 5";
$recent_donations_stmt = $db->prepare($recent_donations_query);
$recent_donations_stmt->execute();
$recent_donations = $recent_donations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending requests that affect inventory
$pending_requests_query = "SELECT blood_group, SUM(quantity_ml) as total_requested 
                          FROM blood_requests 
                          WHERE status IN ('pending', 'approved') 
                          GROUP BY blood_group";
$pending_requests_stmt = $db->prepare($pending_requests_query);
$pending_requests_stmt->execute();
$pending_requests = $pending_requests_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Inventory - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Inventory specific styles */
        .inventory-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .inventory-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }
        
        .blood-group-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .blood-group-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .blood-group-card.critical {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        }
        
        .blood-group-card.low {
            border-color: #fd7e14;
            background: linear-gradient(135deg, #fff4e6 0%, #ffe8cc 100%);
        }
        
        .blood-group-card.adequate {
            border-color: #28a745;
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
        }
        
        .blood-group-card.good {
            border-color: #007bff;
            background: linear-gradient(135deg, #f0f8ff 0%, #cce7ff 100%);
        }
        
        .blood-group {
            font-size: 2rem;
            font-weight: bold;
            color: #c00;
            margin-bottom: 1rem;
        }
        
        .quantity-display {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .quantity-unit {
            font-size: 1rem;
            color: #666;
            font-weight: normal;
        }
        
        .stock-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .status-critical { background: #dc3545; color: white; }
        .status-low { background: #fd7e14; color: white; }
        .status-adequate { background: #28a745; color: white; }
        .status-good { background: #007bff; color: white; }
        
        .inventory-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
        }
        
        .action-btn.primary { background: #007bff; color: white; }
        .action-btn.success { background: #28a745; color: white; }
        .action-btn.warning { background: #ffc107; color: #212529; }
        .action-btn.danger { background: #dc3545; color: white; }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
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
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .close-btn:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        .recent-activity {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007bff;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: #333;
        }
        
        .activity-meta {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Include all other common styles from previous pages */
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

        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 1rem;
            }
            
            .menu-links {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .inventory-grid {
                grid-template-columns: 1fr;
            }
            
            .inventory-actions {
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
                <a href="manage_donations.php"><i class="fas fa-hand-holding-medical"></i> Manage Donations</a>
                <a href="manage_blood_requests.php"><i class="fas fa-blood"></i> Blood Requests</a>
                <a href="blood_inventory.php" class="active"><i class="fas fa-warehouse"></i> Inventory</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-warehouse"></i> Blood Inventory Management</h1>
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

        <!-- Inventory Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $inventory_stats['total_blood'] ?? '0'; ?> ml</div>
                <div class="stat-label">Total Blood Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $inventory_stats['available_groups'] ?? '0'; ?>/8</div>
                <div class="stat-label">Available Blood Groups</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $inventory_stats['low_stock_groups'] ?? '0'; ?></div>
                <div class="stat-label">Low Stock Groups</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $inventory_stats['out_of_stock_groups'] ?? '0'; ?></div>
                <div class="stat-label">Out of Stock</div>
            </div>
        </div>

        <!-- Blood Inventory Grid -->
        <div class="inventory-card">
            <div class="inventory-header">
                <h3><i class="fas fa-vial"></i> Blood Group Inventory</h3>
                <p style="color: #666; margin-top: 0.5rem;">Manage blood stock levels for all blood groups</p>
            </div>
            <div class="inventory-grid">
                <?php foreach ($inventory as $item): 
                    $quantity = $item['quantity_ml'];
                    
                    // Determine stock status
                    if ($quantity == 0) {
                        $status = 'critical';
                        $status_text = 'Out of Stock';
                        $card_class = 'critical';
                    } elseif ($quantity < 500) {
                        $status = 'low';
                        $status_text = 'Low Stock';
                        $card_class = 'low';
                    } elseif ($quantity < 1000) {
                        $status = 'adequate';
                        $status_text = 'Adequate';
                        $card_class = 'adequate';
                    } else {
                        $status = 'good';
                        $status_text = 'Good Stock';
                        $card_class = 'good';
                    }
                    
                    // Calculate pending requests for this blood group
                    $pending_for_group = 0;
                    foreach ($pending_requests as $request) {
                        if ($request['blood_group'] == $item['blood_group']) {
                            $pending_for_group = $request['total_requested'];
                            break;
                        }
                    }
                ?>
                    <div class="blood-group-card <?php echo $card_class; ?>">
                        <div class="blood-group"><?php echo $item['blood_group']; ?></div>
                        <div class="quantity-display">
                            <?php echo number_format($quantity); ?> <span class="quantity-unit">ml</span>
                        </div>
                        <div class="stock-status status-<?php echo $status; ?>">
                            <?php echo $status_text; ?>
                        </div>
                        
                        <?php if ($pending_for_group > 0): ?>
                            <div style="color: #666; font-size: 0.8rem; margin-bottom: 1rem;">
                                <i class="fas fa-clock"></i> <?php echo $pending_for_group; ?>ml pending requests
                            </div>
                        <?php endif; ?>
                        
                        <div class="inventory-actions">
                            <button class="action-btn primary" onclick="openAddStockModal('<?php echo $item['blood_group']; ?>')">
                                <i class="fas fa-plus"></i> Add
                            </button>
                            <button class="action-btn warning" onclick="openRemoveStockModal('<?php echo $item['blood_group']; ?>', <?php echo $quantity; ?>)">
                                <i class="fas fa-minus"></i> Remove
                            </button>
                            <button class="action-btn danger" onclick="resetInventory('<?php echo $item['blood_group']; ?>')">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="recent-activity">
            <h3><i class="fas fa-history"></i> Recent Stock Updates</h3>
            <div class="activity-list">
                <?php if (count($recent_donations) > 0): ?>
                    <?php foreach ($recent_donations as $donation): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-tint"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">
                                    Donation: <?php echo $donation['blood_group']; ?> (+<?php echo $donation['quantity_ml']; ?>ml)
                                </div>
                                <div class="activity-meta">
                                    By <?php echo htmlspecialchars($donation['full_name']); ?> • 
                                    <?php echo date('M j, Y', strtotime($donation['donation_date'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fas fa-history" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No recent stock updates</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Stock Modal -->
    <div id="addStockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Stock</h3>
                <button class="close-btn" onclick="closeAddStockModal()">&times;</button>
            </div>
            <form method="POST" id="addStockForm">
                <input type="hidden" name="action" value="add_stock">
                <input type="hidden" name="blood_group" id="add_blood_group">
                
                <div class="form-group">
                    <label for="add_quantity">Quantity to Add (ml)</label>
                    <input type="number" id="add_quantity" name="add_quantity" required min="1" max="10000" placeholder="Enter quantity in ml">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddStockModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Stock</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Remove Stock Modal -->
    <div id="removeStockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Remove Stock</h3>
                <button class="close-btn" onclick="closeRemoveStockModal()">&times;</button>
            </div>
            <form method="POST" id="removeStockForm">
                <input type="hidden" name="action" value="remove_stock">
                <input type="hidden" name="blood_group" id="remove_blood_group">
                
                <div class="form-group">
                    <label for="remove_quantity">Quantity to Remove (ml)</label>
                    <input type="number" id="remove_quantity" name="remove_quantity" required min="1" max="10000" placeholder="Enter quantity in ml">
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Current stock: <span id="current_stock">0</span> ml
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRemoveStockModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Remove Stock</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddStockModal(bloodGroup) {
            document.getElementById('add_blood_group').value = bloodGroup;
            document.getElementById('addStockModal').style.display = 'block';
            document.getElementById('add_quantity').focus();
        }
        
        function closeAddStockModal() {
            document.getElementById('addStockModal').style.display = 'none';
            document.getElementById('addStockForm').reset();
        }
        
        function openRemoveStockModal(bloodGroup, currentStock) {
            document.getElementById('remove_blood_group').value = bloodGroup;
            document.getElementById('current_stock').textContent = currentStock;
            document.getElementById('remove_quantity').setAttribute('max', currentStock);
            document.getElementById('removeStockModal').style.display = 'block';
            document.getElementById('remove_quantity').focus();
        }
        
        function closeRemoveStockModal() {
            document.getElementById('removeStockModal').style.display = 'none';
            document.getElementById('removeStockForm').reset();
        }
        
        function resetInventory(bloodGroup) {
            if (confirm(`Are you sure you want to reset ${bloodGroup} inventory to 0?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="blood_group" value="${bloodGroup}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addStockModal');
            const removeModal = document.getElementById('removeStockModal');
            
            if (event.target == addModal) {
                closeAddStockModal();
            }
            if (event.target == removeModal) {
                closeRemoveStockModal();
            }
        }
        
        // Add keyboard shortcut to close modals with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAddStockModal();
                closeRemoveStockModal();
            }
        });
        
        // Form validation
        document.getElementById('addStockForm').addEventListener('submit', function(e) {
            const quantity = document.getElementById('add_quantity').value;
            if (quantity < 1) {
                e.preventDefault();
                alert('Please enter a valid quantity (minimum 1 ml)');
            }
        });
        
        document.getElementById('removeStockForm').addEventListener('submit', function(e) {
            const quantity = document.getElementById('remove_quantity').value;
            const max = document.getElementById('remove_quantity').getAttribute('max');
            
            if (quantity < 1) {
                e.preventDefault();
                alert('Please enter a valid quantity (minimum 1 ml)');
            } else if (quantity > max) {
                e.preventDefault();
                alert(`Cannot remove more than ${max} ml (current stock)`);
            }
        });
    </script>
</body>
</html>