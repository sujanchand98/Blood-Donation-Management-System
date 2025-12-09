<?php
// session_start();
include '../config/database.php';
include '../includes/auth.php';
include '../blood_matching.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'seeker') {
    header("Location: ../login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$donors = [];
$search_performed = false;
$blood_group = '';
$location = '';

if ($_POST || isset($_GET['blood_group'])) {
    $blood_group = isset($_POST['blood_group']) ? $_POST['blood_group'] : $_GET['blood_group'];
    $location = isset($_POST['location']) ? $_POST['location'] : '';
    
    if (!empty($blood_group)) {
        $search_performed = true;
        $donors = BloodMatching::findPotentialDonors($db, $blood_group, $location);
    }
}

// Get user's location for autofill
$user_query = "SELECT address FROM users WHERE id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(":user_id", $_SESSION['user_id']);
$user_stmt->execute();
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
$user_location = $user_data['address'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Donors - Blood Donation System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .donor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .donor-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid #c00;
        }
        
        .donor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .donor-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        
        .donor-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        .blood-group-badge {
            background: #c00;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .donor-info {
            margin-bottom: 1rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #666;
        }
        
        .compatibility-badge {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .contact-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .contact-btn {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #c00;
            background: white;
            color: #c00;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .contact-btn:hover {
            background: #c00;
            color: white;
        }
        
        .no-donors {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .compatibility-info {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #007bff;
        }
        
        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .donor-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
        <h1>Find Blood Donors</h1>
        <p>Search for compatible blood donors in your area</p>
        
        <!-- Search Section -->
        <div class="search-section">
            <h2>Search Donors</h2>
            <form method="POST" class="search-form">
                <div class="form-group">
                    <label>Required Blood Group</label>
                    <select name="blood_group" required>
                        <option value="">Select Blood Group</option>
                        <option value="A+" <?php echo ($blood_group == 'A+') ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo ($blood_group == 'A-') ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo ($blood_group == 'B+') ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo ($blood_group == 'B-') ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo ($blood_group == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo ($blood_group == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                        <option value="O+" <?php echo ($blood_group == 'O+') ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo ($blood_group == 'O-') ? 'selected' : ''; ?>>O-</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Location (City/Area)</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($location ?: $user_location); ?>" 
                           placeholder="Enter city or area...">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Search Donors</button>
                </div>
            </form>
        </div>
        
        <?php if ($search_performed): ?>
            <!-- Results Section -->
            <div class="results-section">
                <h2>Compatible Donors Found: <?php echo count($donors); ?></h2>
                
                <?php if (!empty($blood_group)): ?>
                    <div class="compatibility-info">
                        <h4>🩸 Blood Compatibility Information</h4>
                        <p>For blood group <strong><?php echo $blood_group; ?></strong>, compatible donors can have: 
                           <strong><?php echo implode(', ', BloodMatching::getCompatibleBloodGroups($blood_group)); ?></strong></p>
                    </div>
                <?php endif; ?>
                
                <?php if (count($donors) > 0): ?>
                    <div class="donor-grid">
                        <?php foreach ($donors as $donor): ?>
                            <div class="donor-card">
                                <div class="donor-header">
                                    <h3 class="donor-name"><?php echo htmlspecialchars($donor['full_name']); ?></h3>
                                    <div class="blood-group-badge"><?php echo $donor['blood_group']; ?></div>
                                </div>
                                
                                <div class="donor-info">
                                    <div class="info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($donor['address'] ?? 'Address not provided'); ?></span>
                                    </div>
                                    
                                    <div class="info-item">
                                        <i class="fas fa-tint"></i>
                                        <span>Blood Group: <?php echo $donor['blood_group']; ?></span>
                                        <span class="compatibility-badge">Compatible</span>
                                    </div>
                                    
                                    <?php if (!empty($donor['phone'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($donor['phone']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="contact-actions">
                                    <?php if (!empty($donor['phone'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($donor['phone']); ?>" class="contact-btn">
                                            <i class="fas fa-phone"></i> Call
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="contact-btn" onclick="sendMessage('<?php echo htmlspecialchars($donor['full_name']); ?>')">
                                        <i class="fas fa-envelope"></i> Message
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-donors">
                        <h3>😔 No Donors Found</h3>
                        <p>We couldn't find any compatible donors matching your criteria.</p>
                        <p>Suggestions:</p>
                        <ul style="text-align: left; display: inline-block;">
                            <li>Try searching with a different location</li>
                            <li>Check if your required blood group is correct</li>
                            <li><a href="../request_blood.php">Submit a blood request</a> for admin assistance</li>
                            <li>Contact nearby blood banks directly</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Instructions Section -->
            <div class="search-section">
                <h2>How to Find Donors</h2>
                <div class="instructions">
                    <div class="instruction-step">
                        <h3>1. Select Blood Group</h3>
                        <p>Choose the required blood group for the patient</p>
                    </div>
                    <div class="instruction-step">
                        <h3>2. Specify Location (Optional)</h3>
                        <p>Enter your city or area to find nearby donors</p>
                    </div>
                    <div class="instruction-step">
                        <h3>3. Contact Donors</h3>
                        <p>Reach out to compatible donors directly through provided contact information</p>
                    </div>
                </div>
                
                <!-- Quick Search Links -->
                <div class="quick-search" style="margin-top: 2rem;">
                    <h3>Quick Search by Blood Group:</h3>
                    <div class="quick-links">
                        <?php
                        $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                        foreach ($blood_groups as $bg):
                        ?>
                            <a href="find_donor.php?blood_group=<?php echo $bg; ?>" class="btn" style="margin: 0.25rem;">
                                <?php echo $bg; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    

    
    <script>
        function sendMessage(donorName) {
            const message = `Hello, I found your contact through BloodDonor System. I need blood donation for a patient. Could you please help?`;
            alert(`Message to ${donorName}:\n\n${message}\n\nIn a real application, this would open your messaging app.`);
        }
        
        // Auto-submit form when quick search links are used
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const bloodGroup = urlParams.get('blood_group');
            
            if (bloodGroup) {
                document.querySelector('select[name="blood_group"]').value = bloodGroup;
                document.querySelector('form').submit();
            }
        });
    </script>
</body>
</html>