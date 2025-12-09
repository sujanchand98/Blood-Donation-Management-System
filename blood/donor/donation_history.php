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

// Get all donations with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Total donations count
$count_query = "SELECT COUNT(*) as total FROM donations WHERE donor_id = :user_id";
$count_stmt = $db->prepare($count_query);
$count_stmt->bindParam(":user_id", $user_id);
$count_stmt->execute();
$total_donations = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_donations / $limit);

// Get donations with pagination
$donations_query = "SELECT * FROM donations 
                   WHERE donor_id = :user_id 
                   ORDER BY donation_date DESC 
                   LIMIT :limit OFFSET :offset";
$donations_stmt = $db->prepare($donations_query);
$donations_stmt->bindParam(":user_id", $user_id);
$donations_stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
$donations_stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
$donations_stmt->execute();
$donations = $donations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get donation statistics
$stats_query = "SELECT 
                COUNT(*) as total_donations,
                COALESCE(SUM(quantity_ml), 0) as total_ml,
                AVG(quantity_ml) as avg_ml,
                MIN(donation_date) as first_donation,
                MAX(donation_date) as last_donation
                FROM donations 
                WHERE donor_id = :user_id AND status = 'approved'";
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
    <title>Donation History - Blood Donation System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .history-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-box {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #c00;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #c00;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .donations-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 2rem 0;
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .donation-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .donation-row:last-child {
            border-bottom: none;
        }
        
        .donation-row.header {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-align: center;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }
        
        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .page-link:hover, .page-link.active {
            background: #c00;
            color: white;
            border-color: #c00;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .impact-summary {
            background: linear-gradient(135deg, #c00, #f00);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            margin: 2rem 0;
        }
        
        @media (max-width: 768px) {
            .donation-row {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 0.5rem;
            }
            
            .donation-row.header {
                display: none;
            }
            
            .history-stats {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
        <h1>Your Donation History</h1>
        <p>Track your life-saving journey and impact</p>
        
        <!-- Impact Summary -->
        <div class="impact-summary">
            <h2>Your Blood Donation Impact</h2>
            <div class="lives-saved" style="font-size: 3rem; font-weight: bold; margin: 1rem 0;">
                <?php echo floor($stats['total_ml'] / 450); ?>+
            </div>
            <p>Potential Lives Saved</p>
            <p>You've donated <?php echo $stats['total_ml']; ?> ml of blood across <?php echo $stats['total_donations']; ?> donations</p>
        </div>
        
        <!-- Statistics -->
        <div class="history-stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_donations']; ?></div>
                <div class="stat-label">Total Donations</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_ml']; ?> ml</div>
                <div class="stat-label">Blood Donated</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number"><?php echo round($stats['avg_ml']); ?> ml</div>
                <div class="stat-label">Average per Donation</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number">
                    <?php 
                    if ($stats['first_donation']) {
                        echo date('M Y', strtotime($stats['first_donation']));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
                <div class="stat-label">First Donation</div>
            </div>
        </div>
        
        <!-- Donations Table -->
        <div class="donations-table">
            <div class="table-header">
                <h2>Donation Records</h2>
            </div>
            
            <?php if (count($donations) > 0): ?>
                <!-- Table Header -->
                <div class="donation-row header">
                    <div>Date</div>
                    <div>Blood Group</div>
                    <div>Quantity</div>
                    <div>Status</div>
                    <div>Days Ago</div>
                </div>
                
                <!-- Donation Rows -->
                <?php foreach ($donations as $donation): ?>
                    <div class="donation-row">
                        <div>
                            <strong><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></strong>
                        </div>
                        <div>
                            <span style="font-weight: bold; color: #c00;"><?php echo $donation['blood_group']; ?></span>
                        </div>
                        <div><?php echo $donation['quantity_ml']; ?> ml</div>
                        <div>
                            <span class="status-badge status-<?php echo $donation['status']; ?>">
                                <?php echo ucfirst($donation['status']); ?>
                            </span>
                        </div>
                        <div>
                            <?php
                            $donation_date = new DateTime($donation['donation_date']);
                            $today = new DateTime();
                            $interval = $today->diff($donation_date);
                            echo $interval->days . ' days';
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tint"></i>
                    <h3>No Donations Recorded</h3>
                    <p>You haven't made any blood donations yet.</p>
                    <p>Your first donation can save up to 3 lives!</p>
                    <a href="../index.php" class="btn" style="margin-top: 1rem;">Find Donation Centers</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="page-link">Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="page-link">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Donation Milestones -->
        <div class="recent-section" style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            <h2>Donation Milestones</h2>
            <div class="milestones">
                <?php
                $milestones = [1, 5, 10, 25, 50, 100];
                $next_milestone = null;
                
                foreach ($milestones as $milestone) {
                    if ($stats['total_donations'] < $milestone) {
                        $next_milestone = $milestone;
                        break;
                    }
                }
                ?>
                
                <?php if ($next_milestone): ?>
                    <div class="milestone-card" style="background: #e7f3ff; padding: 1.5rem; border-radius: 8px; text-align: center; margin: 1rem 0;">
                        <h3 style="color: #007bff; margin-bottom: 1rem;">Next Milestone: <?php echo $next_milestone; ?> Donations</h3>
                        <p>You're <?php echo $next_milestone - $stats['total_donations']; ?> donations away from reaching this milestone!</p>
                        <div class="progress" style="background: #ddd; height: 10px; border-radius: 5px; margin: 1rem 0;">
                            <div style="background: #007bff; height: 100%; border-radius: 5px; width: <?php echo min(($stats['total_donations'] / $next_milestone) * 100, 100); ?>%;"></div>
                        </div>
                        <p><?php echo $stats['total_donations']; ?> / <?php echo $next_milestone; ?> donations completed</p>
                    </div>
                <?php else: ?>
                    <div class="milestone-card" style="background: #d4edda; padding: 1.5rem; border-radius: 8px; text-align: center;">
                        <h3 style="color: #155724; margin-bottom: 1rem;">🎉 Amazing Achievement!</h3>
                        <p>You've reached all major donation milestones! Thank you for your incredible commitment to saving lives.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
</body>
</html>