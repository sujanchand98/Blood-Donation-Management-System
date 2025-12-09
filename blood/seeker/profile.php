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

// Get user data
$user_query = "SELECT * FROM users WHERE id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(":user_id", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get seeker preferences if they exist
$pref_query = "SELECT * FROM seeker_preferences WHERE user_id = :user_id";
$pref_stmt = $db->prepare($pref_query);
$pref_stmt->bindParam(":user_id", $user_id);

$preferences = $pref_stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $date_of_birth = $_POST['date_of_birth'];
        
        $update_query = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone, address = :address, date_of_birth = :date_of_birth WHERE id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":full_name", $full_name);
        $update_stmt->bindParam(":email", $email);
        $update_stmt->bindParam(":phone", $phone);
        $update_stmt->bindParam(":address", $address);
        $update_stmt->bindParam(":date_of_birth", $date_of_birth);
        $update_stmt->bindParam(":user_id", $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully!";
            $_SESSION['full_name'] = $full_name;
            header("Location: profile.php");
            exit;
        } else {
            $_SESSION['error'] = "Error updating profile. Please try again.";
        }
    }
    
    if (isset($_POST['update_preferences'])) {
        $preferred_blood_group = $_POST['preferred_blood_group'];
        $notification_email = isset($_POST['notification_email']) ? 1 : 0;
        $notification_sms = isset($_POST['notification_sms']) ? 1 : 0;
        $emergency_contact = $_POST['emergency_contact'];
        
        if ($preferences) {
            // Update existing preferences
            $pref_update = "UPDATE seeker_preferences SET preferred_blood_group = :blood_group, notification_email = :notif_email, notification_sms = :notif_sms, emergency_contact = :emergency_contact WHERE user_id = :user_id";
        } else {
            // Insert new preferences
            $pref_update = "INSERT INTO seeker_preferences (user_id, preferred_blood_group, notification_email, notification_sms, emergency_contact) VALUES (:user_id, :blood_group, :notif_email, :notif_sms, :emergency_contact)";
        }
        
        $pref_stmt = $db->prepare($pref_update);
        $pref_stmt->bindParam(":blood_group", $preferred_blood_group);
        $pref_stmt->bindParam(":notif_email", $notification_email);
        $pref_stmt->bindParam(":notif_sms", $notification_sms);
        $pref_stmt->bindParam(":emergency_contact", $emergency_contact);
        if (!$preferences) {
            $pref_stmt->bindParam(":user_id", $user_id);
        }
        
       
            $_SESSION['success'] = "Preferences updated successfully!";
            header("Location: profile.php");
            exit;
        } else {
            $_SESSION['error'] = "Error updating preferences. Please try again.";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (password_verify($current_password, $user_data['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $pass_update = "UPDATE users SET password = :password WHERE id = :user_id";
                    $pass_stmt = $db->prepare($pass_update);
                    $pass_stmt->bindParam(":password", $hashed_password);
                    $pass_stmt->bindParam(":user_id", $user_id);
                    
                    if ($pass_stmt->execute()) {
                        $_SESSION['success'] = "Password changed successfully!";
                    } else {
                        $_SESSION['error'] = "Error changing password. Please try again.";
                    }
                } else {
                    $_SESSION['error'] = "New password must be at least 6 characters long.";
                }
            } else {
                $_SESSION['error'] = "New passwords do not match.";
            }
        } else {
            $_SESSION['error'] = "Current password is incorrect.";
        }
        header("Location: profile.php");
        exit;
    }

// Refresh user data after updates
$user_stmt->execute();
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

$preferences = $pref_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BloodLife</title>
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

        nav a:hover, nav a.active {
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

        /* Profile Header */
        .profile-header {
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
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-info h3 {
            font-size: 18px;
            font-weight: 500;
        }

        .profile-info p {
            font-size: 14px;
            color: var(--gray);
        }

        /* Profile Content */
        .profile-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .card-header i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .card-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--secondary);
        }

        .card-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-item input {
            width: auto;
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.3);
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 1.5rem;
        }

        .stat-item {
            background: var(--light);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            .profile-content {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
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
                        <li><a href="dashboard.php">Dashboard</a></li>
                    </ul>
                </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="welcome-section">
                <h1>My Profile</h1>
                <p>Manage your personal information and preferences</p>
            </div>
            <div class="user-profile">
                <div class="profile-avatar">
                    <?php 
                    $full_name = $user_data['full_name'] ?? 'User';
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

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Profile Content -->
        <div class="profile-content">
            <!-- Personal Information -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-user-circle"></i>
                    <h2>Personal Information</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <div class="input-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <div class="input-group">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <div class="input-group">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <div class="input-group">
                                    <i class="fas fa-calendar"></i>
                                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user_data['date_of_birth'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <div class="input-group">
                                <i class="fas fa-map-marker-alt"></i>
                                <textarea id="address" name="address"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn submit-btn">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
            

            <!-- Account Stats -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i>
                    <h2>Account Statistics</h2>
                </div>
                <div class="card-body">
                    <div class="profile-stats">
                        <?php
                        // Get stats for the user
                        $total_requests = $db->prepare("SELECT COUNT(*) FROM blood_requests WHERE seeker_id = ?");
                        $total_requests->execute([$user_id]);
                        $total_count = $total_requests->fetchColumn();
                        
                        $pending_requests = $db->prepare("SELECT COUNT(*) FROM blood_requests WHERE seeker_id = ? AND status = 'pending'");
                        $pending_requests->execute([$user_id]);
                        $pending_count = $pending_requests->fetchColumn();
                        
                        $approved_requests = $db->prepare("SELECT COUNT(*) FROM blood_requests WHERE seeker_id = ? AND status = 'approved'");
                        $approved_requests->execute([$user_id]);
                        $approved_count = $approved_requests->fetchColumn();
                        
                        $fulfilled_requests = $db->prepare("SELECT COUNT(*) FROM blood_requests WHERE seeker_id = ? AND status = 'fulfilled'");
                        $fulfilled_requests->execute([$user_id]);
                        $fulfilled_count = $fulfilled_requests->fetchColumn();
                        ?>
                        
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_count; ?></div>
                            <div class="stat-label">Total Requests</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $pending_count; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $approved_count; ?></div>
                            <div class="stat-label">Approved</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $fulfilled_count; ?></div>
                            <div class="stat-label">Fulfilled</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--light-gray);">
                        <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user_data['created_at'] ?? 'now')); ?></p>
                        <p><strong>Last Login:</strong> <?php echo date('F j, Y g:i A', strtotime($user_data['last_login'] ?? 'now')); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-lock"></i>
                    <h2>Change Password</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="input-group">
                                <i class="fas fa-key"></i>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn submit-btn">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script>
        // Simple animation for profile cards on page load
        document.addEventListener('DOMContentLoaded', function() {
            const profileCards = document.querySelectorAll('.profile-card');
            profileCards.forEach((card, index) => {
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