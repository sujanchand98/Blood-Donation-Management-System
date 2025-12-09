<?php
// cancel_request.php
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

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // First, let's check what columns actually exist in the table
    $check_columns = $db->prepare("SHOW COLUMNS FROM blood_requests LIKE 'cancellation_reason'");
    $check_columns->execute();
    $column_exists = $check_columns->fetch(PDO::FETCH_ASSOC);
    
    if ($column_exists) {
        // If cancellation_reason column exists
        $cancellation_reason = $_POST['cancellation_reason'] ?? '';
        $update_query = "UPDATE blood_requests SET 
            status = 'cancelled',
            cancellation_reason = :cancellation_reason,
            updated_at = NOW()
            WHERE id = :id AND seeker_id = :user_id";
            
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":cancellation_reason", $cancellation_reason);
    } else {
        // If cancellation_reason column doesn't exist, update without it
        $update_query = "UPDATE blood_requests SET 
            status = 'cancelled',
            updated_at = NOW()
            WHERE id = :id AND seeker_id = :user_id";
            
        $update_stmt = $db->prepare($update_query);
    }
    
    $update_stmt->bindParam(":id", $request_id);
    $update_stmt->bindParam(":user_id", $user_id);

    
} else {
    // If not POST, show confirmation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Request - BloodLife</title>
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
            max-width: 600px;
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

        .confirmation-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin: 50px 0;
            text-align: center;
        }

        .warning-icon {
            font-size: 64px;
            color: var(--warning);
            margin-bottom: 20px;
        }

        .confirmation-card h1 {
            font-size: 24px;
            color: var(--secondary);
            margin-bottom: 15px;
        }

        .confirmation-card p {
            color: var(--gray);
            margin-bottom: 25px;
        }

        .request-info {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: left;
        }

        .request-info h3 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
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
        <form method="POST" class="confirmation-card">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h1>Cancel Blood Request</h1>
            <p>Are you sure you want to cancel this blood request? This action cannot be undone.</p>

            <div class="request-info">
                <h3>Request Details</h3>
                <p><strong>Request ID:</strong> #<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($request['blood_group']); ?></p>
                <p><strong>Quantity:</strong> <?php echo htmlspecialchars($request['quantity_ml']); ?> ml</p>
                <p><strong>Patient:</strong> <?php echo htmlspecialchars($request['patient_name']); ?></p>
                <p><strong>Hospital:</strong> <?php echo htmlspecialchars($request['hospital_name']); ?></p>
            </div>

            <?php
            // Check if cancellation_reason column exists
            $check_columns = $db->prepare("SHOW COLUMNS FROM blood_requests LIKE 'cancellation_reason'");
            $check_columns->execute();
            $column_exists = $check_columns->fetch(PDO::FETCH_ASSOC);
            
            if ($column_exists):
            ?>
            <div class="form-group">
                <label for="cancellation_reason">Reason for Cancellation (Optional)</label>
                <textarea id="cancellation_reason" name="cancellation_reason" placeholder="Please provide a reason for cancellation..."></textarea>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times"></i> Confirm Cancellation
                </button>
                <a href="view_request.php?id=<?php echo $request_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>
        </form>
    </div>
</body>
</html>
<?php
}
?>