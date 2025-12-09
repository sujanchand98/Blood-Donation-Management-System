<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BloodLife - Donate Blood, Save Lives</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #c62828;
            --primary-dark: #b71c1c;
            --primary-light: #ff5f52;
            --secondary: #263238;
            --accent: #ffab00;
            --light: #f5f5f5;
            --gray: #757575;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
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
            position: sticky;
            top: 0;
            z-index: 100;
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

        nav a:hover {
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

        /* Hero Section */
        .hero {
            padding: 4rem 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path fill="%23c62828" opacity="0.1" d="M30,30 Q50,10 70,30 T90,50 T70,70 T50,90 T30,70 T10,50 T30,30 Z"/></svg>');
            background-size: 300px;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .hero-text h1 {
            font-size: 3.2rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .hero-text p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: var(--gray);
        }

        .hero-stats {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
        }

        .hero-image {
            text-align: center;
            position: relative;
        }

        .blood-drop-large {
            width: 300px;
            height: 400px;
            background: var(--primary);
            border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
            margin: 0 auto;
            position: relative;
            box-shadow: 0 10px 30px rgba(198, 40, 40, 0.3);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .blood-drop-large::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 30px;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
        }

        /* Features Section */
        .features {
            padding: 4rem 0;
            background: white;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.2rem;
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .feature-card {
            background: var(--light);
            border-radius: 15px;
            padding: 2.5rem 2rem;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--secondary);
        }

        .feature-card p {
            color: var(--gray);
            line-height: 1.6;
        }

        /* How It Works */
        .how-it-works {
            padding: 4rem 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .steps {
            display: flex;
            justify-content: space-between;
            margin-top: 3rem;
            position: relative;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 40px;
            left: 10%;
            width: 80%;
            height: 3px;
            background: var(--primary);
            z-index: 1;
        }

        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-number {
            width: 80px;
            height: 80px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.3);
        }

        .step h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--secondary);
        }

        .step p {
            color: var(--gray);
            max-width: 250px;
            margin: 0 auto;
        }

        /* Call to Action */
        .cta {
            padding: 5rem 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
        }

        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        .cta p {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .btn-light {
            background: white;
            color: var(--primary);
        }

        .btn-light:hover {
            background: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Footer */
        footer {
            background: var(--secondary);
            color: white;
            padding: 3rem 0 1.5rem;
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
            .hero-content {
                grid-template-columns: 1fr;
            }
            
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .steps {
                flex-direction: column;
                gap: 2rem;
            }
            
            .steps::before {
                display: none;
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
            
            .hero-text h1 {
                font-size: 2.5rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
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
                        <li><a href="homepage.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="login.php">Find Donors</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
                
                <div class="user-actions">
                    <a href="login.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Donate Blood, Save Lives</h1>
                    <p>Your single donation can help save up to three lives. Join thousands of donors who are making a difference in their communities every day.</p>
                    <div class="hero-stats">
                        <div class="stat">
                            <div class="stat-number">5,000+</div>
                            <div class="stat-label">Lives Saved</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number">2,500+</div>
                            <div class="stat-label">Active Donors</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number">50+</div>
                            <div class="stat-label">Donation Centers</div>
                        </div>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="blood-drop-large"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>How We Help</h2>
                <p>Our platform connects blood donors with those in need, making the process simple and efficient</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h3>Easy Donation</h3>
                    <p>Schedule blood donations at your convenience with our simple booking system</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Find Donors</h3>
                    <p>Quickly locate compatible blood donors in your area during emergencies</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Track Inventory</h3>
                    <p>Monitor blood supply levels across different blood types and locations</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Simple steps to become a lifesaver</p>
            </div>
            
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Register</h3>
                    <p>Create an account as a donor or seeker with your basic information</p>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Connect</h3>
                    <p>Donors can schedule donations, seekers can find compatible blood types</p>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Save Lives</h3>
                    <p>Your donation or request helps someone in critical need of blood</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Make a Difference?</h2>
            <p>Join our community of lifesavers today. Whether you want to donate blood or need to find donors, we're here to help.</p>
            <div class="cta-buttons">
                <a href="login.php?type=donor" class="btn btn-light">
                    <i class="fas fa-hand-holding-medical"></i> Become a Donor
                </a>
                <a href="login.php?type=seeker" class="btn btn-outline">
                    <i class="fas fa-search"></i> Find Blood
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h4>BloodLife</h4>
                    <p>Connecting donors with those in need. Your donation can save lives.</p>
                    <div class="social-links">
                        <a href="homepage.php"><i class="fab fa-facebook-f"></i></a>
                        <a href="homepage.php"><i class="fab fa-twitter"></i></a>
                        <a href="homepage.php"><i class="fab fa-instagram"></i></a>
                        <a href="homepage.php"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="homepage.php">Home</a></li>
                        <li><a href="login.php">Donate Blood</a></li>
                        <li><a href="#">Find Centers</a></li>
                        <li><a about.php="#">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Resources</h4>
                    <ul>
                        
                        <li><a href="homepage.php">Blog</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Contact Info</h4>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Kathmandu, Nepal</li>
                        <li><i class="fas fa-phone"></i> +977 1 4123456</li>
                        <li><i class="fas fa-envelope"></i> info@bloodlife.org</li>
                        <li><i class="fas fa-clock"></i> Mon-Fri: 8AM-6PM</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 BloodLife. All rights reserved.  </p>
            </div>
        </div>
    </footer>

    <script>
        // Simple animation for feature cards on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const featureCards = document.querySelectorAll('.feature-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            featureCards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s, transform 0.5s';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>