<?php
// view_request.php
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

if (!$request) {
    header("Location: request_history.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request - BloodLife</title>
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

        .request-details {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .detail-section h3 {
            font-size: 18px;
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--light-gray);
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--secondary);
        }

        .detail-value {
            color: var(--gray);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1edff; color: #004085; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-fulfilled { background: #d4edda; color: #155724; }

        .urgency-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .urgency-high { background: #f8d7da; color: #721c24; }
        .urgency-medium { background: #fff3cd; color: #856404; }
        .urgency-low { background: #d1edff; color: #004085; }

        .action-buttons {
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

        .btn-edit {
            background: var(--warning);
            color: white;
        }

        .btn-edit:hover {
            background: #e0a800;
        }

        .btn-cancel {
            background: var(--danger);
            color: white;
        }

        .btn-cancel:hover {
            background: #c82333;
        }

        .btn-back {
            background: var(--gray);
            color: white;
        }

        .btn-back:hover {
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
                <a href="request_history.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to History
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h1>Request Details</h1>
                <p>Request ID: #<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>

        <div class="request-details">
            <div class="detail-grid">
                <div class="detail-section">
                    <h3>Blood Request Information</h3>
                    <div class="detail-item">
                        <span class="detail-label">Blood Group:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($request['blood_group']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Quantity:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($request['quantity_ml']); ?> ml</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Urgency Level:</span>
                        <span class="detail-value">
                            <span class="urgency-badge urgency-<?php echo strtolower($request['urgency_level']); ?>">
                                <?php echo ucfirst($request['urgency_level']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge status-<?php echo $request['status']; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </span>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Patient Information</h3>
                    <div class="detail-item">
                        <span class="detail-label">Patient Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($request['patient_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Patient Age:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($request['patient_age'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Medical Condition:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($request['medical_condition'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Hospital Information</h3>
                    <div class="detail-item">
                        <span class="detail-label">Hospital Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($request['hospital_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Hospital Address:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($request['hospital_address'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Contact Person:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($request['contact_person'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Contact Phone:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($request['contact_phone'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Request Timeline</h3>
                    <div class="detail-item">
                        <span class="detail-label">Request Date:</span>
                        <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?></span>
                    </div>
                    <?php if ($request['updated_at'] && $request['updated_at'] != $request['created_at']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Last Updated:</span>
                        <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($request['updated_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($request['required_date']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Required By:</span>
                        <span class="detail-value"><?php echo date('F j, Y', strtotime($request['required_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="action-buttons">
                <a href="request_history.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to History
                </a>
                <?php if ($request['status'] == 'pending'): ?>
                <a href="edit_request.php?id=<?php echo $request['id']; ?>" class="btn btn-edit">
                    <i class="fas fa-edit"></i> Edit Request
                </a>
                <a href="cancel_request.php?id=<?php echo $request['id']; ?>" class="btn btn-cancel" onclick="return confirm('Are you sure you want to cancel this request?')">
                    <i class="fas fa-times"></i> Cancel Request
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>