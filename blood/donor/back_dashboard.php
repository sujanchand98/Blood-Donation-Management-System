<?php
session_start();
include '../config/database.php';
include '../includes/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'donor') {
    header("Location: ../login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(":user_id", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get donation eligibility
$last_donation_query = "SELECT donation_date FROM donations WHERE donor_id = :user_id AND status = 'approved' ORDER BY donation_date DESC LIMIT 1";
$last_donation_stmt = $db->prepare($last_donation_query);
$last_donation_stmt->bindParam(":user_id", $user_id);
$last_donation_stmt->execute();
$last_donation = $last_donation_stmt->fetch(PDO::FETCH_ASSOC);

$is_eligible = true;
$eligibility_message = '';
$next_eligible_date = '';

if ($last_donation) {
    $last_donation_date = new DateTime($last_donation['donation_date']);
    $next_eligible = $last_donation_date->modify('+3 months');
    $today = new DateTime();
    
    if ($next_eligible > $today) {
        $is_eligible = false;
        $eligibility_message = "You can donate again after " . $next_eligible->format('F j, Y');
        $next_eligible_date = $next_eligible->format('Y-m-d');
    }
}

// Handle donation form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_donation'])) {
    $blood_group = $_POST['blood_group'];
    $quantity_ml = $_POST['quantity_ml'];
    $donation_date = $_POST['donation_date'];
    $donation_type = $_POST['donation_type'];
    $donation_center = $_POST['donation_center'];
    $health_conditions = $_POST['health_conditions'] ?? '';
    $medications = $_POST['medications'] ?? '';
    $travel_history = $_POST['travel_history'] ?? '';
    $recent_sickness = $_POST['recent_sickness'] ?? 'no';
    $tattoo_piercing = $_POST['tattoo_piercing'] ?? 'no';
    
    // Validate eligibility
    if (!$is_eligible) {
        $error = "You are not yet eligible to donate. " . $eligibility_message;
    } else {
        // Validate inputs
        $validation_errors = [];
        
        if (empty($blood_group)) {
            $validation_errors[] = "Blood group is required";
        }
        
        if (empty($quantity_ml) || $quantity_ml < 350 || $quantity_ml > 500) {
            $validation_errors[] = "Quantity must be between 350ml and 500ml";
        }
        
        if (empty($donation_date)) {
            $validation_errors[] = "Donation date is required";
        } else {
            $donation_date_obj = new DateTime($donation_date);
            $today = new DateTime();
            if ($donation_date_obj > $today) {
                $validation_errors[] = "Donation date cannot be in the future";
            }
        }
        
        if (empty($donation_type)) {
            $validation_errors[] = "Donation type is required";
        }
        
        if (empty($validation_errors)) {
            try {
                // Check if the donations table has the new columns
                $check_columns = $db->query("SHOW COLUMNS FROM donations LIKE 'donation_type'")->fetch();
                
                if ($check_columns) {
                    // Table has new columns - use full query
                    $query = "INSERT INTO donations (donor_id, blood_group, quantity_ml, donation_date, donation_type, donation_center, health_conditions, medications, travel_history, recent_sickness, tattoo_piercing, status) 
                             VALUES (:donor_id, :blood_group, :quantity_ml, :donation_date, :donation_type, :donation_center, :health_conditions, :medications, :travel_history, :recent_sickness, :tattoo_piercing, 'pending')";
                } else {
                    // Table doesn't have new columns - use basic query
                    $query = "INSERT INTO donations (donor_id, blood_group, quantity_ml, donation_date, status) 
                             VALUES (:donor_id, :blood_group, :quantity_ml, :donation_date, 'pending')";
                }
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':donor_id', $user_id);
                $stmt->bindParam(':blood_group', $blood_group);
                $stmt->bindParam(':quantity_ml', $quantity_ml);
                $stmt->bindParam(':donation_date', $donation_date);
                
                if ($check_columns) {
                    $stmt->bindParam(':donation_type', $donation_type);
                    $stmt->bindParam(':donation_center', $donation_center);
                    $stmt->bindParam(':health_conditions', $health_conditions);
                    $stmt->bindParam(':medications', $medications);
                    $stmt->bindParam(':travel_history', $travel_history);
                    $stmt->bindParam(':recent_sickness', $recent_sickness);
                    $stmt->bindParam(':tattoo_piercing', $tattoo_piercing);
                }
                
                if ($stmt->execute()) {
                    $success = "Donation record submitted successfully! It will be reviewed by our team.";
                    // Reset form
                    $_POST = array();
                } else {
                    $error = "Failed to submit donation record. Please try again.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = implode("<br>", $validation_errors);
        }
    }
}

// Get donation centers - check if table exists first
$donation_centers = [];
try {
    $centers_query = "SELECT * FROM donation_centers WHERE status = 'active' ORDER BY name";
    $centers_stmt = $db->prepare($centers_query);
    $centers_stmt->execute();
    $donation_centers = $centers_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If donation_centers table doesn't exist, use default centers
    $donation_centers = [
        ['name' => 'City Blood Bank', 'city' => 'New York'],
        ['name' => 'Community Hospital', 'city' => 'New York'],
        ['name' => 'Red Cross Center', 'city' => 'New York'],
        ['name' => 'University Medical Center', 'city' => 'New York']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate Blood - Blood Donation System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ... (keep all the CSS styles from previous version) ... */
        .page-header {
            background: linear-gradient(135deg, #c00 0%, #a00 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .page-header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .donation-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .donation-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .eligibility-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            height: fit-content;
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #c00;
            box-shadow: 0 0 0 2px rgba(204, 0, 0, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .btn {
            background: #c00;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn:hover {
            background: #a00;
        }
        
        .btn-block {
            width: 100%;
            display: block;
            text-align: center;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
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
        
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .message.info {
            background: #d1edff;
            color: #004085;
            border: 1px solid #b3d7ff;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .form-section h3 {
            color: #c00;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .eligibility-status {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .eligible {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        
        .not-eligible {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }
        
        .eligibility-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .requirements-list {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }
        
        .requirements-list li {
            padding: 0.5rem 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .requirements-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
        }
        
        .requirements-list li.ineligible:before {
            content: "✗";
            color: #dc3545;
        }
        
        .progress-container {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #28a745;
            transition: width 0.3s;
        }
        
        .progress-text {
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .health-question {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .health-question label {
            font-weight: normal;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .radio-group {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .donation-tips {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        .donation-tips h4 {
            margin: 0 0 0.5rem 0;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .donation-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="page-header">
        <div class="page-header-content">
            <h1><i class="fas fa-tint"></i> Donate Blood</h1>
            <p>Your donation can save up to 3 lives. Complete the form below to record your blood donation.</p>
        </div>
    </div>
    
    <div class="donation-container">
        <!-- Main Donation Form -->
        <div class="donation-form">
            <!-- Display Messages -->
            <?php if (isset($success)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Database Structure Warning -->
            <?php 
            try {
                $check_columns = $db->query("SHOW COLUMNS FROM donations LIKE 'donation_type'")->fetch();
                if (!$check_columns): ?>
                    <div class="message warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Note:</strong> Some advanced features are not available. Please run the database update script to enable all features.
                    </div>
                <?php endif;
            } catch (Exception $e) {
                // Silently continue
            }
            ?>
            
            <form method="POST" action="">
                <!-- Donation Details Section -->
                <div class="form-section">
                    <h3><i class="fas fa-tint"></i> Donation Details</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="blood_group">Blood Group *</label>
                            <select name="blood_group" id="blood_group" required>
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo ($user_data['blood_group'] ?? '') == 'A+' ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo ($user_data['blood_group'] ?? '') == 'A-' ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo ($user_data['blood_group'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo ($user_data['blood_group'] ?? '') == 'B-' ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo ($user_data['blood_group'] ?? '') == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo ($user_data['blood_group'] ?? '') == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo ($user_data['blood_group'] ?? '') == 'O+' ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo ($user_data['blood_group'] ?? '') == 'O-' ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity_ml">Quantity Donated (ml) *</label>
                            <input type="number" name="quantity_ml" id="quantity_ml" min="350" max="500" value="450" required>
                            <small style="color: #666;">Standard donation: 450ml (350-500ml range)</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="donation_date">Donation Date *</label>
                            <input type="date" name="donation_date" id="donation_date" max="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="donation_type">Donation Type *</label>
                            <select name="donation_type" id="donation_type" required>
                                <option value="">Select Type</option>
                                <option value="whole_blood">Whole Blood</option>
                                <option value="platelets">Platelets</option>
                                <option value="plasma">Plasma</option>
                                <option value="double_red">Double Red Cells</option>
                                <option value="power_red">Power Red</option>
                            </select>
                        </div>
                    </div>
                    
                     <div class="form-container">
        <h1>Donation Center/Location <span class="required">*</span></h1>
        
        <div class="form-group">
            <label for="donation-center">Select Donation Center</label>
            <select id="donation-center">
                <option value="" disabled selected>Please select a donation center</option>
                <option value="center1">Kathmandu</option>
                <option value="center2">Lalitpur</option>
                <option value="center3">Bhaktapur</option>
                <option value="other">Other Location</option>
            </select>
        </div>
        </div>
                </div>
                
                <!-- Health Screening Section -->
                <div class="form-section">
                    <h3><i class="fas fa-heartbeat"></i> Health Screening</h3>
                    <p style="color: #666; margin-bottom: 1rem;">Please answer these health screening questions honestly.</p>
                    
                    <div class="health-question">
                        <label>Have you had any tattoos or piercings in the last 3 months? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="tattoo_piercing" id="tattoo_no" value="no" required checked>
                                <label for="tattoo_no" style="font-weight: normal;">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="tattoo_piercing" id="tattoo_yes" value="yes">
                                <label for="tattoo_yes" style="font-weight: normal;">Yes</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="health-question">
                        <label>Have you been sick with fever, cold, or flu in the last 2 weeks? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="recent_sickness" id="sickness_no" value="no" required checked>
                                <label for="sickness_no" style="font-weight: normal;">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="recent_sickness" id="sickness_yes" value="yes">
                                <label for="sickness_yes" style="font-weight: normal;">Yes</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="health_conditions">Current Health Conditions</label>
                        <textarea name="health_conditions" id="health_conditions" rows="3" placeholder="Any current health issues, chronic conditions, or recent surgeries..."><?php echo $_POST['health_conditions'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="medications">Current Medications</label>
                        <textarea name="medications" id="medications" rows="2" placeholder="List any medications you're currently taking..."><?php echo $_POST['medications'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="travel_history">Recent Travel History (Last 3 Months)</label>
                        <textarea name="travel_history" id="travel_history" rows="2" placeholder="List any recent travel destinations..."><?php echo $_POST['travel_history'] ?? ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Donation Tips -->
                <div class="donation-tips">
                    <h4><i class="fas fa-lightbulb"></i> Before You Donate</h4>
                    <ul style="margin: 0; padding-left: 1.5rem; color: #856404;">
                        <li>Get a good night's sleep before donating</li>
                        <li>Eat a healthy meal and drink plenty of water</li>
                        <li>Avoid fatty foods before donation</li>
                        <li>Bring a photo ID with you</li>
                    </ul>
                </div>
                
                <!-- Submit Button -->
                <div class="form-group">
                    <button type="submit" name="submit_donation" class="btn btn-success btn-block" <?php echo !$is_eligible ? 'disabled' : ''; ?>>
                        <i class="fas fa-paper-plane"></i> 
                        <?php echo $is_eligible ? 'Submit Donation Record' : 'Not Eligible to Donate'; ?>
                    </button>
                </div>
                
                <p style="text-align: center; color: #666; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> Your donation record will be verified by our team before approval.
                </p>
            </form>
        </div>
        
        <!-- Eligibility Sidebar -->
        <div class="eligibility-card">
            <div class="eligibility-status <?php echo $is_eligible ? 'eligible' : 'not-eligible'; ?>">
                <div class="eligibility-icon">
                    <?php if ($is_eligible): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-times-circle"></i>
                    <?php endif; ?>
                </div>
                <h3 style="margin: 0 0 0.5rem 0;">
                    <?php echo $is_eligible ? 'Eligible to Donate' : 'Not Eligible'; ?>
                </h3>
                <p style="margin: 0;">
                    <?php echo $is_eligible ? 'You meet the requirements for blood donation!' : $eligibility_message; ?>
                </p>
            </div>
            
            <?php if (!$is_eligible && $next_eligible_date): ?>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php 
                            $last_date = new DateTime($last_donation['donation_date']);
                            $next_date = new DateTime($next_eligible_date);
                            $today = new DateTime();
                            $total_days = $last_date->diff($next_date)->days;
                            $days_passed = $last_date->diff($today)->days;
                            echo min(100, ($days_passed / $total_days) * 100);
                        ?>%"></div>
                    </div>
                    <div class="progress-text">
                        Next donation: <?php echo date('M j, Y', strtotime($next_eligible_date)); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <h4><i class="fas fa-clipboard-check"></i> Donor Requirements</h4>
            <ul class="requirements-list">
                <li class="<?php echo (isset($user_data['age']) && $user_data['age'] >= 18 && $user_data['age'] <= 65) ? '' : 'ineligible'; ?>">
                    Age: 18-65 years
                </li>
                <li class="<?php echo (isset($user_data['weight']) && $user_data['weight'] >= 50) ? '' : 'ineligible'; ?>">
                    Weight: ≥50 kg (110 lbs)
                </li>
                <li class="<?php echo $is_eligible ? '' : 'ineligible'; ?>">
                    3+ months since last donation
                </li>
                <li>Good general health</li>
                <li>No flu/cold symptoms</li>
                <li>No recent tattoos/piercings</li>
            </ul>
            
            <div class="message info">
                <h4><i class="fas fa-clock"></i> Donation Timeline</h4>
                <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                    <li><strong>Registration:</strong> 10 minutes</li>
                    <li><strong>Health Screening:</strong> 15 minutes</li>
                    <li><strong>Donation:</strong> 10-15 minutes</li>
                    <li><strong>Recovery:</strong> 15 minutes</li>
                </ul>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <h4><i class="fas fa-mobile-alt"></i> Quick Actions</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="find_drives.php" class="btn" style="background: transparent; color: #c00; border: 1px solid #c00;">
                        <i class="fas fa-map-marker-alt"></i> Find Blood Drives
                    </a>
                    <a href="donation_history.php" class="btn" style="background: transparent; color: #c00; border: 1px solid #c00;">
                        <i class="fas fa-history"></i> View Donation History
                    </a>
                    <a href="profile.php" class="btn" style="background: transparent; color: #c00; border: 1px solid #c00;">
                        <i class="fas fa-user-edit"></i> Update Health Info
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set maximum date to today
        document.getElementById('donation_date').max = new Date().toISOString().split('T')[0];
        
        // Auto-fill blood group from user profile
        const userBloodGroup = "<?php echo $user_data['blood_group'] ?? ''; ?>";
        if (userBloodGroup) {
            document.getElementById('blood_group').value = userBloodGroup;
        }
        
        // Set default date to today
        document.getElementById('donation_date').value = new Date().toISOString().split('T')[0];
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const donationDate = new Date(document.getElementById('donation_date').value);
            const today = new Date();
            
            if (donationDate > today) {
                e.preventDefault();
                alert('Donation date cannot be in the future.');
                return false;
            }
            
            const quantity = parseInt(document.getElementById('quantity_ml').value);
            if (quantity < 350 || quantity > 500) {
                e.preventDefault();
                alert('Please enter a valid quantity between 350ml and 500ml.');
                return false;
            }
            
            // Check health screening questions
            const tattooPiercing = document.querySelector('input[name="tattoo_piercing"]:checked');
            const recentSickness = document.querySelector('input[name="recent_sickness"]:checked');
            
            if (!tattooPiercing || !recentSickness) {
                e.preventDefault();
                alert('Please answer all health screening questions.');
                return false;
            }
            
            if (tattooPiercing.value === 'yes') {
                if (!confirm('Having tattoos or piercings in the last 3 months may affect eligibility. Are you sure you want to continue?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            if (recentSickness.value === 'yes') {
                if (!confirm('Recent sickness may affect eligibility. Are you sure you want to continue?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Show warning for certain conditions
        document.getElementById('health_conditions').addEventListener('input', function() {
            const conditions = this.value.toLowerCase();
            const warningConditions = ['hiv', 'aids', 'hepatitis', 'cancer', 'heart disease'];
            
            for (const condition of warningConditions) {
                if (conditions.includes(condition)) {
                    alert('Please note: Certain medical conditions may affect your eligibility to donate blood. Our team will review your information.');
                    break;
                }
            }
        });
        
        // Real-time quantity indicator
        document.getElementById('quantity_ml').addEventListener('input', function() {
            const quantity = parseInt(this.value);
            let indicator = document.getElementById('quantity_indicator');
            
            if (!indicator) {
                indicator = document.createElement('small');
                indicator.id = 'quantity_indicator';
                indicator.style.color = '#666';
                indicator.style.marginLeft = '0.5rem';
                this.parentNode.appendChild(indicator);
            }
            
            if (quantity < 350) {
                indicator.textContent = '❌ Below minimum';
                indicator.style.color = '#dc3545';
            } else if (quantity > 500) {
                indicator.textContent = '❌ Above maximum';
                indicator.style.color = '#dc3545';
            } else if (quantity === 450) {
                indicator.textContent = '✅ Standard donation';
                indicator.style.color = '#28a745';
            } else {
                indicator.textContent = '⚠️ Non-standard amount';
                indicator.style.color = '#ffc107';
            }
        });
        
        // Trigger quantity indicator on page load
        document.getElementById('quantity_ml').dispatchEvent(new Event('input'));
    </script>
</body>
</html>