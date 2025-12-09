<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate Blood - BloodLife</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

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

        body {
            background: linear-gradient(135deg, #fce4e4 0%, #f5f7fa 100%);
            min-height: 100vh;
            color: var(--secondary);
            line-height: 1.6;
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
            text-decoration: none;
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

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        /* Donation Form */
        .donation-form {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-gray);
        }

        .form-header i {
            font-size: 2rem;
            color: var(--primary);
        }

        .form-header h3 {
            font-size: 1.8rem;
            color: var(--secondary);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
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
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-section {
            margin: 2.5rem 0;
            padding: 1.5rem;
            background: #f9f9f9;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }

        .form-section h4 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .radio-group {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.5rem;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .radio-option input {
            width: auto;
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            margin-top: 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
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
            gap: 2rem;
        }

        .eligibility-card, .info-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .card-header i {
            font-size: 1.8rem;
            color: var(--primary);
        }

        .card-header h3 {
            font-size: 1.5rem;
            color: var(--secondary);
        }

        .eligibility-status {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            background: var(--success);
            color: white;
        }

        .eligibility-status.warning {
            background: var(--warning);
        }

        .eligibility-status.danger {
            background: var(--danger);
        }

        .eligibility-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .requirements-list {
            list-style: none;
        }

        .requirements-list li {
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .requirements-list li:last-child {
            border-bottom: none;
        }

        .requirements-list li i {
            color: var(--success);
        }

        .requirements-list li.ineligible i {
            color: var(--danger);
        }

        .progress-container {
            margin: 1.5rem 0;
        }

        .progress-bar {
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 5px;
            transition: width 0.5s ease;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .info-card ul {
            list-style: none;
        }

        .info-card li {
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .info-card li:last-child {
            border-bottom: none;
        }

        .info-card li i {
            color: var(--primary);
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
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            nav ul {
                gap: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
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
                        <li><a href="donation_history.php">Donation History</a></li>
                        <li><a href="profile.php">Profile</a></li>
                    </ul>
                </nav>
                
                    <a href="../homepage.php" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h1>Schedule Blood Donation</h1>
                <p>Book your appointment to donate blood and save lives</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Donation Form -->
            <div class="donation-form">
                <div class="form-header">
                    <i class="fas fa-tint"></i>
                    <h3>Donation Information</h3>
                </div>

                <form id="donationForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bloodGroup" class="required">Blood Group</label>
                            <div class="input-group">
                                <i class="fas fa-tint"></i>
                                <select id="bloodGroup" name="bloodGroup" required>
                                    <option value="">Select Blood Group</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="quantity" class="required">Quantity (ml)</label>
                            <div class="input-group">
                                <i class="fas fa-weight"></i>
                                <input type="number" id="quantity" name="quantity" min="350" max="500" value="450" required>
                            </div>
                            <small style="color: var(--gray); display: block; margin-top: 5px;">Standard donation is 450ml</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="donationDate" class="required">Donation Date</label>
                            <div class="input-group">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date" id="donationDate" name="donationDate" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="donationType" class="required">Donation Type</label>
                            <div class="input-group">
                                <i class="fas fa-hand-holding-medical"></i>
                                <select id="donationType" name="donationType" required>
                                    <option value="">Select Type</option>
                                    <option value="whole_blood">Whole Blood</option>
                                    <option value="platelets">Platelets</option>
                                    <option value="plasma">Plasma</option>
                                    <option value="double_red">Double Red Cells</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="donationCenter" class="required">Donation Center</label>
                        <div class="input-group">
                            <i class="fas fa-map-marker-alt"></i>
                            <select id="donationCenter" name="donationCenter" required>
                                <option value="" disabled selected>Please select a donation center</option>
                                <option value="kathmandu">Kathmandu Central Blood Bank</option>
                                <option value="lalitpur">Lalitpur Community Hospital</option>
                                <option value="bhaktapur">Bhaktapur Medical Center</option>
                                <option value="other">Other Location</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4><i class="fas fa-heartbeat"></i> Health Screening</h4>
                        <p style="margin-bottom: 1rem; color: var(--gray);">Please answer these health screening questions honestly.</p>

                        <div class="form-group">
                            <label>Have you had any tattoos or piercings in the last 3 months?</label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" id="tattooNo" name="tattoo" value="no" checked>
                                    <label for="tattooNo">No</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="tattooYes" name="tattoo" value="yes">
                                    <label for="tattooYes">Yes</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Have you been sick with fever, cold, or flu in the last 2 weeks?</label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" id="sicknessNo" name="sickness" value="no" checked>
                                    <label for="sicknessNo">No</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="sicknessYes" name="sickness" value="yes">
                                    <label for="sicknessYes">Yes</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="healthConditions">Current Health Conditions</label>
                            <textarea id="healthConditions" name="healthConditions" placeholder="List any current health issues or conditions..."></textarea>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Submit Donation Request
                    </button>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Eligibility Card -->
                <div class="eligibility-card">
                    <div class="card-header">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>Eligibility Status</h3>
                    </div>
                    <div class="eligibility-status">
                        <div class="eligibility-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Eligible to Donate</h3>
                        <p>You meet all requirements for blood donation</p>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 85%"></div>
                        </div>
                        <div class="progress-text">
                            <span>Last donation: 4 months ago</span>
                            <span>Next eligible: Now</span>
                        </div>
                    </div>
                    <h4>Requirements</h4>
                    <ul class="requirements-list">
                        <li><i class="fas fa-check"></i> Age: 18-65 years</li>
                        <li><i class="fas fa-check"></i> Weight: ≥50 kg</li>
                        <li><i class="fas fa-check"></i> 3+ months since last donation</li>
                        <li><i class="fas fa-check"></i> Good general health</li>
                        <li><i class="fas fa-check"></i> No flu/cold symptoms</li>
                        <li><i class="fas fa-check"></i> No recent tattoos/piercings</li>
                    </ul>
                </div>

                <!-- Info Card -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i>
                        <h3>Donation Tips</h3>
                    </div>
                    <ul>
                        <li><i class="fas fa-check"></i> Get a good night's sleep</li>
                        <li><i class="fas fa-check"></i> Eat a healthy meal before donating</li>
                        <li><i class="fas fa-check"></i> Drink plenty of water</li>
                        <li><i class="fas fa-check"></i> Avoid fatty foods</li>
                        <li><i class="fas fa-check"></i> Bring a photo ID</li>
                        <li><i class="fas fa-check"></i> Wear comfortable clothing</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>

            <div class="footer-bottom">
                <p>&copy; 2025 BloodLife. All rights reserved. </p>
            </div>
        </div>
    </footer>

    <script>
        // Set default date to today
        document.getElementById('donationDate').valueAsDate = new Date();
        
        // Form validation
        document.getElementById('donationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const bloodGroup = document.getElementById('bloodGroup').value;
            const quantity = document.getElementById('quantity').value;
            const donationDate = document.getElementById('donationDate').value;
            const donationType = document.getElementById('donationType').value;
            const donationCenter = document.getElementById('donationCenter').value;
            
            if (!bloodGroup || !quantity || !donationDate || !donationType || !donationCenter) {
                alert('Please fill in all required fields.');
                return;
            }
            
            if (quantity < 350 || quantity > 500) {
                alert('Please enter a valid quantity between 350ml and 500ml.');
                return;
            }
            
            // Show success message
            alert('Thank you for scheduling your blood donation! A confirmation will be sent to you shortly.');
            
            // In a real application, you would submit the form data to a server here
            // For now, we'll just reset the form
            this.reset();
            document.getElementById('donationDate').valueAsDate = new Date();
        });
        
        // Real-time quantity indicator
        document.getElementById('quantity').addEventListener('input', function() {
            const quantity = parseInt(this.value);
            let indicator = document.getElementById('quantityIndicator');
            
            if (!indicator) {
                indicator = document.createElement('small');
                indicator.id = 'quantityIndicator';
                indicator.style.marginLeft = '0.5rem';
                indicator.style.fontWeight = '600';
                this.parentNode.appendChild(indicator);
            }
            
            if (quantity < 350) {
                indicator.textContent = '❌ Below minimum';
                indicator.style.color = 'var(--danger)';
            } else if (quantity > 500) {
                indicator.textContent = '❌ Above maximum';
                indicator.style.color = 'var(--danger)';
            } else if (quantity === 450) {
                indicator.textContent = '✅ Standard donation';
                indicator.style.color = 'var(--success)';
            } else {
                indicator.textContent = '⚠️ Non-standard amount';
                indicator.style.color = 'var(--warning)';
            }
        });
        
        // Trigger quantity indicator on page load
        document.getElementById('quantity').dispatchEvent(new Event('input'));
    </script>
</body>
</html>