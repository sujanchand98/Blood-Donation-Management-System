<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - BloodLife</title>
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

        /* Page Header */
        .page-header {
            padding: 4rem 0 2rem;
            text-align: center;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path fill="%23c62828" opacity="0.1" d="M30,30 Q50,10 70,30 T90,50 T70,70 T50,90 T30,70 T10,50 T30,30 Z"/></svg>');
            background-size: 300px;
        }

        .page-header h1 {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .page-header p {
            font-size: 1.2rem;
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }

        /* Main Content */
        .main-content {
            padding: 3rem 0;
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

        /* About Section */
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            margin-bottom: 4rem;
        }

        .about-text h2 {
            font-size: 2.2rem;
            color: var(--secondary);
            margin-bottom: 1.5rem;
        }

        .about-text p {
            margin-bottom: 1.5rem;
            color: var(--gray);
            line-height: 1.8;
        }

        .about-image {
            text-align: center;
        }

        .about-image img {
            max-width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        /* Mission & Vision */
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .mission-card, .vision-card {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s;
        }

        .mission-card:hover, .vision-card:hover {
            transform: translateY(-10px);
        }

        .mission-card {
            border-left: 5px solid var(--primary);
        }

        .vision-card {
            border-left: 5px solid var(--accent);
        }

        .card-icon {
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

        .vision-card .card-icon {
            background: var(--accent);
        }

        .mission-card h3, .vision-card h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: var(--secondary);
        }

        /* Team Section */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .team-member {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .team-member:hover {
            transform: translateY(-10px);
        }

        .member-image {
            height: 250px;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 5rem;
        }

        .member-info {
            padding: 1.5rem;
            text-align: center;
        }

        .member-info h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }

        .member-info p {
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .member-social {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .member-social a {
            width: 35px;
            height: 35px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            transition: all 0.3s;
        }

        .member-social a:hover {
            background: var(--primary);
            color: white;
        }

        /* Contact Section */
        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        .contact-info {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .contact-info h3 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: var(--secondary);
        }

        .contact-details {
            margin-bottom: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .contact-text h4 {
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
            color: var(--secondary);
        }

        .contact-text p {
            color: var(--gray);
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            width: 45px;
            height: 45px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        .contact-form {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .contact-form h3 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
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
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.3);
        }

        /* Map Section */
        .map-section {
            margin: 4rem 0;
        }

        .map-container {
            height: 400px;
            background: var(--light);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 1.2rem;
        }

        /* FAQ Section */
        .faq-section {
            margin: 4rem 0;
        }

        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: white;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .faq-question {
            padding: 1.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--secondary);
        }

        .faq-question i {
            transition: transform 0.3s;
        }

        .faq-answer {
            padding: 0 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s, padding 0.3s;
            color: var(--gray);
        }

        .faq-item.active .faq-answer {
            padding: 0 1.5rem 1.5rem;
            max-height: 500px;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
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
            .about-content, .mission-vision, .contact-container {
                grid-template-columns: 1fr;
            }
            
            .team-grid {
                grid-template-columns: repeat(2, 1fr);
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
            
            .page-header h1 {
                font-size: 2.5rem;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
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
                        <li><a href="homepage.php">Home</a></li>
                        <li><a href="about.php" class="active">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="donation_centers.html">Donation Centers</a></li>
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>About BloodLife</h1>
            <p>Learn about our mission, vision, and the team behind our life-saving platform</p>
        </div>
    </section>

    <!-- Main Content -->
    <section class="main-content">
        <div class="container">
            <!-- About Section -->
            <div class="about-content">
                <div class="about-text">
                    <h2>Our Story</h2>
                    <p>BloodLife was founded in 2018 with a simple yet powerful mission: to create a reliable connection between blood donors and those in need. Our platform was born out of a personal experience when our founder struggled to find a compatible blood donor for a family member during an emergency.</p>
                    <p>Since then, we've grown into a trusted platform with thousands of registered donors and seekers across the country. We've facilitated over 5,000 successful blood donations and helped save countless lives.</p>
                    <p>Our team is composed of healthcare professionals, technology experts, and volunteers who are passionate about making a difference in their communities. We believe that no one should struggle to find blood during emergencies.</p>
                </div>
                <div class="about-image">
                    <div style="width: 100%; height: 400px; background: var(--primary); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                        <i class="fas fa-tint" style="font-size: 5rem; margin-right: 1rem;"></i>
                        <div>BloodLife Team</div>
                    </div>
                </div>
            </div>

            <!-- Mission & Vision -->
            <div class="section-title">
                <h2>Our Mission & Vision</h2>
                <p>What drives us to make a difference every day</p>
            </div>

            <div class="mission-vision">
                <div class="mission-card">
                    <div class="card-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Our Mission</h3>
                    <p>To create a seamless connection between blood donors and recipients, ensuring timely access to safe blood for everyone in need through technology and community engagement.</p>
                </div>
                
                <div class="vision-card">
                    <div class="card-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p>A world where no life is lost due to unavailability of blood, and every community has a sustainable blood donation ecosystem that serves all its members equally.</p>
                </div>
            </div>

            <!-- Team Section -->
            <div class="section-title">
                <h2>Meet Our Team</h2>
                <p>The dedicated people behind BloodLife</p>
            </div>

            <div class="team-grid">
                <div class="team-member">
                    <div class="member-image">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="member-info">
                        <h3>Mr. Sujan Chand</h3>
                        <p>With 15 years of experience in transfusion medicine, Dr. Johnson ensures all our processes meet the highest medical standards.</p>
                        <div class="member-social">
                            <a href="homepage.php"><i class="fab fa-linkedin-in"></i></a>
                            <a href="homepage.php"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- Contact Section -->
            <div class="section-title">
                <h2>Contact Us</h2>
                <p>We'd love to hear from you. Get in touch with any questions or feedback.</p>
            </div>

            <div class="contact-container">
                <div class="contact-info">
                    <h3>Get In Touch</h3>
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-text">
                                <h4>Our Location</h4>
                                <p>123 Health Street, Medical District<br>Kathmandu, Nepal</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-text">
                                <h4>Phone Number</h4>
                                <p>+977 1 4123456<br>+977 9801234567 (Emergency)</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-text">
                                <h4>Email Address</h4>
                                <p>info@bloodlife.org<br>support@bloodlife.org</p>
                            </div>
                        </div>
                    </div>
                    
                    <h3>Follow Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="contact-form">
                    <h3>Send Message</h3>
                    <form id="contactForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <div class="input-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="firstName" name="firstName" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <div class="input-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="lastName" name="lastName" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <div class="input-group">
                                <i class="fas fa-tag"></i>
                                <input type="text" id="subject" name="subject" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="How can we help you?" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn submit-btn">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>

            <!-- Map Section -->
            <!-- <div class="map-section">
                <div class="section-title">
                    <h2>Find Us</h2>
                    <p>Visit our headquarters or find a donation center near you</p>
                </div>
                
                <div class="map-container">
                    <div style="text-align: center;">
                        <i class="fas fa-map-marked-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>Interactive Map Would Appear Here</p>
                        <p style="font-size: 1rem; margin-top: 1rem;">123 Health Street, Medical District, Kathmandu, Nepal</p>
                    </div>
                </div>
            </div> -->

            <!-- FAQ Section -->
            <div class="faq-section">
                <div class="section-title">
                    <h2>Frequently Asked Questions</h2>
                    <p>Quick answers to common questions about BloodLife</p>
                </div>
                
                <div class="faq-container">
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>How does BloodLife ensure the safety of blood donations?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We partner with certified blood banks and medical facilities that follow strict screening and testing protocols. All donated blood is tested for infectious diseases before being made available to recipients.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Is there any cost to use BloodLife?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>BloodLife is completely free for both donors and seekers. Our platform is supported by donations, grants, and partnerships with healthcare organizations.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>How quickly can I find a blood donor in an emergency?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>In urgent situations, our emergency matching system can connect you with compatible donors in your area within minutes. However, response times may vary based on donor availability and location.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Can I donate blood if I have a medical condition?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Eligibility depends on the specific condition. Our platform includes a pre-screening questionnaire, but final eligibility is determined by medical professionals at the donation center based on established guidelines.</p>
                        </div>
                    </div>
                </div>
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
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="contact.html">Contact</a></li>
                        <li><a href="donation_centers.html">Donation Centers</a></li>
                        <li><a href="eligibility.html">Eligibility</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Resources</h4>
                    <ul>
                        <li><a href="blood_types.html">Blood Types</a></li>
                        <li><a href="donation_process.html">Donation Process</a></li>
                        <li><a href="faq.html">FAQs</a></li>
                        <li><a href="blog.html">Blog</a></li>
                        <li><a href="contact.html">Contact Us</a></li>
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
                <p>&copy; 2023 BloodLife. All rights reserved. | Designed with <i class="fas fa-heart" style="color: var(--primary);"></i> for humanity</p>
            </div>
        </div>
    </footer>

    <script>
        // FAQ functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentNode;
                item.classList.toggle('active');
            });
        });

        // Contact form submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            if (!firstName || !lastName || !email || !subject || !message) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Show success message
            alert('Thank you for your message! We will get back to you within 24 hours.');
            
            // Reset form
            this.reset();
        });

        // Simple animation for team members on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const teamMembers = document.querySelectorAll('.team-member');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            teamMembers.forEach(member => {
                member.style.opacity = '0';
                member.style.transform = 'translateY(20px)';
                member.style.transition = 'opacity 0.5s, transform 0.5s';
                observer.observe(member);
            });
        });
    </script>
</body>
</html>