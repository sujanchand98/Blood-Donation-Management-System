<?php
// session_start();
include 'config/database.php';
include 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$error = '';
$success = '';

// Check if user type is specified
$user_type = isset($_GET['type']) ? $_GET['type'] : '';
if (!in_array($user_type, ['donor', 'seeker']) && $user_type != '') {
    $user_type = '';
}

if ($_POST) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $full_name = trim($_POST['full_name']);
    $blood_group = $_POST['blood_group'];
    $date_of_birth = $_POST['date_of_birth'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = "Please fill in all required fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if username already exists
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":username", $username);
        $check_stmt->bindParam(":email", $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Username or email already exists";
        } else {
            // Age validation algorithm for donors
            if ($role === 'donor') {
                $min_donor_age = 18;
                $max_donor_age = 65;
                $birth_date = new DateTime($date_of_birth);
                $today = new DateTime();
                $age = $today->diff($birth_date)->y;
                
                if ($age < $min_donor_age) {
                    $error = "You must be at least 18 years old to donate blood";
                } elseif ($age > $max_donor_age) {
                    $error = "Donors must be under 65 years old";
                }
            }
            
            if (empty($error)) {
                $result = $auth->register($username, $email, $password, $role, $full_name, $blood_group, $phone, $address, $date_of_birth);
                
                if ($result === true) {
                    $success = "Registration successful! Please login.";
                    // Clear form
                    $_POST = array();
                } else {
                    $error = $result;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Blood Donation System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .user-type-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .user-type-option {
            flex: 1;
            padding: 1.5rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-type-option:hover {
            border-color: #c00;
        }
        
        .user-type-option.selected {
            border-color: #c00;
            background-color: #ffe6e6;
        }
        
        .user-type-option h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .required::after {
            content: " *";
            color: #dc3545;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .error-message {
            color: #dc3545;
            background: #f8d7da;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            color: #155724;
            background: #d4edda;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    
    
    <div class="container">
        <div class="form-container">
            <h2>Create Account</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo $success; ?>
                    <br><a href="login.php" style="color: #155724; text-decoration: underline;">Click here to login</a>
                </div>
            <?php endif; ?>
            
            <!-- User Type Selection -->
            <div class="user-type-selector">
                <div class="user-type-option <?php echo ($user_type == 'donor' || (isset($_POST['role']) && $_POST['role'] == 'donor')) ? 'selected' : ''; ?>" 
                     onclick="selectUserType('donor')">
                    <h3>🥇 Blood Donor</h3>
                    <p>I want to donate blood and save lives</p>
                </div>
                <div class="user-type-option <?php echo ($user_type == 'seeker' || (isset($_POST['role']) && $_POST['role'] == 'seeker')) ? 'selected' : ''; ?>" 
                     onclick="selectUserType('seeker')">
                    <h3>🩺 Blood Seeker</h3>
                    <p>I need to find blood for patients</p>
                </div>
            </div>
            
            <form method="POST" id="registrationForm">
                <input type="hidden" name="role" id="role" value="<?php echo isset($_POST['role']) ? htmlspecialchars($_POST['role']) : htmlspecialchars($user_type); ?>">
                
                <!-- Personal Information Section -->
                <div class="form-section active" id="personalInfo">
                    <h3>Personal Information</h3>
                    
                    <div class="form-group">
                        <label class="required">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Email Address</label>
                        <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Address</label>
                        <textarea name="address" rows="3" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn" onclick="nextSection('personalInfo', 'accountInfo')">Next</button>
                    </div>
                </div>
                
                <!-- Account Information Section -->
                <div class="form-section" id="accountInfo">
                    <h3>Account Information</h3>
                    
                    <div class="form-group">
                        <label class="required">Username</label>
                        <input type="text" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required 
                               onblur="checkUsernameAvailability(this.value)">
                        <small id="username-availability" style="font-size: 0.9rem;"></small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Password</label>
                        <input type="password" name="password" id="password" required onkeyup="checkPasswordStrength(this.value)">
                        <div class="password-strength">
                            <span id="password-strength-text">Password strength</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required onkeyup="checkPasswordMatch()">
                        <small id="password-match" style="font-size: 0.9rem;"></small>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="prevSection('accountInfo', 'personalInfo')">Previous</button>
                        <button type="button" class="btn" onclick="nextSection('accountInfo', 'medicalInfo')">Next</button>
                    </div>
                </div>
                
                <!-- Medical Information Section -->
                <div class="form-section" id="medicalInfo">
                    <h3>Medical Information</h3>
                    
                    <div class="form-group">
                        <label class="required">Blood Group</label>
                        <select name="blood_group" required>
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
                    
                    <!-- Donor-specific questions -->
                    <div id="donorQuestions" style="display: none;">
                        <div class="form-group">
                            <label>Have you donated blood before?</label>
                            <select name="previous_donation">
                                <option value="">Select</option>
                                <option value="yes" <?php echo (isset($_POST['previous_donation']) && $_POST['previous_donation'] == 'yes') ? 'selected' : ''; ?>>Yes</option>
                                <option value="no" <?php echo (isset($_POST['previous_donation']) && $_POST['previous_donation'] == 'no') ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Last donation date (if any)</label>
                            <input type="date" name="last_donation_date" value="<?php echo isset($_POST['last_donation_date']) ? htmlspecialchars($_POST['last_donation_date']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Any health conditions we should know about?</label>
                            <textarea name="health_conditions" rows="3"><?php echo isset($_POST['health_conditions']) ? htmlspecialchars($_POST['health_conditions']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Seeker-specific questions -->
                    <div id="seekerQuestions" style="display: none;">
                        <div class="form-group">
                            <label>Hospital/Organization Name</label>
                            <input type="text" name="hospital_name" value="<?php echo isset($_POST['hospital_name']) ? htmlspecialchars($_POST['hospital_name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Patient Name (if applicable)</label>
                            <input type="text" name="patient_name" value="<?php echo isset($_POST['patient_name']) ? htmlspecialchars($_POST['patient_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="terms" required <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                            I agree to the <a href="#" style="color: #c00;">Terms and Conditions</a> and <a href="#" style="color: #c00;">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="prevSection('medicalInfo', 'accountInfo')">Previous</button>
                        <button type="submit" class="btn">Create Account</button>
                    </div>
                </div>
            </form>
            
            <p style="text-align: center; margin-top: 2rem;">
                Already have an account? <a href="login.php" style="color: #c00;">Login here</a>
            </p>
        </div>
    </div>
    
  
    
    <script>
        // User type selection
        function selectUserType(type) {
            document.getElementById('role').value = type;
            const options = document.querySelectorAll('.user-type-option');
            options.forEach(opt => {
                opt.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Show/hide relevant questions
            if (type === 'donor') {
                document.getElementById('donorQuestions').style.display = 'block';
                document.getElementById('seekerQuestions').style.display = 'none';
            } else if (type === 'seeker') {
                document.getElementById('donorQuestions').style.display = 'none';
                document.getElementById('seekerQuestions').style.display = 'block';
            }
        }
        
        // Form navigation
        function nextSection(current, next) {
            // Validate current section
            if (validateSection(current)) {
                document.getElementById(current).classList.remove('active');
                document.getElementById(next).classList.add('active');
            }
        }
        
        function prevSection(current, prev) {
            document.getElementById(current).classList.remove('active');
            document.getElementById(prev).classList.add('active');
        }
        
        // Section validation
        function validateSection(sectionId) {
            const section = document.getElementById(sectionId);
            const inputs = section.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#dc3545';
                } else {
                    input.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields');
            }
            
            return isValid;
        }
        
        // Password strength algorithm
        function checkPasswordStrength(password) {
            const strengthText = document.getElementById('password-strength-text');
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            
            // Contains uppercase
            if (/[A-Z]/.test(password)) strength++;
            
            // Contains lowercase
            if (/[a-z]/.test(password)) strength++;
            
            // Contains numbers
            if (/[0-9]/.test(password)) strength++;
            
            // Contains special characters
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update UI
            switch(strength) {
                case 0:
                case 1:
                    strengthText.innerHTML = '❌ Weak password';
                    strengthText.className = 'strength-weak';
                    break;
                case 2:
                case 3:
                    strengthText.innerHTML = '⚠️ Medium strength';
                    strengthText.className = 'strength-medium';
                    break;
                case 4:
                case 5:
                    strengthText.innerHTML = '✅ Strong password';
                    strengthText.className = 'strength-strong';
                    break;
            }
        }
        
        // Password match check
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('password-match');
            
            if (confirmPassword === '') {
                matchText.innerHTML = '';
            } else if (password === confirmPassword) {
                matchText.innerHTML = '✅ Passwords match';
                matchText.style.color = '#28a745';
            } else {
                matchText.innerHTML = '❌ Passwords do not match';
                matchText.style.color = '#dc3545';
            }
        }
        
        // Username availability check
        function checkUsernameAvailability(username) {
            if (username.length < 3) return;
            
            const availabilityText = document.getElementById('username-availability');
            
            // Simple client-side validation
            if (username.length < 4) {
                availabilityText.innerHTML = '❌ Username must be at least 4 characters';
                availabilityText.style.color = '#dc3545';
                return;
            }
            
            // For now, we'll just do client-side validation
            // In a real application, you'd make an AJAX call to check_username.php
            availabilityText.innerHTML = '✅ Username format is valid';
            availabilityText.style.color = '#28a745';
        }
        
        // Initialize form based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');
            
            if (type === 'donor' || type === 'seeker') {
                selectUserType(type);
            }
            
            // Initialize based on current role value
            const currentRole = document.getElementById('role').value;
            if (currentRole) {
                selectUserType(currentRole);
            }
        });
    </script>
</body>
</html>