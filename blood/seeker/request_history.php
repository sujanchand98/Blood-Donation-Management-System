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

// Handle filters and search
$status_filter = $_GET['status'] ?? '';
$blood_group_filter = $_GET['blood_group'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT * FROM blood_requests WHERE seeker_id = :user_id";
$params = [':user_id' => $user_id];

if (!empty($status_filter)) {
    $query .= " AND status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($blood_group_filter)) {
    $query .= " AND blood_group = :blood_group";
    $params[':blood_group'] = $blood_group_filter;
}

if (!empty($search_query)) {
    $query .= " AND (blood_group LIKE :search OR hospital_name LIKE :search OR patient_name LIKE :search)";
    $params[':search'] = "%$search_query%";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats for filters
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled
    FROM blood_requests WHERE seeker_id = :user_id";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(":user_id", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request History - BloodLife</title>
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

        .new-request-btn {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .new-request-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(198, 40, 40, 0.3);
        }

        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filters Section */
        .filters-section {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .filters-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filters-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--secondary);
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-group {
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
            font-size: 14px;
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

        input, select {
            width: 100%;
            padding: 10px 12px 10px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .btn-filter {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-filter:hover {
            background: var(--primary-dark);
        }

        .btn-reset {
            background: var(--light-gray);
            color: var(--gray);
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .btn-reset:hover {
            background: #dee2e6;
        }

        /* Requests Table */
        .requests-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 40px;
        }

        .section-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--secondary);
        }

        .requests-count {
            color: var(--gray);
            font-size: 14px;
        }

        .requests-table {
            width: 100%;
            border-collapse: collapse;
        }

        .requests-table th {
            background: var(--light);
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--secondary);
            border-bottom: 1px solid var(--light-gray);
        }

        .requests-table td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: top;
        }

        .requests-table tr:last-child td {
            border-bottom: none;
        }

        .requests-table tr:hover {
            background: #f8f9fa;
        }

        .request-id {
            font-weight: 600;
            color: var(--primary);
            font-size: 14px;
        }

        .blood-group {
            font-weight: 700;
            font-size: 16px;
        }

        .patient-info {
            font-size: 14px;
        }

        .hospital-info {
            font-size: 14px;
            color: var(--gray);
        }

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

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1edff; color: #004085; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-fulfilled { background: #d4edda; color: #155724; }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-view {
            background: var(--info);
            color: white;
        }

        .btn-view:hover {
            background: #138496;
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--light-gray);
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--secondary);
        }

        .empty-state p {
            margin-bottom: 25px;
            font-size: 16px;
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
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .requests-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 768px) {
            .page-header {
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
            
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                flex-direction: column;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
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
    </header>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1>Request History</h1>
                <p>View and manage all your blood requests</p>
            </div>
            <a href="request_blood.php" class="new-request-btn">
                <i class="fas fa-plus"></i> New Request
            </a>
        </div>

        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['approved'] ?? 0; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['fulfilled'] ?? 0; ?></div>
                <div class="stat-label">Fulfilled</div>
            </div>
        </div>

        <!-- Requests Section -->
        <div class="requests-section">
            <div class="section-header">
                <h2>Blood Requests</h2>
                <div class="requests-count">
                    <?php echo count($requests); ?> request(s) found
                </div>
            </div>

            <?php if (count($requests) > 0): ?>
                <div class="table-container">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Blood Group</th>
                                <th>Patient Details</th>
                                <th>Hospital</th>
                                <th>Quantity</th>
                                <th>Urgency</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td>
                                        <div class="request-id">#<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    </td>
                                    <td>
                                        <div class="blood-group"><?php echo htmlspecialchars($request['blood_group']); ?></div>
                                    </td>
                                    <td>
                                        <div class="patient-info">
                                            <strong><?php echo htmlspecialchars($request['patient_name'] ?? 'N/A'); ?></strong><br>
                                            <small>Age: <?php echo htmlspecialchars($request['patient_age'] ?? 'N/A'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="hospital-info">
                                            <?php echo htmlspecialchars($request['hospital_name'] ?? 'N/A'); ?><br>
                                            <small><?php echo htmlspecialchars($request['hospital_address'] ?? ''); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($request['quantity_ml']); ?> ml</strong>
                                    </td>
                                    <td>
                                        <span class="urgency-badge urgency-<?php echo strtolower($request['urgency_level']); ?>">
                                            <?php echo ucfirst($request['urgency_level']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($request['created_at'])); ?><br>
                                        <small><?php echo date('g:i A', strtotime($request['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $request['status']; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn-action btn-view">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($request['status'] == 'pending'): ?>
                                                <a href="edit_request.php?id=<?php echo $request['id']; ?>" class="btn-action btn-edit">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="cancel_request.php?id=<?php echo $request['id']; ?>" class="btn-action btn-cancel" onclick="return confirm('Are you sure you want to cancel this request?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-medical"></i>
                    <h3>No Requests Found</h3>
                    <p><?php echo (!empty($status_filter) || !empty($blood_group_filter) || !empty($search_query)) ? 
                        'Try adjusting your filters or search terms.' : 
                        'You haven\'t made any blood requests yet.'; ?></p>
                    <?php if (empty($status_filter) && empty($blood_group_filter) && empty($search_query)): ?>
                        <a href="request_blood.php" class="new-request-btn">
                            <i class="fas fa-plus"></i> Make Your First Request
                        </a>
                    <?php else: ?>
                        <a href="request_history.php" class="btn-reset" style="padding: 12px 24px;">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <script>
        // Simple animation for page elements
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            const filtersSection = document.querySelector('.filters-section');
            const requestsSection = document.querySelector('.requests-section');
            
            // Animate stat cards
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s, transform 0.5s';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
            
            // Animate filters and requests section
            setTimeout(() => {
                filtersSection.style.opacity = '0';
                filtersSection.style.transform = 'translateY(20px)';
                filtersSection.style.transition = 'opacity 0.5s, transform 0.5s';
                filtersSection.style.opacity = '1';
                filtersSection.style.transform = 'translateY(0)';
                
                requestsSection.style.opacity = '0';
                requestsSection.style.transform = 'translateY(20px)';
                requestsSection.style.transition = 'opacity 0.5s, transform 0.5s';
                requestsSection.style.opacity = '1';
                requestsSection.style.transform = 'translateY(0)';
            }, 600);
        });
    </script>
</body>
</html>