<?php
// Define allowed access for includes
define('ALLOWED_ACCESS', true);
require_once dirname(__DIR__) . '/config.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - eSIM Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Files - Organized by component -->
    <!--<link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">-->
    <link rel="stylesheet" href="assets/css/navigation.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/about.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/footer.css?v=<?= time() ?>">
    
    <meta name="theme-color" content="#4f46e5">
    <meta name="description" content="Learn about eSIM Store - Your trusted partner for global connectivity solutions">
</head>
<body>

<!-- Theme Toggle -->
<button class="theme-toggle-floating" id="themeToggle" aria-label="Toggle theme">
    <i class="fas fa-moon" id="themeIcon"></i>
</button>

<!-- Navigation -->
<?php include dirname(__DIR__) . '/src/includes/navigation.php'; ?>

<!-- Main Content -->
<main class="main-content">
    <!-- Hero Section -->
    <section class="hero-section">
        <h1 class="hero-title">
            <span class="gradient-text">About eSIM Store</span>
            <span class="hero-subtitle">Your Global Connectivity Partner</span>
        </h1>
        <p class="hero-description">We're dedicated to making travel connectivity simple, affordable, and accessible for everyone, everywhere.</p>
    </section>

    <!-- About Content -->
    <section class="about-section">
        <div class="about-container">
            <!-- Company Overview -->
            <div class="about-card">
                <div class="about-card-header">
                    <div class="about-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h2>Our Company</h2>
                </div>
                <div class="about-card-content">
                    <p>eSIM Store is a leading digital connectivity provider specializing in eSIM technology for travelers and digital nomads worldwide. Founded with a vision to eliminate roaming charges and connectivity barriers, we've been serving customers across the globe since 2020.</p>
                    
                    <p>Our mission is simple: to provide instant, reliable, and affordable mobile connectivity wherever your journey takes you. Through partnerships with major telecom operators worldwide, we offer comprehensive coverage across 190+ countries and territories.</p>
                </div>
            </div>

            <!-- What We Do -->
            <div class="about-card">
                <div class="about-card-header">
                    <div class="about-icon">
                        <i class="fas fa-sim-card"></i>
                    </div>
                    <h2>What We Do</h2>
                </div>
                <div class="about-card-content">
                    <h3>eSIM Solutions</h3>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> <strong>Local eSIMs:</strong> Country-specific data plans with local rates</li>
                        <li><i class="fas fa-check"></i> <strong>Regional eSIMs:</strong> Multi-country packages for regional travel</li>
                        <li><i class="fas fa-check"></i> <strong>Global eSIMs:</strong> Worldwide coverage for international travelers</li>
                        <li><i class="fas fa-check"></i> <strong>Unlimited Plans:</strong> Day-based unlimited data packages</li>
                    </ul>

                    <h3>Key Services</h3>
                    <div class="services-grid">
                        <div class="service-item">
                            <i class="fas fa-bolt"></i>
                            <h4>Instant Activation</h4>
                            <p>Get connected within minutes via QR code</p>
                        </div>
                        <div class="service-item">
                            <i class="fas fa-headset"></i>
                            <h4>24/7 Support</h4>
                            <p>Round-the-clock customer assistance</p>
                        </div>
                        <div class="service-item">
                            <i class="fas fa-shield-alt"></i>
                            <h4>Secure Transactions</h4>
                            <p>Safe and encrypted payment processing</p>
                        </div>
                        <div class="service-item">
                            <i class="fas fa-mobile-alt"></i>
                            <h4>Easy Management</h4>
                            <p>User-friendly dashboard for plan management</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How It Works -->
            <div class="about-card">
                <div class="about-card-header">
                    <div class="about-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h2>How Our eSIM Delivery Works</h2>
                </div>
                <div class="about-card-content">
                    <div class="process-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Choose Your Plan</h4>
                                <p>Browse and select the perfect eSIM package for your destination and data needs.</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Complete Payment</h4>
                                <p>Secure checkout with multiple payment options including credit cards and digital wallets.</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Receive eSIM Instantly</h4>
                                <p>Get your eSIM QR code and installation instructions via email within 2-5 minutes.</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h4>Install & Connect</h4>
                                <p>Scan the QR code on your device and get connected instantly upon arrival.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Our Values -->
            <div class="about-card">
                <div class="about-card-header">
                    <div class="about-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h2>Our Values</h2>
                </div>
                <div class="about-card-content">
                    <div class="values-grid">
                        <div class="value-item">
                            <i class="fas fa-users"></i>
                            <h4>Customer First</h4>
                            <p>Every decision we make puts our customers' needs and satisfaction first.</p>
                        </div>
                        <div class="value-item">
                            <i class="fas fa-lightbulb"></i>
                            <h4>Innovation</h4>
                            <p>We continuously improve our technology and services to stay ahead.</p>
                        </div>
                        <div class="value-item">
                            <i class="fas fa-handshake"></i>
                            <h4>Reliability</h4>
                            <p>Dependable connectivity and consistent service quality worldwide.</p>
                        </div>
                        <div class="value-item">
                            <i class="fas fa-dollar-sign"></i>
                            <h4>Affordability</h4>
                            <p>Competitive pricing without compromising on quality or coverage.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team -->
            <div class="about-card">
                <div class="about-card-header">
                    <div class="about-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h2>Our Team</h2>
                </div>
                <div class="about-card-content">
                    <p>Our diverse team of connectivity experts, engineers, and customer success specialists work around the clock to ensure you stay connected wherever you go. With backgrounds in telecommunications, technology, and travel, we understand the challenges of staying connected while exploring the world.</p>
                    
                    <div class="team-stats">
                        <div class="stat-item">
                            <div class="stat-number">500K+</div>
                            <div class="stat-label">Happy Customers</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">190+</div>
                            <div class="stat-label">Countries Covered</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">99.9%</div>
                            <div class="stat-label">Uptime Guarantee</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Customer Support</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="about-card">
                <div class="about-card-header">
                    <div class="about-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h2>Get In Touch</h2>
                </div>
                <div class="about-card-content">
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h4>Email Support</h4>
                                <p>support@esimstore.com</p>
                                <small>Response within 24 hours</small>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fab fa-whatsapp"></i>
                            <div>
                                <h4>WhatsApp Support</h4>
                                <p>+62 813-2552-5646</p>
                                <small>Available 24/7</small>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>Business Hours</h4>
                                <p>24/7 Online Service</p>
                                <small>Instant eSIM delivery</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include dirname(__DIR__) . '/src/includes/footer.php'; ?>

<!-- JavaScript Files - Organized by component -->
<script src="assets/js/about.js?v=<?= time() ?>"></script>
<script src="assets/js/footer.js?v=<?= time() ?>"></script>
</body>
</html>