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

// Handle filters
$location_filter = $_GET['location'] ?? '';
$date_filter = $_GET['date'] ?? '';
$organization_filter = $_GET['organization'] ?? '';
$radius_filter = $_GET['radius'] ?? '10';

// Build query for blood drives
$query = "SELECT * FROM blood_drives WHERE status = 'active' AND drive_date >= CURDATE()";
$params = [];

if (!empty($location_filter)) {
    $query .= " AND (location LIKE ? OR address LIKE ? OR city LIKE ?)";
    $params[] = "%$location_filter%";
    $params[] = "%$location_filter%";
    $params[] = "%$location_filter%";
}

if (!empty($date_filter)) {
    $query .= " AND DATE(drive_date) = ?";
    $params[] = $date_filter;
}

if (!empty($organization_filter)) {
    $query .= " AND organizing_org LIKE ?";
    $params[] = "%$organization_filter%";
}

$query .= " ORDER BY drive_date ASC, urgency_level DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$blood_drives = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique cities for filter dropdown
$cities_query = "SELECT DISTINCT city FROM blood_drives WHERE city IS NOT NULL AND city != '' ORDER BY city";
$cities_stmt = $db->prepare($cities_query);
$cities_stmt->execute();
$cities = $cities_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get unique organizations for filter dropdown
$orgs_query = "SELECT DISTINCT organizing_org FROM blood_drives WHERE organizing_org IS NOT NULL AND organizing_org != '' ORDER BY organizing_org";
$orgs_stmt = $db->prepare($orgs_query);
$orgs_stmt->execute();
$organizations = $orgs_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Blood Drives - Blood Donation System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        
        .filters-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group select, 
        .form-group input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #c00;
            color: white;
        }
        
        .btn-primary:hover {
            background: #a00;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .drives-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .drive-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .drive-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .drive-header {
            background: linear-gradient(135deg, #c00 0%, #a00 100%);
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        
        .drive-date {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .drive-title {
            margin: 0;
            font-size: 1.25rem;
            margin-right: 80px;
        }
        
        .drive-org {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .drive-body {
            padding: 1.5rem;
        }
        
        .drive-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }
        
        .info-item i {
            color: #c00;
            width: 16px;
        }
        
        .drive-description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }
        
        .drive-requirements {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .requirements-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .requirements-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .requirements-list li {
            padding: 0.25rem 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .requirements-list li:before {
            content: "•";
            color: #c00;
            font-weight: bold;
        }
        
        .drive-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-outline {
            background: transparent;
            color: #c00;
            border: 1px solid #c00;
        }
        
        .btn-outline:hover {
            background: #c00;
            color: white;
        }
        
        .urgency-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .urgency-high { background: #fff3cd; color: #856404; }
        .urgency-medium { background: #d1edff; color: #004085; }
        .urgency-low { background: #d4edda; color: #155724; }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .map-view {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .map-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 5px;
            padding: 3rem;
            text-align: center;
            color: #666;
        }
        
        .view-toggle {
            display: flex;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .view-option {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            border: none;
            background: #f8f9fa;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .view-option.active {
            background: #c00;
            color: white;
        }
        
        .view-option:not(.active):hover {
            background: #e9ecef;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
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
        
        .featured-drive {
            background: linear-gradient(135deg, #ff6b6b 0%, #c00 100%);
            color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .featured-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .drives-grid {
                grid-template-columns: 1fr;
            }
            
            .drive-info {
                grid-template-columns: 1fr;
            }
            
            .drive-actions {
                flex-direction: column;
            }
            
            .view-toggle {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="page-header">
        <div class="page-header-content">
            <h1><i class="fas fa-map-marker-alt"></i> Find Blood Drives</h1>
            <p>Discover blood donation camps and drives near you. Save lives in your community!</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($blood_drives); ?></div>
                <div class="stat-label">Upcoming Drives</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($cities); ?>+</div>
                <div class="stat-label">Cities Covered</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Support Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">100%</div>
                <div class="stat-label">Free Service</div>
            </div>
        </div>
        
        <!-- View Toggle -->
        <div class="view-toggle">
            <button class="view-option active" onclick="showView('grid')">
                <i class="fas fa-th"></i> Grid View
            </button>
            <button class="view-option" onclick="showView('map')">
                <i class="fas fa-map"></i> Map View
            </button>
            <button class="view-option" onclick="showView('list')">
                <i class="fas fa-list"></i> List View
            </button>
        </div>
        
        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Location</label>
                    <select name="location">
                        <option value="">All Locations</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city); ?>" 
                                <?php echo $location_filter == $city ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Organization</label>
                    <select name="organization">
                        <option value="">All Organizations</option>
                        <?php foreach ($organizations as $org): ?>
                            <option value="<?php echo htmlspecialchars($org); ?>" 
                                <?php echo $organization_filter == $org ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($org); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Radius (km)</label>
                    <select name="radius">
                        <option value="5" <?php echo $radius_filter == '5' ? 'selected' : ''; ?>>5 km</option>
                        <option value="10" <?php echo $radius_filter == '10' ? 'selected' : ''; ?>>10 km</option>
                        <option value="25" <?php echo $radius_filter == '25' ? 'selected' : ''; ?>>25 km</option>
                        <option value="50" <?php echo $radius_filter == '50' ? 'selected' : ''; ?>>50 km</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="find_drives.php" class="btn btn-secondary" style="margin-top: 0.5rem;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Grid View -->
        <div id="grid-view" class="view-content">
            <?php if (count($blood_drives) > 0): ?>
                <div class="drives-grid">
                    <?php foreach ($blood_drives as $drive): ?>
                        <div class="drive-card">
                            <div class="drive-header">
                                <div class="drive-date">
                                    <?php echo date('M j', strtotime($drive['drive_date'])); ?>
                                </div>
                                <h3 class="drive-title"><?php echo htmlspecialchars($drive['title']); ?></h3>
                                <p class="drive-org">by <?php echo htmlspecialchars($drive['organizing_org']); ?></p>
                            </div>
                            
                            <div class="drive-body">
                                <div class="drive-info">
                                    <div class="info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($drive['location']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo date('g:i A', strtotime($drive['start_time'])); ?> - <?php echo date('g:i A', strtotime($drive['end_time'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $drive['expected_donors'] ?? '50'; ?> expected donors</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-bolt"></i>
                                        <span class="urgency-badge urgency-<?php echo $drive['urgency_level'] ?? 'medium'; ?>">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo ucfirst($drive['urgency_level'] ?? 'Medium'); ?> Need
                                        </span>
                                    </div>
                                </div>
                                
                                <p class="drive-description">
                                    <?php echo htmlspecialchars($drive['description'] ?? 'Join us for this blood donation drive and help save lives in our community.'); ?>
                                </p>
                                
                                <div class="drive-requirements">
                                    <div class="requirements-title">
                                        <i class="fas fa-clipboard-check"></i>
                                        Requirements
                                    </div>
                                    <ul class="requirements-list">
                                        <li>Age 18-65 years</li>
                                        <li>Weight >50 kg</li>
                                        <li>Good health condition</li>
                                        <li>Photo ID required</li>
                                    </ul>
                                </div>
                                
                                <div class="drive-actions">
                                    <button class="btn-sm btn-success" onclick="registerForDrive(<?php echo $drive['id']; ?>)">
                                        <i class="fas fa-calendar-plus"></i> Register
                                    </button>
                                    <a href="#" class="btn-sm btn-outline" onclick="shareDrive('<?php echo htmlspecialchars($drive['title']); ?>')">
                                        <i class="fas fa-share-alt"></i> Share
                                    </a>
                                    <a href="#" class="btn-sm btn-outline" onclick="getDirections('<?php echo htmlspecialchars($drive['address'] ?? $drive['location']); ?>')">
                                        <i class="fas fa-directions"></i> Directions
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>No Blood Drives Found</h3>
                    <p>No upcoming blood drives match your current filters.</p>
                    <p>Try adjusting your search criteria or check back later for new drives.</p>
                    <a href="find_drives.php" class="btn btn-primary">Clear Filters</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Map View -->
        <div id="map-view" class="view-content" style="display: none;">
            <div class="map-view">
                <h3><i class="fas fa-map"></i> Blood Drives Map</h3>
                <div class="map-placeholder">
                    <i class="fas fa-map-marked-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h4>Interactive Map View</h4>
                    <p>This feature shows blood drives on an interactive map.</p>
                    <p>In a full implementation, this would integrate with Google Maps API.</p>
                    <button class="btn btn-primary" onclick="showView('grid')">
                        <i class="fas fa-th"></i> Switch to Grid View
                    </button>
                </div>
            </div>
        </div>
        
        <!-- List View -->
        <div id="list-view" class="view-content" style="display: none;">
            <div class="map-view">
                <h3><i class="fas fa-list"></i> Blood Drives List</h3>
                <?php if (count($blood_drives) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 1rem; text-align: left;">Drive</th>
                                    <th style="padding: 1rem; text-align: left;">Date & Time</th>
                                    <th style="padding: 1rem; text-align: left;">Location</th>
                                    <th style="padding: 1rem; text-align: left;">Organization</th>
                                    <th style="padding: 1rem; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blood_drives as $drive): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 1rem;">
                                            <strong><?php echo htmlspecialchars($drive['title']); ?></strong>
                                            <br>
                                            <span class="urgency-badge urgency-<?php echo $drive['urgency_level'] ?? 'medium'; ?>" style="font-size: 0.7rem;">
                                                <?php echo ucfirst($drive['urgency_level'] ?? 'Medium'); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <?php echo date('M j, Y', strtotime($drive['drive_date'])); ?><br>
                                            <small><?php echo date('g:i A', strtotime($drive['start_time'])); ?> - <?php echo date('g:i A', strtotime($drive['end_time'])); ?></small>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <?php echo htmlspecialchars($drive['location']); ?><br>
                                            <small><?php echo htmlspecialchars($drive['address'] ?? ''); ?></small>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <?php echo htmlspecialchars($drive['organizing_org']); ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: center;">
                                            <button class="btn-sm btn-success" onclick="registerForDrive(<?php echo $drive['id']; ?>)">
                                                <i class="fas fa-calendar-plus"></i> Register
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-list"></i>
                        <h3>No Blood Drives Found</h3>
                        <p>No upcoming blood drives match your current filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Featured Drive Section -->
        <?php if (count($blood_drives) > 0): ?>
            <div class="featured-drive">
                <div class="featured-badge">
                    <i class="fas fa-star"></i> Featured Drive
                </div>
                <h2 style="margin-top: 0;"><?php echo htmlspecialchars($blood_drives[0]['title']); ?></h2>
                <p style="font-size: 1.1rem; margin-bottom: 1.5rem; opacity: 0.9;">
                    <?php echo htmlspecialchars($blood_drives[0]['description'] ?? 'Join our featured blood donation drive and make a difference!'); ?>
                </p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div>
                        <strong><i class="fas fa-calendar"></i> Date</strong><br>
                        <?php echo date('F j, Y', strtotime($blood_drives[0]['drive_date'])); ?>
                    </div>
                    <div>
                        <strong><i class="fas fa-clock"></i> Time</strong><br>
                        <?php echo date('g:i A', strtotime($blood_drives[0]['start_time'])); ?> - <?php echo date('g:i A', strtotime($blood_drives[0]['end_time'])); ?>
                    </div>
                    <div>
                        <strong><i class="fas fa-map-marker-alt"></i> Location</strong><br>
                        <?php echo htmlspecialchars($blood_drives[0]['location']); ?>
                    </div>
                    <div>
                        <strong><i class="fas fa-building"></i> Organizer</strong><br>
                        <?php echo htmlspecialchars($blood_drives[0]['organizing_org']); ?>
                    </div>
                </div>
                <button class="btn" style="background: white; color: #c00; padding: 1rem 2rem; font-size: 1.1rem;" onclick="registerForDrive(<?php echo $blood_drives[0]['id']; ?>)">
                    <i class="fas fa-calendar-plus"></i> Register for Featured Drive
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Additional Information -->
        <div class="recent-section" style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            <h2><i class="fas fa-info-circle"></i> Why Donate at Blood Drives?</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="tip-card">
                        <h4><i class="fas fa-users"></i> Community Impact</h4>
                        <p>Blood drives bring together communities to support local hospitals and patients in need.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="tip-card">
                        <h4><i class="fas fa-convenience"></i> Convenient</h4>
                        <p>Multiple locations and flexible timings make it easy to donate blood around your schedule.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="tip-card">
                        <h4><i class="fas fa-gift"></i> Special Benefits</h4>
                        <p>Many drives offer refreshments, donor recognition, and sometimes small thank-you gifts.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // View toggle functionality
        function showView(viewType) {
            // Hide all views
            document.querySelectorAll('.view-content').forEach(view => {
                view.style.display = 'none';
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.view-option').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected view and activate button
            document.getElementById(viewType + '-view').style.display = 'block';
            event.target.classList.add('active');
        }
        
        // Drive registration function
        function registerForDrive(driveId) {
            if (confirm('Would you like to register for this blood drive? We will send you reminders and updates.')) {
                // In a real implementation, this would make an AJAX call to register the user
                alert('Registration feature would be implemented here! Drive ID: ' + driveId);
                // Example: fetch(`register_drive.php?drive_id=${driveId}`)
            }
        }
        
        // Share drive function
        function shareDrive(driveTitle) {
            if (navigator.share) {
                navigator.share({
                    title: 'Blood Drive: ' + driveTitle,
                    text: 'Check out this blood donation drive!',
                    url: window.location.href
                });
            } else {
                // Fallback for browsers that don't support Web Share API
                alert('Share this drive: ' + driveTitle);
            }
        }
        
        // Get directions function
        function getDirections(address) {
            const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(address)}`;
            window.open(mapsUrl, '_blank');
        }
        
        // Auto-refresh filters when date is selected
        document.querySelector('input[name="date"]').addEventListener('change', function() {
            if (this.value) {
                document.querySelector('.filter-form').submit();
            }
        });
    </script>
</body>
</html>