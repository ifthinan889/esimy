<?php
// Define allowed access for includes
define('ALLOWED_ACCESS', true);

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
    <title>Privacy Policy - eSIM Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Files - Organized by component -->
    <link rel="stylesheet" href="assets/css/about.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/navigation.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/privacy.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/footer.css?v=<?= time() ?>">
    
    <meta name="theme-color" content="#4f46e5">
    <meta name="description" content="Privacy Policy for eSIM Store - How we collect, use, and protect your personal information">
</head>
<body>

<!-- Theme Toggle -->
<button class="theme-toggle-floating" id="themeToggle" aria-label="Toggle theme">
    <i class="fas fa-moon" id="themeIcon"></i>
</button>

<!-- Navigation -->
<?php include dirname(__DIR__) . '/src/includes/navigation.php'; ?>

<main class="main-content">
    <section class="legal-section">
        <div class="legal-container">
            <div class="legal-header">
                <h1 class="legal-title">
                    <i class="fas fa-shield-alt"></i>
                    <span class="gradient-text">Privacy Policy</span>
                </h1>
                <p class="last-updated">Last updated: <?= date('F d, Y') ?></p>
                <p class="legal-subtitle">How we collect, use, and protect your personal information</p>
            </div>

            <div class="legal-content">
                <!-- Quick Summary -->
                <div class="legal-card highlight policy-summary">
                    <h2><i class="fas fa-info-circle"></i> Quick Summary</h2>
                    <p>We respect your privacy and are committed to protecting your personal data. We only collect information necessary to provide our eSIM services, never sell your data to third parties, and implement strong security measures to keep your information safe.</p>
                    <div class="summary-points">
                        <div class="summary-point">
                            <i class="fas fa-check-circle"></i>
                            <span>We don't sell your data</span>
                        </div>
                        <div class="summary-point">
                            <i class="fas fa-shield-alt"></i>
                            <span>Bank-level security</span>
                        </div>
                        <div class="summary-point">
                            <i class="fas fa-user-check"></i>
                            <span>You control your data</span>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-database"></i> Information We Collect</h2>
                    <h3>1.1 Personal Information</h3>
                    <p>When you use our eSIM services, we may collect the following personal information:</p>
                    <ul class="info-list">
                        <li><i class="fas fa-user"></i> <strong>Identity:</strong> Name and contact information (email address, phone number)</li>
                        <li><i class="fas fa-credit-card"></i> <strong>Payment:</strong> Payment information (processed securely through our payment partners)</li>
                        <li><i class="fas fa-mobile-alt"></i> <strong>Device:</strong> Device information (IMEI, device model for eSIM compatibility)</li>
                        <li><i class="fas fa-chart-line"></i> <strong>Usage:</strong> Usage data (data consumption, connection logs)</li>
                    </ul>

                    <h3>1.2 Automatically Collected Information</h3>
                    <ul class="info-list">
                        <li><i class="fas fa-map-marker-alt"></i> IP address and location information</li>
                        <li><i class="fas fa-browser"></i> Browser type and operating system</li>
                        <li><i class="fas fa-mouse-pointer"></i> Website usage patterns and preferences</li>
                        <li><i class="fas fa-cookie-bite"></i> Cookies and similar tracking technologies</li>
                    </ul>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-cogs"></i> How We Use Your Information</h2>
                    <p>We use your personal information for the following purposes:</p>
                    <div class="usage-grid">
                        <div class="usage-item">
                            <i class="fas fa-sim-card"></i>
                            <h4>Service Delivery</h4>
                            <p>To provide, activate, and manage your eSIM services</p>
                        </div>
                        <div class="usage-item">
                            <i class="fas fa-headset"></i>
                            <h4>Customer Support</h4>
                            <p>To respond to your inquiries and provide technical assistance</p>
                        </div>
                        <div class="usage-item">
                            <i class="fas fa-credit-card"></i>
                            <h4>Payment Processing</h4>
                            <p>To process transactions and send receipts</p>
                        </div>
                        <div class="usage-item">
                            <i class="fas fa-envelope"></i>
                            <h4>Communication</h4>
                            <p>To send service updates, promotional offers, and important notices</p>
                        </div>
                        <div class="usage-item">
                            <i class="fas fa-shield-alt"></i>
                            <h4>Security</h4>
                            <p>To detect and prevent fraud, abuse, and security threats</p>
                        </div>
                        <div class="usage-item">
                            <i class="fas fa-chart-bar"></i>
                            <h4>Improvement</h4>
                            <p>To analyze usage patterns and improve our services</p>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-share-alt"></i> Information Sharing and Disclosure</h2>
                    <div class="no-sell-banner">
                        <i class="fas fa-ban"></i>
                        <div>
                            <h3>We Do Not Sell Your Data</h3>
                            <p>We do not sell, rent, or trade your personal information to third parties for their marketing purposes.</p>
                        </div>
                    </div>

                    <h3>Limited Sharing</h3>
                    <p>We may share your information in the following circumstances:</p>
                    <div class="sharing-grid">
                        <div class="sharing-item">
                            <i class="fas fa-handshake"></i>
                            <h4>Service Providers</h4>
                            <p>With trusted partners who help us operate our services (payment processors, telecom partners)</p>
                        </div>
                        <div class="sharing-item">
                            <i class="fas fa-gavel"></i>
                            <h4>Legal Requirements</h4>
                            <p>When required by law, court order, or government regulation</p>
                        </div>
                        <div class="sharing-item">
                            <i class="fas fa-building"></i>
                            <h4>Business Transfers</h4>
                            <p>In connection with a merger, acquisition, or sale of assets</p>
                        </div>
                        <div class="sharing-item">
                            <i class="fas fa-shield-alt"></i>
                            <h4>Protection</h4>
                            <p>To protect our rights, property, or safety, or that of our users</p>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-lock"></i> Data Security</h2>
                    <p>We implement industry-standard security measures to protect your personal information:</p>
                    <div class="security-features">
                        <div class="security-feature">
                            <i class="fas fa-certificate"></i>
                            <div>
                                <h4>SSL/TLS Encryption</h4>
                                <p>All data transmission is encrypted using industry-standard protocols</p>
                            </div>
                        </div>
                        <div class="security-feature">
                            <i class="fas fa-credit-card"></i>
                            <div>
                                <h4>Secure Payment Processing</h4>
                                <p>Through certified payment providers with PCI DSS compliance</p>
                            </div>
                        </div>
                        <div class="security-feature">
                            <i class="fas fa-search"></i>
                            <div>
                                <h4>Regular Security Audits</h4>
                                <p>Vulnerability assessments and penetration testing</p>
                            </div>
                        </div>
                        <div class="security-feature">
                            <i class="fas fa-user-shield"></i>
                            <div>
                                <h4>Access Controls</h4>
                                <p>Employee training and strict access controls on data</p>
                            </div>
                        </div>
                        <div class="security-feature">
                            <i class="fas fa-database"></i>
                            <div>
                                <h4>Data Backup</h4>
                                <p>Regular backups and disaster recovery procedures</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-clock"></i> Data Retention</h2>
                    <p>We retain your personal information only as long as necessary to:</p>
                    <div class="retention-timeline">
                        <div class="retention-item">
                            <div class="retention-icon">
                                <i class="fas fa-sim-card"></i>
                            </div>
                            <div class="retention-content">
                                <h4>Service Provision</h4>
                                <p>While you're an active customer</p>
                            </div>
                        </div>
                        <div class="retention-item">
                            <div class="retention-icon">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <div class="retention-content">
                                <h4>Legal Compliance</h4>
                                <p>As required by applicable laws</p>
                            </div>
                        </div>
                        <div class="retention-item">
                            <div class="retention-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="retention-content">
                                <h4>Dispute Resolution</h4>
                                <p>To resolve disputes and enforce agreements</p>
                            </div>
                        </div>
                        <div class="retention-item">
                            <div class="retention-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="retention-content">
                                <h4>Business Records</h4>
                                <p>7 years after account closure (typical)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-user-check"></i> Your Rights and Choices</h2>
                    <p>Depending on your location, you may have the following rights:</p>
                    <div class="rights-grid">
                        <div class="right-item">
                            <i class="fas fa-eye"></i>
                            <h4>Access</h4>
                            <p>Request a copy of your personal information</p>
                        </div>
                        <div class="right-item">
                            <i class="fas fa-edit"></i>
                            <h4>Correction</h4>
                            <p>Request correction of inaccurate information</p>
                        </div>
                        <div class="right-item">
                            <i class="fas fa-trash"></i>
                            <h4>Deletion</h4>
                            <p>Request deletion of your personal information</p>
                        </div>
                        <div class="right-item">
                            <i class="fas fa-download"></i>
                            <h4>Portability</h4>
                            <p>Request transfer of your data to another service</p>
                        </div>
                        <div class="right-item">
                            <i class="fas fa-ban"></i>
                            <h4>Objection</h4>
                            <p>Object to certain processing of your information</p>
                        </div>
                        <div class="right-item">
                            <i class="fas fa-hand-paper"></i>
                            <h4>Withdrawal</h4>
                            <p>Withdraw consent where processing is based on consent</p>
                        </div>
                    </div>
                    <div class="contact-privacy">
                        <p>To exercise these rights, please contact us at <a href="mailto:privacy@esimstore.com">privacy@esimstore.com</a></p>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-cookie-bite"></i> Cookies and Tracking</h2>
                    <h3>How We Use Cookies</h3>
                    <div class="cookie-purposes">
                        <div class="cookie-purpose">
                            <i class="fas fa-cog"></i>
                            <span>Remember your preferences and settings</span>
                        </div>
                        <div class="cookie-purpose">
                            <i class="fas fa-chart-line"></i>
                            <span>Analyze website traffic and usage patterns</span>
                        </div>
                        <div class="cookie-purpose">
                            <i class="fas fa-user"></i>
                            <span>Provide personalized content and recommendations</span>
                        </div>
                        <div class="cookie-purpose">
                            <i class="fas fa-shield-alt"></i>
                            <span>Ensure security and prevent fraud</span>
                        </div>
                    </div>

                    <div class="cookie-management">
                        <h3>Cookie Management</h3>
                        <p>You can control cookies through your browser settings. However, disabling cookies may affect the functionality of our website.</p>
                        <button class="btn-cookie-settings" onclick="showCookieSettings()">
                            <i class="fas fa-cog"></i> Manage Cookie Preferences
                        </button>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-globe"></i> International Data Transfers</h2>
                    <p>As a global service, your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place for such transfers:</p>
                    <div class="transfer-safeguards">
                        <div class="safeguard-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Adequacy decisions by relevant authorities</span>
                        </div>
                        <div class="safeguard-item">
                            <i class="fas fa-file-contract"></i>
                            <span>Standard contractual clauses</span>
                        </div>
                        <div class="safeguard-item">
                            <i class="fas fa-building"></i>
                            <span>Binding corporate rules</span>
                        </div>
                        <div class="safeguard-item">
                            <i class="fas fa-certificate"></i>
                            <span>Certification schemes and codes of conduct</span>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-child"></i> Children's Privacy</h2>
                    <div class="children-policy">
                        <div class="age-restriction">
                            <i class="fas fa-birthday-cake"></i>
                            <div>
                                <h3>Age Restriction: 16+</h3>
                                <p>Our services are not intended for children under 16 years of age.</p>
                            </div>
                        </div>
                        <p>We do not knowingly collect personal information from children under 16. If we become aware that we have collected such information, we will take steps to delete it promptly.</p>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-sync-alt"></i> Changes to This Policy</h2>
                    <p>We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. We will notify you of significant changes by:</p>
                    <div class="notification-methods">
                        <div class="notification-method">
                            <i class="fas fa-globe"></i>
                            <span>Posting the updated policy on our website</span>
                        </div>
                        <div class="notification-method">
                            <i class="fas fa-envelope"></i>
                            <span>Sending an email notification to registered users</span>
                        </div>
                        <div class="notification-method">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Displaying a prominent notice on our homepage</span>
                        </div>
                    </div>
                    <p class="policy-acceptance">Your continued use of our services after the effective date constitutes acceptance of the updated policy.</p>
                </div>

                <div class="legal-card contact-card">
                    <h2><i class="fas fa-envelope"></i> Contact Information</h2>
                    <p>If you have questions about this Privacy Policy or our data practices, please contact us:</p>
                    <div class="contact-methods">
                        <div class="contact-method">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Privacy Email</strong>
                                <a href="mailto:privacy@esimstore.com">privacy@esimstore.com</a>
                                <small>For privacy-related inquiries</small>
                            </div>
                        </div>
                        <div class="contact-method">
                            <i class="fab fa-whatsapp"></i>
                            <div>
                                <strong>WhatsApp Support</strong>
                                <a href="https://wa.me/6281325525646">+62 813-2552-5646</a>
                                <small>Available 24/7</small>
                            </div>
                        </div>
                        <div class="contact-method">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Privacy Office</strong>
                                <span>eSIM Store Privacy Office<br>Jakarta, Indonesia</span>
                                <small>For formal privacy requests</small>
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
<script src="assets/js/privacy.js?v=<?= time() ?>"></script>
<script src="assets/js/footer.js?v=<?= time() ?>"></script>
</body>
</html>