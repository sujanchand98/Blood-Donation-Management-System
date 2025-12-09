<?php
// edit_request.php
// session_start();
include '../config/database.php';
include '../includes/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'seeker') {
    header("Location: ../login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    header("Location: request_history.php");
    exit;
}

$request_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get request details
$query = "SELECT * FROM blood_requests WHERE id = :id AND seeker_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $request_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request || $request['status'] != 'pending') {
    header("Location: request_history.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blood_group = $_POST['blood_group'];
    $quantity_ml = $_POST['quantity_ml'];
    $urgency_level = $_POST['urgency_level'];
    $patient_name = $_POST['patient_name'];
    $patient_age = $_POST['patient_age'];
    $medical_condition = $_POST['medical_condition'];
    $hospital_name = $_POST['hospital_name'];
    $hospital_address = $_POST['hospital_address'];
    $contact_person = $_POST['contact_person'];
    $contact_phone = $_POST['contact_phone'];
    $required_date = $_POST['required_date'];
    $additional_notes = $_POST['additional_notes'];

    $update_query = "UPDATE blood_requests SET 
        blood_group = :blood_group,
        quantity_ml = :quantity_ml,
        urgency_level = :urgency_level,
        patient_name = :patient_name,
        patient_age = :patient_age,
        medical_condition = :medical_condition,
        hospital_name = :hospital_name,
        hospital_address = :hospital_address,
        contact_person = :contact_person,
        contact_phone = :contact_phone,
        required_date = :required_date,
        additional_notes = :additional_notes,
        updated_at = NOW()
        WHERE id = :id AND seeker_id = :user_id";

    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":blood_group", $blood_group);
    $update_stmt->bindParam(":quantity_ml", $quantity_ml);
    $update_stmt->bindParam(":urgency_level", $urgency_level);
    $update_stmt->bindParam(":patient_name", $patient_name);
    $update_stmt->bindParam(":patient_age", $patient_age);
    $update_stmt->bindParam(":medical_condition", $medical_condition);
    $update_stmt->bindParam(":hospital_name", $hospital_name);
    $update_stmt->bindParam(":hospital_address", $hospital_address);
    $update_stmt->bindParam(":contact_person", $contact_person);
    $update_stmt->bindParam(":contact_phone", $contact_phone);
    $update_stmt->bindParam(":required_date", $required_date);
    $update_stmt->bindParam(":additional_notes", $additional_notes);
    $update_stmt->bindParam(":id", $request_id);
    $update_stmt->bindParam(":user_id", $user_id);

  
        $_SESSION['success'] = "Request updated successfully!";
        header("Location: view_request.php?id=" . $request_id);
        exit;
    } else {
        $error = "Error updating request. Please try again.";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Request - BloodLife</title>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }

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

        .back-btn {
            background: white;
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .back-btn:hover {
            background: var(--light);
            transform: translateY(-2px);
        }

        .page-header {
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

        .edit-form {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
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

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-tint"></i>
                    <span>BloodLife</span>
                </div>
                <a href="view_request.php?id=<?php echo $request_id; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Request
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h1>Edit Blood Request</h1>
                <p>Update your blood request details</p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="edit-form">
            <div class="form-section">
                <h3><i class="fas fa-tint"></i> Blood Requirements</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="blood_group">Blood Group *</label>
                        <div class="input-group">
                            <i class="fas fa-tint"></i>
                            <select id="blood_group" name="blood_group" required>
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo ($request['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo ($request['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo ($request['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo ($request['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo ($request['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo ($request['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo ($request['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo ($request['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity_ml">Quantity (ml) *</label>
                        <div class="input-group">
                            <i class="fas fa-weight"></i>
                            <input type="number" id="quantity_ml" name="quantity_ml" min="100" max="1000" value="<?php echo htmlspecialchars($request['quantity_ml']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="urgency_level">Urgency Level *</label>
                        <div class="input-group">
                            <i class="fas fa-clock"></i>
                            <select id="urgency_level" name="urgency_level" required>
                                <option value="low" <?php echo ($request['urgency_level'] == 'low') ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo ($request['urgency_level'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo ($request['urgency_level'] == 'high') ? 'selected' : ''; ?>>High</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="required_date">Required Date</label>
                        <div class="input-group">
                            <i class="fas fa-calendar"></i>
                            <input type="date" id="required_date" name="required_date" value="<?php echo htmlspecialchars($request['required_date'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-user-injured"></i> Patient Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_name">Patient Name *</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="patient_name" name="patient_name" value="<?php echo htmlspecialchars($request['patient_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="patient_age">Patient Age</label>
                        <div class="input-group">
                            <i class="fas fa-birthday-cake"></i>
                            <input type="number" id="patient_age" name="patient_age" min="0" max="120" value="<?php echo htmlspecialchars($request['patient_age'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="medical_condition">Medical Condition</label>
                    <textarea id="medical_condition" name="medical_condition" placeholder="Describe the patient's medical condition..."><?php echo htmlspecialchars($request['medical_condition'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-hospital"></i> Hospital Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="hospital_name">Hospital Name *</label>
                        <div class="input-group">
                            <i class="fas fa-hospital"></i>
                            <input type="text" id="hospital_name" name="hospital_name" value="<?php echo htmlspecialchars($request['hospital_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_person">Contact Person</label>
                        <div class="input-group">
                            <i class="fas fa-user-md"></i>
                            <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($request['contact_person'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="hospital_address">Hospital Address</label>
                    <textarea id="hospital_address" name="hospital_address" placeholder="Enter hospital address..."><?php echo htmlspecialchars($request['hospital_address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="contact_phone">Contact Phone</label>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($request['contact_phone'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-sticky-note"></i> Additional Notes</h3>
                <div class="form-group">
                    <textarea id="additional_notes" name="additional_notes" placeholder="Any additional information or special requirements..."><?php echo htmlspecialchars($request['additional_notes'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Request
                </button>
                <a href="view_request.php?id=<?php echo $request_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>