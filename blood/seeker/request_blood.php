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

$success = '';
$error = '';

// First, let's check what columns actually exist in the blood_requests table
$check_columns = $db->prepare("SHOW COLUMNS FROM blood_requests");
$check_columns->execute();
$existing_columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);

if ($_POST) {
    // Only use fields that exist in the database
    $blood_group = $_POST['blood_group'];
    $quantity_ml = $_POST['quantity_ml'];
    $urgency_level = $_POST['urgency_level'];
    $hospital_name = $_POST['hospital_name'] ?? '';
    $patient_name = $_POST['patient_name'] ?? '';
    $reason = $_POST['reason'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($blood_group)) {
        $errors[] = "Blood group is required";
    }
    
    if (empty($quantity_ml) || $quantity_ml < 50 || $quantity_ml > 2000) {
        $errors[] = "Quantity must be between 50ml and 2000ml";
    }
    
    if (empty($urgency_level)) {
        $errors[] = "Urgency level is required";
    }
    
    if (count($errors) === 0) {
        // Build query based on available columns
        $columns = [];
        $values = [];
        $params = [];
        
        // Always include these basic fields
        $columns[] = "seeker_id";
        $values[] = ":seeker_id";
        $params[':seeker_id'] = $user_id;
        
        $columns[] = "blood_group";
        $values[] = ":blood_group";
        $params[':blood_group'] = $blood_group;
        
        $columns[] = "quantity_ml";
        $values[] = ":quantity_ml";
        $params[':quantity_ml'] = $quantity_ml;
        
        $columns[] = "urgency_level";
        $values[] = ":urgency_level";
        $params[':urgency_level'] = $urgency_level;
        
        $columns[] = "status";
        $values[] = "'pending'";
        
        // Add optional fields only if they exist in database and have values
        if (in_array('hospital_name', $existing_columns) && !empty($hospital_name)) {
            $columns[] = "hospital_name";
            $values[] = ":hospital_name";
            $params[':hospital_name'] = $hospital_name;
        }
        
        if (in_array('patient_name', $existing_columns) && !empty($patient_name)) {
            $columns[] = "patient_name";
            $values[] = ":patient_name";
            $params[':patient_name'] = $patient_name;
        }
        
        if (in_array('reason', $existing_columns) && !empty($reason)) {
            $columns[] = "reason";
            $values[] = ":reason";
            $params[':reason'] = $reason;
        }
        
        // Insert blood request
        $query = "INSERT INTO blood_requests (" . implode(', ', $columns) . ") 
                  VALUES (" . implode(', ', $values) . ")";
        
        $stmt = $db->prepare($query);
        
        // Bind all parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if ($stmt->execute()) {
            $success = "Blood request submitted successfully! Our team will review it soon.";
            $_POST = array(); // Clear form
        } else {
            $error = "Failed to submit request. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Get user's recent requests for reference
$recent_query = "SELECT blood_group, quantity_ml, urgency_level, status, created_at 
                 FROM blood_requests 
                 WHERE seeker_id = :user_id 
                 ORDER BY created_at DESC LIMIT 3";
$recent_stmt = $db->prepare($recent_query);
$recent_stmt->bindParam(":user_id", $user_id);
$recent_stmt->execute();
$recent_requests = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Blood - BloodLife</title>
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

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .page-title h1 {
            font-size: 28px;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 5px;
        }

        .page-title p {
            color: var(--gray);
            font-size: 16px;
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        /* Request Form */
        .request-form {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-gray);
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .form-section h3 {
            font-size: 18px;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
        }

        .required::after {
            content: " *";
            color: var(--danger);
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
        }

        .form-help {
            font-size: 12px;
            color: var(--gray);
            margin-top: 5px;
        }

        /* Urgency Options */
        .urgency-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .urgency-option {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: white;
        }

        .urgency-option:hover {
            border-color: var(--primary-light);
            transform: translateY(-2px);
        }

        .urgency-option.selected {
            border-color: var(--primary);
            background: #ffe6e6;
            transform: translateY(-2px);
        }

        .urgency-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .urgency-low { border-left: 4px solid var(--success); }
        .urgency-medium { border-left: 4px solid var(--warning); }
        .urgency-high { border-left: 4px solid #fd7e14; }
        .urgency-critical { border-left: 4px solid var(--danger); }

        /* Submit Button */
        .submit-btn {
            background: var(--primary);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.3);
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
        }

        .sidebar-card h3 {
            font-size: 18px;
            color: var(--secondary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .recent-request-item {
            padding: 15px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            margin-bottom: 15px;
            transition: var(--transition);
        }

        .recent-request-item:hover {
            border-color: var(--primary-light);
        }

        .request-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .blood-group {
            font-weight: 700;
            font-size: 16px;
            color: var(--primary);
        }

        .request-quantity {
            font-size: 14px;
            color: var(--gray);
        }

        .request-details {
            font-size: 13px;
            color: var(--gray);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1edff; color: #004085; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-fulfilled { background: #d4edda; color: #155724; }

        .quick-action {
            display: block;
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 10px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .quick-action:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .quick-action.secondary {
            background: var(--gray);
        }

        .quick-action.secondary:hover {
            background: #5a6268;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
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

        /* Responsive */
        @media (max-width: 992px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .urgency-options {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            nav ul {
                gap: 1rem;
            }
        }

        @media (max-width: 576px) {
            .urgency-options {
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
                        <li><a href="request_blood.php" class="active">Request Blood</a></li>
                        <li><a href="find_donor.php">Find Donors</a></li>
                        <li><a href="request_history.php">Request History</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1>Request Blood</h1>
                <p>Submit a blood request for patients in need</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="main-content">
            <!-- Request Form -->
            <div class="request-form">
                <form method="POST" id="bloodRequestForm">
                    <!-- Blood Requirements Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-tint"></i> Blood Requirements</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="blood_group" class="required">Blood Group</label>
                                <div class="input-group">
                                    <i class="fas fa-tint"></i>
                                    <select id="blood_group" name="blood_group" required>
                                        <option value="">Select Blood Group</option>
                                        <option value="A+" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                        <option value="AB+" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                        <option value="O+" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity_ml" class="required">Quantity Required (ml)</label>
                                <div class="input-group">
                                    <i class="fas fa-weight"></i>
                                    <input type="number" id="quantity_ml" name="quantity_ml" 
                                           min="50" max="2000" step="50" 
                                           value="<?php echo isset($_POST['quantity_ml']) ? $_POST['quantity_ml'] : '450'; ?>" 
                                           required>
                                </div>
                                <div class="form-help">Typically 450-500ml per unit. Minimum 50ml, maximum 2000ml</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="required">Urgency Level</label>
                            <div class="urgency-options">
                                <div class="urgency-option urgency-low <?php echo (isset($_POST['urgency_level']) && $_POST['urgency_level'] == 'low') ? 'selected' : ''; ?>" 
                                     onclick="selectUrgency('low')">
                                    <div class="urgency-icon">🟢</div>
                                    <div class="urgency-info">
                                        <strong>Low</strong>
                                        <div class="form-help">1-2 weeks</div>
                                    </div>
                                </div>
                                <div class="urgency-option urgency-medium <?php echo (isset($_POST['urgency_level']) && $_POST['urgency_level'] == 'medium') ? 'selected' : ''; ?>" 
                                     onclick="selectUrgency('medium')">
                                    <div class="urgency-icon">🟡</div>
                                    <div class="urgency-info">
                                        <strong>Medium</strong>
                                        <div class="form-help">3-7 days</div>
                                    </div>
                                </div>
                                <div class="urgency-option urgency-high <?php echo (isset($_POST['urgency_level']) && $_POST['urgency_level'] == 'high') ? 'selected' : ''; ?>" 
                                     onclick="selectUrgency('high')">
                                    <div class="urgency-icon">🟠</div>
                                    <div class="urgency-info">
                                        <strong>High</strong>
                                        <div class="form-help">24-48 hours</div>
                                    </div>
                                </div>
                                <div class="urgency-option urgency-critical <?php echo (isset($_POST['urgency_level']) && $_POST['urgency_level'] == 'critical') ? 'selected' : ''; ?>" 
                                     onclick="selectUrgency('critical')">
                                    <div class="urgency-icon">🔴</div>
                                    <div class="urgency-info">
                                        <strong>Critical</strong>
                                        <div class="form-help">Immediate</div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="urgency_level" id="urgency_level" value="<?php echo isset($_POST['urgency_level']) ? $_POST['urgency_level'] : 'medium'; ?>" required>
                        </div>
                    </div>

                    <!-- Additional Information Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
                        
                        <?php if (in_array('hospital_name', $existing_columns)): ?>
                        <div class="form-group">
                            <label for="hospital_name">Hospital/Organization Name</label>
                            <div class="input-group">
                                <i class="fas fa-hospital"></i>
                                <input type="text" id="hospital_name" name="hospital_name" 
                                       value="<?php echo isset($_POST['hospital_name']) ? htmlspecialchars($_POST['hospital_name']) : ''; ?>" 
                                       placeholder="Name of hospital or medical facility">
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array('patient_name', $existing_columns)): ?>
                        <div class="form-group">
                            <label for="patient_name">Patient Name</label>
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" id="patient_name" name="patient_name" 
                                       value="<?php echo isset($_POST['patient_name']) ? htmlspecialchars($_POST['patient_name']) : ''; ?>" 
                                       placeholder="Name of patient requiring blood">
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array('reason', $existing_columns)): ?>
                        <div class="form-group">
                            <label for="reason">Reason for Request</label>
                            <textarea id="reason" name="reason" 
                                      placeholder="Please provide details about why blood is needed..."><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                            <div class="form-help">This information helps us understand the urgency and context of your request</div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Submit Blood Request
                    </button>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Recent Requests -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-history"></i> Recent Requests</h3>
                    <?php if (count($recent_requests) > 0): ?>
                        <?php foreach ($recent_requests as $request): ?>
                            <div class="recent-request-item">
                                <div class="request-meta">
                                    <span class="blood-group"><?php echo $request['blood_group']; ?></span>
                                    <span class="request-quantity"><?php echo $request['quantity_ml']; ?>ml</span>
                                </div>
                                <div class="request-details">
                                    <div>Urgency: <?php echo ucfirst($request['urgency_level']); ?></div>
                                    <div>Date: <?php echo date('M j', strtotime($request['created_at'])); ?></div>
                                </div>
                                <div style="margin-top: 8px;">
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="request_history.php" class="quick-action secondary">
                            <i class="fas fa-list"></i> View All History
                        </a>
                    <?php else: ?>
                        <p style="color: var(--gray); text-align: center; padding: 20px 0;">
                            No recent requests found.
                        </p>
                        <a href="request_history.php" class="quick-action secondary">
                            <i class="fas fa-history"></i> View Request History
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <a href="find_donor.php" class="quick-action">
                        <i class="fas fa-search"></i> Find Donors
                    </a>
                    <a href="request_history.php" class="quick-action secondary">
                        <i class="fas fa-history"></i> Request History
                    </a>
                    <a href="dashboard.php" class="quick-action secondary">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </div>

                <!-- Help Information -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-info-circle"></i> Need Help?</h3>
                    <p style="color: var(--gray); font-size: 14px; margin-bottom: 15px;">
                        If you need immediate assistance or have an emergency, please call our 24/7 helpline.
                    </p>
                    <div style="background: var(--light); padding: 15px; border-radius: 8px; text-align: center;">
                        <strong style="color: var(--primary);">Emergency Helpline</strong><br>
                        <span style="font-size: 18px; font-weight: 600;">+977 9876543210</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectUrgency(level) {
            document.getElementById('urgency_level').value = level;
            document.querySelectorAll('.urgency-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }
        
        // Initialize urgency selection
        document.addEventListener('DOMContentLoaded', function() {
            const currentUrgency = document.getElementById('urgency_level').value;
            if (currentUrgency) {
                document.querySelectorAll('.urgency-option').forEach(opt => {
                    if (opt.classList.contains('urgency-' + currentUrgency)) {
                        opt.classList.add('selected');
                    }
                });
            }
        });
        
        // Form validation
        document.getElementById('bloodRequestForm').addEventListener('submit', function(e) {
            const bloodGroup = document.getElementById('blood_group').value;
            const quantity = document.getElementById('quantity_ml').value;
            const urgency = document.getElementById('urgency_level').value;
            
            if (!bloodGroup || !quantity || !urgency) {
                e.preventDefault();
                alert('Please fill in all required fields marked with *');
                return false;
            }
            
            if (quantity < 50 || quantity > 2000) {
                e.preventDefault();
                alert('Quantity must be between 50ml and 2000ml');
                return false;
            }
        });
    </script>
</body>
</html>