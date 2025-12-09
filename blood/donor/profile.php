<?php
// session_start();
include '../config/database.php';
include '../includes/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'donor') {
    header("Location: ../login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

$success = '';
$error = '';

// Get current user data
$user_query = "SELECT * FROM users WHERE id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(":user_id", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

if ($_POST) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $blood_group = $_POST['blood_group'];
    $date_of_birth = $_POST['date_of_birth'];
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error = "Please fill in all required fields";
    } else {
        // Check if email already exists (excluding current user)
        $email_check_query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
        $email_check_stmt = $db->prepare($email_check_query);
        $email_check_stmt->bindParam(":email", $email);
        $email_check_stmt->bindParam(":user_id", $user_id);
        $email_check_stmt->execute();
        
        if ($email_check_stmt->rowCount() > 0) {
            $error = "Email already exists";
        } else {
            // Age validation for donors
            if ($date_of_birth) {
                $min_donor_age = 18;
                $max_donor_age = 65;
                $birth_date = new DateTime($date_of_birth);
                $today = new DateTime();
                $age = $today->diff($birth_date)->y;
                
                if ($age < $min_donor_age) {
                    $error = "You must be at least 18 years old to be a donor";
                } elseif ($age > $max_donor_age) {
                    $error = "Donors must be under 65 years old";
                }
            }
            
            if (empty($error)) {
                // Update user data
                $update_query = "UPDATE users SET 
                                full_name = :full_name,
                                email = :email,
                                phone = :phone,
                                address = :address,
                                blood_group = :blood_group,
                                date_of_birth = :date_of_birth
                                WHERE id = :user_id";
                
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(":full_name", $full_name);
                $update_stmt->bindParam(":email", $email);
                $update_stmt->bindParam(":phone", $phone);
                $update_stmt->bindParam(":address", $address);
                $update_stmt->bindParam(":blood_group", $blood_group);
                $update_stmt->bindParam(":date_of_birth", $date_of_birth);
                $update_stmt->bindParam(":user_id", $user_id);
                
                if ($update_stmt->execute()) {
                    $success = "Profile updated successfully!";
                    // Update session data
                    $_SESSION['full_name'] = $full_name;
                    // Refresh user data
                    $user_stmt->execute();
                    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Failed to update profile. Please try again.";
                }
            }
        }
    }
}

// Get donation eligibility info
$eligibility_info = [];
if ($user_data['date_of_birth']) {
    $birth_date = new DateTime($user_data['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($birth_date)->y;
    
    $eligibility_info['age'] = $age;
    $eligibility_info['age_eligible'] = $age >= 18 && $age <= 65;
    
    // Check last donation date
    $last_donation_query = "SELECT donation_date FROM donations 
                           WHERE donor_id = :user_id AND status = 'approved' 
                           ORDER BY donation_date DESC LIMIT 1";
    $last_donation_stmt = $db->prepare($last_donation_query);
    $last_donation_stmt->bindParam(":user_id", $user_id);
    $last_donation_stmt->execute();
    $last_donation = $last_donation_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_donation) {
        $last_donation_date = new DateTime($last_donation['donation_date']);
        $next_eligible_date = $last_donation_date->modify('+3 months');
        $eligibility_info['next_eligible'] = $next_eligible_date;
        $eligibility_info['can_donate'] = $today >= $next_eligible_date;
    } else {
        $eligibility_info['next_eligible'] = null;
        $eligibility_info['can_donate'] = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Blood Donation System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .profile-card, .eligibility-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #c00, #f00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .profile-info h2 {
            margin: 0;
            color: #333;
        }
        
        .profile-info p {
            margin: 0.25rem 0;
            color: #666;
        }
        
        .blood-group-badge {
            background: #c00;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .eligibility-status {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .eligible {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .not-eligible {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: bold;
            color: #333;
        }
        
        .donor-badges {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }
        
        .badge {
            background: #e7f3ff;
            color: #0066cc;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    
    
    <div class="container">
        <h1>My Donor Profile</h1>
        <p>Manage your personal information and donation preferences</p>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-container">
            <!-- Profile Information -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user_data['full_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user_data['full_name']); ?></h2>
                        <p>Blood Donor</p>
                        <div class="blood-group-badge">
                            <?php echo $user_data['blood_group'] ?? 'Not Specified'; ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Blood Group</label>
                            <select name="blood_group" required>
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo ($user_data['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo ($user_data['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo ($user_data['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo ($user_data['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo ($user_data['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo ($user_data['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo ($user_data['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo ($user_data['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?php echo $user_data['date_of_birth'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="3"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Update Profile</button>
                </form>
            </div>
            
            <!-- Donation Eligibility -->
            <div class="eligibility-card">
                <h2>Donation Eligibility</h2>
                
                <?php if (!empty($eligibility_info)): ?>
                    <div class="eligibility-status <?php echo ($eligibility_info['age_eligible'] && $eligibility_info['can_donate']) ? 'eligible' : 'not-eligible'; ?>">
                        <h3>
                            <?php if ($eligibility_info['age_eligible'] && $eligibility_info['can_donate']): ?>
                                ✅ Eligible to Donate
                            <?php else: ?>
                                ❌ Not Eligible Currently
                            <?php endif; ?>
                        </h3>
                        <p>
                            <?php if (!$eligibility_info['age_eligible']): ?>
                                Age requirement not met (Must be 18-65 years)
                            <?php elseif (!$eligibility_info['can_donate']): ?>
                                Next eligible: <?php echo $eligibility_info['next_eligible']->format('M j, Y'); ?>
                            <?php else: ?>
                                You meet all eligibility criteria for blood donation
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Age</div>
                        <div class="info-value">
                            <?php echo $eligibility_info['age'] ?? 'Not specified'; ?> years
                            <?php if (isset($eligibility_info['age_eligible'])): ?>
                                <br><small style="color: <?php echo $eligibility_info['age_eligible'] ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo $eligibility_info['age_eligible'] ? '✅ Eligible' : '❌ Not eligible'; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Blood Group</div>
                        <div class="info-value"><?php echo $user_data['blood_group'] ?? 'Not specified'; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Last Donation</div>
                        <div class="info-value">
                            <?php 
                            if (isset($eligibility_info['next_eligible'])) {
                                echo $eligibility_info['next_eligible'] ? 'Within 3 months' : 'Never donated';
                            } else {
                                echo 'Not available';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <?php if (isset($eligibility_info['can_donate']) && $eligibility_info['can_donate']): ?>
                                <span style="color: #28a745;">Active Donor</span>
                            <?php else: ?>
                                <span style="color: #dc3545;">Temporarily Ineligible</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Donor Badges -->
                <h3>Your Donor Badges</h3>
                <div class="donor-badges">
                    <div class="badge">🩸 First Time Donor</div>
                    <div class="badge">💪 Regular Donor</div>
                    <div class="badge">🎯 Blood Group Hero</div>
                    <?php if ($user_data['blood_group'] == 'O-'): ?>
                        <div class="badge">🌟 Universal Donor</div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Stats -->
                <div class="info-grid">
                    <?php
                    // Get quick stats
                    $quick_stats_query = "SELECT 
                                         COUNT(*) as total_donations,
                                         COALESCE(SUM(quantity_ml), 0) as total_ml
                                         FROM donations 
                                         WHERE donor_id = :user_id AND status = 'approved'";
                    $quick_stats_stmt = $db->prepare($quick_stats_query);
                    $quick_stats_stmt->bindParam(":user_id", $user_id);
                    $quick_stats_stmt->execute();
                    $quick_stats = $quick_stats_stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="info-item" style="text-align: center;">
                        <div class="info-label">Total Donations</div>
                        <div class="info-value" style="font-size: 1.5rem; color: #c00;">
                            <?php echo $quick_stats['total_donations']; ?>
                        </div>
                    </div>
                    
                    <div class="info-item" style="text-align: center;">
                        <div class="info-label">Blood Donated</div>
                        <div class="info-value" style="font-size: 1.5rem; color: #c00;">
                            <?php echo $quick_stats['total_ml']; ?> ml
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div style="margin-top: 2rem;">
                    <a href="donation_history.php" class="btn" style="width: 100%; text-align: center; margin-bottom: 0.5rem;">
                        View Donation History
                    </a>
                   
                </div>
            </div>
        </div>
        
       
    
    
</body>
</html>