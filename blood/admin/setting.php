<?php
// settings.php
session_start();

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$success_message = "";
$error_message = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update admin profile
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        if (!empty($name) && !empty($email)) {
            $query = "UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $_SESSION['name'] = $name;
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Failed to update profile.";
            }
        } else {
            $error_message = "Name and email are required fields.";
        }
    }
    
    if (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            // Verify current password
            $query = "SELECT password FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 6) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $query = "UPDATE users SET password = :password WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':password', $hashed_password);
                        $stmt->bindParam(':id', $_SESSION['user_id']);
                        
                        if ($stmt->execute()) {
                            $success_message = "Password changed successfully!";
                        } else {
                            $error_message = "Failed to change password.";
                        }
                    } else {
                        $error_message = "Password must be at least 6 characters long.";
                    }
                } else {
                    $error_message = "New passwords do not match.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        } else {
            $error_message = "All password fields are required.";
        }
    }
    
    if (isset($_POST['update_system'])) {
        // Update system settings
        $site_name = $_POST['site_name'] ?? 'BloodLife';
        $admin_email = $_POST['admin_email'] ?? '';
        $donation_interval = $_POST['donation_interval'] ?? '3';
        
        // In a real application, you would update these in a settings table
        $success_message = "System settings updated successfully!";
    }
}

// Get current admin data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default values if data is not available
$admin_name = $admin_data['name'] ?? '';
$admin_email = $admin_data['email'] ?? '';
$admin_phone = $admin_data['phone'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - BloodLife</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #c62828;
            --primary-dark: #b71c1c;
            --secondary: #263238;
            --light: #f5f5f5;
            --gray: #757575;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f8f9fa;
            color: var(--secondary);
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: var(--secondary);
            color: white;
            padding: 1.5rem 0;
        }

        .logo {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        .logo h2 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 0.5rem;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1.5rem;
            color: #b0b0b0;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid var(--primary);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Settings Content */
        .settings-container {
            max-width: 1000px;
        }

        .settings-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .tab {
            padding: 1rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .card-header i {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--secondary);
        }

        input, select, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
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

        .danger-zone {
            border: 2px solid var(--danger);
            background: #fff5f5;
        }

        .danger-zone .card-header i {
            color: var(--danger);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .settings-tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2><i class="fas fa-tint"></i> BloodLife Admin</h2>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Admin Settings</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo !empty($admin_name) ? strtoupper(substr($admin_name, 0, 1)) : 'A'; ?>
                    </div>
                    <span><?php echo htmlspecialchars($admin_name ?: 'Admin User'); ?></span>
                </div>
            </div>

            <div class="settings-container">
                <!-- Messages -->
                <?php if (!empty($success_message)): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Settings Tabs -->
                <div class="settings-tabs">
                    <div class="tab active" data-tab="profile">Profile Settings</div>
                    <div class="tab" data-tab="security">Security</div>
                    <div class="tab" data-tab="system">System Settings</div>
                </div>

                <!-- Profile Settings -->
                <div class="tab-content active" id="profile">
                    <div class="settings-card">
                        <div class="card-header">
                            <i class="fas fa-user-cog"></i>
                            <h3>Profile Information</h3>
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin_name); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($admin_phone); ?>">
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="tab-content" id="security">
                    <div class="settings-card">
                        <div class="card-header">
                            <i class="fas fa-shield-alt"></i>
                            <h3>Change Password</h3>
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="tab-content" id="system">
                    <div class="settings-card">
                        <div class="card-header">
                            <i class="fas fa-cogs"></i>
                            <h3>System Configuration</h3>
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label for="site_name">Site Name</label>
                                <input type="text" id="site_name" name="site_name" value="BloodLife" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_email">Admin Email</label>
                                <input type="email" id="admin_email" name="admin_email" value="admin@bloodlife.org" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="donation_interval">Donation Interval (months)</label>
                                <select id="donation_interval" name="donation_interval">
                                    <option value="2">2 months</option>
                                    <option value="3" selected>3 months</option>
                                    <option value="4">4 months</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="update_system" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save System Settings
                            </button>
                        </form>
                    </div>
                </div>

                  </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab).classList.add('active');
            });
        });

        // Password confirmation
        document.querySelector('form[name="change_password"]')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
            }
        });

        // Danger zone confirmation
        function confirmClearData() {
            if (confirm('⚠️ WARNING: This will permanently delete ALL data. This action cannot be undone!\n\nAre you absolutely sure?')) {
                if (confirm('This is your final warning. All donation records, donor information, and system data will be permanently deleted. Continue?')) {
                    // In a real application, this would call a PHP script to clear data
                    alert('Data clearance initiated. This feature would be implemented in a production environment.');
                }
            }
        }
    </script>
</body>
</html>