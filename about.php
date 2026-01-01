<?php
// about.php - About TechStore Page
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About TechStore - Premium Electronics Store</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="logo.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --accent: #28a745;
            --accent-dark: #218838;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #212529;
            --text-light: #6c757d;
            --border: #e9ecef;
            --shadow: 0 6px 20px rgba(0,0,0,0.08);
            --shadow-light: 0 4px 12px rgba(0,0,0,0.05);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.7;
            margin: 0;
            padding: 0;
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* Hero Section */
        .about-hero {
            text-align: center;
            padding: 4rem 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: var(--radius);
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .about-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1518709268805-4e9042af2176?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            opacity: 0.1;
        }

        .about-hero h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .about-hero p {
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto 2rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Section Styling */
        .section {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .section:hover {
            box-shadow: var(--shadow);
            transform: translateY(-3px);
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid var(--accent);
            display: inline-block;
        }

        .section-subtitle {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
            margin: 1.5rem 0 1rem;
        }

        /* Who We Are Section */
        .who-we-are {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .who-we-are-content {
            flex: 1;
        }

        .who-we-are-image {
            flex: 1;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .who-we-are-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: var(--transition);
        }

        .who-we-are-image:hover img {
            transform: scale(1.05);
        }

        /* Mission & Values */
        .mission-values {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        .mission-card, .values-card {
            background: var(--bg);
            padding: 2rem;
            border-radius: var(--radius);
            border-left: 4px solid var(--accent);
        }

        .mission-card {
            border-left-color: var(--primary);
        }

        .values-list {
            list-style: none;
            padding: 0;
        }

        .values-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .values-list li:last-child {
            border-bottom: none;
        }

        .values-list i {
            color: var(--accent);
            font-size: 1.25rem;
        }

        /* What We Offer */
        .offerings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .offering-card {
            background: var(--bg);
            padding: 1.5rem;
            border-radius: var(--radius);
            text-align: center;
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .offering-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
            border-color: var(--primary);
        }

        .offering-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        /* Contact Info */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem;
            background: var(--bg);
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .contact-item:hover {
            background: white;
            box-shadow: var(--shadow-light);
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .contact-details h4 {
            margin: 0 0 0.5rem 0;
            color: var(--text);
            font-weight: 600;
        }

        .contact-details p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* Business Hours Table */
        .hours-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: var(--bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-light);
        }

        .hours-table th,
        .hours-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .hours-table th {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        .hours-table tr:last-child td {
            border-bottom: none;
        }

        /* Team Section - 更新的样式 */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .team-card {
            background: var(--bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            text-align: center;
            padding: 2rem;
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .team-avatar {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
            border: 4px solid white;
        }

        .avatar-svg {
            width: 100%;
            height: 100%;
        }

        .team-info {
            padding: 0;
        }

        .team-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--text);
            font-weight: 600;
            font-size: 1.3rem;
        }

        .team-info .position {
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 0.75rem;
            display: block;
            font-size: 1rem;
        }

        .team-info p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Technology Stack */
        .tech-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .tech-item {
            background: var(--bg);
            padding: 0.75rem 1.25rem;
            border-radius: 25px;
            font-size: 0.9rem;
            color: var(--text);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .tech-item:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .who-we-are,
            .mission-values {
                grid-template-columns: 1fr;
            }
            
            .about-hero h1 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .section {
                padding: 1.5rem;
            }
            
            .section-title {
                font-size: 1.75rem;
            }
            
            .contact-grid,
            .offerings-grid {
                grid-template-columns: 1fr;
            }
            
            .about-hero {
                padding: 3rem 1rem;
            }
        }

        @media (max-width: 480px) {
            .about-container {
                padding: 1rem;
            }
            
            .about-hero h1 {
                font-size: 2rem;
            }
            
            .about-hero p {
                font-size: 1.1rem;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .team-avatar {
                width: 100px;
                height: 100px;
            }
        }

        /* Additional Styling */
        .highlight {
            color: var(--primary);
            font-weight: 600;
        }

        .btn-primary {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .text-center {
            text-align: center;
        }

        .mb-4 {
            margin-bottom: 2rem;
        }

        .mt-4 {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="about-container">
        <!-- Hero Section -->
        <section class="about-hero">
            <h1>About TechStore</h1>
            <p>Your trusted destination for premium electronics and cutting-edge technology</p>
            <a href="products.php" class="btn-primary">Explore Our Products</a>
        </section>

        <!-- Who We Are -->
        <section class="section">
            <h2 class="section-title">Who We Are</h2>
            <div class="who-we-are">
                <div class="who-we-are-content">
                    <p><strong>TechStore</strong> is a specialized e-commerce platform dedicated to providing technology enthusiasts with access to the latest and most innovative electronic products. We curate a premium selection of gadgets, devices, and accessories that enhance your digital lifestyle.</p>
                    <p>Our mission is to bridge the gap between cutting-edge technology and everyday consumers, offering quality products through an intuitive, user-friendly shopping experience. We believe that everyone should have access to technology that makes life better, smarter, and more connected.</p>
                    <p>Founded on the principles of innovation, reliability, and customer satisfaction, TechStore has grown from a small startup to a trusted name in the electronics retail space. We're passionate about technology and committed to helping our customers find exactly what they need.</p>
                </div>
                <div class="who-we-are-image">
                    <img src="https://images.unsplash.com/photo-1558494949-ef010cbdcc31?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="TechStore Office">
                </div>
            </div>
        </section>

        <!-- Mission & Values -->
        <section class="section">
            <h2 class="section-title">Our Mission & Values</h2>
            <div class="mission-values">
                <div class="mission-card">
                    <h3 class="section-subtitle">🎯 Our Mission</h3>
                    <p>To democratize access to premium technology by providing a curated selection of high-quality electronic products through a seamless, secure, and enjoyable online shopping platform that prioritizes customer satisfaction above all else.</p>
                </div>
                <div class="values-card">
                    <h3 class="section-subtitle">💎 Our Values</h3>
                    <ul class="values-list">
                        <li><i class="fas fa-check-circle"></i> <strong>Innovation First</strong> - Always seeking the latest technology</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Customer-Centric</strong> - Your satisfaction is our priority</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Quality Assurance</strong> - Only premium, verified products</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Transparency</strong> - Clear pricing and honest information</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Community Focus</strong> - Building tech enthusiasts community</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- What We Offer -->
        <section class="section">
            <h2 class="section-title">What We Offer</h2>
            <div class="offerings-grid">
                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4>Smartphones & Tablets</h4>
                    <p>Latest models from Apple, Samsung, Google and more with competitive pricing and warranty</p>
                </div>
                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h4>Computers & Laptops</h4>
                    <p>High-performance laptops, desktops, and accessories for work, gaming, and creativity</p>
                </div>
                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-headphones"></i>
                    </div>
                    <h4>Audio & Accessories</h4>
                    <p>Premium headphones, speakers, and audio equipment from top brands worldwide</p>
                </div>
                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <h4>Gaming Gear</h4>
                    <p>Complete gaming setups including consoles, peripherals, and gaming accessories</p>
                </div>
                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h4>Fast Delivery</h4>
                    <p>Reliable shipping with tracking and secure packaging to protect your investments</p>
                </div>
                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Secure Shopping</h4>
                    <p>Encrypted transactions and secure payment gateways for worry-free shopping</p>
                </div>
            </div>
        </section>

        <!-- Contact & Location -->
        <section class="section">
            <h2 class="section-title">Our Headquarters</h2>
            <p>Based in the heart of the technology district, we serve customers nationwide with fast, reliable service and expert support.</p>
            
            <div class="contact-grid">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h4>Headquarters Address</h4>
                        <p>Suite 1200, 456 Tech Boulevard<br>
                           San Francisco, CA 94107<br>
                           United States</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-details">
                        <h4>Email Address</h4>
                        <p>info@techstore.com<br>
                           support@techstore.com</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-details">
                        <h4>Contact Phone</h4>
                        <p>+1 (555) 123-4567 (Office)<br>
                           +1 (800) 987-6543 (Support)</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="contact-details">
                        <h4>Business Hours</h4>
                        <p>Monday - Friday: 9:00 AM - 8:00 PM<br>
                           Saturday: 10:00 AM - 6:00 PM<br>
                           Sunday: 11:00 AM - 5:00 PM</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Who We Serve -->
        <section class="section">
            <h2 class="section-title">Who We Serve</h2>
            <div class="who-we-are">
                <div class="who-we-are-content">
                    <h3 class="section-subtitle">Primary Customers</h3>
                    <ul class="values-list">
                        <li><i class="fas fa-user-graduate"></i> <strong>Tech Enthusiasts</strong> - Early adopters and gadget lovers</li>
                        <li><i class="fas fa-laptop-code"></i> <strong>Professionals</strong> - Remote workers and digital creators</li>
                        <li><i class="fas fa-gamepad"></i> <strong>Gamers</strong> - Casual and competitive gaming communities</li>
                        <li><i class="fas fa-graduation-cap"></i> <strong>Students</strong> - Technology for education and productivity</li>
                    </ul>
                </div>
                <div class="who-we-are-content">
                    <h3 class="section-subtitle">Secondary Customers</h3>
                    <ul class="values-list">
                        <li><i class="fas fa-gift"></i> <strong>Gift Shoppers</strong> - Finding perfect tech gifts for loved ones</li>
                        <li><i class="fas fa-briefcase"></i> <strong>Businesses</strong> - Bulk purchases for offices and teams</li>
                        <li><i class="fas fa-home"></i> <strong>Home Users</strong> - Smart home devices and everyday electronics</li>
                        <li><i class="fas fa-camera"></i> <strong>Content Creators</strong> - Equipment for streaming and production</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Meet Our Team - 修正版 -->
        <section class="section">
            <h2 class="section-title">Meet Our Team</h2>
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-avatar">
                        <svg class="avatar-svg" viewBox="0 0 100 100">
                            <defs>
                                <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#007bff"/>
                                    <stop offset="100%" stop-color="#0056b3"/>
                                </linearGradient>
                            </defs>
                            <circle cx="50" cy="50" r="45" fill="url(#grad1)"/>
                            <text x="50" y="58" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="28" font-weight="bold">AC</text>
                        </svg>
                    </div>
                    <div class="team-info">
                        <h4>Alex Chen</h4>
                        <span class="position">Founder & CEO</span>
                        <p>Technology visionary with 15+ years in electronics retail and e-commerce</p>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-avatar">
                        <svg class="avatar-svg" viewBox="0 0 100 100">
                            <defs>
                                <linearGradient id="grad2" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#28a745"/>
                                    <stop offset="100%" stop-color="#218838"/>
                                </linearGradient>
                            </defs>
                            <circle cx="50" cy="50" r="45" fill="url(#grad2)"/>
                            <text x="50" y="58" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="28" font-weight="bold">SJ</text>
                        </svg>
                    </div>
                    <div class="team-info">
                        <h4>Sarah Johnson</h4>
                        <span class="position">Head of Operations</span>
                        <p>Ensures seamless customer experiences and efficient supply chain management</p>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-avatar">
                        <svg class="avatar-svg" viewBox="0 0 100 100">
                            <defs>
                                <linearGradient id="grad3" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#dc3545"/>
                                    <stop offset="100%" stop-color="#c82333"/>
                                </linearGradient>
                            </defs>
                            <circle cx="50" cy="50" r="45" fill="url(#grad3)"/>
                            <text x="50" y="58" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="28" font-weight="bold">MR</text>
                        </svg>
                    </div>
                    <div class="team-info">
                        <h4>Michael Rodriguez</h4>
                        <span class="position">Lead Developer</span>
                        <p>Full-stack developer passionate about creating intuitive shopping platforms</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Technology Stack -->
        <section class="section">
            <h2 class="section-title">Built With Modern Technology</h2>
            <p>TechStore is developed using a robust, modern technology stack that ensures reliability, security, and performance:</p>
            
            <div class="tech-stack">
                <span class="tech-item">PHP 8.1+</span>
                <span class="tech-item">MySQL 8.0</span>
                <span class="tech-item">HTML5 & CSS3</span>
                <span class="tech-item">JavaScript (ES6+)</span>
                <span class="tech-item">InfinityFree Hosting</span>
                <span class="tech-item">Responsive Design</span>
                <span class="tech-item">SSL Encryption</span>
                <span class="tech-item">AJAX & Fetch API</span>
                <span class="tech-item">Font Awesome</span>
                <span class="tech-item">Google Fonts</span>
            </div>
            
            <div class="mt-4">
                <p>Our platform is regularly updated with security patches and performance enhancements to ensure your shopping experience is always smooth, secure, and up-to-date with the latest web standards.</p>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="section text-center">
            <h2 class="section-title">Ready to Explore?</h2>
            <p class="mb-4">Discover our curated collection of premium electronics and experience the TechStore difference.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="products.php" class="btn-primary">Browse Products</a>
                <a href="contact.php" class="btn-primary" style="background: var(--accent);">Contact Us</a>
                <a href="index.php" class="btn-primary" style="background: var(--text-light);">Back to Home</a>
            </div>
        </section>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if(targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if(targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Add animation to sections on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if(entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(section);
            });

            // Add hover effects to team cards
            document.querySelectorAll('.team-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    const svg = this.querySelector('.avatar-svg');
                    if(svg) {
                        svg.style.transform = 'scale(1.1)';
                        svg.style.transition = 'transform 0.3s ease';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    const svg = this.querySelector('.avatar-svg');
                    if(svg) {
                        svg.style.transform = 'scale(1)';
                    }
                });
            });
        });
    </script>
</body>
</html>